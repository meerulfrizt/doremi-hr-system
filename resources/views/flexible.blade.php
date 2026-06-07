<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Flexible Hours - DOREMi Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root { --doremi-red: #D6001C; --sidebar-bg: #D6001C; --bg-light: #F9FAFB; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        html, body { height: 100%; width: 100%; overflow: hidden; }
        body { background-color: var(--bg-light); display: flex; }

        /* SIDEBAR CONSISTENCY - DESIGN LOCKED */
        .sidebar { width: 260px; min-width: 260px; background-color: var(--sidebar-bg); color: white; display: flex; flex-direction: column; height: 100vh; }
        .brand { padding: 40px 20px; display: flex; justify-content: center; align-items: center; }
        .brand img { width: 170px; height: auto; filter: brightness(0) invert(1); }
        .menu { flex: 1; padding: 10px 0; overflow-y: auto; }
        .menu a { text-decoration: none; color: white; display: block; }
        .menu-item { width: 100%; padding: 12px 25px; display: flex; align-items: center; gap: 12px; background: none; border: none; color: white; font-size: 14px; cursor: pointer; text-align: left; }
        .menu-item.active { background: white; color: #111827 !important; font-weight: 700; border-radius: 50px 0 0 50px; margin-left: 15px; width: calc(100% - 15px); }
        .menu-group-title { padding: 20px 25px 10px; font-size: 11px; font-weight: 700; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 1px; }

        /* MAIN & TOPBAR - DESIGN LOCKED */
        .main { flex: 1; display: flex; flex-direction: column; height: 100vh; overflow-y: auto; width: 100%; }
        .topbar { background: white; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #edf2f7; position: sticky; top: 0; z-index: 10; }
        
        .topbar-right { display: flex; align-items: center; gap: 20px; }
        .icon-btn { background: #f3f4f6; border: none; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; position: relative; }
        .badge { position: absolute; top: 0; right: 0; background: var(--doremi-red); color: white; font-size: 10px; padding: 2px 5px; border-radius: 50%; border: 2px solid white; }

        .profile-box { display: flex; align-items: center; gap: 12px; border-left: 1px solid #e5e7eb; padding-left: 20px; }
        .admin-avatar { width: 40px; height: 40px; background: #f3f4f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; border: 1px solid #e5e7eb; }
        .logout-link { color: var(--doremi-red); font-size: 11px; font-weight: 700; cursor: pointer; border: none; background: none; }

        /* CONTENT & TABLES */
        .content { padding: 40px; width: 100%; }
        .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border-bottom: 4px solid #eee; }
        .stat-card.red { border-bottom-color: var(--doremi-red); }

        .panel { background: white; border-radius: 15px; padding: 35px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .panel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        
        .table { width: 100%; border-collapse: collapse; }
        .table th { text-align: left; padding: 12px; color: #6b7280; font-size: 13px; border-bottom: 2px solid #f3f4f6; }
        .table td { padding: 18px 12px; font-size: 14px; border-bottom: 1px solid #f3f4f6; color: #111; }

        .status-badge { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .status-badge.pending { background: #fff7ed; color: #c2410c; }
        .status-badge.approved { background: #dcfce7; color: #166534; }
        .status-badge.rejected { background: #fee2e2; color: #991b1b; }

        .btn-approve { background: #10b981; color: white; border: none; padding: 8px 15px; border-radius: 6px; font-weight: 700; font-size: 12px; cursor: pointer; margin-right: 5px; }
        .btn-reject { background: var(--doremi-red); color: white; border: none; padding: 8px 15px; border-radius: 6px; font-weight: 700; font-size: 12px; cursor: pointer; }
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
            <a href="{{ route('flexible') }}"><button class="menu-item active">🔁 Flexible Hours</button></a>
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
            <h1 style="font-size: 20px; font-weight: 800;">Flexible Hours</h1>
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
                    <div style="color:#6b7280; font-size:13px; font-weight:600;">Pending Requests</div>
                    <div style="font-size:28px; font-weight:800; margin-top:8px;">{{ $stats['pending'] }}</div>
                </div>
                <div class="stat-card">
                    <div style="color:#6b7280; font-size:13px; font-weight:600;">Approved Today</div>
                    <div style="font-size:28px; font-weight:800; margin-top:8px; color:#10b981;">{{ $stats['approved_today'] }}</div>
                </div>
                <div class="stat-card">
                    <div style="color:#6b7280; font-size:13px; font-weight:600;">Total This Month</div>
                    <div style="font-size:28px; font-weight:800; margin-top:8px;">{{ $stats['total_this_month'] }}</div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <h3>Applications List</h3>
                    <form method="GET" action="{{ route('flexible') }}" style="display:flex; gap:10px;">
                        <input type="date" name="date" style="padding:8px; border-radius:6px; border:1px solid #ddd;" value="{{ request('date') }}">
                        <select name="status" style="padding:8px; border-radius:6px; border:1px solid #ddd;">
                            <option value="">All Status</option>
                            <option value="Pending">Pending</option>
                            <option value="Approved">Approved</option>
                            <option value="Rejected">Rejected</option>
                        </select>
                        <button type="submit" style="background:#f3f4f6; border:1px solid #ddd; padding:8px 15px; border-radius:6px; font-weight:700; cursor:pointer;">Filter</button>
                    </form>
                </div>

                <table class="table">
    <thead>
        <tr>
            <th>Employee</th>
            <th>Date</th>
            <th>Total Hours</th>
            <th>Staff Credit Balance</th> <th>Status</th>
            <th style="text-align:right;">Action</th>
        </tr>
    </thead>
    <tbody>
        @foreach($flexibleRequests as $req)
        <tr>
            <td style="font-weight:700;">{{ $req->staff_name }}</td>
            <td>{{ $req->date }}</td>
            <td style="font-weight:700; color:var(--doremi-red);">{{ $req->total_hours }}H</td>
            
            <td style="font-weight:700; color: #10b981;">
                {{ $req->staff_balance }}H
                @if($req->staff_balance < $req->total_hours && strtolower($req->status) == 'pending')
                    <span style="display:block; font-size:10px; color:var(--doremi-red);">⚠️ Low Balance</span>
                @endif
            </td>

            <td><span class="status-badge {{ strtolower($req->status) }}">{{ $req->status }}</span></td>
            <td style="text-align:right;">
                @if(strtolower($req->status) == 'pending')
                    <button class="btn-approve" onclick="handleFlexAction('{{ $req->id }}', '{{ $req->uid }}', {{ $req->total_hours }}, 'Approved', {{ $req->staff_balance }})">Approve</button>
                    <button class="btn-reject" onclick="handleFlexAction('{{ $req->id }}', '{{ $req->uid }}', {{ $req->total_hours }}, 'Rejected', {{ $req->staff_balance }})">Reject</button>
                @else
                    <span style="color:#9ca3af; font-size:11px; font-weight:700; text-transform:uppercase;">Processed</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
            </div>
        </section>
    </main>

<script>
    // Handle Approve/Reject via Laravel backend (server-side balance check & Firestore update)
    async function handleFlexAction(reqId, uid, requestedHours, action, currentBalance) {
        if (!reqId || !uid) {
            alert("Error: Missing Request ID or User ID!");
            return;
        }

        // Client-side guard (server also re-validates)
        if (action === 'Approved' && parseFloat(currentBalance) < parseFloat(requestedHours)) {
            alert(`Blocked! Staff only has ${currentBalance}H credit, cannot approve ${requestedHours}H.`);
            return;
        }

        const confirmMsg = action === 'Approved'
            ? `Confirm Approve? Staff Credit will be deducted by ${requestedHours}H.`
            : `Reject this request?`;

        if (!confirm(confirmMsg)) return;

        try {
            const res = await fetch(`/flexible/update/${reqId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: action, uid: uid, total_hours: requestedHours })
            });

            // Server redirects on success — just reload
            if (res.ok || res.redirected || res.status === 302) {
                location.reload();
            } else {
                let msg = 'Unknown error';
                try {
                    const data = await res.json();
                    msg = data.message || data.error || msg;
                } catch(e) {}
                alert("Error: " + msg);
            }
        } catch (error) {
            console.error("Request Error:", error);
            alert("Network Error: " + error.message);
        }
      }

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