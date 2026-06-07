<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Dashboard - DOREMi Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --doremi-red: #D6001C;
            --sidebar-bg: #D6001C;
            --bg-light: #F9FAFB;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }

        body { background-color: var(--bg-light); display: flex; height: 100vh; overflow: hidden; }

        /* SIDEBAR - Ikut gambar kedua bos */
        .sidebar {
            width: 260px;
            background-color: var(--sidebar-bg);
            color: white;
            display: flex;
            flex-direction: column;
            transition: all 0.3s;
        }

        .brand { padding: 30px 20px; text-align: center; }
        .brand img { width: 150px; filter: brightness(0) invert(1); }

        .menu { flex: 1; padding: 10px 0; }
        .menu a { text-decoration: none; color: white; display: block; }
        
        .menu-item {
            width: 100%;
            padding: 12px 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            background: none;
            border: none;
            color: white;
            font-size: 14px;
            cursor: pointer;
            transition: 0.2s;
        }

        .menu-item:hover { background: rgba(255, 255, 255, 0.1); color: white; }

        .menu-item.active {
            background: white;
            color: var(--doremi-red);
            font-weight: 700;
            border-radius: 50px 0 0 50px;
            margin-left: 15px;
            width: calc(100% - 15px);
        }

        .menu-group-title {
            padding: 20px 25px 10px;
            font-size: 11px;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.5);
            text-transform: uppercase;
        }

        /* MAIN CONTENT */
        .main { flex: 1; display: flex; flex-direction: column; overflow-y: auto; }

        /* TOPBAR - Ikut gambar pertama bos (Search & Notif) */
        .topbar {
            background: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #edf2f7;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .search-container {
            position: relative;
            width: 400px;
        }

        .search-container input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }

        .topbar-right { display: flex; align-items: center; gap: 20px; }

        .icon-btn {
            background: #f3f4f6;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            position: relative;
        }

        .badge {
            position: absolute;
            top: 0;
            right: 0;
            background: var(--doremi-red);
            color: white;
            font-size: 10px;
            padding: 2px 5px;
            border-radius: 50%;
            border: 2px solid white;
        }

        /* CONTENT */
        .content { padding: 30px; }
        .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            border-bottom: 4px solid #e5e7eb;
        }
        .stat-card.red { border-bottom-color: var(--doremi-red); }
        .stat-label { color: #6b7280; font-size: 14px; font-weight: 500; }
        .stat-value { font-size: 28px; font-weight: 700; margin-top: 10px; color: #111827; }

        .dashboard-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 25px; }
        
        .panel { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .panel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        
        .snapshot-table { width: 100%; border-collapse: collapse; }
        .snapshot-table th { text-align: left; padding: 12px; color: #6b7280; font-size: 13px; border-bottom: 1px solid #f3f4f6; }
        .snapshot-table td { padding: 15px 12px; font-size: 14px; border-bottom: 1px solid #f3f4f6; }

        .status-pill { padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; }
        .pill-green { background: #dcfce7; color: #166534; }
        .pill-orange { background: #fef3c7; color: #92400e; }

        .btn-action { background: var(--doremi-red); color: white; border: none; padding: 12px; border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%; margin-top: 10px; transition: 0.2s; }
        .btn-action:hover { opacity: 0.9; }
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
        <div class="brand">
            <img src="{{ asset('img/Doremi logo.png') }}" alt="logo">
        </div>
        <nav class="menu">
            <a href="{{ route('dashboard') }}"><button class="menu-item active">🏠 Dashboard</button></a>
            <a href="{{ route('attendance') }}"><button class="menu-item">🕒 Attendance</button></a>
            
            <div class="menu-group-title">Management</div>
            <a href="{{ route('leave') }}"><button class="menu-item">🗓️ Leave Requests</button></a>
            <a href="{{ route('overtime') }}"><button class="menu-item">⏱️ Overtime (OT)</button></a>
            <a href="{{ route('flexible') }}"><button class="menu-item">🔁 Flexible Hours</button></a>
            <a href="{{ route('tasks.assigned') }}"><button class="menu-item">📍 Assigned Tasks</button></a>

            
            <div class="menu-group-title">Reports</div>
            <a href="{{ route('analytics') }}"><button class="menu-item">📊 Analytics</button></a>
            <a href="{{ route('directory') }}"><button class="menu-item">👥 Directory</button></a>
        </nav>

        {{-- ── LIVE COMPANY PULSE + SYSTEM HEALTH (shared partial) ── --}}
        @include('layouts.pulse')

    </aside>

    <main class="main">

           <header class="topbar">
    <div class="search-container">
        <span class="search-icon">🔍</span>
        <input type="text" placeholder="Search for employees, attendance...">
    </div>

    {{-- LIVE CLOCK --}}
    <div style="display:flex; flex-direction:column; align-items:center; line-height:1.3;">
        <span id="live-clock-time" style="font-size:18px; font-weight:800; color:#111; letter-spacing:-0.5px;"></span>
        <span id="live-clock-date" style="font-size:11px; color:#6b7280; font-weight:600;"></span>
    </div>

    <div class="topbar-right">
        <div style="position: relative;">
            <button class="icon-btn" id="notifToggle">
                🔔 <span class="badge" id="notifBadge">{{ $total_pending }}</span>
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
            <div class="user-avatar">
                {{ substr(session('firebase_user.displayName') ?? 'A', 0, 1) }}
            </div>
            <div style="display: flex; flex-direction: column; line-height: 1.2;">
                <span style="font-weight: 700; font-size: 14px; color: #111;">{{ session('firebase_user.displayName') ?? 'Admin' }}</span>
                <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                    @csrf
                    <button type="submit" class="logout-btn">Log Out</button>
                </form>
            </div>
        </div>
    </div>
</header>
        </header>

        <section class="content">
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-label">Clock-in Today</div>
                    <div class="stat-value">{{ $clock_in_count }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Late Attendance</div>
                    <div class="stat-value" style="color: #f59e0b;">{{ $late_count }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Clock-out Today</div>
                    <div class="stat-value">{{ $clock_out_count }}</div>
                </div>
                <div class="stat-card red">
                    <div class="stat-label">Pending Requests</div>
                    <div class="stat-value" style="color: var(--doremi-red);">{{ $total_pending }}</div>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="panel">
                    <div class="panel-header">
                        <h3 style="font-size: 16px;">Live Attendance Snapshot</h3>
                        <a href="{{ route('attendance') }}" style="font-size: 12px; color: var(--doremi-red); text-decoration: none; font-weight: 600;">View All →</a>
                    </div>
                    <table class="snapshot-table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Check In</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recent_attendance as $row)
                            <tr>
                                <td style="font-weight: 600;">{{ $row->user->name }}</td>
                                <td>{{ $row->clock_in_time ? \Carbon\Carbon::parse($row->clock_in_time)->format('h:i A') : '--' }}</td>
                                <td>
                                    <span class="status-pill {{ $row->status == 'Late' ? 'pill-orange' : 'pill-green' }}">
                                        {{ $row->status }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="3" style="text-align: center; color: #9ca3af; padding: 30px;">No attendance yet today.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="side-widgets">
                    <div class="panel" style="margin-bottom: 20px;">
                        <h4 style="font-size: 14px; margin-bottom: 15px;">Quick Actions</h4>
                        <button class="btn-action" onclick="window.location.href='{{ route('leave') }}'">Approve Leave</button>
                        <button class="btn-action" onclick="window.location.href='{{ route('overtime') }}'">Verify Overtime</button>
                    </div>
                </div>
            </div>
        </section>
        <!-- NEW WIDGETS SECTION -->
        <style>
            .widget-grid {
                display: grid;
                grid-template-columns: 60% calc(40% - 12px);
                gap: 12px;
                margin-top: 20px;
            }
            .widget-col-left {
                display: flex;
                flex-direction: column;
                gap: 12px;
            }
            .widget-card {
                background: white;
                border: 0.5px solid #e5e7eb;
                border-radius: 12px;
                padding: 20px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            }
            .widget-card.ai-alerts {
                background: #fefce8;
                border: 1px solid #f59e0b;
                height: 100%;
            }
            .chart-container {
                display: flex;
                align-items: center;
                gap: 20px;
                height: 180px;
            }
            .chart-legend {
                display: flex;
                flex-direction: column;
                gap: 10px;
                font-size: 13px;
                color: #4b5563;
            }
            .legend-item {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .legend-color {
                width: 12px;
                height: 12px;
                border-radius: 3px;
            }
            .leaderboard-item {
                margin-top: 15px;
            }
            .leaderboard-header {
                display: flex;
                justify-content: space-between;
                font-size: 13px;
                margin-bottom: 5px;
                font-weight: 600;
                color: #374151;
            }
            .progress-bg {
                background: #f3f4f6;
                height: 8px;
                border-radius: 4px;
                overflow: hidden;
            }
            .progress-fill {
                height: 100%;
                border-radius: 4px;
            }
            .ai-alert-item {
                display: flex;
                gap: 10px;
                margin-top: 15px;
                padding: 12px;
                border-radius: 8px;
                background: white;
                border-left: 4px solid #f59e0b;
            }
            .ai-alert-item.danger { border-left-color: #ef4444; }
            .ai-alert-item.info { border-left-color: #3b82f6; }
            
            .ai-alert-content h5 { margin: 0 0 4px 0; font-size: 13px; color: #111827; }
            .ai-alert-content p { margin: 0; font-size: 12px; color: #6b7280; }
        </style>

        <section class="widget-grid">
            <div class="widget-col-left">
                <!-- Widget 1: Flexi-Credit -->
                <div class="widget-card">
                    <h4 style="margin:0 0 15px 0; font-size:15px; color:#111;">Flexi-Credit Utilization</h4>
                    <div class="chart-container">
                        <div style="position:relative; width:150px; height:150px;">
                            <canvas id="flexiChart"></canvas>
                            <div id="flexiTotal" style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); font-weight:700; font-size:16px;">0H</div>
                        </div>
                        <div class="chart-legend" id="flexiLegend">
                            <!-- Populated by JS -->
                        </div>
                    </div>
                </div>

                <!-- Widget 2: Leaderboard -->
                <div class="widget-card">
                    <h4 style="margin:0; font-size:15px; color:#111;">Department Attendance Leaderboard</h4>
                    <div id="leaderboardContainer">
                        <div style="font-size:13px; color:#888; margin-top:10px;">Loading data...</div>
                    </div>
                </div>
            </div>

            <!-- Widget 3: AI Alerts -->
            <div class="widget-card ai-alerts">
                <h4 style="margin:0 0 15px 0; font-size:15px; color:#92400e; display:flex; align-items:center; gap:8px;">
                    ✨ HR Smart Alerts (Gemini)
                </h4>
                <div id="aiAlertsContainer">
                    <div style="color:#b45309; font-size:13px; font-style:italic;">Generating...</div>
                </div>
            </div>
        </section>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const projectId = 'doremi-admin';

                // --- 1. Fetch Flexi-Credit Data ---
                async function loadFlexiData() {
                    try {
                        const usersUrl = `https://firestore.googleapis.com/v1/projects/${projectId}/databases/(default)/documents/users`;
                        const usersRes = await fetch(usersUrl);
                        const usersData = await usersRes.json();
                        
                        let totalOt = 0;
                        let totalFlexi = 0;
                        
                        if (usersData.documents) {
                            usersData.documents.forEach(doc => {
                                const f = doc.fields || {};
                                totalOt += (f.ot_balance?.doubleValue || f.ot_balance?.integerValue * 1.0 || 0);
                                totalFlexi += (f.ot_balance?.doubleValue || f.ot_balance?.integerValue * 1.0 || 0);
                            });
                        }

                        // Assuming Used = TotalOT - FlexiBalance for display purposes
                        const usedThisMonth = totalOt > totalFlexi ? (totalOt - totalFlexi) : 0;
                        const unutilizedOt = totalOt > 0 ? (totalOt * 0.1) : 0; // dummy heuristic if needed, or exact 
                        const remaining = totalFlexi;

                        // Render Chart
                        const ctx = document.getElementById('flexiChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: ['Used This Month', 'Remaining Balance', 'Unutilized OT'],
                                datasets: [{
                                    data: [usedThisMonth, remaining, unutilizedOt],
                                    backgroundColor: ['#8B5CF6', '#3B82F6', '#e5e7eb'],
                                    borderWidth: 1,
                                    borderColor: '#e5e7eb'
                                }]
                            },
                            options: {
                                cutout: '70%',
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false }, tooltip: { enabled: true } }
                            }
                        });

                        document.getElementById('flexiTotal').innerText = `${totalOt.toFixed(1)}H`;
                        document.getElementById('flexiLegend').innerHTML = `
                            <div class="legend-item"><div class="legend-color" style="background:#8B5CF6;"></div>Used This Month: <strong>${usedThisMonth.toFixed(1)}H</strong></div>
                            <div class="legend-item"><div class="legend-color" style="background:#3B82F6;"></div>Remaining Balance: <strong>${remaining.toFixed(1)}H</strong></div>
                            <div class="legend-item"><div class="legend-color" style="background:#e5e7eb; border:1px solid #ccc;"></div>Unutilized OT: <strong>${unutilizedOt.toFixed(1)}H</strong></div>
                        `;
                    } catch(e) { console.error('Flexi Error:', e); }
                }

                // --- 2. Fetch Leaderboard Data ---
                async function loadLeaderboard() {
                    try {
                        const attUrl = `https://firestore.googleapis.com/v1/projects/${projectId}/databases/(default)/documents/attendances`;
                        const attRes = await fetch(attUrl);
                        const attData = await attRes.json();
                        
                        let deps = {};
                        if (attData.documents) {
                            attData.documents.forEach(doc => {
                                const f = doc.fields || {};
                                const dName = f.department?.stringValue || 'Unknown';
                                const stat = f.status?.stringValue || '';
                                if (!deps[dName]) deps[dName] = { total: 0, onTime: 0 };
                                deps[dName].total++;
                                if (stat === 'On Time' || stat === 'Present') deps[dName].onTime++;
                            });
                        }

                        let sorted = Object.keys(deps).map(k => ({
                            name: k,
                            pct: deps[k].total > 0 ? (deps[k].onTime / deps[k].total) * 100 : 0
                        })).sort((a, b) => b.pct - a.pct).slice(0, 3);

                        const colors = ['#10b981', '#3b82f6', '#8b5cf6'];
                        let html = '';
                        sorted.forEach((d, i) => {
                            html += `
                                <div class="leaderboard-item">
                                    <div class="leaderboard-header">
                                        <span>#${i+1} ${d.name}</span>
                                        <span>${d.pct.toFixed(1)}%</span>
                                    </div>
                                    <div class="progress-bg">
                                        <div class="progress-fill" style="width:${d.pct}%; background:${colors[i]};"></div>
                                    </div>
                                </div>
                            `;
                        });
                        document.getElementById('leaderboardContainer').innerHTML = html || '<div style="font-size:13px; color:#888;">No data</div>';
                    } catch(e) { console.error('Leaderboard Error:', e); }
                }

                // --- 3. Fetch AI Smart Alerts ---
                async function loadAiAlerts() {
                    const container = document.getElementById('aiAlertsContainer');
                    try {
                        const res = await fetch('/dashboard/ai-alerts');
                        const data = await res.json();
                        
                        if (Array.isArray(data) && data.length > 0) {
                            let html = '';
                            data.forEach(item => {
                                const t = item.type || 'info';
                                html += `
                                    <div class="ai-alert-item ${t}">
                                        <div class="ai-alert-content">
                                            <h5>${item.message || 'Notice'}</h5>
                                            <p>${item.meta || ''}</p>
                                        </div>
                                    </div>
                                `;
                            });
                            container.innerHTML = html;
                        } else {
                            container.innerHTML = '<div style="font-size:13px; color:#6b7280;">No new alerts at this time.</div>';
                        }
                    } catch(e) {
                        console.error('AI Alerts Error:', e);
                        container.innerHTML = '<div style="font-size:13px; color:#ef4444;">Failed to load alerts.</div>';
                    }
                }

                loadFlexiData();
                loadLeaderboard();
                loadAiAlerts();

                // Refresh AI alerts every 30 mins (1800000 ms)
                setInterval(loadAiAlerts, 1800000);
            });
        </script>
    </main>
<script>
    // ── LIVE CLOCK ──
    function updateClock() {
        const now  = new Date();
        const days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        const hh   = String(now.getHours()).padStart(2,'0');
        const mm   = String(now.getMinutes()).padStart(2,'0');
        const ss   = String(now.getSeconds()).padStart(2,'0');
        document.getElementById('live-clock-time').textContent = `${hh}:${mm}:${ss}`;
        document.getElementById('live-clock-date').textContent =
            `${days[now.getDay()]}, ${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
    }
    updateClock();
    setInterval(updateClock, 1000);

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