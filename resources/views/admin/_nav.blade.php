<div class="flex flex-wrap items-end gap-4">
    <div>
        <h1 class="font-display text-3xl font-extrabold tracking-tight">{{ $heading }}</h1>
        @isset($sub)<p class="mt-1 text-tinta/70">{{ $sub }}</p>@endisset
    </div>
    <nav class="ml-auto flex flex-wrap gap-1 rounded-lg border border-garis bg-white p-1 text-sm font-semibold" aria-label="Menu koordinator">
        @foreach (['admin.dashboard' => 'Ringkasan', 'admin.farmers' => 'Peternak', 'admin.users' => 'Pengepul & Mitra API', 'admin.messages' => 'Log WhatsApp', 'admin.audit' => 'Jejak Audit'] as $route => $label)
            <a href="{{ route($route) }}" @class(['rounded-md px-3 py-1.5', 'bg-papan text-kapur' => request()->routeIs($route), 'text-tinta/70 hover:text-tinta' => ! request()->routeIs($route)])>{{ $label }}</a>
        @endforeach
    </nav>
</div>
