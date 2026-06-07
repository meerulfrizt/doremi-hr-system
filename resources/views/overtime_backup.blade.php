<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Overtime Management - DOREMi Admin</title>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
  
  <style>
    /* --- CSS ASAL AWAK (DESIGN) --- */
   /* Update bahagian ini dalam <style> */

.filter-bar { 
    display: flex; 
    gap: 15px; /* Dahulu 10px, kita besarkan jadi 15px */
    align-items: center; 
}

.filter-input { 
    padding: 8px 12px; 
    border: 1px solid #d1d5db; /* Border gelap sikit supaya nampak jelas */
    border-radius: 6px; 
    outline: none; 
    font-size: 13px; 
    background-color: white;
    cursor: pointer;
}

/* Style baru untuk butang Reset */
.btn-reset {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 12px; 
    color: #b91c1c; 
    background: #fee2e2; 
    padding: 8px 12px; 
    border-radius: 6px; 
    text-decoration: none; 
    font-weight: 600;
    transition: all 0.2s;
    border: 1px solid #fca5a5;
}

.btn-reset:hover {
    background: #fecaca;
    transform: translateY(-1px);
}

    /* DETAIL PANEL (SLIDE UP) */
    #detailPanel {
        display: none; /* Hidden by default */
        margin-top: 20px;
        background: #e2e4e7;
        border-radius: 8px;
        padding: 25px;
        border-left: 5px solid #D6001C;
        animation: slideDown 0.3s ease-out;
    }

    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .detail-title { font-size: 18px; font-weight: 700; color: #111; margin-bottom: 15px; text-transform: uppercase; }
    .detail-row { margin-bottom: 8px; font-size: 14px; color: #333; }
    .detail-label { font-weight: 600; width: 140px; display: inline-block; }

    /* AI Insight Box */
    .ai-box {
        margin-top: 15px; padding: 10px; background: rgba(255,255,255,0.5);
        border-radius: 6px; font-style: italic; color: #555; border-left: 3px solid #6C5CE7;
    }

    /* Big Action Buttons */
    .big-actions { margin-top: 20px; display: flex; gap: 10px; }
    .btn-big { padding: 10px 20px; border-radius: 20px; font-weight: 700; border: none; cursor: pointer; color: white; font-size: 13px; }
    .btn-big.approve { background: #008000; }
    .btn-big.reject { background: #D6001C; }
    .btn-big:hover { opacity: 0.9; }
  </style>
</head>
<body>
  <div class="app">

    <aside class="sidebar" id="sidebar">
      <div class="brand">
        <img src="{{ asset('img/Doremi logo.png') }}" alt="logo" class="brand-logo">
      </div>
      <nav class="menu">
        <a href="{{ route('dashboard') }}" style="text-decoration: none; color: inherit;">
            <button class="menu-item"><span class="icon">🏠</span> <span class="label">Dashboard</span></button>
        </a>
        <a href="{{ route('attendance') }}" style="text-decoration: none; color: inherit;">
            <button class="menu-item"><span class="icon">🕒</span> <span class="label">Attendance Monitoring</span></button>
        </a>
        <div class="menu-group">
          <div class="menu-group-title">Management</div>
          <a href="{{ route('overtime') }}" style="text-decoration: none; color: inherit;">
              <button class="menu-item sub active"><span class="icon">⏱️</span> <span class="label">Overtime</span></button>
          </a>
          <button class="menu-item sub"><span class="icon">🔁</span> <span class="label">Flexible Hours</span></button>
          <a href="{{ route('leave') }}" style="text-decoration: none; color: inherit;">
            <button class="menu-item sub"><span class="icon">🗓️</span> <span class="label">Leave Application</span></button>
          </a>
        </div>
        <a href="{{ route('analytics') }}" style="text-decoration: none; color: inherit;">
            <button class="menu-item"> <span class="icon">📊</span> <span class="label">Analytics</span></button>
        </a>
        <a href="{{ route('directory') }}" style="text-decoration: none; color: inherit;">
            <button class="menu-item"> <span class="icon">👥</span> <span class="label">Employee Directory</span></button>
        </a>    
      </nav>
    </aside>

    <main class="main">
      <header class="topbar">
        <div class="left">
          <button id="mobileMenuBtn" class="hamburger">☰</button>
          <h1 class="page-title">Overtime Management</h1>
        </div>
        <div class="right">
          <div class="topbar-actions">
            <button class="icon-btn">🔔 <span class="badge">{{ $pending_count }}</span></button>
            <div class="profile">
              <div class="avatar">{{ substr(Auth::user()->name, 0, 1) }}</div>
              <div class="profile-menu">
                <div class="profile-name">{{ Auth::user()->name }}</div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" style="background:none; border:none; color:#D6001C; font-size:11px; cursor:pointer; font-weight:600;">Log Out</button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </header>

      <section class="content">

        <div class="card-row">
          <div class="stat-card">
            <div class="stat-title">Total OT Today</div>
            <div class="stat-value">{{ $total_hours_today }} <span style="font-size:14px; color:#888;">Hours</span></div>
          </div>
          <div class="stat-card">
            <div class="stat-title">Total OT This Week</div>
            <div class="stat-value">{{ $total_hours_week }} <span style="font-size:14px; color:#888;">Hours</span></div>
          </div>
          <div class="stat-card warning" style="border-left-color: #f59e0b;">
            <div class="stat-title">Pending OT Approvals</div>
            <div class="stat-value" style="color: #d97706;">{{ $pending_count }} Requests</div>
          </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <h3>OT Requests List</h3>
                <div class="panel-header">
                
                
                <form method="GET" action="{{ route('overtime') }}">
                    <div class="filter-bar">
                        <input type="date" name="date" class="filter-input" 
                               value="{{ request('date') }}" 
                               onchange="this.form.submit()">
                        
                        <select name="status" class="filter-input" onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <option value="Pending" {{ request('status') == 'Pending' ? 'selected' : '' }}>Pending</option>
                            <option value="Verified" {{ request('status') == 'Verified' ? 'selected' : '' }}>Approved</option>
                            <option value="Rejected" {{ request('status') == 'Rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                        
                        @if(request('status') || request('date'))
                            <a href="{{ route('overtime') }}" class="btn-reset">
                                <span>🔄</span> Reset Filter
                            </a>
                        @endif
                    </div>
                </form>
                </div>
            </div>

            <table class="table">
            <thead>
                <tr>
                <th>Date</th>
                <th>Name</th>
                <th>Event / Reason</th>
                <th>Req. Hours</th>
                <th>Status</th>
                <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ot_requests as $ot)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($ot->date)->format('d/m/Y') }}</td>
                    <td style="font-weight:600;">{{ $ot->user->name }}</td>
                    <td>{{ $ot->event_name }}</td>
                    <td>{{ $ot->duration_hours }}H</td>
                    
                    <td>
                        @if($ot->status == 'Verified')
                            <span class="status-badge approved">Approved</span>
                        @elseif($ot->status == 'Rejected')
                            <span class="status-badge rejected">Rejected</span>
                        @else
                            <span class="status-badge pending">Pending</span>
                        @endif
                    </td>
                    
                    <td>
                        <button class="btn-table btn-view" 
                                onclick="openDetail('{{ $ot->id }}', '{{ $ot->user->name }}', '{{ $ot->event_name }}', '{{ $ot->duration_hours }}')">
                            VIEW DETAILS
                        </button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center; padding:20px; color:#888;">No overtime requests found.</td></tr>
                @endforelse
            </tbody>
            </table>
            
            <div style="margin-top:15px;">
                {{ $ot_requests->links('pagination::bootstrap-5') }}
            </div>
        </div>

        <div id="detailPanel">
            <div class="detail-title">Employee : <span id="detailName">...</span></div>
            
            <div class="detail-grid">
                <div>
                    <div class="detail-row"><span class="detail-label">EVENT :</span> <span id="detailEvent">...</span></div>
                    <div class="detail-row"><span class="detail-label">DURATION :</span> <span id="detailOT">...</span> Hours</div>
                    <div class="detail-row"><span class="detail-label">CURRENT STATUS :</span> Pending Approval</div>
                    <div class="detail-row"><span class="detail-label">REPLACEMENT :</span> (Auto Calculated)</div>
                </div>

                <div>
                    <div class="ai-box">
                        <strong>🤖 AI Insight:</strong><br>
                        Frequent OT detected this week for this employee. Consider schedule adjustment for next event.
                    </div>
                </div>
            </div>

            <div class="big-actions">
                <form id="approveForm" method="POST" action="">
                    @csrf 
                    <input type="hidden" name="status" value="Verified">
                    <button type="submit" class="btn-big approve" onclick="return confirm('Approve this OT claim?')">APPROVE</button>
                </form>

                <form id="rejectForm" method="POST" action="">
                    @csrf 
                    <input type="hidden" name="status" value="Rejected">
                    <button type="submit" class="btn-big reject" onclick="return confirm('Reject this OT claim?')">REJECT</button>
                </form>

                <button class="btn-big" style="background:#555;" onclick="closeDetail()">CLOSE</button>
            </div>
        </div>

      </section>
    </main>
  </div>

  <script>
    function openDetail(id, name, event, duration) {
        // 1. Masukkan data ke dalam text panel
        document.getElementById('detailName').innerText = name;
        document.getElementById('detailEvent').innerText = event;
        document.getElementById('detailOT').innerText = duration;

        // 2. Update Link Action Form supaya dia tahu ID mana nak update
        // URL akan jadi contoh: /overtime/3/update
        let updateUrl = "/overtime/" + id + "/update";
        document.getElementById('approveForm').action = updateUrl;
        document.getElementById('rejectForm').action = updateUrl;

        // 3. Tunjukkan Panel
        let panel = document.getElementById('detailPanel');
        panel.style.display = 'block';
        panel.scrollIntoView({ behavior: 'smooth' });
    }

    function closeDetail() {
        document.getElementById('detailPanel').style.display = 'none';
    }
  </script>
</body>
</html>