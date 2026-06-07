<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Leave Management - DOREMi Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --doremi-red: #D6001C; --sidebar-bg: #D6001C; --bg-light: #F9FAFB; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        html, body { height: 100%; width: 100%; overflow: hidden; }
        body { background-color: var(--bg-light); display: flex; }

        /* SIDEBAR */
        .sidebar { width: 260px; min-width: 260px; background-color: var(--sidebar-bg); color: white; display: flex; flex-direction: column; height: 100vh; }
        .brand { padding: 40px 20px; display: flex; justify-content: center; align-items: center; }
        .brand img { width: 170px; height: auto; filter: brightness(0) invert(1); }
        .menu { flex: 1; padding: 10px 0; overflow-y: auto; }
        .menu a { text-decoration: none; color: white; display: block; }
        .menu-item { width: 100%; padding: 12px 25px; display: flex; align-items: center; gap: 12px; background: none; border: none; color: white; font-size: 14px; cursor: pointer; text-align: left; }
        .menu-item.active { background: white; color: #111827 !important; font-weight: 700; border-radius: 50px 0 0 50px; margin-left: 15px; width: calc(100% - 15px); }
        .menu-group-title { padding: 20px 25px 10px; font-size: 11px; font-weight: 700; color: rgba(255, 255, 255, 0.5); text-transform: uppercase; letter-spacing: 1px; }

        /* MAIN CONTENT */
        .main { flex: 1; display: flex; flex-direction: column; height: 100vh; overflow-y: auto; width: 100%; }
        .topbar { background: white; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #edf2f7; position: sticky; top: 0; z-index: 10; width: 100%; }
        .topbar-right { display: flex; align-items: center; gap: 20px; }
        .icon-btn { background: #f3f4f6; border: none; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; position: relative; }
        .badge { position: absolute; top: 0; right: 0; background: var(--doremi-red); color: white; font-size: 10px; padding: 2px 5px; border-radius: 50%; border: 2px solid white; }
        .logout-link { color: var(--doremi-red); font-size: 11px; font-weight: 700; border: none; background: none; cursor: pointer; }

        /* PANEL & TABLES */
        .content { padding: 40px; width: 100%; }
        .stats-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 12px; border-bottom: 4px solid #eee; }
        .stat-card.red { border-bottom-color: var(--doremi-red); }

        .panel { background: white; border-radius: 15px; padding: 35px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); width: 100%; }
        .panel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .filter-select { padding: 8px 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 13px; outline: none; background: #f9fafb; cursor: pointer; }

        .table { width: 100%; border-collapse: collapse; }
        .table th { text-align: left; padding: 12px; color: #6b7280; font-size: 13px; border-bottom: 2px solid #f3f4f6; }
        .table td { padding: 20px 12px; font-size: 14px; border-bottom: 1px solid #f3f4f6; color: #111; }
        .btn-approve { background: #10b981; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 700; cursor: pointer; }

        /* ── LEAVE STATUS BADGES ── */
        .status-badge { display:inline-block; padding:5px 12px; border-radius:7px; font-size:12px; font-weight:700; letter-spacing:.3px; }
        .status-badge.pending  { background:#fff7ed; color:#c2410c; border:1px solid #fed7aa; }
        .status-badge.approved { background:#f0fdf4; color:#15803d; border:1px solid #86efac; }
        .status-badge.rejected { background:#fff5f5; color:#dc2626; border:1px solid #fca5a5; }

        /* ── AI LEAVE PATTERN ADD-ON ── */
        .btn-analyze { background:none; border:1px solid #6366f1; color:#6366f1; padding:3px 9px; border-radius:5px; font-size:10px; font-weight:700; cursor:pointer; margin-left:6px; transition:all .15s; vertical-align:middle; }
        .btn-analyze:hover { background:#6366f1; color:#fff; }
        #leave-ai-card { display:none; background:#fff; border-radius:15px; padding:28px 32px; box-shadow:0 4px 20px rgba(0,0,0,.05); border-left:4px solid #6366f1; margin-top:20px; width:100%; }
        #leave-ai-card.open { display:block; animation:slideDown .25s ease; }
        @keyframes slideDown { from{opacity:0;transform:translateY(-8px)} to{opacity:1;transform:translateY(0)} }
        .leave-ai-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; }
        .leave-ai-header h4 { font-size:15px; font-weight:800; color:#111; margin:0; }
        .leave-ai-spinner { width:32px; height:32px; border:3px solid #ede9fe; border-top-color:#6366f1; border-radius:50%; animation:spin .8s linear infinite; margin:20px auto; }
        @keyframes spin { to { transform:rotate(360deg); } }
        .leave-type-bar { display:flex; gap:10px; margin:14px 0; }
        .ltb { flex:1; background:#f8f9ff; border:1px solid #ede9fe; border-radius:10px; padding:10px; text-align:center; }
        .ltb span { font-size:20px; font-weight:800; color:#6366f1; display:block; }
        .ltb small { font-size:10px; color:#6b7280; font-weight:700; text-transform:uppercase; }
        /* --- NOTIFICATION DROPDOWN --- */
        .notif-dropdown {
            display: none; 
            position: absolute; 
            top: 55px; 
            right: 0; 
            width: 300px; 
            background: white; 
            border-radius: 12px; 
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); 
            border: 1px solid #e5e7eb; 
            z-index: 100;
        }
        .notif-dropdown.show { display: block; animation: fadeIn 0.2s ease-out; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .notif-header {
            padding: 15px;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .notif-list { list-style: none; max-height: 300px; overflow-y: auto; }
        .notif-item {
            padding: 12px 15px;
            border-bottom: 1px solid #f9fafb;
            transition: 0.2s;
            text-align: left;
        }
        .notif-item:hover { background: #f9fafb; }
        .logout-btn {
            background: none;
            border: none;
            padding: 0;
            color: #D6001C;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            text-align: left;
        }
        .logout-btn:hover {
            text-decoration: underline;
        }
        .user-avatar {
            width: 35px;
            height: 35px;
            background: #f3f4f6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #374151;
            border: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="brand"><img src="{{ asset('img/Doremi logo.png') }}" alt="logo"></div>
        <nav class="menu">
            <a href="{{ route('dashboard') }}"><button class="menu-item">🏠 Dashboard</button></a>
            <a href="{{ route('attendance') }}"><button class="menu-item">🕒 Attendance</button></a>

            <div class="menu-group-title">Management</div>
            <a href="{{ route('leave') }}"><button class="menu-item active">🗓️ Leave Requests</button></a>
            <a href="{{ route('flexible') }}"><button class="menu-item">🔁 Flexible Hours</button></a>
            <a href="{{ route('overtime') }}"><button class="menu-item">⏱️ Overtime</button></a>
            <a href="{{ route('tasks.assigned') }}"><button class="menu-item">📍 Assigned Tasks</button></a>
            <div class="menu-group-title">Reports</div>
            <a href="{{ route('analytics') }}"><button class="menu-item">📊 Analytics</button></a>
            <a href="{{ route('directory') }}"><button class="menu-item">👥 Directory</button></a>
            
            
        </nav>
        @include('layouts.pulse')
    </aside>

    <main class="main">
        <header class="topbar">
            <h1 style="font-size: 20px; font-weight: 800;">Leave Application</h1>
            <div class="topbar-right">
                <div style="position: relative;">
                    <button class="icon-btn" id="notifToggle">
                        🔔 <span class="badge" id="notifBadge" style="display: none;">0</span>
                    </button>
                    
                    <div class="notif-dropdown" id="notifPanel">
                        <div class="notif-header">
                            <span style="font-weight: 700; font-size: 14px;">Notifications</span>
                            <button onclick="markAllRead()" style="background:none; border:none; color:var(--doremi-red); font-size:12px; cursor:pointer;">Mark all read</button>
                        </div>
                        <ul class="notif-list" id="notifList">
                            <li style="padding:20px; text-align:center; color:#888; font-size:12px;">Loading...</li>
                        </ul>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 12px; border-left: 1px solid #e5e7eb; padding-left: 20px;">
                    <div class="user-avatar">{{ substr(session('firebase_user.displayName') ?? 'A', 0, 1) }}</div>
                    <div style="display: flex; flex-direction: column; line-height: 1.2;">
                        <span style="font-weight:700; font-size:14px; color:#111;">{{ session('firebase_user.displayName') ?? 'Admin' }}</span>
                        <form action="{{ route('logout') }}" method="POST" style="margin:0;">
                            @csrf 
                            <button type="submit" class="logout-btn">Log Out</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <section class="content">
            <div class="stats-row">
                <div class="stat-card red">
                    <div style="color:#6b7280; font-size:14px; font-weight:600;">Pending Requests</div>
                    <div style="font-size:32px; font-weight:800; margin-top:10px;">{{ $stats['pending'] }}</div>
                </div>
                <div class="stat-card">
                    <div style="color:#6b7280; font-size:14px; font-weight:600;">Approved ({{ $displayDate }})</div>
                    <div style="font-size:32px; font-weight:800; margin-top:10px; color:#10b981;">{{ $stats['approved'] }}</div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
    <h3 style="font-size: 18px; font-weight: 800;">Requests List ({{ $displayDate }})</h3>
    
    <form method="GET" action="{{ route('leave') }}" style="display:flex; gap:10px;">
        <select name="month" class="filter-select" onchange="this.form.submit()">
            @foreach(range(1, 12) as $m)
                <option value="{{ $m }}" {{ $selectedMonth == $m ? 'selected' : '' }}>
                    {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                </option>
            @endforeach
        </select>

        <select name="year" class="filter-select" onchange="this.form.submit()">
            @for($y = 2024; $y <= 2027; $y++)
                <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
        </select>

        <select name="status" class="filter-select" style="border: 2px solid var(--doremi-red);" onchange="this.form.submit()">
            <option value="">All Status</option>
            <option value="Pending" {{ request('status') == 'Pending' ? 'selected' : '' }}>⚠️ Pending</option>
            <option value="Approved" {{ request('status') == 'Approved' ? 'selected' : '' }}>✅ Approved</option>
            <option value="Rejected" {{ request('status') == 'Rejected' ? 'selected' : '' }}>❌ Rejected</option>
        </select>

        @if(request('status') || request('month') != now()->month)
            <a href="{{ route('leave') }}" style="color:var(--doremi-red); font-size:12px; align-self:center; text-decoration:none; font-weight:700;">Reset</a>
        @endif
    </form>
</div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Staff Name</th>
                            <th>Type</th>
                            <th>Start Date</th>
                            <th>Reason</th>
                            <th>Attachment</th>
                            <th>Days</th>
                            <th>Status</th>
                            <th style="text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
    @forelse($leaves as $leave)
    <tr>
        <td style="font-weight:700;">{{ $leave->staff_name }}
            <button class="btn-analyze" onclick="analyzeLeave('{{ $leave->uid }}','{{ addslashes($leave->staff_name) }}')">+ Pattern</button>
        </td>
        <td>{{ $leave->type }}</td>
        <td style="color:#374151;">{{ $leave->start_date ?? '-' }}</td>
        <td style="color:#6b7280; font-style:italic;">{{ $leave->reason }}</td>
        <td>
            @if($leave->attachment_url)
                <a href="{{ $leave->attachment_url }}" target="_blank" style="display:inline-flex; align-items:center; gap:4px; font-size:12px; font-weight:700; color:#4f46e5; background:#e0e7ff; padding:4px 8px; border-radius:6px; text-decoration:none;">
                    📎 View Proof
                </a>
            @else
                <span style="color:#9ca3af; font-size:12px;">No file</span>
            @endif
        </td>
        <td style="font-weight:700; color:var(--doremi-red);">{{ $leave->total_days }} Days</td>
        <td>
            <span class="status-badge {{ strtolower($leave->status) }}">
                {{ strtoupper($leave->status) }}
            </span>
        </td>
        <td style="text-align:right;">
    @if(strtolower($leave->status) == 'pending')
        <div style="display: flex; gap: 8px; justify-content: flex-end;">
            
            <button onclick="handleLeaveAction('{{ $leave->id }}', '{{ $leave->uid }}', {{ $leave->total_days }}, '{{ $leave->type }}', 'Approved')"
                style="background: #10b981; color: white; border: none; padding: 8px 15px; border-radius: 6px; font-weight: 700; cursor: pointer; font-size: 12px;">
                APPROVE
            </button>

            <button onclick="handleLeaveAction('{{ $leave->id }}', '{{ $leave->uid }}', {{ $leave->total_days }}, '{{ $leave->type }}', 'Rejected')"
                style="background: #D6001C; color: white; border: none; padding: 8px 15px; border-radius: 6px; font-weight: 700; cursor: pointer; font-size: 12px;">
                REJECT
            </button>
            
        </div>
    @else
        <span style="color:#9ca3af; font-size:11px; font-weight:700; text-transform: uppercase; letter-spacing: 1px;">
            {{ $leave->status }}
        </span>
    @endif
</td>
    </tr>
    @empty
    <tr>
        <td colspan="6" style="text-align:center; padding:50px; color:#9ca3af;">
            No records found for {{ $displayDate }}.
        </td>
    </tr>
    @endforelse
</tbody>
                </table>
            </div>

            {{-- AI Leave Pattern Card (below existing panel) --}}
            <div id="leave-ai-card">
                <div class="leave-ai-header">
                    <div>
                        <h4>📈 AI Leave Pattern Insight</h4>
                        <p id="leave-ai-for" style="font-size:12px;color:#6b7280;margin:2px 0 0;">Select an employee above to analyze</p>
                    </div>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <span id="leave-ai-src-badge"></span>
                        <button onclick="document.getElementById('leave-ai-card').classList.remove('open')" style="background:#f3f4f6;border:none;padding:5px 10px;border-radius:6px;font-size:12px;cursor:pointer;">Close ✕</button>
                    </div>
                </div>
                <div id="leave-ai-body"><div class="leave-ai-spinner"></div></div>
            </div>
        </section>
    </main>

    <script>
        const LEAVE_CSRF = '{{ csrf_token() }}';
        let leaveRequesting = false;

        function analyzeLeave(userId, name) {
            const card = document.getElementById('leave-ai-card');
            card.classList.add('open');
            card.scrollIntoView({ behavior:'smooth', block:'nearest' });
            document.getElementById('leave-ai-for').textContent = 'Analyzing: ' + name;
            document.getElementById('leave-ai-src-badge').innerHTML = '';
            if (!leaveRequesting) fetchLeavePattern(userId);
        }

        async function fetchLeavePattern(userId) {
            if (leaveRequesting) return;
            leaveRequesting = true;
            document.getElementById('leave-ai-body').innerHTML = '<div class="leave-ai-spinner"></div>';
            try {
                const res  = await fetch(`/api/ai/leave-pattern/${userId}`, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':LEAVE_CSRF,'Accept':'application/json'} });
                const data = await res.json();
                if (!res.ok || !data.success) { renderLeaveError(data.message||'Error'); return; }
                renderLeaveResult(data);
            } catch(e) { renderLeaveError('Network error: '+e.message); }
            finally { leaveRequesting = false; }
        }

        function renderLeaveResult(data) {
            const s   = data.stats || {};
            const tb  = s.type_breakdown || {};
            const src = data.source === 'gemini'
                ? '<span style="background:#ede9fe;color:#6366f1;font-size:10px;font-weight:700;padding:2px 8px;border-radius:12px;">✨ Gemini AI</span>'
                : '<span style="background:#f3f4f6;color:#6b7280;font-size:10px;font-weight:700;padding:2px 8px;border-radius:12px;">⚙️ Rule Engine</span>';
            document.getElementById('leave-ai-src-badge').innerHTML = src;
            document.getElementById('leave-ai-body').innerHTML = `
                <div class="leave-type-bar">
                    <div class="ltb"><span>${tb.AL || 0}</span><small>Annual (AL)</small></div>
                    <div class="ltb"><span>${tb.EL || 0}</span><small>Emergency (EL)</small></div>
                    <div class="ltb"><span>${tb.MC || 0}</span><small>Medical (MC)</small></div>
                    <div class="ltb"><span>${s.total_days || 0}</span><small>Total Days</small></div>
                </div>
                <div style="border-left:3px solid #6366f1;padding:12px 14px;background:#fafafa;border-radius:8px;font-size:13px;line-height:1.7;color:#374151;margin-bottom:12px;">${esc2(data.insight)}</div>
                <div style="background:#ede9fe;border-radius:8px;padding:10px 14px;font-size:13px;color:#4c1d95;"><strong>📌 HR Recommendation:</strong> ${esc2(data.recommendation)}</div>
                <p style="margin-top:10px;font-size:10px;color:#9ca3af;">Based on all leave records · AI-generated · Verify before action.</p>`;
        }
        function renderLeaveError(msg) {
            document.getElementById('leave-ai-body').innerHTML = `<div style="background:#fff5f5;border-radius:8px;padding:14px;color:#dc2626;font-size:13px;"><strong>⚠️</strong> ${esc2(msg)}</div>`;
        }
        function esc2(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
    </script>

    <script>
    window.handleLeaveAction = async function(leaveId, uid, days, type, action) {
        const confirmMsg = action === 'Approved' 
            ? `Approve & deduct ${days} days from staff balance?` 
            : `Reject this application?`;
        
        if (!confirm(confirmMsg)) return;

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

        try {
            const res = await fetch(`/leave/update/${leaveId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: action, uid: uid, total_days: days, type: type })
            });

            if (res.ok) {
                alert(action === 'Approved' ? "Leave request has been approved successfully." : "Leave request has been rejected.");
                location.reload();
            } else {
                let msg = 'Unknown error';
                try {
                    const data = await res.json();
                    msg = data.message || msg;
                } catch(e) {}
                alert("Error: " + msg);
            }
        } catch (error) {
            console.error("Request Error:", error);
            alert("Network Error: " + error.message);
        }
    };
    </script>
<script>
    const notifToggle = document.getElementById('notifToggle');
    const notifPanel = document.getElementById('notifPanel');

    // Buka/Tutup bila klik loceng
    notifToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        notifPanel.classList.toggle('show');
    });

    // Tutup bila klik kat luar
    document.addEventListener('click', (e) => {
        if (!notifPanel.contains(e.target) && e.target !== notifToggle) {
            notifPanel.classList.remove('show');
        }
    });

    // Sedut data dari API yang bos dah buat
    function fetchLiveNotifications() {
        fetch('{{ route("api.notifications") }}')
            .then(res => res.json())
            .then(data => {
                const badge = document.getElementById('notifBadge');
                const list = document.getElementById('notifList');

                // Update Badge
                if(data.count > 0) {
                    badge.style.display = 'block';
                    badge.innerText = data.count;
                } else {
                    badge.style.display = 'none';
                }

                // Update List
                if(data.data.length > 0) {
                    list.innerHTML = '';
                    data.data.forEach(item => {
                        list.innerHTML += `
                            <li class="notif-item">
                                <div style="font-weight:600; font-size:13px; color:#111;">${item.title}</div>
                                <div style="font-size:11px; color:#6b7280;">${item.desc}</div>
                                <div style="font-size:10px; color:var(--doremi-red); margin-top:4px;">${item.time}</div>
                            </li>
                        `;
                    });
                } else {
                    list.innerHTML = '<li style="padding:20px; text-align:center; color:#888; font-size:12px;">No pending requests 🎉</li>';
                }
            });
    }

    function markAllRead() {
        document.getElementById('notifBadge').style.display = 'none';
        document.getElementById('notifList').innerHTML = '<li style="padding:20px; text-align:center; color:#888; font-size:12px;">All requests marked as read.</li>';
    }

    // Jalankan sekali masa load, pastu setiap 10 saat
    fetchLiveNotifications();
    setInterval(fetchLiveNotifications, 10000);
</script>
</body>
</html>