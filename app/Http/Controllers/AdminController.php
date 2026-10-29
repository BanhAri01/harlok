<?php

namespace App\Http\Controllers;

use App\Ai\AiGateway;
use App\Models\AiCall;
use App\Models\AuditLog;
use App\Models\Farmer;
use App\Models\InboundMessage;
use App\Models\OutboundMessage;
use App\Models\Region;
use App\Models\StockReport;
use App\Models\User;
use App\Services\PriceBoard;
use App\Services\ReportService;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Laravel\Sanctum\PersonalAccessToken;

class AdminController extends Controller
{
    public function dashboard(AiGateway $ai): View
    {
        return view('admin.dashboard', [
            'flagged' => StockReport::query()->with('farmer:id,name,phone', 'commodity:id,name', 'region:id,name')->where('status', StockReport::FLAGGED)->latest('id')->limit(30)->get(),
            'unverified' => Farmer::query()->with('region:id,name')->whereNull('verified_at')->where('channel', 'wa')->latest('id')->limit(10)->get(),
            'counts' => [
                'farmers' => Farmer::query()->count(),
                'unverified' => Farmer::query()->whereNull('verified_at')->where('channel', 'wa')->count(),
                'available' => StockReport::query()->available()->count(),
                'inbound_today' => InboundMessage::query()->where('created_at', '>=', now()->startOfDay())->count(),
                'outbound_today' => OutboundMessage::query()->where('channel', 'wa')->where('status', 'sent')->where('created_at', '>=', now()->startOfDay())->count(),
                'failed_today' => OutboundMessage::query()->where('status', 'failed')->where('created_at', '>=', now()->startOfDay())->count(),
            ],
            'limit' => config('lapor.whatsapp.daily_outbound_limit'),
            'ai' => $this->aiStats($ai),
        ]);
    }

    private function aiStats(AiGateway $ai): array
    {
        $calls = AiCall::query()->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('purpose, status, count(*) as total, avg(latency_ms) as latency')
            ->groupBy('purpose', 'status')
            ->get();
        $answered = $calls->where('status', AiCall::OK);

        return [
            'active' => $ai->available(),
            'provider' => config('lapor.ai.driver').' · '.(config('lapor.ai.model') ?: config('lapor.ai.models.'.config('lapor.ai.driver'))),
            'total' => (int) $calls->sum('total'),
            'ok' => (int) $answered->sum('total'),
            'fallback' => (int) $calls->where('status', '!=', AiCall::OK)->sum('total'),
            'latency' => $answered->sum('total') ? (int) round($answered->sum(fn ($row) => $row->latency * $row->total) / $answered->sum('total')) : null,
            'purposes' => $calls->groupBy('purpose')->map(fn ($rows) => (int) $rows->sum('total'))->all(),
        ];
    }

