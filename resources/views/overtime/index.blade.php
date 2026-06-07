<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Overtime Summary - DOREMi Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root { --doremi-red: #D6001C; --sidebar-bg: #D6001C; --bg-light: #F9FAFB; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        html, body { height: 100%; width: 100%; overflow: hidden; }
        body { background-color: var(--bg-light); display: flex; }

        /* SIDEBAR CONSISTENCY */
        .sidebar { width: 260px; min-width: 260px; background-color: var(--sidebar-bg); color: white; display: flex; flex-direction: column; height: 100vh; }
        .brand { padding: 40px 20px; display: flex; justify-content: center; align-items: center; }
        .brand img { width: 170px; height: auto; filter: brightness(0) invert(1); }
        .menu { flex: 1; padding: 10px 0; overflow-y: auto; }
        .menu a { text-decoration: none; color: white; display: block; }
        .menu-item { width: 100%; padding: 12px 25px; display: flex; align-items: center; gap: 12px; background: none; border: none; color: white; font-size: 14px; cursor: pointer; text-align: left; }
        .menu-item.active { background: white; color: #111827 !important; font-weight: 700; border-radius: 50px 0 0 50px; margin-left: 15px; width: calc(100% - 15px); }
        .menu-group-title { padding: 20px 25px 10px; font-size: 11px; font-weight: 700; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 1px; }

        /* MAIN & TOPBAR */
        .main { flex: 1; display: flex; flex-direction: column; height: 100vh; overflow-y: auto; width: 100%; }
        .topbar { background: white; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #edf2f7; position: sticky; top: 0; z-index: 10; width: 100%; }
        
        .search-container { position: relative; width: 400px; }
        .search-container input { width: 100%; padding: 10px 15px 10px 40px; background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 8px; outline: none; }
        .search-icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #9ca3af; }

        .topbar-right { display: flex; align-items: center; gap: 20px; }
        .icon-btn { background: #f3f4f6; border: none; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; position: relative; }
        .badge { position: absolute; top: 0; right: 0; background: var(--doremi-red); color: white; font-size: 10px; padding: 2px 5px; border-radius: 50%; border: 2px solid white; }

        .profile-box { display: flex; align-items: center; gap: 12px; border-left: 1px solid #e5e7eb; padding-left: 20px; }
        .admin-avatar { width: 40px; height: 40px; background: #f3f4f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; border: 1px solid #e5e7eb; }
        .logout-link { color: var(--doremi-red); font-size: 11px; font-weight: 700; cursor: pointer; border: none; background: none; }

        /* CONTENT */
        .content { padding: 40px; width: 100%; }
        .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border-bottom: 4px solid #eee; }
        .stat-card.red { border-bottom-color: var(--doremi-red); }

        /* PANEL & FILTER */
        .panel { background: white; border-radius: 15px; padding: 35px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .panel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        
        .filter-controls { display: flex; align-items: center; gap: 10px; }
        .filter-select { padding: 8px 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 13px; outline: none; background: #f9fafb; cursor: pointer; }
        .btn-reset { color: var(--doremi-red); font-size: 12px; font-weight: 700; text-decoration: none; margin-left: 5px; }

        .table { width: 100%; border-collapse: collapse; }
        .table th { text-align: left; padding: 12px; color: #6b7280; font-size: 13px; border-bottom: 2px solid #f3f4f6; }
        .table td { padding: 20px 12px; font-size: 14px; border-bottom: 1px solid #f3f4f6; color: #111; }
        
        .badge-warning { background: #fff7ed; color: #c2410c; padding: 6px 12px; border-radius: 8px; font-weight: 700; font-size: 12px; }
        .badge-success { background: #f0fdf4; color: #15803d; padding: 6px 12px; border-radius: 8px; font-weight: 700; font-size: 12px; }
        .btn-view { background: #f3f4f6; border: 1px solid #ddd; padding: 8px 15px; border-radius: 6px; font-weight: 700; font-size: 12px; cursor: pointer; }

        /* ── AI OT VERIFY ADD-ON ── */
        .btn-ai-verify { background: linear-gradient(135deg,#6366f1,#8b5cf6); color:#fff; border:none; padding:7px 13px; border-radius:6px; font-size:11px; font-weight:700; cursor:pointer; margin-right:6px; transition:all .2s; }
        .btn-ai-verify:hover { transform:translateY(-1px); box-shadow:0 4px 12px rgba(99,102,241,.4); }
        #ot-modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.4); z-index:998; backdrop-filter:blur(3px); }
        #ot-modal { display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); width:480px; max-width:95vw; background:#fff; border-radius:20px; z-index:999; box-shadow:0 20px 60px rgba(0,0,0,.2); overflow:hidden; }
        #ot-modal-header { background:linear-gradient(135deg,#6366f1,#8b5cf6); padding:22px 26px; display:flex; justify-content:space-between; align-items:center; }
        #ot-modal-header h3 { color:#fff; font-size:16px; font-weight:800; margin:0; }
        #ot-modal-header p  { color:rgba(255,255,255,.75); font-size:12px; margin:3px 0 0; }
        #ot-modal-close { background:rgba(255,255,255,.2); border:none; color:#fff; width:30px; height:30px; border-radius:50%; cursor:pointer; font-size:16px; display:flex; align-items:center; justify-content:center; }
        #ot-modal-body { padding:26px; }
        .ot-verdict-badge { display:inline-block; padding:8px 20px; border-radius:30px; font-size:13px; font-weight:800; letter-spacing:.5px; margin-bottom:16px; }
        .verdict-green  { background:#f0fdf4; color:#15803d; border:2px solid #86efac; }
        .verdict-red    { background:#fff5f5; color:#dc2626; border:2px solid #fca5a5; }
        .verdict-amber  { background:#fffbeb; color:#b45309; border:2px solid #fde68a; }
        .verdict-gray   { background:#f3f4f6; color:#6b7280; border:2px solid #d1d5db; }
        .verdict-hazard { background:#fee2e2; color:#b91c1c; border:1px solid #ef4444; }
        .ot-stats-mini  { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin:14px 0; }
        .ot-stat-mini   { background:#f8f9ff; border:1px solid #ede9fe; border-radius:10px; padding:12px; text-align:center; }
        .ot-stat-mini span { display:block; font-size:20px; font-weight:800; color:#6366f1; }
        .ot-stat-mini small { font-size:10px; color:#6b7280; font-weight:700; text-transform:uppercase; }
        .ot-explanation { font-size:13px; line-height:1.7; color:#374151; background:#fafafa; border-left:3px solid #6366f1; padding:12px 14px; border-radius:8px; }
        .ot-loading { text-align:center; padding:40px 20px; }
        .ot-spinner { width:40px; height:40px; border:3px solid #ede9fe; border-top-color:#6366f1; border-radius:50%; animation:spin .8s linear infinite; margin:0 auto 12px; }
        @keyframes spin { to { transform:rotate(360deg); } }
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
            <a href="{{ route('leave') }}"><button class="menu-item">🗓️ Leave Application</button></a>
            <a href="{{ route('flexible') }}"><button class="menu-item">🔁 Flexible Hours</button></a>
            <a href="{{ route('overtime') }}"><button class="menu-item active">⏱️ Overtime</button></a>
            <a href="{{ route('tasks.assigned') }}"><button class="menu-item">📍 Assigned Tasks</button></a>

            <div class="menu-group-title">Reports</div>
            <a href="{{ route('analytics') }}"><button class="menu-item">📊 Analytics</button></a>
            <a href="{{ route('directory') }}"><button class="menu-item">👥 Directory</button></a>
        </nav>
        @include('layouts.pulse')
    </aside>

    <main class="main">
        <header class="topbar">
            <div class="search-container">
                <span class="search-icon">🔍</span>
                <input type="text" placeholder="Search staff overtime...">
            </div>
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

                <div class="profile-box">
                    <div class="user-avatar">{{ substr(session('firebase_user.displayName') ?? 'A', 0, 1) }}</div>
                    <div style="display:flex; flex-direction:column; line-height:1.2;">
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
                    <div style="color:#6b7280; font-size:13px; font-weight:600;">Total OT (Today)</div>
                    <div style="font-size:28px; font-weight:800; margin-top:8px;">{{ $total_hours_today }}H</div>
                </div>
                <div class="stat-card">
                    <div style="color:#6b7280; font-size:13px; font-weight:600;">Total OT (This Week)</div>
                    <div style="font-size:28px; font-weight:800; margin-top:8px;">{{ $total_hours_week }}H</div>
                </div>
                <div class="stat-card" style="border-bottom-color: #f59e0b;">
                    <div style="color:#6b7280; font-size:13px; font-weight:600;">Pending Review</div>
                    <div style="font-size:28px; font-weight:800; margin-top:8px; color:#c2410c;">{{ $pending_total }} Req</div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <h3 style="font-size: 18px; font-weight: 800;">Employee Summary ({{ $displayDate }})</h3>
                    
                    <form method="GET" action="{{ route('overtime') }}" class="filter-controls">
                        <select name="month" class="filter-select" onchange="this.form.submit()">
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}" {{ ($selectedMonth ?? now()->month) == $m ? 'selected' : '' }}>
                                    {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                </option>
                            @endforeach
                        </select>
                        <select name="year" class="filter-select" onchange="this.form.submit()">
                            @for($y = 2024; $y <= 2027; $y++)
                                <option value="{{ $y }}" {{ ($selectedYear ?? now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                        <select name="filter" class="filter-select" onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <option value="pending" {{ request('filter') == 'pending' ? 'selected' : '' }}>⚠️ Pending Only</option>
                        </select>
                        @if(request('filter') || request('month'))
                            <a href="{{ route('overtime') }}" class="btn-reset">↺ Reset</a>
                        @endif
                    </form>
                </div>

                <table class="table">
                    <thead>
                        <tr>
                            <th>Staff Name</th>
                            <th>Department</th>
                            <th>Total Claim</th>
                            <th>Status Overview</th>
                            <th style="text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($summaries as $summary)
                        <tr>
                            <td style="font-weight:700;">{{ $summary->user->name }}</td>
                            <td>{{ $summary->user->department }}</td>
                            <td style="font-weight:700; color:var(--doremi-red);">{{ $summary->total_ot_hours }} Hours</td>
                            <td>
                                <span class="status-badge {{ $summary->pending_count > 0 ? 'badge-warning' : 'badge-success' }}">
                                    {{ $summary->pending_count > 0 ? '⚠️ '.$summary->pending_count.' Pending' : '✅ Processed' }}
                                </span>
                            </td>
                            <td style="text-align:right;">
                                <button class="btn-ai-verify" onclick="openOTModal('{{ $summary->user_id }}','{{ addslashes($summary->user->name) }}')">🤖 AI Verify</button>
                                <a href="{{ route('overtime.review', $summary->user_id) }}"><button class="btn-view">VIEW DETAILS</button></a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- ═══ AI OT VERIFY MODAL (ADD-ON) ═══ -->
    <div id="ot-modal-overlay" onclick="closeOTModal()"></div>
    <div id="ot-modal">
        <div id="ot-modal-header">
            <div><h3>🤖 AI OT Verification</h3><p id="ot-modal-subtitle">90-day pattern analysis</p></div>
            <button id="ot-modal-close" onclick="closeOTModal()">✕</button>
        </div>
        <div id="ot-modal-body"><div class="ot-loading"><div class="ot-spinner"></div><p style="color:#6b7280;font-size:13px;">Analyzing OT pattern…</p></div></div>
    </div>

    <script>
        const OT_CSRF = '{{ csrf_token() }}';
        let otRequesting = false;

        function openOTModal(userId, name) {
            document.getElementById('ot-modal-subtitle').textContent = 'Analyzing: ' + name;
            document.getElementById('ot-modal-overlay').style.display = 'block';
            document.getElementById('ot-modal').style.display = 'block';
            showOTLoading();
            if (!otRequesting) fetchOTVerdict(userId, name);
        }
        function closeOTModal() {
            document.getElementById('ot-modal-overlay').style.display = 'none';
            document.getElementById('ot-modal').style.display = 'none';
            otRequesting = false;
        }
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeOTModal(); });

        function showOTLoading() {
            document.getElementById('ot-modal-body').innerHTML = '<div class="ot-loading"><div class="ot-spinner"></div><p style="color:#6b7280;font-size:13px;">Analyzing 90-day OT pattern…</p></div>';
        }

        async function fetchOTVerdict(userId, name) {
            if (otRequesting) return;
            otRequesting = true;
            try {
                const res  = await fetch(`/api/ai/ot-verify/${userId}`, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':OT_CSRF,'Accept':'application/json'} });
                const data = await res.json();
                if (!res.ok || !data.success) { renderOTError(data.message || 'Error'); return; }
                renderOTResult(data, name);
            } catch(e) { renderOTError('Network error: ' + e.message); }
            finally { otRequesting = false; }
        }

        function renderOTResult(data, name) {
            const colourMap = { green:'verdict-green', red:'verdict-red', amber:'verdict-amber', gray:'verdict-gray' };
            let cls = colourMap[data.colour] || 'verdict-gray';
            if (data.verdict === 'HEALTH HAZARD') {
                cls = 'verdict-hazard';
            }
            const icon = { PRODUCTIVE:'✅', SUSPICIOUS:'🚨', 'NEEDS REVIEW':'⚠️', 'NO DATA':'ℹ️', 'HEALTH HAZARD':'🏥' }[data.verdict] || '🔍';
            const tooltipAttr = data.verdict === 'HEALTH HAZARD' ? 'title="Rest gap < 8h or excessive OT detected — admin action required" style="cursor:help;"' : '';
            const s    = data.stats || {};
            const src  = data.source === 'gemini' ? '<span style="background:#ede9fe;color:#6366f1;font-size:10px;font-weight:700;padding:2px 8px;border-radius:12px;">✨ Gemini AI</span>' : '<span style="background:#f3f4f6;color:#6b7280;font-size:10px;font-weight:700;padding:2px 8px;border-radius:12px;">⚙️ Rule Engine</span>';
            document.getElementById('ot-modal-body').innerHTML = `
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
                    <span class="ot-verdict-badge ${cls}" ${tooltipAttr}>${icon} ${data.verdict}</span>${src}
                </div>
                <div class="ot-stats-mini">
                    <div class="ot-stat-mini"><span>${s.total_hours ?? 0}H</span><small>Total OT</small></div>
                    <div class="ot-stat-mini"><span>${s.submissions ?? 0}</span><small>Submissions</small></div>
                    <div class="ot-stat-mini"><span>${s.avg_per_week ?? 0}H</span><small>Avg/Week</small></div>
                </div>
                <div class="ot-explanation">${esc(data.explanation)}</div>
                <p style="margin-top:12px;font-size:10px;color:#9ca3af;text-align:center;">AI analysis · Verify against official records before action.</p>`;
        }
        function renderOTError(msg) {
            document.getElementById('ot-modal-body').innerHTML = `<div style="background:#fff5f5;border:1px solid #fca5a5;border-radius:10px;padding:16px;color:#dc2626;font-size:13px;"><strong>⚠️ Error</strong><br><br>${esc(msg)}</div>`;
        }
        function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>'); }

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