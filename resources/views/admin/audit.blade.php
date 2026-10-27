@extends('layouts.app', ['title' => 'Jejak Audit'])

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8">
    @include('admin._nav', ['heading' => 'Jejak audit', 'sub' => 'Setiap perubahan penting tercatat: siapa, apa, kapan, dari mana.'])

    <div class="panel mt-6 overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-garis text-tinta/60">
                <tr><th class="px-4 py-3 font-semibold">Waktu</th><th class="px-4 py-3 font-semibold">Pelaku</th><th class="px-4 py-3 font-semibold">Aksi</th><th class="px-4 py-3 font-semibold">Objek</th><th class="px-4 py-3 font-semibold">Detail</th><th class="px-4 py-3 font-semibold">IP</th></tr>
            </thead>
            <tbody class="divide-y divide-garis">
                @foreach ($logs as $log)
                    <tr>
                        <td class="whitespace-nowrap px-4 py-2.5">{{ $log->created_at->format('d/m H.i.s') }}</td>
                        <td class="px-4 py-2.5">{{ str_starts_with($log->actor, 'wa:') ? 'WA '.\App\Support\Phone::mask(substr($log->actor, 3)) : $log->actor }}</td>
                        <td class="px-4 py-2.5 font-semibold">{{ $log->action }}</td>
                        <td class="px-4 py-2.5">{{ $log->subject_type ? class_basename($log->subject_type).' #'.$log->subject_id : '—' }}</td>
                        <td class="px-4 py-2.5 text-tinta/70">{{ $log->meta ? json_encode($log->meta, JSON_UNESCAPED_UNICODE) : '' }}</td>
                        <td class="px-4 py-2.5 text-tinta/60">{{ $log->ip }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $logs->links() }}</div>
</div>
@endsection
