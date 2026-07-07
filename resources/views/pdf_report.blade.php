<!DOCTYPE html>
<html>
<head>
    <title>Performance Report {{ $reportPeriod }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11px; color: #333; }

        .text-center { text-align: center; }
        .logo { color: #D6001C; font-size: 28px; font-weight: bold; margin-bottom: 5px; letter-spacing: 2px; }
        .report-title { font-size: 14px; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
        .report-date { font-size: 10px; color: #666; margin-bottom: 20px; }
        .divider { border-bottom: 2px solid #ccc; margin-bottom: 20px; }

        /* Summary Boxes */
        .summary-table { width: 100%; margin-bottom: 20px; border-collapse: separate; border-spacing: 10px 0; }
        .summary-box { border: 1px solid #eee; background: #fafafa; text-align: center; padding: 12px 5px; }
        .summary-title { font-size: 9px; color: #666; text-transform: uppercase; margin-bottom: 6px; }
        .summary-value { font-size: 20px; font-weight: bold; color: #D6001C; }
        .summary-value.green { color: #15803d; }
        .summary-value.orange { color: #c2410c; }
        .summary-value.blue { color: #1d4ed8; }

        /* Main Data Table */
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .data-table th, .data-table td { border: 1px solid #ddd; padding: 7px 10px; text-align: left; }
        .data-table th { background-color: #222; color: #fff; font-size: 10px; font-weight: normal; text-transform: uppercase; }
        .data-table tr:nth-child(even) { background-color: #f9f9f9; }

        .status-present { color: #15803d; font-weight: bold; }
        .status-late    { color: #D6001C; font-weight: bold; }
        .status-absent  { color: #991b1b; font-weight: bold; }

        .no-data { text-align: center; padding: 30px; color: #999; font-style: italic; }

        /* Footer signatures */
        .sig-table { width: 100%; margin-top: 50px; text-align: center; font-weight: bold; }
        .sig-table td { width: 50%; padding-top: 50px; }
        .sig-line { border-top: 1px solid #333; width: 200px; margin: 0 auto; padding-top: 5px; }
        .footer-note { text-align: center; font-size: 9px; color: #aaa; margin-top: 30px; }
    </style>
</head>
<body>

    @php
        $totalRecords = count($reportData);
        $onTimeCount  = $onTime  ?? 0;
        $lateCount    = $late    ?? 0;
        $absentCount  = $absent  ?? 0;
    @endphp

    {{-- HEADER --}}
    <div class="text-center">
        <div class="logo">DOREMI</div>
        <div class="report-title">Performance Report — {{ $reportPeriod ?? 'All Records' }}</div>
        <div class="report-date">Generated on: {{ \Carbon\Carbon::now()->timezone('Asia/Kuala_Lumpur')->format('d F Y, h:i A') }}</div>
    </div>
    <div class="divider"></div>

    {{-- SUMMARY BOXES --}}
    <table class="summary-table">
        <tr>
            <td class="summary-box">
                <div class="summary-title">Total Staff</div>
                <div class="summary-value blue">{{ $totalStaff ?? 0 }}</div>
            </td>
            <td class="summary-box">
                <div class="summary-title">On Time</div>
                <div class="summary-value green">{{ $onTimeCount }}</div>
            </td>
            <td class="summary-box">
                <div class="summary-title">Late</div>
                <div class="summary-value orange">{{ $lateCount }}</div>
            </td>
            <td class="summary-box">
                <div class="summary-title">Absent</div>
                <div class="summary-value">{{ $absentCount }}</div>
            </td>
            <td class="summary-box">
                <div class="summary-title">OT Hours (Approved)</div>
                <div class="summary-value blue">{{ $totalOTHours ?? 0 }}</div>
            </td>
            <td class="summary-box">
                <div class="summary-title">Leave Days (Approved)</div>
                <div class="summary-value orange">{{ $totalLeaveDays ?? 0 }}</div>
            </td>
        </tr>
    </table>

    {{-- ATTENDANCE TABLE --}}
    @if($totalRecords > 0)
    <table class="data-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Employee Name</th>
                <th>Department</th>
                <th>Clock In</th>
                <th>Clock Out</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reportData as $row)
            @php
                $s = strtolower($row->status);
                $sClass = $s === 'late' ? 'status-late' : ($s === 'absent' ? 'status-absent' : 'status-present');
            @endphp
            <tr>
                <td>{{ \Carbon\Carbon::parse($row->date)->format('d/m/Y') }}</td>
                <td><strong>{{ $row->name }}</strong></td>
                <td>{{ $row->department }}</td>
                <td>{{ ($row->clock_in && $row->clock_in !== '--:--') ? \Carbon\Carbon::parse($row->clock_in)->timezone('Asia/Kuala_Lumpur')->format('h:i A') : '--:--' }}</td>
                <td>{{ ($row->clock_out && $row->clock_out !== '--:--') ? \Carbon\Carbon::parse($row->clock_out)->timezone('Asia/Kuala_Lumpur')->format('h:i A') : '--:--' }}</td>
                <td class="{{ $sClass }}">{{ $row->status }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="no-data">No attendance records found for {{ $reportPeriod }}.</div>
    @endif

    {{-- SIGNATURES --}}
    <table class="sig-table">
        <tr>
            <td>
                <div class="sig-line">Prepared By: HR Admin</div>
            </td>
            <td>
                <div class="sig-line">Acknowledged By: Director</div>
            </td>
        </tr>
    </table>

    <div class="footer-note">CONFIDENTIAL DOCUMENT &nbsp;|&nbsp; DOREMI SERVICES &amp; RENTAL SDN BHD</div>

</body>
</html>