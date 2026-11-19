<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Fee receipt R-{{ $receipt->id }}</title>
<style>
@page { size: A4; margin: 18mm; }
body { margin: 0; color: #203b36; font: 14px/1.5 "DejaVu Sans", sans-serif; }
main, nav { max-width: 680px; margin: 24px auto; padding: 0 16px; }
h1 { font: 32px Georgia, serif; margin: 7px 0; }
.school { color: #286351; font-weight: bold; margin-top: 24px; }
table { width: 100%; border-collapse: collapse; margin-top: 24px; }
th, td { text-align: left; padding: 10px 4px; border-bottom: 1px solid #d3dcd8; }
td { text-align: right; }
.total { background: #e9f2ee; font-weight: bold; }
button, a { font: inherit; color: #205541; margin-right: 12px; }
@media print { nav { display: none; } main { margin: 0; padding: 0; } }
</style>
</head>
<body>
@if(!$pdf)
<nav aria-label="Receipt actions">
<a href="{{ route('home') }}">Back to fees</a>
<button type="button" onclick="window.print()">Print receipt</button>
<a href="{{ route('receipts.pdf', $receipt->id) }}">Download PDF</a>
</nav>
@endif
<main>
<p class="school">{{ $receipt->name }}</p>
<h1>School fee receipt</h1>
<p>Receipt R-{{ $receipt->id }}</p>
<table>
<tr><th>Student</th><td>{{ $receipt->student }}</td></tr>
<tr><th>Recorded on</th><td>{{ \Carbon\Carbon::parse($receipt->created_at)->timezone('Asia/Kolkata')->format('d M Y, h:i A') }} IST</td></tr>
<tr><th>Balance before payment</th><td>INR {{ number_format(($receipt->fee_paise - $receipt->collected + $receipt->amount_paise) / 100, 2) }}</td></tr>
<tr class="total"><th>Amount received</th><td>INR {{ number_format($receipt->amount_paise / 100, 2) }}</td></tr>
<tr><th>Balance after payment</th><td>INR {{ number_format(($receipt->fee_paise - $receipt->collected) / 100, 2) }}</td></tr>
</table>
</main>
</body>
</html>
