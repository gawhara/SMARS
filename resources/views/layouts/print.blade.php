@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - {{ config('app.name') }}</title>
    @vite('resources/css/app.css')
    <style>
        body.print-body { background: #eef0f4; color: #111827; margin: 0; font-family: 'Cairo', ui-sans-serif, sans-serif; }
        .print-toolbar { position: sticky; top: 0; display: flex; gap: 10px; justify-content: center; padding: 12px; background: #fff; border-bottom: 1px solid #e5e7eb; z-index: 5; }
        .print-toolbar button, .print-toolbar a { padding: 9px 20px; border-radius: 8px; font: inherit; font-weight: 700; font-size: .9rem; cursor: pointer; text-decoration: none; border: 1px solid #d1d5db; }
        .print-toolbar .pt-print { background: #4f46e5; color: #fff; border-color: #4f46e5; }
        .print-toolbar .pt-back { background: #fff; color: #374151; }
        .print-sheet { max-width: 860px; margin: 18px auto; background: #fff; padding: 30px 34px; box-shadow: 0 2px 12px rgba(0, 0, 0, .08); }
        .print-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 18px; border-bottom: 2px solid #111827; padding-bottom: 14px; margin-bottom: 16px; }
        .print-head h1 { margin: 0; font-size: 1.35rem; }
        .print-head .company { font-weight: 800; font-size: 1.05rem; }
        .print-head .muted { color: #6b7280; font-size: .82rem; margin-top: 2px; }
        .print-meta { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px 20px; margin-bottom: 16px; font-size: .85rem; }
        .print-meta span { color: #6b7280; font-size: .74rem; }
        .print-meta strong { display: block; margin-top: 2px; }
        .print-summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 18px; }
        .print-summary .box { border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 12px; text-align: center; }
        .print-summary .box span { display: block; color: #6b7280; font-size: .72rem; font-weight: 700; }
        .print-summary .box strong { display: block; margin-top: 4px; font-size: 1.4rem; }
        table.print-days { width: 100%; border-collapse: collapse; font-size: .8rem; }
        table.print-days th, table.print-days td { border: 1px solid #d1d5db; padding: 5px 9px; text-align: start; }
        table.print-days th { background: #f3f4f6; font-size: .72rem; text-transform: uppercase; letter-spacing: .03em; }
        .st { font-weight: 800; }
        .st-present { color: #059669; } .st-late { color: #d97706; } .st-absent { color: #dc2626; }
        .st-rest { color: #6b7280; } .st-holiday { color: #7c3aed; } .st-leave { color: #2563eb; } .st-future { color: #9ca3af; }
        .row-weekend { background: #fafafa; }
        .print-sign { display: flex; justify-content: space-between; margin-top: 40px; }
        .print-sign div { width: 40%; border-top: 1px solid #111827; padding-top: 6px; text-align: center; font-size: .82rem; color: #374151; }
        .print-foot { margin-top: 18px; color: #9ca3af; font-size: .72rem; text-align: center; }
        @media print {
            body.print-body { background: #fff; }
            .no-print { display: none !important; }
            .print-sheet { margin: 0; max-width: none; box-shadow: none; padding: 0; }
            table.print-days th, .st { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            @page { size: A4; margin: 14mm; }
        }
    </style>
</head>
<body class="print-body">
    @yield('content')
</body>
</html>
