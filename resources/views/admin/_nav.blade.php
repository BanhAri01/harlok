<div class="flex flex-wrap items-end gap-x-4 gap-y-5">
    <div>
        <h1 class="text-2xl font-bold text-papan sm:text-3xl">{{ $heading }}</h1>
        @isset($sub)<p class="mt-1 text-tinta/70">{{ $sub }}</p>@endisset
    </div>
    <nav class="flex w-full flex-wrap gap-x-5 gap-y-1 border-b border-garis text-[0.95rem]" aria-label="Menu koordinator">
        @foreach (['admin.dashboard' => 'Ringkasan', 'admin.farmers' => 'Peternak', 'admin.users' => 'Pengepul dan mitra API', 'admin.messages' => 'Log WhatsApp', 'admin.audit' => 'Jejak audit'] as $route => $label)
            <a href="{{ route($route) }}" @class(['-mb-px border-b-2 py-2', 'border-papan font-bold text-papan' => request()->routeIs($route), 'border-transparent text-tinta/70 hover:text-tinta' => ! request()->routeIs($route)])>{{ $label }}</a>
        @endforeach
    </nav>
</div>
