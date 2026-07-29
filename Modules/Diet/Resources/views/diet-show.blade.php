<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ $diet['plan_name'] ?? 'برنامه رژیم غذایی' }}</title>

    <style>
        /* ===== Fonts ===== */
        @font-face{font-family:'Vazirmatn';src:url('{{ asset('assets/fonts/vazirmatn/Vazirmatn-Regular.woff2') }}') format('woff2');font-weight:400;font-style:normal;font-display:swap}
        @font-face{font-family:'Vazirmatn';src:url('{{ asset('assets/fonts/vazirmatn/Vazirmatn-Medium.woff2') }}') format('woff2');font-weight:500;font-style:normal;font-display:swap}
        @font-face{font-family:'Vazirmatn';src:url('{{ asset('assets/fonts/vazirmatn/Vazirmatn-Bold.woff2') }}') format('woff2');font-weight:700;font-style:normal;font-display:swap}

        :root{
            --ink:#0f172a; --muted:#6b7b93; --border:#d9dee7; --card:#f8fafc; --soft:#f1f5f9;
            /* day headers */
            --day1:#e7f2ff; --day2:#ecf9ef; --day3:#efeaff; --day4:#fff3e0; --day5:#ffe9ea; --day6:#e9fbff; --day7:#eefbe6;
            /* meal tag palette */
            --t-breakfast:#3b82f6;   /* آبی */
            --t-lunch:#10b981;       /* سبز */
            --t-dinner:#ef4444;      /* قرمز */
            --t-snack1:#f59e0b;      /* کهربایی - میان‌وعده 1 */
            --t-snack2:#a855f7;      /* بنفش - میان‌وعده 2 */
            --t-snack3:#06b6d4;      /* آبی‌فیروزه‌ای - میان‌وعده 3 */
            --t-snack:#f59e0b;       /* fallback */
        }

        html,body{font-family:'Vazirmatn',system-ui,-apple-system,Segoe UI,Roboto,sans-serif;color:var(--ink);margin:0;background:#fff;-webkit-print-color-adjust:exact;print-color-adjust:exact}
        .page{max-width:190mm;margin:0 auto;padding:8px}

        /* ===== Top bar ===== */
        .brandbar{
            display:grid;grid-template-columns:1fr auto;gap:12px;align-items:center;
            background:var(--card);border:1px solid var(--border);border-radius:14px;padding:10px 12px;margin-bottom:10px
        }
        .brand{display:flex;gap:10px;align-items:center}
        .brand img{max-height:46px}
        .brand .title{font-weight:800}
        .brand .sub{font-size:12px;color:var(--muted)}
        .qr{width:64px;height:64px;border:1px dashed var(--border);border-radius:10px;display:grid;place-items:center}

        /* ===== header meta & stats ===== */
        .header{display:grid;grid-template-columns:1fr;gap:10px;border-bottom:1px solid var(--border);padding-bottom:8px;margin-bottom:8px}
        .pills{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;margin-top:6px}
        .pill{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:8px 10px;font-size:12px}
        .stats{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:8px;margin:10px 0}
        .stat{background:var(--soft);border:1px solid var(--border);border-radius:10px;padding:8px}
        .stat .l{font-size:11px;color:var(--muted)}
        .stat .v{font-weight:800}

        /* ===== Days ===== */
        .days{display:grid;grid-template-columns:1fr;gap:14px}
        .day{border:1px solid var(--border);border-radius:16px;overflow:hidden;background:#fff;page-break-inside:avoid;break-inside:avoid}
        .dayhead{padding:10px 14px;font-weight:800}
        .day--1 .dayhead{background:var(--day1)}
        .day--2 .dayhead{background:var(--day2)}
        .day--3 .dayhead{background:var(--day3)}
        .day--4 .dayhead{background:var(--day4)}
        .day--5 .dayhead{background:var(--day5)}
        .day--6 .dayhead{background:var(--day6)}
        .day--7 .dayhead{background:var(--day7)}

        /* جدول وعده‌ها با خط‌چین‌های واضح – دسکتاپ */
        .meal-table{width:100%;border-collapse:separate;border-spacing:0}
        .meal-table tbody{border-top:1px dashed var(--border)}
        .meal-row{
            display:grid;
            grid-template-columns:140px 1fr;
            column-gap:0px;
            align-items:stretch;
            min-height:89px;
            border-bottom:1px dashed var(--border);
        }
        .rail{
            border-right:1px dashed var(--border);
            min-height:100%;
            display:flex;align-items:center;justify-content:center;
        }
        .cell{position:relative;padding:22px 20px;text-align:right}
        .content{font-size:13px;line-height:1.9;word-break:break-word;color:var(--ink)}

        /* برچسب وعده ۹۰ درجه */
        .vtag{
            color:#fff;background:#64748b;font-weight:800;font-size:10px;letter-spacing:.2px;
            padding:3px 8px;border-radius:6px;opacity:.96;white-space:nowrap;z-index:1;box-shadow:0 2px 6px rgba(0,0,0,.06);
        }
        .vtag--breakfast{background:var(--t-breakfast)}
        .vtag--lunch{background:var(--t-lunch)}
        .vtag--dinner{background:var(--t-dinner)}
        .vtag--snack {background:var(--t-snack)}   /* fallback */
        .vtag--snack1{background:var(--t-snack1)} /* میان‌وعده 1 */
        .vtag--snack2{background:var(--t-snack2)} /* میان‌وعده 2 */
        .vtag--snack3{background:var(--t-snack3)} /* میان‌وعده 3 */

        /* رفع سایه روی صفحه فقط هنگام نمایش */
        @media screen { .vtag{ box-shadow:none } }

        /* ===== Mobile (<=768px) ===== */
        @media (max-width: 768px) {
            .brandbar{grid-template-columns:1fr 48px;gap:8px}
            .brand img{max-height:36px}
            .brand .title{font-size:14px}
            .brand .sub{font-size:11px}
            .qr{width:48px;height:48px}

            .header{grid-template-columns:1fr;gap:8px}
            .pills{grid-template-columns:repeat(2,minmax(0,1fr));gap:6px}
            .pill{padding:6px 8px;font-size:11px}

            .stats{grid-template-columns:repeat(2,minmax(0,1fr));gap:6px}
            .stat{padding:6px}
            .stat .l{font-size:10px}
            .stat .v{font-size:12px}

            .days{gap:10px}
            .day{border-radius:12px}
            .dayhead{padding:8px 10px;font-size:14px}

            /*.meal-row{grid-template-columns:96px 1fr;column-gap:0px;min-height:54px}*/
            .vtag{font-size:8.8px;padding:2px 6px}
            .vtag--snack, .vtag--snack1, .vtag--snack2, .vtag--snack3 { font-size:8.2px; padding:2px 5px; }

            .cell{padding:12px}
            .content{font-size:11px;line-height:1.7}
        }

        /* ===== Very small phones (<=480px) ===== */
        @media (max-width: 480px){
            /*.meal-row{grid-template-columns:100px 1fr;column-gap:10px;min-height:52px}*/
            .vtag{font-size:8.4px;padding:2px 5px}
            .vtag--snack, .vtag--snack1, .vtag--snack2, .vtag--snack3 { font-size:8px; padding:2px 4px; }
            .content{font-size:10.5px;line-height:1.6}
        }

        /* === Tight layout for meal rows (Desktop) === */
        @media (min-width: 769px) {
            /*.meal-row { grid-template-columns: 45px 1fr; column-gap: 0px; }*/
            .cell { padding: 16px 14px; }
            .vtag { padding: 2px 7px; font-size: 13px; }
        }

        /* === Tight layout (Mobile ≤768px) === */
        @media (max-width: 768px) {
            /*.meal-row { grid-template-columns: 50px 1fr; column-gap: 0px; min-height: 55px; }*/
            .cell { padding: 10px 10px; }
            .content { font-size: 11px; line-height: 1.65; }
            .vtag { font-size: 8.8px; padding: 1px 5px; }
            .vtag--snack, .vtag--snack1, .vtag--snack2, .vtag--snack3 { font-size: 8.2px; padding: 1px 4px; }
        }

        /* === Very small phones (≤480px) === */
        @media (max-width: 480px) {
            /*.meal-row { grid-template-columns: 45px 1fr; column-gap: 1px; min-height: 48px; }*/
            .rail { padding: 1px 3px; }
            .cell { padding: 9px 8px; }
            .vtag { font-size: 8.4px; padding: 1px 4px; }
            .vtag--snack, .vtag--snack1, .vtag--snack2, .vtag--snack3 { font-size: 8px; padding: 1px 3px; }
        }

        /* ===== Print: desktop layout ONLY for the top area ===== */
        @media print {
            .brandbar { grid-template-columns: 1fr auto !important; gap: 12px !important; }
            .brand img { max-height: 46px !important; }
            .qr { width: 64px !important; height: 64px !important; }

            .pills { grid-template-columns: repeat(4, minmax(0,1fr)) !important; gap: 8px !important; }
            .pill { padding: 8px 10px !important; font-size: 12px !important; }

            .stats { grid-template-columns: repeat(6, minmax(0,1fr)) !important; gap: 8px !important; }
            .stat { padding: 8px !important; }
            .stat .l { font-size: 11px !important; }
            .stat .v { font-size: 12px !important; }
        }
        .option-line {
            margin-bottom: 4px;
            padding: 4px 0; /* کمی فاصله برای تنفس */
            display: flex;
            align-items: center;
            gap: 6px;
            border-bottom: 1px dashed #ddd; /* خط خیلی کم‌رنگ */
        }

        .option-line:last-child {
            border-bottom: none; /* آخرین خط جداکننده نداشته باشه */
        }

        .option-line .badge {
            display: inline-block;
            background: #999999;
            color: #fff;
            font-size: 11px;
            padding: 0px 8px;
            white-space: nowrap;
        }
        .notes-box {
            border: 1px solid #bbb;
            border-radius: 8px;
            padding: 12px 14px;
            margin-top: 16px;
            background: #f9fafb; /* پس‌زمینه خیلی ملایم */
            font-size: 11px;
            line-height: 1.5;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .notes-box h3 {
            font-size: 13px;
            font-weight: 700;
            margin: 0 0 8px 0;
            padding-bottom: 4px;
            border-bottom: 1px solid #ddd; /* خط زیر تیتر */
            color: #374151; /* خاکستری تیره */
        }

        .notes-box p {
            font-size: 11px;
            line-height: 1.5;
            margin: 0;
            color: #111827;
            text-align: justify; /* متن منظم‌تر */
        }
        @media print {


            @page { size: A4 portrait; margin: 8mm; }

            .day, .meal-row { break-inside: avoid; page-break-inside: avoid; }

            .meal-row {
                display: grid !important;
                grid-template-columns: 140px 1fr !important;
                column-gap: 4px !important;
                min-height: auto !important;
                border-bottom: 1px dashed #ccc !important;
            }
            .rail {
                display: flex !important;
                align-items: center !important;
                justify-content: flex-end !important;
                padding: 0 2px !important;
                border-right: 1px dashed #ddd !important;
                text-align: right !important;
            }
            .vtag {
                position: static !important;
                transform: none !important;
                font-size: 9px !important;
                padding: 0 4px !important;
                line-height: 1.3 !important;
                white-space: nowrap !important;
                margin: 0 !important;
                text-align: right !important;
            }

            .cell { padding: 1px 2px !important; }
            .content { font-size: {{$fontPrintContent ?? 13}}px !important; font-weight: bold; line-height: 1.25 !important; }

            .dayhead {
                padding: 2px 3px !important;
                font-size: 10px !important;
                font-weight: 700 !important;
                background: #f3f4f6 !important;
                border-bottom: 1px solid #ddd !important;
            }

            /* option-line compact in print */
            .option-line { padding: 2px 0 !important; gap: 4px !important; }
            .option-line .badge { font-size: 8.5px !important; padding: 0 4px !important; }

            /* notes-box: یک استایل واحد */
            .notes-box {
                border: 1px solid #bbb !important;
                border-radius: 8px !important;
                padding: 10px 12px !important;
                margin-top: 12px !important;
                background: #f9fafb !important;
                font-size: 10.5px !important;
                line-height: 1.4 !important;
                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }
            .notes-box h3 {
                font-size: 12px !important;
                margin: 0 0 6px 0 !important;
                padding-bottom: 3px !important;
                border-bottom: 1px solid #ddd !important;
            }
            .notes-box p { font-size: 10.5px !important; line-height: 1.45 !important; margin: 0 !important; }
        }
    </style>
</head>
<body>

@php
    $mealClass = function ($name) {
        $n = str_replace('‌', ' ', $name);
        if (str_contains($n, 'صبحانه')) return 'vtag--breakfast';
        if (preg_match('/میان\s*وعده\s*[1۱]/u', $n)) return 'vtag--snack1';
        if (preg_match('/میان\s*وعده\s*[2۲]/u', $n)) return 'vtag--snack2';
        if (preg_match('/میان\s*وعده\s*[3۳]/u', $n)) return 'vtag--snack3';
        if (str_contains($n, 'ناهار')) return 'vtag--lunch';
        if (str_contains($n, 'شام')) return 'vtag--dinner';
        return 'vtag--snack';
    };
@endphp

<div class="page">

    <section class="brandbar">
        <div class="brand">
            <img src="{{asset('shop/images/logo.webp')}}" alt="logo">
            <div>
                @if($user->nutritionists->count())
                @endif
            </div>
        </div>
        <div class="qr">qr</div>
    </section>
    {{-- ================= HEADER ================= --}}
    <section class="header">
        <div>
            <div style="font-weight:800;font-size:18px">
                برنامه رژیم
            </div>
            <div style="color:var(--muted);font-size:12px">این رژیم مخصوص شماست؛ از اشتراک آن با دیگران خودداری کنید.</div>

            <div class="pills">
                <div class="pill"><b>نام:</b> {{ ($user->full_name) }}</div>
                <div class="pill"><b>کد درخواست:</b> {{ $dietRequest->id }}</div>
                <div class="pill"><b>تاریخ شروع:</b> {{ getPersianDateSimple($dietRequest->start_date) }}</div>
                <div class="pill"><b>وزن شما:</b> {{isset($dietRequest->user_information['WEIGHT']) ? $dietRequest->user_information['WEIGHT'] :  ' - '}} kg</div>
            </div>
        </div>
    </section>

    {{-- ================= DAYS ================= --}}
    <section class="days">

        @foreach($diet['data'] as $dayIndex => $day)
            <div class="day day--{{ ($loop->index % 7) + 1 }}">

                {{-- Day Header --}}
                <div class="dayhead">
                    {{ $day['full_date_of_day_persian']}}
                </div>

                <table class="meal-table">
                    <tbody>

                    @forelse($day['foods'] as $food)
                        <tr class="meal-row">

                            {{-- Meal Title --}}
                            <td class="rail">
                                <span class="vtag {{ $mealClass($food['title']) }}">
                                    {{ $food['title'] }}
                                </span>
                            </td>

                            {{-- Meal Content --}}
                            <td class="cell">
                                <div class="content">

                                    @php
                                        $meals = $food['meals'] ?? [];
                                        $multi = count($meals) > 1;
                                    @endphp

                                    @foreach($meals as $i => $meal)
                                        <div class="option-line">

                                            @if($multi )
                                                @if($i == 0)
                                                    <span class="badge">
                                                   +
                                                </span>
                                                @else
                                                    <span class="badge">
                                                   +
                                                </span>
                                                @endif
                                            @endif

                                            {{ $meal['title'] }}

                                            @if(!empty($meal['calorie']))
                                                <span style="font-size:10px;color:#6b7280">
                                                    ({{ $meal['calorie'] }} کالری)
                                                </span>
                                            @endif
                                        </div>
                                    @endforeach

                                    {{-- Hand Written --}}
                                    @if(!empty($food['hand_written']))
                                        <div class="option-line">
                                            {{ $food['hand_written'] }}
                                        </div>
                                    @endif

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="meal-row">
                            <td class="rail"></td>
                            <td class="cell">
                                <div class="content">
                                    برای این روز برنامه‌ای ثبت نشده است.
                                </div>
                            </td>
                        </tr>
                    @endforelse

                    </tbody>
                </table>

            </div>
        @endforeach

    </section>

    {{-- ================= DESCRIPTION ================= --}}
    @if(!empty($diet['description']))
        <section class="notes-box">
            <h3>توضیحات رژیم</h3>
            <p>{!! nl2br(e($diet['description'])) !!}</p>
        </section>
    @endif

</div>

</body>
</html>
