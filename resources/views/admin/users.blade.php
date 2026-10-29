@extends('layouts.app', ['title' => 'Pengepul & Mitra API'])

@section('content')
<div class="mx-auto max-w-6xl px-4 py-8">
    @include('admin._nav', ['heading' => 'Pengepul & mitra API', 'sub' => 'Pengepul menawar lewat web. Mitra API (dinas, koperasi, aplikasi lain) hanya membaca data harga gabungan.'])

    <div class="mt-6 grid gap-6 lg:grid-cols-[1.5fr_1fr]">
        <div class="panel overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b-2 border-garis bg-daun text-tinta/70">
                    <tr><th class="px-4 py-3 font-semibold">Nama</th><th class="px-4 py-3 font-semibold">Peran</th><th class="px-4 py-3 font-semibold">Wilayah</th><th class="px-4 py-3 font-semibold">Status</th><th class="px-4 py-3"></th></tr>
                </thead>
                <tbody class="divide-y divide-garis">
                    @foreach ($users as $user)
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-semibold">{{ $user->displayName() }} @if ($user->is_demo)<span class="ml-1 rounded bg-kuning-muda px-1.5 text-xs">demo</span>@endif</p>
                                <p class="text-tinta/60">{{ $user->email }}{{ $user->phone ? ', '.\App\Support\Phone::display($user->phone) : '' }}</p>
                            </td>
                            <td class="px-4 py-3">{{ ['admin' => 'Koordinator', 'buyer' => 'Pengepul', 'partner' => 'Mitra API'][$user->role] }}</td>
                            <td class="px-4 py-3">{{ $user->region?->name ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    @if ($user->role === 'partner')
                                        <a href="{{ route('admin.tokens', $user) }}" class="btn-garis">Token ({{ $user->tokens_count }})</a>
                                    @endif
                                    @unless ($user->is(auth()->user()))
                                        <form method="POST" action="{{ route('admin.users.toggle', $user) }}">@csrf<button class="btn-garis">{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <form method="POST" action="{{ route('admin.users.store') }}" class="panel space-y-4 p-5">
            @csrf
            <h2 class="judul-bagian">Tambah akun</h2>
            <div>
                <label for="role" class="label">Peran</label>
                <select id="role" name="role" class="input">
                    <option value="buyer" @selected(old('role') === 'buyer')>Pengepul</option>
                    <option value="partner" @selected(old('role') === 'partner')>Mitra API</option>
                </select>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label for="name" class="label">Nama</label><input id="name" name="name" value="{{ old('name') }}" required class="input"></div>
                <div><label for="business_name" class="label">Nama usaha/instansi</label><input id="business_name" name="business_name" value="{{ old('business_name') }}" class="input"></div>
            </div>
            <div><label for="email" class="label">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required class="input"></div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label for="phone" class="label">No. WhatsApp</label><input id="phone" name="phone" value="{{ old('phone') }}" placeholder="08…" class="input"></div>
                <div>
                    <label for="region_id" class="label">Wilayah</label>
                    <select id="region_id" name="region_id" class="input">
                        <option value="">—</option>
                        @foreach ($regions as $region)<option value="{{ $region->id }}" @selected(old('region_id') == $region->id)>{{ $region->name }}</option>@endforeach
                    </select>
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="notify_wa" value="1" @checked(old('notify_wa')) class="size-4 accent-papan"> Kirim WA saat ada stok baru di wilayahnya</label>
            <div><label for="password" class="label">Kata sandi awal</label><input id="password" name="password" type="password" required minlength="8" class="input"></div>
            @if ($errors->any())<p class="text-sm text-turun">{{ $errors->first() }}</p>@endif
            <button class="btn-gelap w-full py-3">Simpan akun</button>
        </form>
    </div>
</div>
@endsection
