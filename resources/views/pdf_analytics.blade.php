<!DOCTYPE html>
<html>
<head>
    <title>Analytics Report</title>
    <style>
        body { font-family: 'Helvetica', Arial, sans-serif; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #D6001C; padding-bottom: 15px; margin-bottom: 20px; }
        .title { font-size: 20px; color: #D6001C; text-transform: uppercase; font-weight: bold; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td { width: 50%; vertical-align: top; text-align: center; padding: 10px; }
        .section-title { font-size: 13px; font-weight: bold; margin-bottom: 10px; border-left: 4px solid #D6001C; padding-left: 10px; text-align: left; background: #f3f4f6; padding: 5px; }
        .chart-img { width: 100%; max-width: 250px; }
        .summary-box { border: 1px solid #eee; padding: 10px; font-size: 11px; margin-top: 10px; background: #fafafa; }
    </style>
</head>
<body>

    <div class="header">
        <h1 class="title">{{ $reportTitle }}</h1>
        <div style="font-size: 11px; color: #666; margin-top: 5px;">Generated on: {{ now()->format('d F Y, h:i A') }}</div>
    </div>

    <table class="grid">
        <tr>
            <td>
                <div class="section-title">Attendance Distribution</div>
                <img src="{{ $chartAtt }}" class="chart-img">
                <div class="summary-box">
                    On Time: <strong>{{ $onTime }}</strong> | Late: <span style="color:red;">{{ $late }}</span>
                </div>
            </td>
            <td>
                <div class="section-title">Leave Breakdown (Days)</div>
                <img src="{{ $chartLeave }}" class="chart-img">
                <div class="summary-box">
                    Total Leave Taken: <strong>{{ $totalDaysMonth }} Days</strong>
                </div>
            </td>
        </tr>
    </table>

    <div style="margin-top: 20px;">
        <div class="section-title">Overtime Trend ({{ $currentYear }})</div>
        <div style="text-align: center;">
            <img src="{{ $chartOT }}" style="width: 100%; max-height: 230px;">
        </div>
    </div>

    <div style="margin-top: 30px; font-size: 10px; text-align: center; color: #999; border-top: 1px dashed #ccc; padding-top: 10px;">
        CONFIDENTIAL DOCUMENT - DOREMi SERVICES & RENTAL SDN BHD
    </div>

</body>
</html>