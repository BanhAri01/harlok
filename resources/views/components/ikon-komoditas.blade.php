@props(['slug', 'size' => 28])
<svg {{ $attributes->merge(['width' => $size, 'height' => $size]) }} viewBox="0 0 48 48" aria-hidden="true">
    @switch($slug)
        @case('telur')
            <ellipse cx="20" cy="27" rx="11" ry="14" fill="#F4E3C3" stroke="#1F2B24" stroke-width="2.2"/>
            <ellipse cx="31" cy="30" rx="10" ry="12.5" fill="#FFF8EA" stroke="#1F2B24" stroke-width="2.2"/>
            <path d="M26 24c1-3 3-5 5-5.5" fill="none" stroke="#fff" stroke-width="2.4" stroke-linecap="round"/>
            @break
        @case('ayam-broiler')
            <path d="M14 30c0-9 6-15 14-15 7 0 12 5 12 12 0 8-6 13-14 13-7 0-12-4-12-10z" fill="#FFF8EA" stroke="#1F2B24" stroke-width="2.2"/>
            <path d="M22 15c-1-4 2-7 4-6 0-3 4-3 4 0 2-1 4 2 2 5" fill="#D7462B" stroke="#1F2B24" stroke-width="2" stroke-linejoin="round"/>
            <path d="M13 26l-6 3 6 2z" fill="#F5B700" stroke="#1F2B24" stroke-width="2" stroke-linejoin="round"/>
            <path d="M14 31c1 3 3 4 5 4" fill="none" stroke="#D7462B" stroke-width="2.4" stroke-linecap="round"/>
            <circle cx="19" cy="24" r="1.8" fill="#1F2B24"/>
            <path d="M30 28c3 0 6-2 7-5" fill="none" stroke="#1F2B24" stroke-width="2" stroke-linecap="round"/>
            @break
        @case('cabai-rawit')
            <path d="M15 14c2-3 6-4 8-2" fill="none" stroke="#3E8E4F" stroke-width="3" stroke-linecap="round"/>
            <path d="M20 15c-4 1-6 5-4 9 4 8 12 13 21 15 2 0 2-2 0-3-7-4-11-9-13-17-1-3-2-4-4-4z" fill="#D7462B" stroke="#1F2B24" stroke-width="2.2" stroke-linejoin="round"/>
            <path d="M20 20c1 5 4 9 8 12" fill="none" stroke="#fff" stroke-opacity=".55" stroke-width="2" stroke-linecap="round"/>
            @break
        @case('beras')
            <path d="M12 18c0-3 3-5 12-5s12 2 12 5l2 18c0 3-5 5-14 5S10 39 10 36z" fill="#F4E3C3" stroke="#1F2B24" stroke-width="2.2" stroke-linejoin="round"/>
            <path d="M14 17c3 2 17 2 20 0" fill="none" stroke="#1F2B24" stroke-width="2" stroke-linecap="round"/>
            <ellipse cx="20" cy="28" rx="1.6" ry="2.6" fill="#fff" stroke="#1F2B24" stroke-width="1.4"/>
            <ellipse cx="26" cy="31" rx="1.6" ry="2.6" fill="#fff" stroke="#1F2B24" stroke-width="1.4"/>
            <ellipse cx="27" cy="25" rx="1.6" ry="2.6" fill="#fff" stroke="#1F2B24" stroke-width="1.4"/>
            @break
        @default
            <path d="M24 8c6 0 9 7 9 16s-3 16-9 16-9-7-9-16 3-16 9-16z" fill="#F5B700" stroke="#1F2B24" stroke-width="2.2"/>
            <path d="M19 18h10M18 24h12M19 30h10M24 10v28" fill="none" stroke="#C98A00" stroke-width="1.6"/>
            <path d="M15 26c-5 3-7 9-5 14 4-2 9-5 12-9M33 26c5 3 7 9 5 14-4-2-9-5-12-9" fill="#3E8E4F" stroke="#1F2B24" stroke-width="2" stroke-linejoin="round"/>
    @endswitch
</svg>
