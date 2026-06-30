<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $share->resourceName() }} — 9Drive</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-full bg-gray-50 dark:bg-gray-900 flex items-center justify-center p-4">
    <div class="w-full max-w-2xl">
        <div class="rounded-2xl bg-white shadow-xl overflow-hidden dark:bg-gray-800">
            {{-- Header --}}
            <div class="bg-gradient-to-r from-amber-500 to-amber-600 px-8 py-6">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-white/20 p-2">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-white">{{ $share->resourceName() }}</h1>
                        <p class="text-amber-100 text-sm">Shared by {{ $share->creator?->name ?? 'Someone' }}</p>
                    </div>
                </div>
            </div>

            <div class="p-8 space-y-6">
                {{-- File info --}}
                @if($share->file)
                <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-700 space-y-2 text-sm text-gray-600 dark:text-gray-300">
                    <div class="flex justify-between"><span class="font-medium">Size</span><span>{{ $share->file->formatted_size }}</span></div>
                    <div class="flex justify-between"><span class="font-medium">Type</span><span>{{ $share->file->mime_type }}</span></div>
                    @if($share->expires_at)
                    <div class="flex justify-between"><span class="font-medium">Expires</span><span>{{ $share->expires_at->diffForHumans() }}</span></div>
                    @endif
                    @if($share->download_limit)
                    <div class="flex justify-between"><span class="font-medium">Downloads</span><span>{{ $share->download_count }} / {{ $share->download_limit }}</span></div>
                    @endif
                </div>
                @endif

                {{-- Actions --}}
                <div class="flex gap-3">
                    <a href="{{ route('share.download', $share->token) }}"
                        class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 px-5 py-3 text-sm font-semibold text-white hover:bg-amber-600">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Download
                    </a>
                </div>

                {{-- QR Code --}}
                <div class="flex flex-col items-center gap-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                    <p class="text-xs text-gray-400">Scan to share</p>
                    <img src="{{ app(\App\Application\Services\SharingService::class)->generateQrCode($share) }}"
                        alt="QR Code" class="w-32 h-32 rounded-lg" loading="lazy" />
                </div>
            </div>
        </div>

        <p class="text-center text-xs text-gray-400 mt-4">Powered by 9Drive</p>
    </div>
</body>
</html>
