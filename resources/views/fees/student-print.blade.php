<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fee statement — {{ $student->full_name }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: "Segoe UI", Arial, sans-serif; color: #1b1f1d; margin: 0; background: #f4f3ef; font-size: 12px; }
        .sheet { width: 210mm; min-height: 297mm; margin: 16px auto; background: #fff; border: 1px solid #ddd; padding: 16mm 14mm; }
        .top { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #1f5640; padding-bottom: 12px; }
        .school h1 { margin: 0; font-size: 20px; color: #1f5640; }
        .school p { margin: 2px 0 0; font-size: 11px; color: #555; }
        .doc { text-align: right; }
        .doc .tag { display: inline-block; background: #1f5640; color: #fff; padding: 3px 10px; border-radius: 4px; font-size: 11px; letter-spacing: .05em; }
        .doc p { margin: 6px 0 0; font-size: 11px; color: #555; }
        .meta { display: flex; flex-wrap: wrap; gap: 4px 36px; margin: 14px 0 6px; }
        .meta div span { color: #777; display: block; font-size: 10px; }
        .meta div { font-size: 13px; font-weight: 600; }
        h2.head { font-size: 13px; margin: 16px 0 4px; color: #1f5640; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 5px 8px; border-bottom: 1px solid #eee; }
        th { background: #f0eee8; font-size: 10px; text-transform: uppercase; letter-spacing: .04em; color: #555; }
        td.num, th.num { text-align: right; font-variant-numeric: tabular-nums; }
        tr.sub td { border-top: 1px solid #1f5640; font-weight: 700; background: #fafafa; }
        .st { font-weight: 700; font-size: 10px; text-transform: uppercase; }
        .st.paid { color: #237a4f; } .st.partial { color: #9a5b06; } .st.unpaid { color: #b3261e; } .st.waived { color: #1f5f8b; }
        .grand { margin-top: 14px; border-top: 2px solid #1f5640; padding-top: 10px; }
        .grand table { width: 60%; margin-left: auto; }
        .grand td { border: 0; padding: 3px 8px; }
        .grand tr.total td { border-top: 1px solid #999; font-weight: 700; font-size: 14px; }
        .sign { display: flex; justify-content: space-between; margin-top: 44px; font-size: 11px; color: #555; }
        .sign div { border-top: 1px solid #999; padding-top: 4px; width: 180px; text-align: center; }
        .foot { margin-top: 18px; font-size: 10px; color: #999; text-align: center; }
        .actions { width: 210mm; margin: 0 auto 12px; text-align: right; }
        .actions button, .actions a { font: inherit; padding: 8px 16px; border-radius: 6px; border: 1px solid #1f5640; cursor: pointer; text-decoration: none; }
        .actions .print { background: #1f5640; color: #fff; }
        .actions .close { background: #fff; color: #1f5640; }
        @media print {
            body { background: #fff; }
            .sheet { border: 0; margin: 0; width: auto; min-height: auto; padding: 0; }
            .actions { display: none; }
            @page { size: A4; margin: 12mm; }
            tr, h2.head { page-break-inside: avoid; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="actions">
        <a class="close" href="{{ route('fees.student', $student) }}">Close</a>
        <button class="print" onclick="window.print()">Print</button>
    </div>

    <div class="sheet">
        <div class="top">
            <div class="school">
                <h1>{{ $school->name ?? config('app.name') }}</h1>
                @if($school?->address)<p>{{ $school->address }}</p>@endif
                @if($school?->phone)<p>Phone: {{ $school->phone }}</p>@endif
            </div>
            <div class="doc">
                <span class="tag">FEE STATEMENT</span>
                <p>Date: {{ now()->format('d M Y') }}</p>
            </div>
        </div>

        <div class="meta">
            <div><span>Student</span>{{ $student->full_name }}</div>
            <div><span>Admission No</span>{{ $student->admission_number ?? '—' }}</div>
            @php $enr = $student->enrollments->firstWhere('status', 'active'); @endphp
            @if($enr)<div><span>Class</span>{{ $enr->schoolClass->name ?? '' }}{{ $enr->section ? ' · ' . $enr->section->name : '' }}</div>@endif
        </div>

        @forelse($groups as $label => $fees)
            @php
                $head = $fees->first();
                $gBill = $fees->sum(fn ($f) => (float) $f->amount + (float) $f->fine - (float) $f->discount);
                $gWaive = $fees->sum(fn ($f) => (float) $f->waived_amount);
                $gPaid = $fees->sum(fn ($f) => (float) $f->paid_amount);
            @endphp
            <h2 class="head">{{ $head->title }}@if($head->schoolClass) · {{ $head->schoolClass->name }}@endif</h2>
            <table>
                <thead>
                    <tr><th>Period</th><th>Due</th><th class="num">Amount</th><th class="num">Waived</th><th class="num">Paid</th><th class="num">Balance</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @foreach($fees as $fee)
                        <tr>
                            <td>{{ $fee->period ?: $fee->title }}</td>
                            <td>{{ optional($fee->due_date)->format('d M Y') ?? '—' }}</td>
                            <td class="num">{{ number_format((float) $fee->amount + (float) $fee->fine - (float) $fee->discount, 2) }}</td>
                            <td class="num">{{ $fee->waived_amount > 0 ? number_format($fee->waived_amount, 2) : '—' }}</td>
                            <td class="num">{{ number_format($fee->paid_amount, 2) }}</td>
                            <td class="num">{{ number_format($fee->balance, 2) }}</td>
                            <td><span class="st {{ $fee->status }}">{{ ucfirst($fee->status) }}</span></td>
                        </tr>
                    @endforeach
                    <tr class="sub">
                        <td colspan="2">Subtotal</td>
                        <td class="num">{{ number_format($gBill, 2) }}</td>
                        <td class="num">{{ $gWaive > 0 ? number_format($gWaive, 2) : '—' }}</td>
                        <td class="num">{{ number_format($gPaid, 2) }}</td>
                        <td class="num">{{ number_format($gBill - $gWaive - $gPaid, 2) }}</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        @empty
            <p style="color:#777;margin-top:20px">No fees recorded for this student.</p>
        @endforelse

        <div class="grand">
            <table>
                <tbody>
                    <tr><td>Total billed</td><td class="num">{{ number_format($totals['billed'], 2) }}</td></tr>
                    @if($totals['waived'] > 0)<tr><td>Total waived</td><td class="num">− {{ number_format($totals['waived'], 2) }}</td></tr>@endif
                    <tr><td>Total paid</td><td class="num">− {{ number_format($totals['paid'], 2) }}</td></tr>
                    <tr class="total"><td>Balance due</td><td class="num">{{ number_format($totals['balance'], 2) }}</td></tr>
                </tbody>
            </table>
        </div>

        <div class="sign">
            <div>Parent / Guardian</div>
            <div>Authorised signature</div>
        </div>
        <div class="foot">Computer-generated statement · Printed {{ now()->format('d M Y, g:i A') }}</div>
    </div>
</body>
</html>
