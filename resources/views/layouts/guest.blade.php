<!DOCTYPE html>
<html lang="fa" dir="rtl" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'ورود' }} — نگین</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-full bg-[#e7e7ea]" style="background-image:radial-gradient(circle at 1px 1px,#d7d7dc 1px,transparent 0);background-size:22px 22px;font-family:'Vazirmatn',system-ui,sans-serif" dir="rtl">
    <div class="mx-auto flex min-h-screen w-full max-w-[440px] flex-col bg-white shadow-[0_20px_60px_-20px_rgba(20,20,30,.25)]">
        {{ $slot }}
    </div>
    @livewireScripts
</body>
</html>
