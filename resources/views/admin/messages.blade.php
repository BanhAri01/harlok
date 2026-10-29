@extends('layouts.app', ['title' => 'Log WhatsApp'])

@section('content')
<div class="mx-auto max-w-6xl px-4 py-8">
    @include('admin._nav', ['heading' => 'Log WhatsApp', 'sub' => '50 pesan terakhir dari dan ke nomor WhatsApp asli.'])

    <form method="GET" class="mt-6 flex gap-2">
        <label for="nomor" class="sr-only">Nomor</label>
        <input id="nomor" name="nomor" value="{{ $phone ? \App\Support\Phone::display($phone) : '' }}" placeholder="Saring nomor, mis. 0812…" class="input max-w-xs">
        <button class="btn-gelap">Saring</button>
    </form>

    <div class="mt-4 grid gap-6 lg:grid-cols-2">
        @foreach (['Masuk' => $inbound, 'Keluar' => $outbound] as $heading => $messages)
            <section class="panel">
                <h2 class="border-b border-garis px-5 py-3 text-lg font-bold">{{ $heading }}</h2>
                <ul class="max-h-[40rem] divide-y divide-garis overflow-y-auto">
                    @forelse ($messages as $message)
                        <li class="px-5 py-3 text-sm">
                            <p class="flex gap-2 text-tinta/60">
                                <a href="{{ route('admin.messages', ['nomor' => \App\Support\Phone::display($message->phone)]) }}" class="font-semibold text-papan">{{ \App\Support\Phone::display($message->phone) }}</a>
                                <span>{{ $message->created_at->format('d/m H.i') }}</span>
                                <span @class(['ml-auto', 'text-turun' => in_array($message->status, ['failed', 'skipped'], true)])>{{ $message->status }}</span>
                            </p>
                            <p class="mt-1 whitespace-pre-line">{{ $message->body }}</p>
                            @if ($message->error ?? null)<p class="mt-1 text-turun">{{ $message->error }}</p>@endif
                        </li>
                    @empty
                        <li class="px-5 py-8 text-center text-tinta/60">Belum ada pesan.</li>
                    @endforelse
                </ul>
            </section>
        @endforeach
    </div>
</div>
@endsection
