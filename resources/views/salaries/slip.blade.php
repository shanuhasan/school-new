<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payslip — {{ $salary->employee_name }} ({{ $salary->month_label }})</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: "Segoe UI", Arial, sans-serif; color: #1b1f1d; margin: 0; background: #f4f3ef; }
        .sheet { max-width: 640px; margin: 24px auto; background: #fff; border: 1px solid #ddd; padding: 28px 32px; }
        .top { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #1f5640; padding-bottom: 14px; }
        .school h1 { margin: 0; font-size: 20px; color: #1f5640; }
        .school p { margin: 2px 0 0; font-size: 12px; color: #555; }
        .doc { text-align: right; }
        .doc .tag { display: inline-block; background: #1f5640; color: #fff; padding: 3px 10px; border-radius: 4px; font-size: 12px; letter-spacing: .05em; }
        .doc p { margin: 6px 0 0; font-size: 12px; color: #555; }
        .meta { display: flex; flex-wrap: wrap; gap: 6px 32px; margin: 18px 0; font-size: 13px; }
        .meta div span { color: #777; display: block; font-size: 11px; }
        .cols { display: flex; gap: 24px; }
        .cols table { flex: 1; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 13px; }
        th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #eee; }
        th { background: #f0eee8; font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: #555; }
        td.num { text-align: right; font-variant-numeric: tabular-nums; }
        .net { margin-top: 16px; border-top: 2px solid #1f5640; padding-top: 10px; display: flex; justify-content: space-between; font-weight: 700; font-size: 16px; }
        .sign { display: flex; justify-content: space-between; margin-top: 50px; font-size: 12px; color: #555; }
        .sign div { border-top: 1px solid #999; padding-top: 4px; width: 180px; text-align: center; }
        .foot { margin-top: 22px; font-size: 11px; color: #999; text-align: center; }
        .actions { max-width: 640px; margin: 0 auto 24px; text-align: right; }
        .actions button, .actions a { font: inherit; padding: 8px 16px; border-radius: 6px; border: 1px solid #1f5640; cursor: pointer; text-decoration: none; }
        .actions .print { background: #1f5640; color: #fff; }
        .actions .close { background: #fff; color: #1f5640; }
        @media print { body { background: #fff; } .sheet { border: 0; margin: 0; max-width: none; } .actions { display: none; } @page { margin: 12mm; } }
    </style>
</head>
<body onload="window.print()">
    <div class="actions">
        <a class="close" href="{{ route('salaries.show', $salary) }}">Close</a>
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
                <span class="tag">PAYSLIP</span>
                <p>No: PS-{{ str_pad($salary->id, 5, '0', STR_PAD_LEFT) }}</p>
                <p>For: {{ $salary->month_label }}</p>
            </div>
        </div>

        <div class="meta">
            <div><span>Employee</span>{{ $salary->employee_name }}</div>
            <div><span>Designation</span>{{ $salary->designation ?? '—' }}</div>
            <div><span>Status</span>{{ ucfirst($salary->status) }}{{ $salary->paid_on ? ' · ' . $salary->paid_on->format('d M Y') : '' }}</div>
        </div>

        <div class="cols">
            <table>
                <thead><tr><th>Earnings</th><th class="num">Amount</th></tr></thead>
                <tbody>
                    <tr><td>Basic</td><td class="num">{{ number_format($salary->basic, 2) }}</td></tr>
                    <tr><td>Allowances</td><td class="num">{{ number_format($salary->allowances, 2) }}</td></tr>
                </tbody>
            </table>
            <table>
                <thead><tr><th>Deductions</th><th class="num">Amount</th></tr></thead>
                <tbody>
                    <tr><td>Deductions</td><td class="num">{{ number_format($salary->deductions, 2) }}</td></tr>
                    <tr><td>&nbsp;</td><td class="num">&nbsp;</td></tr>
                </tbody>
            </table>
        </div>

        <div class="net"><span>Net salary {{ $salary->method ? '(' . $salary->method . ')' : '' }}</span><span>{{ number_format($salary->net_salary, 2) }}</span></div>

        @if($salary->remarks)<p style="font-size:12px;color:#555;margin-top:14px">Remarks: {{ $salary->remarks }}</p>@endif

        <div class="sign">
            <div>Employee signature</div>
            <div>Authorised signature</div>
        </div>
        <div class="foot">This is a computer-generated payslip.</div>
    </div>
</body>
</html>