    public function farmers(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $farmers = Farmer::query()
            ->with('region:id,name', 'commodity:id,name')
            ->withCount('reports')
            ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('phone', 'like', '%'.preg_replace('/\D/', '', $search).'%')))
            ->when($request->query('status') === 'belum', fn ($query) => $query->whereNull('verified_at'))
            ->orderByRaw('verified_at is null desc')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.farmers', ['farmers' => $farmers, 'search' => $search]);
    }

    public function verify(Request $request, Farmer $farmer, PriceBoard $prices): RedirectResponse
    {
        abort_if($farmer->channel === 'simulator', 422, 'Akun simulator tidak dapat diverifikasi.');

        $verify = $request->boolean('verified');
        $farmer->forceFill(['verified_at' => $verify ? now() : null])->save();
        AuditLog::record('user:'.$request->user()->id, $verify ? 'farmer.verified' : 'farmer.unverified', $farmer);

        $farmer->reports()
            ->whereIn('status', StockReport::PRICED)
            ->where('confirmed_at', '>=', now()->subDays((int) config('lapor.market.lookback_days'))->startOfDay())
            ->get(['id', 'commodity_id', 'region_id', 'confirmed_at'])
            ->unique(fn (StockReport $report) => $report->commodity_id.'-'.$report->region_id.'-'.$report->confirmed_at->toDateString())
            ->each(fn (StockReport $report) => $prices->recomputeFor($report));

        return back()->with('status', $verify ? "{$farmer->name} terverifikasi." : "Verifikasi {$farmer->name} dicabut.");
    }

    public function approve(Request $request, StockReport $report, ReportService $reports): RedirectResponse
    {
        abort_unless($report->status === StockReport::FLAGGED, 422);
        $reports->approve($report, $request->user());

        return back()->with('status', 'Laporan disetujui.');
    }

    public function reject(Request $request, StockReport $report, ReportService $reports): RedirectResponse
    {
        abort_unless($report->status === StockReport::FLAGGED, 422);
        $reports->reject($report, $request->user());

        return back()->with('status', 'Laporan ditolak.');
    }

    public function users(): View
    {
        return view('admin.users', [
            'users' => User::query()->with('region:id,name')->withCount('tokens')->orderBy('role')->orderBy('name')->get(),
            'regions' => Region::ordered(),
        ]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'business_name' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'role' => ['required', Rule::in([User::BUYER, User::PARTNER])],
            'phone' => ['nullable', 'string', 'max:20'],
            'region_id' => ['nullable', 'exists:regions,id'],
            'notify_wa' => ['boolean'],
            'password' => ['required', Password::min(8)],
        ]);

        $phone = filled($data['phone'] ?? null) ? Phone::normalize($data['phone']) : null;
        if (filled($data['phone'] ?? null) && ! $phone) {
            return back()->withErrors(['phone' => 'Nomor WhatsApp tidak valid.'])->withInput();
        }
        if ($phone && User::query()->where('phone', $phone)->exists()) {
            return back()->withErrors(['phone' => 'Nomor ini sudah dipakai.'])->withInput();
        }

        $user = User::query()->create([...$data, 'phone' => $phone, 'notify_wa' => $request->boolean('notify_wa')]);
        AuditLog::record('user:'.$request->user()->id, 'user.created', $user, ['role' => $user->role]);

        return back()->with('status', "Akun {$user->name} dibuat.");
    }

    public function toggleUser(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is(auth()->user()), 422, 'Tidak bisa menonaktifkan akun sendiri.');

        $user->forceFill(['is_active' => ! $user->is_active])->save();
        if (! $user->is_active) {
            $user->tokens()->delete();
        }
        AuditLog::record('user:'.$request->user()->id, $user->is_active ? 'user.activated' : 'user.deactivated', $user);

        return back()->with('status', "Status {$user->name} diperbarui.");
    }

    public function createToken(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role === User::PARTNER && $user->is_active, 422, 'Token API hanya untuk mitra aktif.');

        $data = $request->validate(['label' => ['required', 'string', 'max:60']]);
        $token = $user->createToken($data['label'], ['prices:read'], now()->addMonths(6));
        AuditLog::record('user:'.$request->user()->id, 'token.created', $user, ['label' => $data['label']]);

        return back()->with('token', $token->plainTextToken)->with('status', 'Token dibuat. Salin sekarang, karena token ini tidak akan ditampilkan lagi.');
    }

    public function revokeToken(Request $request, PersonalAccessToken $token): RedirectResponse
    {
        AuditLog::record('user:'.$request->user()->id, 'token.revoked', $token->tokenable, ['label' => $token->name]);
        $token->delete();

        return back()->with('status', 'Token dicabut.');
    }

    public function tokens(User $user): View
    {
        abort_unless($user->role === User::PARTNER, 404);

        return view('admin.tokens', ['partner' => $user, 'tokens' => $user->tokens()->latest('id')->get()]);
    }

    public function messages(Request $request): View
    {
        $phone = Phone::normalize($request->query('nomor'));

        return view('admin.messages', [
            'inbound' => InboundMessage::query()->when($phone, fn ($query) => $query->where('phone', $phone))->where('channel', 'wa')->latest('id')->limit(50)->get(),
            'outbound' => OutboundMessage::query()->when($phone, fn ($query) => $query->where('phone', $phone))->where('channel', 'wa')->latest('id')->limit(50)->get(),
            'phone' => $phone,
        ]);
    }

    public function audit(): View
    {
        return view('admin.audit', ['logs' => AuditLog::query()->latest('id')->paginate(50)]);
    }
}
