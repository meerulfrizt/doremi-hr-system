<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Review Overtime - DOREMi Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root { --doremi-red: #D6001C; --sidebar-bg: #D6001C; --bg-light: #F9FAFB; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-light); display: flex; height: 100vh; width: 100%; overflow: hidden; }

        .sidebar { width: 260px; min-width: 260px; background-color: var(--sidebar-bg); color: white; display: flex; flex-direction: column; }
        .brand { padding: 40px 20px; display: flex; justify-content: center; align-items: center; }
        .brand img { width: 170px; filter: brightness(0) invert(1); }
        .menu-item.active { background: white; color: #111827 !important; font-weight: 700; border-radius: 50px 0 0 50px; margin-left: 15px; width: calc(100% - 15px); padding: 12px 25px; display: flex; align-items: center; gap: 12px; border: none; }

        .main { flex: 1; display: flex; flex-direction: column; overflow-y: auto; }
        .topbar { background: white; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #edf2f7; }
        
        .content { padding: 40px; }
        .panel { background: white; border-radius: 15px; padding: 35px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }

        .btn-save { background: #16a34a; color: white; border: none; padding: 12px 25px; border-radius: 8px; font-weight: 800; cursor: pointer; transition: 0.2s; }
        .btn-save:hover { background: #15803d; transform: translateY(-2px); }

        .radio-label { display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; cursor: pointer; }
        .input-remarks { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 12px; margin-top: 8px; }
        
        .tr-rejected { background: #fff1f2; }
        .tr-verified { background: #f0fdf4; }

        /* --- NOTIFICATION DROPDOWN & TOPBAR --- */
        .topbar-right { display: flex; align-items: center; gap: 20px; }
        .icon-btn { background: #f3f4f6; border: none; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; position: relative; }
        .badge { position: absolute; top: 0; right: 0; background: var(--doremi-red); color: white; font-size: 10px; padding: 2px 5px; border-radius: 50%; border: 2px solid white; }
        
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
        .notif-list { list-style: none; max-height: 300px; overflow-y: auto; padding: 0; margin: 0; }
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
            <a href="{{ route('overtime') }}"><button class="menu-item active">⬅️ Back to Summary</button></a>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <h1 style="font-size: 20px; font-weight: 800;">Reviewing: {{ $user->name }}</h1>
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

                <div class="profile-box" style="display: flex; align-items: center; gap: 12px; border-left: 1px solid #e5e7eb; padding-left: 20px;">
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
            <form action="{{ route('overtime.bulk_update') }}" method="POST">
                @csrf
                <div class="panel">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
                        <div>
                            <h3 style="font-size:18px;">Overtime Details - {{ $user->department }}</h3>
                            <p style="color:#6b7280; font-size:14px; margin-top:5px;">Total Claimed: <strong style="color:var(--doremi-red);">{{ $requests->where('status', '!=', 'Rejected')->sum('duration_hours') }} Hours</strong></p>
                        </div>
                        <button type="submit" class="btn-save">💾 SUBMIT ALL DECISIONS</button>
                    </div>

                    <table style="width:100%; border-collapse: collapse;">
                        <thead>
                            <tr style="text-align:left; border-bottom:2px solid #f3f4f6; color:#6b7280; font-size:13px;">
                                <th style="padding:12px;">Date & Time</th>
                                <th>Event / Reason</th>
                                <th>Duration</th>
                                <th style="width:350px;">Action & Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($requests as $ot)
                            @php $isLocked = $ot->status === 'Verified' || $ot->status === 'Rejected'; @endphp
                            <tr style="border-bottom:1px solid #f3f4f6;" class="{{ $ot->status == 'Rejected' ? 'tr-rejected' : ($ot->status == 'Verified' ? 'tr-verified' : '') }}">
                                <td style="padding:20px 12px;">
                                    <div style="font-weight:700;">{{ \Carbon\Carbon::parse($ot->date)->format('d/m/Y') }}</div>
                                    <div style="font-size:11px; color:#888;">{{ $ot->start_time }} - {{ $ot->end_time }}</div>
                                </td>
                                <td>
                                    <div style="font-weight:700;">{{ $ot->event_name }}</div>
                                    <div style="font-size:12px; color:#666; font-style:italic;">"{{ $ot->reason }}"</div>
                                </td>
                                <td style="font-weight:800; color:var(--doremi-red);">{{ $ot->duration_hours }}H</td>
                                <td>
                                    <div style="display:flex; gap:15px; opacity: {{ $isLocked ? '0.6' : '1' }}; pointer-events: {{ $isLocked ? 'none' : 'auto' }};">
                                        <label class="radio-label" style="color:#16a34a;"><input type="radio" name="requests[{{ $ot->id }}][status]" value="Verified" {{ $ot->status == 'Verified' ? 'checked' : '' }} {{ $isLocked ? 'disabled' : '' }}> Approve</label>
                                        <label class="radio-label" style="color:#dc2626;"><input type="radio" name="requests[{{ $ot->id }}][status]" value="Rejected" {{ $ot->status == 'Rejected' ? 'checked' : '' }} {{ $isLocked ? 'disabled' : '' }}> Reject</label>
                                        <label class="radio-label" style="color:#6b7280;"><input type="radio" name="requests[{{ $ot->id }}][status]" value="Pending" {{ $ot->status == 'Pending' ? 'checked' : '' }} {{ $isLocked ? 'disabled' : '' }}> Pending</label>
                                    </div>
                                    @if($isLocked)
                                        <input type="hidden" name="requests[{{ $ot->id }}][status]" value="{{ $ot->status }}">
                                        <input type="hidden" name="requests[{{ $ot->id }}][remarks]" value="{{ $ot->admin_remarks }}">
                                    @endif
                                    <input type="hidden" name="requests[{{ $ot->id }}][uid]" value="{{ $user->id }}">
                                    <input type="hidden" name="requests[{{ $ot->id }}][duration_hours]" value="{{ $ot->duration_hours }}">
                                    <input type="hidden" name="requests[{{ $ot->id }}][old_status]" value="{{ $ot->status }}">
                                    <input type="text" name="requests[{{ $ot->id }}][remarks]" class="input-remarks" value="{{ $ot->admin_remarks }}" placeholder="Add remarks here..." {{ $isLocked ? 'readonly style=background:#f3f4f6;' : '' }}>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </form>
        </section>
    </main>

    <script>
        const notifToggle = document.getElementById('notifToggle');
        const notifPanel = document.getElementById('notifPanel');

        notifToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            notifPanel.classList.toggle('show');
        });

        document.addEventListener('click', (e) => {
            if (!notifPanel.contains(e.target) && e.target !== notifToggle) {
                notifPanel.classList.remove('show');
            }
        });

        function fetchLiveNotifications() {
            fetch('{{ route("api.notifications") }}')
                .then(res => res.json())
                .then(data => {
                    const badge = document.getElementById('notifBadge');
                    const list = document.getElementById('notifList');

                    if(data.count > 0) {
                        badge.style.display = 'block';
                        badge.innerText = data.count;
                    } else {
                        badge.style.display = 'none';
                    }

                    if(data.data.length > 0) {
                        list.innerHTML = '';
                        data.data.forEach(item => {
                            list.innerHTML += `
                                <li class="notif-item">
                                    <div style="font-weight:600; font-size:13px; color:#111; text-align:left;">${item.title}</div>
                                    <div style="font-size:11px; color:#6b7280; text-align:left;">${item.desc}</div>
                                    <div style="font-size:10px; color:#D6001C; margin-top:4px; text-align:left;">${item.time}</div>
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

        fetchLiveNotifications();
        // setInterval(fetchLiveNotifications, 10000); // Stopped to save quota
    </script>
</body>
</html>