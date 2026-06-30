<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $share->resourceName() }} — 9Drive</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-full bg-gray-50 dark:bg-gray-900 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="rounded-2xl bg-white shadow-xl p-8 dark:bg-gray-800">
            <div class="flex flex-col items-center gap-4">
                <div class="rounded-full bg-amber-100 p-4 dark:bg-amber-900/20">
                    <svg class="h-10 w-10 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white text-center">{{ $share->resourceName() }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Shared by {{ $share->creator?->name ?? 'Someone' }}</p>

                <form method="POST" action="{{ route('share.authenticate', $share->token) }}" class="w-full mt-2 space-y-3">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password required</label>
                        <input type="password" name="password" autofocus
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="Enter password" />
                        @error('password')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit"
                        class="w-full rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600">
                        Unlock
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
