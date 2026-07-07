<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Performance Analytics - DOREMi Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --doremi-red: #D6001C; --sidebar-bg: #D6001C; --bg-light: #F9FAFB; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        html, body { height: 100%; width: 100%; overflow: hidden; }
        body { background-color: var(--bg-light); display: flex; }

        /* SIDEBAR - DESIGN LOCKED */
        .sidebar { width: 260px; min-width: 260px; background-color: var(--sidebar-bg); color: white; display: flex; flex-direction: column; height: 100vh; }
        .brand { padding: 40px 20px; display: flex; justify-content: center; align-items: center; }
        .brand img { width: 170px; height: auto; filter: brightness(0) invert(1); }
        .menu { flex: 1; padding: 10px 0; overflow-y: auto; }
        .menu a { text-decoration: none; color: white; display: block; }
        .menu-item { width: 100%; padding: 12px 25px; display: flex; align-items: center; gap: 12px; background: none; border: none; color: white; font-size: 14px; cursor: pointer; text-align: left; }
        .menu-item.active { background: white; color: #111827 !important; font-weight: 700; border-radius: 50px 0 0 50px; margin-left: 15px; width: calc(100% - 15px); }
        .menu-group-title { padding: 20px 25px 10px; font-size: 11px; font-weight: 700; color: rgba(255, 255, 255, 0.5); text-transform: uppercase; letter-spacing: 1px; }

        /* MAIN & TOPBAR - DESIGN LOCKED */
        .main { flex: 1; display: flex; flex-direction: column; height: 100vh; overflow-y: auto; width: 100%; }
        .topbar { background: white; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #edf2f7; position: sticky; top: 0; z-index: 10; width: 100%; }
        .topbar-right { display: flex; align-items: center; gap: 20px; }
        .icon-btn { background: #f3f4f6; border: none; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; position: relative; cursor: pointer; }
        .badge { position: absolute; top: 0; right: 0; background: var(--doremi-red); color: white; font-size: 10px; padding: 2px 5px; border-radius: 50%; border: 2px solid white; }
        .logout-link { color: var(--doremi-red); font-size: 11px; font-weight: 700; border: none; background: none; cursor: pointer; text-align: left; }

        /* ANALYTICS CONTENT */
        .content { padding: 40px; width: 100%; }
        .top-cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 25px; }
        .stat-card { background: #fff; padding: 25px; border-radius: 12px; border-bottom: 4px solid #eee; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        .stat-card.red { border-bottom-color: var(--doremi-red); }
        .stat-value { font-size: 28px; font-weight: 800; margin-top: 5px; }

        .panel-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 25px; margin-bottom: 25px; }
        .panel { background: #fff; border-radius: 15px; padding: 25px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .panel-title { font-size: 15px; font-weight: 700; margin-bottom: 20px; border-bottom: 1px solid #f3f4f6; padding-bottom: 10px; }

        .dept-list { background: #fff1f2; border: 1px solid #fecdd3; border-radius: 12px; padding: 20px; }
        .dept-item { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px dashed #fecdd3; color: #9f1239; font-weight: 600; font-size: 14px; }
        
        /* EXPORT FORM STICKER */
        .export-box { display: flex; align-items: center; gap: 8px; background: #333; padding: 6px 12px; border-radius: 8px; }
        .export-select { background: transparent; color: white; border: none; font-size: 12px; font-weight: 600; outline: none; cursor: pointer; }
        .export-select option { color: black; }
        .btn-export { background: var(--doremi-red); color: white; border: none; padding: 5px 12px; border-radius: 5px; font-size: 11px; font-weight: 800; cursor: pointer; transition: 0.2s; }
        .btn-export:hover { opacity: 0.8; }
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
            <a href="{{ route('leave') }}"><button class="menu-item">🗓️ Leave Requests</button></a>
            <a href="{{ route('overtime') }}"><button class="menu-item">⏱️ Overtime</button></a>
            <a href="{{ route('flexible') }}"><button class="menu-item">🔁 Flexible Hours</button></a>
            <a href="{{ route('tasks.assigned') }}"><button class="menu-item">📍 Assigned Tasks</button></a>

            <div class="menu-group-title">Reports</div>
            <a href="{{ route('analytics') }}"><button class="menu-item active">📊 Analytics</button></a>
            <a href="{{ route('directory') }}"><button class="menu-item">👥 Directory</button></a>
        </nav>
        @include('layouts.pulse')
    </aside>

    <main class="main">
        <header class="topbar">
            <div style="display:flex; align-items:center; gap:20px; flex-wrap:wrap;">
                <h1 style="font-size: 20px; font-weight: 800;">Performance Analytics</h1>

                {{-- ── MONTH/YEAR FILTER (view mode) ── --}}
                <form action="{{ route('analytics') }}" method="GET" class="export-box" id="filterForm">
                    <span style="color:rgba(255,255,255,0.6); font-size:11px; font-weight:700;">VIEW</span>
                    <select name="month" class="export-select" onchange="this.form.submit()">
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}" {{ $filterMonth == $m ? 'selected' : '' }}>
                                {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                            </option>
                        @endforeach
                    </select>
                    <select name="year" class="export-select" onchange="this.form.submit()">
                        @foreach([2026, 2025, 2024] as $y)
                            <option value="{{ $y }}" {{ $filterYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </form>

                {{-- ── EXPORT PDF (separate form) ── --}}
                <form action="{{ route('analytics.pdf') }}" method="GET" class="export-box">
                    <input type="hidden" name="month" value="{{ $filterMonth }}">
                    <input type="hidden" name="year" value="{{ $filterYear }}">
                    <button type="submit" class="btn-export">📄 EXPORT PDF</button>
                </form>
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
            <div class="top-cards">
                <div class="stat-card" style="border-bottom-color: #10b981;">
                    <div style="font-size:11px; color:#6b7280; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">Attendance Rate</div>
                    <div class="stat-value" style="color:#10b981;">{{ array_sum($attendanceStats) > 0 ? round(($attendanceStats[0]/array_sum($attendanceStats))*100) : 0 }}%</div>
                </div>
                <div class="stat-card" style="border-bottom-color: #f59e0b;">
                    <div style="font-size:11px; color:#6b7280; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">Late Instances</div>
                    <div class="stat-value" style="color:#f59e0b;">{{ $attendanceStats[1] }}</div>
                </div>
                <div class="stat-card" style="border-bottom-color: #4f46e5;">
                    <div style="font-size:11px; color:#6b7280; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">OT Hours (YTD)</div>
                    <div class="stat-value" style="color:#4f46e5;">{{ array_sum($otTrendData) }}H</div>
                </div>
                
                <div class="stat-card red">
                    <div style="font-size:11px; color:#6b7280; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">TOTAL LEAVES ({{ strtoupper($filterLabel) }})</div>
                    <div class="stat-value" style="color:var(--doremi-red);">
                        {{ $leavesThisMonthDays }} <span style="font-size:14px; color:#888; font-weight:500;">Days</span>
                    </div>
                    <div style="font-size:10px; color:#9ca3af; margin-top:5px; font-weight:600;">*Approved only</div>
                </div>
            </div>

            <div class="panel-grid">
                <div class="panel">
                    <h3 class="panel-title">Attendance Overview — {{ $filterLabel }}</h3>
                    <div style="height: 250px;"><canvas id="attChart"></canvas></div>
                </div>
                <div class="dept-list">
                    <h3 class="panel-title" style="color:#9f1239; border-color:#fda4af;">Operational Depts</h3>
                    @forelse($activeByDept as $dept => $count)
                        <div class="dept-item">
                            <span>{{ $deptIcons[$dept] ?? '🏢' }} {{ $dept }}</span> 
                            <span style="font-size:10px; background:#f43f5e; color:white; padding:2px 8px; border-radius:10px;">{{ $count }} ACTIVE</span>
                        </div>
                    @empty
                        <div class="dept-item">
                            <span>No active departments</span>
                        </div>
                    @endforelse
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:25px;">
                <div class="panel">
                    <h3 class="panel-title">Leave Type Distribution — {{ $filterLabel }}</h3>
                    <div style="height: 250px;"><canvas id="leaveChart"></canvas></div>
                </div>
                <div class="panel">
                    <h3 class="panel-title">OT Hours Trend — {{ $currentYear }}</h3>
                    <div style="height: 250px;"><canvas id="otChart"></canvas></div>
                </div>
            </div>
        </section>
    </main>

    <script>
        // CHARTS LOGIC
        Chart.defaults.font.family = "'Inter', sans-serif";

        new Chart(document.getElementById('attChart'), {
            type: 'doughnut',
            data: { 
                labels: ['On Time', 'Late', 'Absent'], 
                datasets: [{ 
                    data: @json($attendanceStats), 
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'], 
                    borderWidth: 0,
                    cutout: '70%'
                }] 
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                plugins: { legend: { position: 'right', labels: { usePointStyle: true, padding: 20 } } } 
            }
        });

        new Chart(document.getElementById('leaveChart'), {
            type: 'bar',
            data: { 
                labels: ['Annual', 'Medical', 'Emergency'], 
                datasets: [{ 
                    label: 'Days', 
                    data: @json($leaveStats), 
                    backgroundColor: ['#3b82f6', '#f59e0b', '#ef4444'], 
                    borderRadius: 6,
                    barThickness: 40
                }] 
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true }, x: { grid: { display: false } } }
            }
        });

        new Chart(document.getElementById('otChart'), {
            type: 'line',
            data: { 
                labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'], 
                datasets: [{ 
                    label: 'Hours', 
                    data: @json($otTrendData), 
                    borderColor: '#D6001C', 
                    borderWidth: 3,
                    tension: 0.4, 
                    fill: true, 
                    backgroundColor: 'rgba(214, 0, 28, 0.05)',
                    pointRadius: 0
                }] 
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { grid: { display: false } }, y: { beginAtZero: true } }
            }
        });

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
        // setInterval(fetchLiveNotifications, 10000); // Stopped to save quota
    </script>
</body>
</html>