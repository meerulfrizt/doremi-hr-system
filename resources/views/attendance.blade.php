<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Attendance Monitoring - DOREMi Admin</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            /* WARNA MERAH DASHBOARD BOS */
            --doremi-red: #D6001C; 
            --sidebar-bg: #D6001C;
            --bg-light: #F9FAFB;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }

        body { background-color: var(--bg-light); display: flex; height: 100vh; overflow: hidden; }

        /* SIDEBAR - SERAGAM 100% */
        .sidebar {
            width: 260px;
            background-color: var(--sidebar-bg);
            color: white;
            display: flex;
            flex-direction: column;
            transition: all 0.3s;
        }

        /* LOGO CENTRE */
        .brand { 
            padding: 30px 20px; 
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .brand img { 
            width: 150px; 
            height: auto;
            filter: brightness(0) invert(1);
        }

        .menu { flex: 1; padding: 10px 0; }
        .menu a { text-decoration: none; color: white; display: block; }
        
        .menu-item {
            width: 100%; padding: 12px 25px; display: flex; align-items: center;
            gap: 15px; background: none; border: none; color: white;
            font-size: 14px; cursor: pointer; transition: 0.2; text-align: left;
        }

        .menu-item:hover { background: rgba(255, 255, 255, 0.1); color: white; }

        /* MENU ACTIVE TULISAN HITAM */
        .menu-item.active {
            background: white; 
            color: #111827 !important; 
            font-weight: 700;
            border-radius: 50px 0 0 50px; 
            margin-left: 15px; 
            width: calc(100% - 15px);
        }

        .menu-group-title {
            padding: 20px 25px 10px; font-size: 11px; font-weight: 700;
            color: rgba(255, 255, 255, 0.5); text-transform: uppercase;
        }

        /* MAIN CONTENT */
        .main { flex: 1; display: flex; flex-direction: column; overflow-y: auto; }

        /* TOPBAR SERAGAM */
        .topbar {
            background: white; padding: 15px 30px; display: flex;
            justify-content: space-between; align-items: center;
            border-bottom: 1px solid #edf2f7; position: sticky; top: 0; z-index: 10;
        }

        .search-container { position: relative; width: 400px; }
        .search-container input {
            width: 100%; padding: 10px 15px 10px 40px; background: #f3f4f6;
            border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; outline: none;
        }
        .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; }

        .topbar-right { display: flex; align-items: center; gap: 20px; }

        .icon-btn {
            background: #f3f4f6; border: none; width: 40px; height: 40px;
            border-radius: 50%; display: flex; align-items: center;
            justify-content: center; cursor: pointer; position: relative; font-size: 18px;
        }

        .badge {
            position: absolute; top: 0; right: 0; background: var(--doremi-red);
            color: white; font-size: 10px; padding: 2px 5px; border-radius: 50%; border: 2px solid white;
        }

        /* DROPDOWN NOTIF */
        .notif-dropdown {
            display: none; position: absolute; top: 55px; right: 0;
            width: 300px; background: white; border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb; z-index: 100;
        }
        .notif-dropdown.show { display: block; animation: fadeIn 0.2s ease-out; }
        .notif-header { padding: 15px; border-bottom: 1px solid #f3f4f6; display: flex; justify-content: space-between; align-items: center; }
        .notif-header span { color: #111827; font-weight: 700; font-size: 14px; }
        .notif-list { list-style: none; max-height: 300px; overflow-y: auto; }
        .notif-item { padding: 12px 15px; border-bottom: 1px solid #f9fafb; transition: 0.2s; }

        /* CONTENT AREA */
        .content { padding: 30px; }
        .panel { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .panel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        
        .filter-bar { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .filter-input { padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; outline: none; background: white; }
        .filter-input:focus { border-color: var(--doremi-red); }

        .table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table th { text-align: left; padding: 12px; color: #6b7280; font-size: 13px; border-bottom: 1px solid #f3f4f6; }
        .table td { padding: 15px 12px; font-size: 14px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; color: #111827; }

        .status-badge { padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; }
        .status-badge.ontime { background: #dcfce7; color: #166534; }
        .status-badge.late { background: #fef3c7; color: #92400e; }

        .btn-doremi { background: var(--doremi-red); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-doremi:hover { opacity: 0.9; }

        .logout-btn { background: none; border: none; color: var(--doremi-red); font-size: 11px; font-weight: 600; cursor: pointer; }
        .logout-btn:hover { text-decoration: underline; }

        #detailPanel {
            display: none; background: white; border-radius: 12px; padding: 25px; margin-top: 25px;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); border-left: 5px solid var(--doremi-red);
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes zoomIn {
            from { transform: scale(0.92); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="brand">
            <img src="{{ asset('img/Doremi logo.png') }}" alt="logo">
        </div>
        <nav class="menu">
            <a href="{{ route('dashboard') }}"><button class="menu-item">🏠 Dashboard</button></a>
            <a href="{{ route('attendance') }}"><button class="menu-item active">🕒 Attendance</button></a>
            
            <div class="menu-group-title">Management</div>
            <a href="{{ route('leave') }}"><button class="menu-item">🗓️ Leave Requests</button></a>
            <a href="{{ route('overtime') }}"><button class="menu-item">⏱️ Overtime (OT)</button></a>
            <a href="{{ route('flexible') }}"><button class="menu-item">🔁 Flexible Hours</button></a>
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
                <input type="text" placeholder="Search for employees, attendance...">
            </div>

            <div class="topbar-right">
                <div style="position: relative;">
                    <button class="icon-btn" id="notifToggle">
                        🔔 <span class="badge" id="notifBadge">0</span>
                    </button>
                    <div class="notif-dropdown" id="notifPanel">
                        <div class="notif-header">
                            <span>Notifications</span>
                            <button onclick="markAllRead()" style="background:none; border:none; color:var(--doremi-red); font-size:12px; cursor:pointer;">Mark all read</button>
                        </div>
                        <ul class="notif-list" id="notifList">
                            <li style="padding:20px; text-align:center; color:#888; font-size:12px;">Loading...</li>
                        </ul>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 12px; border-left: 1px solid #e5e7eb; padding-left: 20px;">
                    <div style="width: 35px; height: 35px; background: #f3f4f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; border: 1px solid #e5e7eb; color: #111827;">
                        {{ substr(session('firebase_user.displayName') ?? 'A', 0, 1) }}
                    </div>
                    <div style="display: flex; flex-direction: column; line-height: 1.2;">
                        <span style="font-weight: 700; font-size: 14px; color: #111827;">{{ session('firebase_user.displayName') ?? 'Admin' }}</span>
                        <form method="POST" action="{{ route('logout') }}">
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
                    <div>
                        <h3 style="font-size: 18px; font-weight: 700; color: #111827;">Attendance Monitoring</h3>
                        <p style="font-size: 13px; color: #6b7280;">Live attendance records overview.</p>
                    </div>
                    <div>
                        <a href="{{ route('attendance', array_merge(request()->query(), ['refresh' => 1])) }}" class="btn-doremi" style="background: #2563eb; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-size: 13px; padding: 8px 16px; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);">
                            🔄 Sync Live Data
                        </a>
                    </div>
                </div>

                <form method="GET" action="{{ route('attendance') }}">
                    <div class="filter-bar">
                        <input type="text" name="search" value="{{ request('search') }}" class="filter-input" placeholder="Search by name..." style="width: 250px;">
                        <input type="date" name="date" value="{{ request('date', now()->toDateString()) }}" class="filter-input">
                        <select name="status" class="filter-input">
                            <option value="">All Status</option>
                            <option value="Present" {{ request('status') == 'Present' ? 'selected' : '' }}>On Time</option>
                            <option value="Late" {{ request('status') == 'Late' ? 'selected' : '' }}>Late</option>
                        </select>
                        <button type="submit" class="btn-doremi">Filter</button>
                        <a href="{{ route('attendance') }}" style="font-size: 13px; color: #6b7280; text-decoration: none; margin-left: 10px;">Reset</a>
                    </div>
                </form>

                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Employee</th>
                            <th>Type</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Location</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendances as $row)
                        <tr>
                            <td>{{ $row->display_date }}</td>
                            <td>
                                <div style="font-weight: 600; color: #111827;">{{ $row->name }}</div>
                                <div style="font-size: 11px; color: #6b7280;">{{ $row->department }}</div>
                            </td>
                            <td><span style="font-weight:600; color:#4f46e5;">{{ $row->type }}</span></td>
                            <td style="font-weight: 600; color: #059669;">{{ $row->timestamp }}</td>
                            <td>
                                <span class="status-badge {{ strtolower($row->status) == 'late' ? 'late' : 'ontime' }}">
                                    {{ $row->status ?? 'Present' }}
                                </span>
                            </td>
                            <td>
                                @if($row->lat !== null && $row->lng !== null)
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <button class="btn-doremi" style="padding:4px 8px; font-size:12px; background:#f3f4f6; color:#111; border:1px solid #e5e7eb;" 
                                            onclick="openMapModal('{{ addslashes($row->name) }}', '{{ $row->full_timestamp }}', '{{ $row->lat }}', '{{ $row->lng }}', '{{ $row->status }}', '{{ $row->type }}', '{{ addslashes($row->locationSource) }}', '{{ $row->distance }}')">
                                            📍 Map
                                        </button>
                                        @if($row->inZone)
                                            <span title="Within {{ config('services.hq.radius') }}m of {{ $row->locationSource }}" style="font-size:11px; background:#dcfce7; color:#166534; padding:2px 6px; border-radius:4px; font-weight:600;">✅ In Zone</span>
                                        @else
                                            <span title="Out of zone: {{ $row->locationSource }}" style="font-size:11px; background:#fee2e2; color:#b91c1c; padding:2px 6px; border-radius:4px; font-weight:600;">⚠️ Out of Zone</span>
                                        @endif
                                    </div>
                                @else
                                    <span style="color:#9ca3af; font-size:12px;">—</span>
                                @endif
                            </td>
                            <td>
                                <button class="btn-doremi" style="padding: 5px 12px; font-size: 12px;" onclick="openDetail('{{ addslashes($row->name) }}', '{{ addslashes($row->department) }}', '{{ $row->display_date }}', '{{ $row->status }}', '{{ $row->type }}', '{{ $row->clock_in_time }}', '{{ $row->clock_out_time }}', '{{ addslashes($row->photo_url) }}', '{{ addslashes($row->checkout_photo_url) }}', '{{ addslashes($row->checkout_location) }}')">View</button>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" style="text-align: center; padding: 40px; color: #9ca3af;">No attendance records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div id="detailPanel" style="display: none; background: white; border-radius: 12px; padding: 25px; margin-top: 25px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); border-left: 5px solid var(--doremi-red); animation: slideIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #f3f4f6; padding-bottom: 12px;">
                    <div>
                        <h4 style="color: var(--doremi-red); font-weight: 800; font-size: 18px; letter-spacing: -0.025em; display: flex; align-items: center; gap: 8px;">
                            🕒 Detailed Attendance Record
                        </h4>
                        <p style="font-size: 12px; color: #6b7280; margin-top: 2px;">Comprehensive check-in and check-out logs</p>
                    </div>
                    <button onclick="closeDetail()" style="border: none; background: #f3f4f6; font-size: 18px; cursor: pointer; color: #4b5563; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all 0.2s;" onmouseover="this.style.background='#e5e7eb'; this.style.color='#111827'" onmouseout="this.style.background='#f3f4f6'; this.style.color='#4b5563'">&times;</button>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 24px;">
                    <!-- Employee Profile Info Card -->
                    <div style="background: #f9fafb; border-radius: 10px; padding: 18px; border: 1px solid #f0fdf4;">
                        <h5 style="font-size: 11px; text-transform: uppercase; color: #6b7280; font-weight: 700; letter-spacing: 0.05em; margin-bottom: 12px;">Staff Profile</h5>
                        <div style="display: flex; flex-direction: column; gap: 8px; font-size: 14px;">
                            <div><span style="color: #6b7280; font-size: 12px;">Name</span><div style="font-weight: 700; color: #111827; margin-top: 2px;" id="dName">-</div></div>
                            <div><span style="color: #6b7280; font-size: 12px;">Department</span><div style="font-weight: 600; color: #4b5563; margin-top: 2px;" id="dDept">-</div></div>
                            <div><span style="color: #6b7280; font-size: 12px;">Date</span><div style="font-weight: 600; color: #4b5563; margin-top: 2px;" id="dDate">-</div></div>
                            <div><span style="color: #6b7280; font-size: 12px;">Overall Status</span>
                                <div style="margin-top: 4px;">
                                    <span id="dStatusBadge" class="status-badge" style="display: inline-block;">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Clock-In Activity Card -->
                    <div style="background: #f9fafb; border-radius: 10px; padding: 18px; border: 1px solid #eef2f6; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                <h5 style="font-size: 11px; text-transform: uppercase; color: #059669; font-weight: 700; letter-spacing: 0.05em;">☀️ Clock-In Log</h5>
                                <span id="dTypeBadge" style="font-size: 11px; padding: 2px 8px; border-radius: 12px; font-weight: 600; background: #e0e7ff; color: #4338ca;">-</span>
                            </div>
                            <div style="margin-bottom: 12px;">
                                <span style="color: #6b7280; font-size: 12px;">Recorded Time</span>
                                <div id="dClockIn" style="font-size: 24px; font-weight: 800; color: #059669; margin-top: 2px;">--:--</div>
                            </div>
                        </div>
                        
                        <div id="dPhotoContainer" style="margin-top: 10px;">
                            <span style="color: #6b7280; font-size: 12px; display: block; margin-bottom: 6px;">Attachment Photo</span>
                            <div style="position: relative; width: 100%; height: 110px; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb; background: #f3f4f6; cursor: pointer; transition: transform 0.2s;" onclick="zoomImage(this.querySelector('img').src)" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                                <img id="dPhoto" src="" alt="Clock-In Photo" style="width: 100%; height: 100%; object-fit: cover;">
                                <div style="position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.5); color: white; text-align: center; font-size: 10px; padding: 4px 0; font-weight: 600;">🔍 Click to Zoom</div>
                            </div>
                        </div>
                        <div id="dPhotoPlaceholder" style="margin-top: 10px; display: none; height: 110px; border: 1px dashed #d1d5db; border-radius: 8px; align-items: center; justify-content: center; color: #9ca3af; font-size: 12px; background: #f9fafb;">
                            📷 No photo uploaded
                        </div>
                    </div>

                    <!-- Clock-Out Activity Card -->
                    <div style="background: #f9fafb; border-radius: 10px; padding: 18px; border: 1px solid #eef2f6; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                <h5 style="font-size: 11px; text-transform: uppercase; color: #ea580c; font-weight: 700; letter-spacing: 0.05em;">🌙 Clock-Out Log</h5>
                                <span id="dCheckoutLoc" style="font-size: 11px; padding: 2px 8px; border-radius: 12px; font-weight: 600; background: #fef3c7; color: #d97706;">-</span>
                            </div>
                            <div style="margin-bottom: 12px;">
                                <span style="color: #6b7280; font-size: 12px;">Recorded Time</span>
                                <div id="dClockOut" style="font-size: 24px; font-weight: 800; color: #ea580c; margin-top: 2px;">--:--</div>
                            </div>
                        </div>
                        
                        <div id="dCheckoutPhotoContainer" style="margin-top: 10px;">
                            <span style="color: #6b7280; font-size: 12px; display: block; margin-bottom: 6px;">Attachment Photo</span>
                            <div style="position: relative; width: 100%; height: 110px; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb; background: #f3f4f6; cursor: pointer; transition: transform 0.2s;" onclick="zoomImage(this.querySelector('img').src)" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                                <img id="dCheckoutPhoto" src="" alt="Clock-Out Photo" style="width: 100%; height: 100%; object-fit: cover;">
                                <div style="position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.5); color: white; text-align: center; font-size: 10px; padding: 4px 0; font-weight: 600;">🔍 Click to Zoom</div>
                            </div>
                        </div>
                        <div id="dCheckoutPhotoPlaceholder" style="margin-top: 10px; display: none; height: 110px; border: 1px dashed #d1d5db; border-radius: 8px; align-items: center; justify-content: center; color: #9ca3af; font-size: 12px; background: #f9fafb;">
                            📷 No check-out photo
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fullscreen Image Viewer Modal -->
            <div id="imageZoomModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(17, 24, 39, 0.9); z-index: 2000; justify-content: center; align-items: center; cursor: zoom-out;" onclick="closeZoomModal()">
                <div style="position: absolute; top: 20px; right: 30px; color: white; font-size: 36px; font-weight: 300; cursor: pointer; user-select: none;">&times;</div>
                <img id="zoomedImage" src="" alt="Zoomed Photo" style="max-width: 90%; max-height: 85%; border-radius: 12px; border: 4px solid white; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); animation: zoomIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);">
            </div>

            <!-- Google Maps Modal -->
            <div id="mapModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
                <div style="background: white; width: 500px; border-radius: 12px; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
                    <div style="padding: 15px 20px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; background: #f9fafb;">
                        <div>
                            <h3 id="mapModalTitle" style="font-size: 16px; font-weight: 700; color: #111;">Name · Time</h3>
                        </div>
                        <button onclick="document.getElementById('mapModal').style.display='none'" style="background:none; border:none; font-size:24px; cursor:pointer; color:#6b7280;">&times;</button>
                    </div>
                    <iframe id="mapIframe" width="100%" height="300" frameborder="0" style="border:0" allowfullscreen></iframe>
                    <div style="padding: 20px; font-size: 13px; color: #374151; display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div style="grid-column: 1 / -1;">📍 Coordinates: <span id="mapCoords" style="font-weight:600;"></span></div>
                        <div>✅ Status: <span id="mapStatus" style="font-weight:600;"></span></div>
                        <div>🕐 Type: <span id="mapType" style="font-weight:600;"></span></div>
                        <div>📌 Zone: <span id="mapZone" style="font-weight:600;"></span></div>
                        <div>📏 Distance: <span id="mapDistance" style="font-weight:600;"></span></div>
                        <div style="grid-column: 1 / -1; margin-top: 15px;">
                            <button onclick="openExternalMap()" class="btn-doremi" style="width: 100%;">Open in Google Maps ↗</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
        const notifToggle = document.getElementById('notifToggle');
        const notifPanel = document.getElementById('notifPanel');

        notifToggle.onclick = (e) => { e.stopPropagation(); notifPanel.classList.toggle('show'); };
        document.onclick = (e) => { if (!notifPanel.contains(e.target)) notifPanel.classList.remove('show'); };

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
                                <li class="notif-item" style="padding:12px 15px; border-bottom:1px solid #f9fafb; list-style:none;">
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

        function openDetail(name, dept, date, status, type, clockIn, clockOut, photo, checkoutPhoto, checkoutLoc) {
            document.getElementById('dName').innerText = name;
            document.getElementById('dDept').innerText = dept;
            document.getElementById('dDate').innerText = date;
            
            // Overall Status
            const statusBadge = document.getElementById('dStatusBadge');
            statusBadge.innerText = status || 'Present';
            statusBadge.className = 'status-badge ' + ((status || '').toLowerCase() === 'late' ? 'late' : 'ontime');
            
            // Work Location Type
            const typeBadge = document.getElementById('dTypeBadge');
            typeBadge.innerText = type || 'Unknown';
            if ((type || '').toLowerCase() === 'on-site') {
                typeBadge.style.background = '#e0e7ff';
                typeBadge.style.color = '#4338ca';
            } else {
                typeBadge.style.background = '#f3f4f6';
                typeBadge.style.color = '#374151';
            }

            // Clock In Time
            document.getElementById('dClockIn').innerText = clockIn || '--:--';
            
            // Clock In Photo
            if (photo && photo.trim() !== '') {
                document.getElementById('dPhoto').src = photo;
                document.getElementById('dPhotoContainer').style.display = 'block';
                document.getElementById('dPhotoPlaceholder').style.display = 'none';
            } else {
                document.getElementById('dPhotoContainer').style.display = 'none';
                document.getElementById('dPhotoPlaceholder').style.display = 'flex';
            }

            // Clock Out Time
            document.getElementById('dClockOut').innerText = clockOut || '--:--';
            
            // Checkout Location
            const checkoutLocBadge = document.getElementById('dCheckoutLoc');
            if (checkoutLoc && checkoutLoc.trim() !== '') {
                checkoutLocBadge.innerText = checkoutLoc;
                checkoutLocBadge.style.display = 'inline-block';
                if (checkoutLoc.toLowerCase() === 'on-site') {
                    checkoutLocBadge.style.background = '#dcfce7';
                    checkoutLocBadge.style.color = '#166534';
                } else {
                    checkoutLocBadge.style.background = '#fef3c7';
                    checkoutLocBadge.style.color = '#d97706';
                }
            } else {
                checkoutLocBadge.innerText = '--';
                checkoutLocBadge.style.background = '#f3f4f6';
                checkoutLocBadge.style.color = '#9ca3af';
            }

            // Clock Out Photo
            if (checkoutPhoto && checkoutPhoto.trim() !== '') {
                document.getElementById('dCheckoutPhoto').src = checkoutPhoto;
                document.getElementById('dCheckoutPhotoContainer').style.display = 'block';
                document.getElementById('dCheckoutPhotoPlaceholder').style.display = 'none';
            } else {
                document.getElementById('dCheckoutPhotoContainer').style.display = 'none';
                document.getElementById('dCheckoutPhotoPlaceholder').style.display = 'flex';
            }

            document.getElementById('detailPanel').style.display = 'block';
            
            // Smoothly scroll down to details if on mobile/small screen
            document.getElementById('detailPanel').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function closeDetail() { document.getElementById('detailPanel').style.display = 'none'; }

        function zoomImage(src) {
            document.getElementById('zoomedImage').src = src;
            document.getElementById('imageZoomModal').style.display = 'flex';
        }

        function closeZoomModal() {
            document.getElementById('imageZoomModal').style.display = 'none';
        }

        let currentMapUrl = '';
        function openMapModal(name, time, lat, lng, status, type, locationSource, distance) {
            document.getElementById('mapModalTitle').innerText = name + ' · ' + time;
            document.getElementById('mapIframe').src = 'https://www.google.com/maps?q=' + lat + ',' + lng + '&output=embed';
            document.getElementById('mapCoords').innerText = lat + '° N, ' + lng + '° E';
            document.getElementById('mapStatus').innerText = status;
            document.getElementById('mapType').innerText = type;
            document.getElementById('mapZone').innerText = locationSource;
            document.getElementById('mapDistance').innerText = distance + 'm from reference point';
            currentMapUrl = 'https://www.google.com/maps?q=' + lat + ',' + lng;
            
            document.getElementById('mapModal').style.display = 'flex';
        }

        function openExternalMap() {
            if(currentMapUrl) window.open(currentMapUrl, '_blank');
        }

        fetchLiveNotifications();
        // setInterval(fetchLiveNotifications, 10000); // Stopped to save quota
    </script>
</body>
</html>