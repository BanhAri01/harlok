@extends('layouts.app', ['title' => 'Coba Chat'])

@section('content')
<div class="mx-auto grid max-w-6xl gap-10 px-4 py-10 lg:grid-cols-[minmax(0,26rem)_1fr]"
    x-data="simulator({ sendUrl: @js(route('simulator.send')), pollUrl: @js(route('simulator.messages')), resetUrl: @js(route('simulator.reset')) })">

    <div class="mx-auto w-full max-w-[26rem] overflow-hidden rounded-[2rem] border-[10px] border-tinta bg-tinta shadow-2xl">
        <div class="flex items-center gap-3 bg-papan px-4 py-3 text-kapur">
            <svg width="34" height="34" viewBox="0 0 32 32" aria-hidden="true"><rect width="32" height="32" rx="16" fill="#1F4433"/><path d="M9 8h9l7 7-9 9-7-7z" fill="#F5B700"/><circle cx="13" cy="12" r="1.8" fill="#163326"/></svg>
            <div>
                <p class="font-semibold leading-tight">Harlok</p>
                <p class="text-xs text-kapur/60" x-text="busy ? 'mengetik…' : 'online'"></p>
            </div>
            <button type="button" @click="reset()" class="ml-auto rounded-md px-2 py-1 text-xs text-kapur/70 hover:bg-white/10" title="Mulai sebagai nomor baru">Nomor baru</button>
        </div>

        <div x-ref="feed" class="h-[30rem] space-y-2 overflow-y-auto bg-[#EFEAE2] px-3 py-4" aria-live="polite">
            <p class="mx-auto w-fit rounded-md bg-white/80 px-3 py-1 text-center text-xs text-tinta/60">Simulasi chat WhatsApp. Ketik <strong>halo</strong> untuk mulai mendaftar.</p>
            <template x-for="message in messages" :key="message.key">
                <div class="flex" :class="message.from === 'me' ? 'justify-end' : 'justify-start'">
                    <div class="max-w-[85%] rounded-lg px-3 py-2 text-[0.92rem] leading-snug shadow-sm"
                        :class="message.from === 'me' ? 'rounded-tr-none bg-wa' : 'rounded-tl-none bg-white'">
                        <div x-html="message.html"></div>
                        <div class="mt-1 text-right text-[0.68rem] text-tinta/45" x-text="message.time"></div>
                    </div>
                </div>
            </template>
        </div>

        <div class="flex gap-1.5 overflow-x-auto bg-[#EFEAE2] px-3 pb-2">
            <template x-for="suggestion in suggestions" :key="suggestion">
                <button type="button" @click="send(suggestion)" class="shrink-0 rounded-full border border-papan/20 bg-white px-3 py-1 text-xs font-medium text-papan hover:bg-kapur" x-text="suggestion"></button>
            </template>
        </div>

        <form @submit.prevent="send()" class="flex items-center gap-2 bg-[#EFEAE2] px-3 pb-3">
            <label for="pesan" class="sr-only">Pesan</label>
            <input id="pesan" x-ref="input" x-model="text" maxlength="500" autocomplete="off" placeholder="Ketik pesan" class="min-w-0 flex-1 rounded-full border-0 bg-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-papan/30">
            <button class="grid size-11 shrink-0 place-items-center rounded-full bg-papan text-kuning disabled:opacity-50" :disabled="busy || !text.trim()" aria-label="Kirim">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3 20.5 21 12 3 3.5v6.6l12 1.9-12 1.9z"/></svg>
            </button>
        </form>
        <p class="bg-[#EFEAE2] px-4 pb-3 text-xs text-turun" x-show="error" x-text="error"></p>
    </div>

    <div class="self-center">
        <h1 class="font-display text-4xl font-extrabold tracking-tight">Rasakan jadi peternak.</h1>
        <p class="mt-3 max-w-xl text-lg text-tinta/75">Chat di samping memakai bot yang sama persis dengan nomor WhatsApp Harlok. Bedanya, semua pesannya tetap di halaman ini dan tidak ada yang terkirim ke WhatsApp.</p>

        <ol class="mt-8 space-y-4">
            <li class="flex gap-4">
                <span class="angka grid size-9 shrink-0 place-items-center rounded-full bg-papan font-bold text-kuning">1</span>
                <div><p class="font-semibold">Daftar dalam tiga balasan</p><p class="text-tinta/70">Ketik <em>halo</em>, isi nama, pilih wilayah dan komoditas dengan angka.</p></div>
            </li>
            <li class="flex gap-4">
                <span class="angka grid size-9 shrink-0 place-items-center rounded-full bg-papan font-bold text-kuning">2</span>
                <div><p class="font-semibold">Lapor stok dengan bahasa sehari-hari</p><p class="text-tinta/70">Coba <em>telur 2 ton 26rb</em>, <em>20 peti 390rb/peti</em>, atau <em>cabe rawit 3 kwintal 45.000</em>. Balas <strong>YA</strong> untuk menyimpan.</p></div>
            </li>
            <li class="flex gap-4">
                <span class="angka grid size-9 shrink-0 place-items-center rounded-full bg-papan font-bold text-kuning">3</span>
                <div><p class="font-semibold">Lihat stok Anda muncul di peta</p><p class="text-tinta/70">Buka <a href="{{ route('home') }}" target="_blank" class="font-semibold text-papan underline">Papan Harga</a> di tab lain. Laporan dari simulator tampil abu-abu karena nomornya belum diverifikasi koordinator.</p></div>
            </li>
            <li class="flex gap-4">
                <span class="angka grid size-9 shrink-0 place-items-center rounded-full bg-papan font-bold text-kuning">4</span>
                <div><p class="font-semibold">Terima tawaran dari pengepul</p><p class="text-tinta/70">Masuk sebagai pengepul demo, tawar stok Anda, lalu balas <strong>1</strong>, <strong>2</strong>, atau <strong>3</strong> di chat ini.</p></div>
            </li>
        </ol>
    </div>
</div>
@endsection
