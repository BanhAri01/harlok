<?php

namespace App\Http\Controllers;

use App\Models\Commodity;
use App\Models\Offer;
use App\Models\StockReport;
use App\Services\BoardData;
use App\Services\OfferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BuyerController extends Controller
{
    public function dashboard(Request $request, BoardData $board): View
    {
        $focus = $request->integer('report') ?: null;
        $commodity = $board->commodity($request->query('komoditas'));

        if ($focus && $report = StockReport::query()->find($focus, ['id', 'commodity_id'])) {
            $commodity = Commodity::active()->firstWhere('id', $report->commodity_id) ?? $commodity;
        }

        return view('buyer.dashboard', [
            'commodities' => Commodity::active(),
            'commodity' => $commodity,
            'focus' => $focus,
        ]);
    }

    public function data(Request $request, BoardData $board): JsonResponse
    {
        return response()->json($board->payload($board->commodity($request->query('komoditas')), $request->user()));
    }

    public function offer(Request $request, StockReport $report, OfferService $offers): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'price_per_kg' => ['required', 'integer', 'min:'.$report->commodity->min_price, 'max:'.$report->commodity->max_price],
            'quantity_kg' => ['required', 'numeric', 'min:1', 'max:'.$report->quantity_kg],
        ]);

        $offer = $offers->create($request->user(), $report, (int) $data['price_per_kg'], (float) $data['quantity_kg']);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'sent', 'offer_id' => $offer->id, 'percent' => $offer->percentFromMarket()]);
        }

        return back()->with('status', 'Tawaran terkirim ke WhatsApp peternak.');
    }

    public function offers(Request $request): View
    {
        $offers = Offer::query()
            ->with('report.commodity:id,name', 'report.region:id,name', 'report.farmer:id,name,phone')
            ->where('buyer_id', $request->user()->id)
            ->latest('id')
            ->paginate(20);

        return view('buyer.offers', ['offers' => $offers]);
    }

    public function acceptCounter(Request $request, Offer $offer, OfferService $offers): RedirectResponse
    {
        abort_unless($offer->buyer_id === $request->user()->id, 404);
        abort_unless($offer->status === Offer::COUNTERED, 422, 'Tawaran ini tidak sedang ditawar balik.');

        $offers->accept($offer, 'user:'.$request->user()->id);

        return back()->with('status', 'Sepakat! Kontak peternak sudah tampil dan dikirim lewat WhatsApp.');
    }

    public function cancel(Request $request, Offer $offer, OfferService $offers): RedirectResponse
    {
        abort_unless($offer->buyer_id === $request->user()->id, 404);

        $offers->cancel($offer, $request->user());

        return back()->with('status', 'Tawaran dibatalkan.');
    }
}
