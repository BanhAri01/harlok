@extends('layouts.app', ['title' => 'Coba Chat'])

@section('content')
<div class="mx-auto grid max-w-6xl items-center gap-x-14 gap-y-10 px-4 py-10 lg:grid-cols-[minmax(0,26rem)_1fr]"
    x-data="simulator({ sendUrl: @js(route('simulator.send')), pollUrl: @js(route('simulator.messages')), resetUrl: @js(route('simulator.reset')) })">

    <div class="mx-auto w-full max-w-[26rem] overflow-hidden rounded-[2.2rem] border-[10px] border-tinta bg-tinta shadow-[0_24px_50px_-20px_rgb(16_42_28/0.6)] lg:order-2">
        <div class="flex items-center gap-3 rounded-t-[1.5rem] bg-papan px-4 py-3 text-kapur">
            <svg width="34" height="34" viewBox="0 0 64 64" aria-hidden="true"><rect width="64" height="64" rx="32" fill="#163A29"/><path d="M15 15h28l9 13-9 13H27l-10 9v-9h-2a3 3 0 0 1-3-3V18a3 3 0 0 1 3-3z" fill="#F5B700"/><circle cx="43" cy="28" r="3" fill="#163A29"/><path d="M20 24h14M20 31h9" stroke="#163A29" stroke-width="3.2" stroke-linecap="round"/></svg>
            <div>
                <p class="font-bold leading-tight">Harlok</p>
                <p class="text-xs text-kapur/60" x-text="busy ? 'mengetik…' : 'online'"></p>
            </div>
            <button type="button" @click="reset()" class="ml-auto rounded-full border border-kapur/25 px-3 py-1 text-xs font-bold text-kapur/80 hover:bg-white/10" title="Mulai sebagai nomor baru">Nomor baru</button>
        </div>

        <div x-ref="feed" class="h-[30rem] space-y-2 overflow-y-auto bg-wa-latar px-3 py-4" aria-live="polite">
            <p class="mx-auto w-fit rounded-md bg-white/80 px-3 py-1 text-center text-sm text-tinta/65">Ketik HARLOK untuk mulai mendaftar.</p>
            <template x-for="message in messages" :key="message.key">
                <div class="flex" :class="message.from === 'me' ? 'justify-end' : 'justify-start'">
                    <div class="max-w-[85%] rounded-lg px-3 py-2 text-[0.95rem] leading-snug"
                        :class="message.from === 'me' ? 'rounded-tr-none bg-wa' : 'rounded-tl-none bg-white'">
                        <div x-html="message.html"></div>
                        <div class="mt-1 text-right text-[0.68rem] text-tinta/45" x-text="message.time"></div>
                    </div>
                </div>
            </template>
        </div>

        <div class="flex gap-1.5 overflow-x-auto bg-wa-latar px-3 pb-2">
            <template x-for="suggestion in suggestions" :key="suggestion">
                <button type="button" @click="send(suggestion)" class="shrink-0 rounded-full border-2 border-papan/15 bg-white px-3 py-1 text-sm font-bold text-papan hover:border-papan/40" x-text="suggestion"></button>
            </template>
        </div>

        <form @submit.prevent="send()" class="flex items-center gap-2 rounded-b-[1.5rem] bg-wa-latar px-3 pb-3">
            <label for="pesan" class="sr-only">Pesan</label>
            <input id="pesan" x-ref="input" x-model="text" maxlength="500" autocomplete="off" placeholder="Ketik pesan" class="min-w-0 flex-1 rounded-full border-0 bg-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-papan/30">
            <button class="grid size-11 shrink-0 place-items-center rounded-full bg-papan text-kuning disabled:opacity-50" :disabled="busy || !text.trim()" aria-label="Kirim">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3 20.5 21 12 3 3.5v6.6l12 1.9-12 1.9z"/></svg>
            </button>
        </form>
        <p class="bg-tinta px-4 pt-2 text-xs text-[#FFB4A6]" x-show="error" x-text="error"></p>
    </div>

    <div class="max-w-xl lg:order-1">
        <x-ilustrasi-chat class="mb-4 w-48" />
        <h1 class="judul-halaman">Coba jadi peternak yang melapor</h1>
        <p class="mt-4 text-lg text-tinta/80">Chat di sini memakai bot yang sama dengan nomor WhatsApp Harlok. Bedanya, pesannya hanya ada di halaman ini dan tidak terkirim ke WhatsApp siapa pun.</p>

        <h2 class="judul-bagian mt-8">Urutan yang enak dicoba</h2>
        <ol class="mt-4 space-y-4">
            @foreach ([
                'Ketik <span class="stabilo font-bold">HARLOK</span>, isi nama, lalu pilih wilayah dan komoditas dengan angka.',
                'Laporkan stok seperti chat biasa, misalnya <em>telur 2 ton 26rb</em>, <em>20 peti 390rb/peti</em>, atau <em>cabe rawit 3 kwintal 45.000</em>. Balas YA untuk menyimpan.',
                'Buka <a href="'.e(route('home')).'" target="_blank" class="tautan">halaman harga</a> di tab lain. Stok Anda muncul di peta sebagai titik abu-abu, karena nomor simulator belum diverifikasi koordinator.',
                'Masuk sebagai pengepul demo, tawar stok tadi, lalu jawab tawarannya di sini dengan 1, 2, atau 3.',
                'Tulis pesan bebas, termasuk bahasa Bali, seperti <em>taluh 1 ton, 25 ribu a kilo</em> atau <em>ajin tabia jani kuda?</em> Asisten AI menjawab dari data laporan. Menyimpan laporan dan menerima tawaran tetap menunggu balasan Anda.',
            ] as $step)
                <li class="flex gap-4">
                    <span class="font-display grid size-10 shrink-0 place-items-center rounded-full bg-kuning text-xl font-extrabold text-tinta shadow-[0_3px_0_#C98A00]">{{ $loop->iteration }}</span>
                    <p class="pt-1.5 text-tinta/85">{!! $step !!}</p>
                </li>
            @endforeach
        </ol>
    </div>
</div>
@endsection
