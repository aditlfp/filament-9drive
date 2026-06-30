{{-- DESIGN.md: Blurple #5865f2, Magenta #ec48bd gradient logo --}}
<div class="flex items-center gap-2">
    <svg class="h-10 w-10" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <linearGradient id="brandGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" style="stop-color:#5865f2"/>
                <stop offset="100%" style="stop-color:#ec48bd"/>
            </linearGradient>
        </defs>
        {{-- Server stacks (back) --}}
        <rect x="12" y="32" width="24" height="4" rx="2" fill="url(#brandGrad)" opacity="0.5"/>
        <circle cx="31" cy="34" r="1" fill="white" opacity="0.6"/>
        <rect x="12" y="25" width="24" height="4" rx="2" fill="url(#brandGrad)" opacity="0.65"/>
        <circle cx="31" cy="27" r="1" fill="white" opacity="0.6"/>
        <rect x="12" y="18" width="24" height="4" rx="2" fill="url(#brandGrad)" opacity="0.8"/>
        <circle cx="31" cy="20" r="1" fill="#35ed7e" opacity="0.95"/>
        {{-- Cloud (front) --}}
        <path d="M10 18a6 6 0 016-6 8 8 0 0114 0 6 6 0 016 6 6 6 0 01-6 6H16a6 6 0 01-6-6z" fill="url(#brandGrad)"/>
        {{-- Upload arrow --}}
        <path d="M24 21v-6M21 17l3-3 3 3" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <span class="text-2xl font-bold" style="background: linear-gradient(135deg, #5865f2 0%, #ec48bd 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-family: 'ABC Ginto Nord', 'ggsans', sans-serif; letter-spacing: -0.02em;">9DRIVE</span>
</div>
