<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Employee Directory - DOREMi Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
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

        /* HEADER & MAIN - FULL WIDTH STRATEGY */
        .main { flex: 1; display: flex; flex-direction: column; height: 100vh; overflow-y: auto; width: 100%; }
        .topbar { background: white; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #edf2f7; position: sticky; top: 0; z-index: 10; width: 100%; }
        
        .search-container { position: relative; width: 400px; }
        .search-container input { width: 100%; padding: 10px 15px 10px 40px; background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 8px; outline: none; }
        .search-icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #9ca3af; }

        .topbar-right { display: flex; align-items: center; gap: 20px; }
        .icon-btn { background: #f3f4f6; border: none; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; position: relative; cursor: pointer; }
        .badge { position: absolute; top: 0; right: 0; background: var(--doremi-red); color: white; font-size: 10px; padding: 2px 5px; border-radius: 50%; border: 2px solid white; }
        
        .profile-container { display: flex; align-items: center; gap: 12px; border-left: 1px solid #e5e7eb; padding-left: 20px; }
        .user-avatar-circle { width: 40px; height: 40px; background: #f3f4f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; border: 1px solid #e5e7eb; color: #111; }
        .logout-link { color: var(--doremi-red); font-size: 11px; font-weight: 700; border: none; background: none; cursor: pointer; text-align: left; }

        /* DIRECTORY CONTENT */
        .content { padding: 40px; width: 100%; }
        .panel { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); width: 100%; }
        .panel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }

        .table { width: 100%; border-collapse: collapse; }
        .table th { text-align: left; padding: 12px; color: #6b7280; font-size: 13px; border-bottom: 2px solid #f3f4f6; text-transform: uppercase; letter-spacing: 0.5px; }
        .table td { padding: 18px 12px; font-size: 14px; border-bottom: 1px solid #f3f4f6; color: #111; vertical-align: middle; }

        .staff-avatar { width: 38px; height: 38px; background: #fff1f2; color: var(--doremi-red); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; border: 1px solid #fecdd3; }
        .btn-action { width: 32px; height: 32px; border-radius: 6px; border: 1px solid #e5e7eb; background: white; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: 0.2s; text-decoration: none; }
        .btn-action:hover { background: #f9fafb; border-color: #d1d5db; }
        
        .btn-add { background: var(--doremi-red); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 13px; text-decoration: none; }
        .btn-add:hover { opacity: 0.9; }

        .search-bar-inline { display: flex; gap: 10px; margin-bottom: 20px; }
        .input-inline { flex: 1; max-width: 300px; padding: 10px 15px; border: 1px solid #ddd; border-radius: 8px; outline: none; }

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
        .logout-btn:hover { text-decoration: underline; }
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
            <a href="{{ route('analytics') }}"><button class="menu-item">📊 Analytics</button></a>
            <a href="{{ route('directory') }}"><button class="menu-item active">👥 Directory</button></a>
        </nav>
        @include('layouts.pulse')
    </aside>

    <main class="main">
        <header class="topbar">
            <div class="search-container">
                <span class="search-icon">🔍</span>
                <input type="text" id="globalSearch" placeholder="Quick search staff...">
            </div>
            
            <div class="topbar-right">
                <div style="position: relative;">
                    <button class="icon-btn" id="notifToggle">
                        🔔 <span class="badge" id="notifBadge" style="display: none;">0</span>
                    </button>
                    
                    <div class="notif-dropdown" id="notifPanel">
                        <div class="notif-header">
                            <span style="font-weight: 700; font-size: 14px; color: #111;">Notifications</span>
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

        <section class="content">
            <div class="panel">
                <div class="panel-header">
                    <h3 style="font-size: 18px; font-weight: 800;">Employee Directory</h3>
                    <a href="{{ route('employees.create') }}" class="btn-add">+ Add New Staff</a>
                </div>

                <form method="GET" action="{{ route('directory') }}" class="search-bar-inline">
                    <input type="text" name="search" value="{{ request('search') }}" class="input-inline" placeholder="Search by name...">

                    <select name="department" style="padding:10px 12px; border:1px solid #ddd; border-radius:8px; outline:none; font-size:13px; color:#374151; background:#fff; cursor:pointer;">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept }}" {{ request('department') === $dept ? 'selected' : '' }}>{{ $dept }}</option>
                        @endforeach
                    </select>

                    <button type="submit" style="padding:10px 18px; background:var(--doremi-red); color:white; border:none; border-radius:8px; font-weight:700; cursor:pointer; font-size:13px;">Search</button>
                    @if(request('search') || request('department'))
                        <a href="{{ route('directory') }}" style="padding:10px 14px; border:1px solid #e5e7eb; border-radius:8px; font-size:13px; color:#6b7280; text-decoration:none; font-weight:600;">✕ Reset</a>
                    @endif
                </form>
                <div style="margin-bottom:16px; font-size:13px; color:#6b7280; font-weight:600;">
                    Showing <span style="color:#111; font-weight:800;">{{ $employees->count() }}</span> employee(s){{ request('department') ? ' in <span style="color:var(--doremi-red);">'.request('department').'</span>' : '' }}
                </div>

                <table class="table">
                    <thead>
                        <tr>
                            <th>Employee Name</th>
                            <th>Contact Details</th>
                            <th>Department</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $employee)
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div class="staff-avatar">{{ substr($employee->full_name, 0, 1) }}</div>
                                    <div>
                                        <div style="font-weight: 700; color: #111;">{{ $employee->full_name }}</div>
                                        <div style="font-size: 11px; color: #6b7280;">ID: {{ $employee->id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 500;">{{ $employee->email }}</div>
                                <div style="font-size: 12px; color: #6b7280;">{{ $employee->phone_number ?? 'No Phone' }}</div>
                            </td>
                            <td>
                                <span style="font-weight: 600; color: #374151;">{{ $employee->department ?? 'General' }}</span>
                            </td>
                            <td style="text-align:right;">
                                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                    <a href="{{ route('employees.show', $employee->id) }}" class="btn-action" title="View Profile">👁️</a>
                                    <a href="{{ route('employees.edit', $employee->id) }}" class="btn-action" title="Edit">✏️</a>
                                    <form action="{{ route('employee.destroy', $employee->id) }}" method="POST" style="display:inline;">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-action" style="color: var(--doremi-red);" onclick="return confirm('Are you sure you want to delete this employee? This action cannot be undone.')">🗑️</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" style="text-align:center; padding:50px; color:#9ca3af;">No employees found in directory.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function runAIAnalysis(userId, staffName) {
            Swal.fire({
                title: 'Analysing ' + staffName,
                text: 'Gemini AI is scanning records...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('/admin/analyze/' + userId) 
                .then(res => res.json())
                .then(data => {
                    Swal.fire({
                        title: 'AI HR Insight',
                        html: `<div style="text-align:left; background:#fff1f2; padding:15px; border-radius:10px; border-left:5px solid var(--doremi-red); font-style: italic;">"${data.insight}"</div>`,
                        confirmButtonColor: '#D6001C'
                    });
                })
                .catch(() => Swal.fire('Error', 'Service unavailable', 'error'));
        }

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
                                    <div style="font-size:10px; color:var(--doremi-red); margin-top:4px; text-align:left;">${item.time}</div>
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
        setInterval(fetchLiveNotifications, 10000);
    </script>
</body>
</html>