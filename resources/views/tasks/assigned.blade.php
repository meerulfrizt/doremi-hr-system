<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Assigned Tasks - DOREMi Admin</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    
    <style>
        :root {
            var(--doremi-red): #D6001C; 
            --sidebar-bg: #D6001C;
            --bg-light: #F9FAFB;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }

        body { background-color: var(--bg-light); display: flex; height: 100vh; overflow: hidden; }

        .sidebar {
            width: 260px;
            background-color: var(--sidebar-bg);
            color: white;
            display: flex;
            flex-direction: column;
            transition: all 0.3s;
        }

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

        .menu { flex: 1; padding: 10px 0; overflow-y: auto; }
        .menu a { text-decoration: none; color: white; display: block; }
        
        .menu-item {
            width: 100%; padding: 12px 25px; display: flex; align-items: center;
            gap: 15px; background: none; border: none; color: white;
            font-size: 14px; cursor: pointer; transition: 0.2s; text-align: left;
        }

        .menu-item:hover { background: rgba(255, 255, 255, 0.1); color: white; }
        .menu-item.active { background: white; color: #111827 !important; font-weight: 700; border-radius: 50px 0 0 50px; margin-left: 15px; width: calc(100% - 15px); }

        .menu-group-title { padding: 20px 25px 10px; font-size: 11px; font-weight: 700; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 1px; }

        .main { flex: 1; display: flex; flex-direction: column; overflow-y: auto; }
        
        .topbar { background: white; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #edf2f7; position: sticky; top: 0; z-index: 10;}
        .topbar h2 { font-size: 18px; color: #111827; }
        .topbar-right { display: flex; align-items: center; gap: 20px; }

        .content { padding: 30px; }
        .panel { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 30px;}
        .panel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group label { font-size: 13px; font-weight: 600; color: #374151; }
        .form-group input, .form-group select { padding: 10px 15px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none; }
        .form-group input:focus, .form-group select:focus { border-color: #D6001C; }
        .form-full { grid-column: 1 / -1; }

        .table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table th { text-align: left; padding: 12px; color: #6b7280; font-size: 13px; border-bottom: 1px solid #f3f4f6; }
        .table td { padding: 15px 12px; font-size: 14px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; color: #111827; }

        .status-badge { padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; }
        .status-badge.active { background: #dcfce7; color: #166534; }
        .status-badge.expired { background: #f3f4f6; color: #4b5563; }
        .status-badge.cancelled { background: #fee2e2; color: #b91c1c; }

        .btn-doremi { background: #D6001C; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-doremi:hover { opacity: 0.9; }
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; font-weight: 500; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

        /* --- NOTIFICATION & USER STYLE --- */
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
            font-size: 18px;
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

        /* --- SEARCH AUTOCOMPLETE SUGGESTIONS --- */
        .search-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            max-height: 250px;
            overflow-y: auto;
            margin-top: 5px;
            display: none;
        }
        .suggestion-item {
            padding: 12px 15px;
            cursor: pointer;
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.2s;
            display: flex;
            flex-direction: column;
            gap: 2px;
            text-align: left;
        }
        .suggestion-item:last-child {
            border-bottom: none;
        }
        .suggestion-item:hover {
            background: #f3f4f6;
        }
        .suggestion-title {
            font-weight: 700;
            font-size: 13px;
            color: #111827;
        }
        .suggestion-subtitle {
            font-size: 11px;
            color: #6b7280;
        }
        @keyframes spin {
            to { transform: translateY(-50%) rotate(360deg); }
        }

        /* --- STAFF CHECKBOX GRID --- */
        .staff-checkbox-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
            max-height: 180px;
            overflow-y: auto;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 15px;
            background: #f9fafb;
            width: 100%;
        }
        .staff-checkbox-item {
            display: flex;
            align-items: center;
            gap: 10px;
            background: white;
            padding: 8px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            user-select: none;
        }
        .staff-checkbox-item:hover {
            border-color: #D6001C;
            background: #fff5f5;
        }
        .staff-checkbox-item input[type="checkbox"] {
            accent-color: #D6001C;
            width: 16px;
            height: 16px;
            cursor: pointer;
            margin: 0;
        }
        .staff-avatar-mini {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            color: #374151;
            text-transform: uppercase;
        }
        .staff-name-label {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
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
            <a href="{{ route('attendance') }}"><button class="menu-item">🕒 Attendance</button></a>
            
            <div class="menu-group-title">Management</div>
            <a href="{{ route('leave') }}"><button class="menu-item">🗓️ Leave Requests</button></a>
            <a href="{{ route('overtime') }}"><button class="menu-item">⏱️ Overtime (OT)</button></a>
            <a href="{{ route('flexible') }}"><button class="menu-item">🔁 Flexible Hours</button></a>
            <a href="{{ route('tasks.assigned') }}"><button class="menu-item active">📍 Assigned Tasks</button></a>
            
            <div class="menu-group-title">Reports</div>
            <a href="{{ route('analytics') }}"><button class="menu-item">📊 Analytics</button></a>
            <a href="{{ route('directory') }}"><button class="menu-item">👥 Directory</button></a>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <h2>Assigned Tasks</h2>
            <div class="topbar-right">
                <div style="position: relative;">
                    <button class="icon-btn" id="notifToggle">
                        🔔 <span class="badge" id="notifBadge">0</span>
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

        <section class="content">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="panel">
                <div class="panel-header">
                    <h3 style="font-size:16px; font-weight:700; color:#111;">Assign New Location Task</h3>
                </div>
                <form id="assignTaskForm" action="{{ route('tasks.store') }}" method="POST">
                    @csrf
                    <div class="form-grid">
                        <div class="form-group form-full">
                            <label>Select Staff Members (Assign one or more)</label>
                            
                            <!-- Premium Staff Search Bar -->
                            <div style="position: relative; margin-bottom: 10px; width: 100%;">
                                <input type="text" id="staffSearchInput" placeholder="Type staff name to filter..." autocomplete="off" style="width: 100%; padding: 10px 15px 10px 40px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; outline: none;">
                                <span style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 13px; pointer-events: none;">🔍</span>
                            </div>

                            <div class="staff-checkbox-container" id="staffCheckboxList" style="display: none;">
                                @foreach($users as $user)
                                <label class="staff-checkbox-item" data-name="{{ strtolower($user->name) }}">
                                    <input type="checkbox" name="uids[]" value="{{ $user->uid }}">
                                    <div class="staff-avatar-mini">{{ substr($user->name, 0, 1) }}</div>
                                    <span class="staff-name-label">{{ $user->name }}</span>
                                </label>
                                @endforeach
                            </div>
                            <small style="color:#6b7280; font-size:11px;">Tick the checkboxes next to the staff members you want to assign to this location. Checked selections are preserved while searching.</small>
                        </div>
                        <div class="form-group">
                            <label>Task Date</label>
                            <input type="date" name="task_date" required min="{{ date('Y-m-d') }}">
                        </div>
                        <div class="form-group form-full" style="position: relative;">
                            <label>Location Name</label>
                            <div style="position: relative; width: 100%;">
                                <input type="text" id="location_name" name="location_name" placeholder="Search and select location e.g. Setia City Convention Centre" autocomplete="off" required style="width: 100%; padding-right: 40px;">
                                <div id="suggestions" class="search-suggestions"></div>
                                <div id="search-spinner" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); display: none; width: 18px; height: 18px; border: 2px solid #f3f4f6; border-top-color: #D6001C; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
                            </div>
                            <small style="color:#6b7280; font-size:11px;">Search for any place/venue name above. Selecting a suggestion automatically aligns the map and updates the marker.</small>
                        </div>
                        <div class="form-group form-full">
                            <label>Select Location on Map</label>
                            <div id="map" style="height: 300px; width: 100%; border-radius: 8px; border: 1px solid #d1d5db; z-index: 1;"></div>
                            <small style="color:#6b7280; font-size:11px;">Drag the blue marker to pinpoint the exact location.</small>
                        </div>
                        <div class="form-group">
                            <label>Latitude</label>
                            <input type="number" step="any" name="latitude" id="latitude" placeholder="3.0972881" required>
                        </div>
                        <div class="form-group">
                            <label>Longitude</label>
                            <input type="number" step="any" name="longitude" id="longitude" placeholder="101.683066" required>
                        </div>
                        <div class="form-group">
                            <label>Radius (Meters)</label>
                            <input type="number" name="radius_meters" value="200" min="50" max="2000" required>
                        </div>
                        <div class="form-group form-full" style="align-items: flex-end; margin-top: 10px;">
                            <button type="submit" class="btn-doremi">Assign Task</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <h3 style="font-size:16px; font-weight:700; color:#111;">Task History</h3>
                </div>
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Staff</th>
                                <th>Location</th>
                                <th>Radius</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tasks as $row)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($row->task_date)->format('d M Y') }}</td>
                                <td style="font-weight:600;">{{ $row->staff_name }}</td>
                                <td>
                                    <div style="font-weight:600;">{{ $row->location_name }}</div>
                                    <div style="font-size:11px; color:#6b7280; margin-top:4px;">
                                        <a href="https://www.google.com/maps?q={{ $row->latitude }},{{ $row->longitude }}" target="_blank" style="color:#2563eb; text-decoration:none;">
                                            {{ $row->latitude }}, {{ $row->longitude }} ↗
                                        </a>
                                    </div>
                                </td>
                                <td>{{ $row->radius_meters }}m</td>
                                <td>
                                    <span class="status-badge {{ strtolower($row->status) }}">
                                        {{ $row->status }}
                                    </span>
                                </td>
                                <td>
                                    @if($row->status === 'Active')
                                        <form action="{{ route('tasks.cancel', $row->id) }}" method="POST" onsubmit="return confirm('Cancel this assigned task?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" style="background:none; border:none; color:#b91c1c; font-weight:600; font-size:12px; cursor:pointer; text-decoration:underline;">
                                                Cancel Task
                                            </button>
                                        </form>
                                    @else
                                        <span style="color:#9ca3af; font-size:12px;">—</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" style="text-align:center; padding:30px; color:#6b7280;">No tasks assigned yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const hqLat = {{ config('services.hq.lat', 3.0972881) }};
            const hqLng = {{ config('services.hq.lng', 101.683066) }};
            
            const map = L.map('map').setView([hqLat, hqLng], 14);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap'
            }).addTo(map);

            let marker = L.marker([hqLat, hqLng], {draggable: true}).addTo(map);
            
            const latInput = document.getElementById('latitude');
            const lngInput = document.getElementById('longitude');

            // Set initial values
            latInput.value = hqLat;
            lngInput.value = hqLng;

            // Update inputs on marker drag
            marker.on('dragend', function (e) {
                const position = marker.getLatLng();
                latInput.value = position.lat.toFixed(7);
                lngInput.value = position.lng.toFixed(7);
            });

            // Update map on input change
            function updateMarkerFromInput() {
                const lat = parseFloat(latInput.value);
                const lng = parseFloat(lngInput.value);
                if(!isNaN(lat) && !isNaN(lng)) {
                    const newLatLng = new L.LatLng(lat, lng);
                    marker.setLatLng(newLatLng);
                    map.panTo(newLatLng);
                }
            }

            latInput.addEventListener('input', updateMarkerFromInput);
            lngInput.addEventListener('input', updateMarkerFromInput);

            // ── NOMINATIM GEOLOCATION AUTOCOMPLETE SEARCH ──
            const locationInput = document.getElementById('location_name');
            const suggestionsContainer = document.getElementById('suggestions');
            const spinner = document.getElementById('search-spinner');
            let searchTimeout = null;

            locationInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const query = this.value.trim();

                if (query.length < 3) {
                    suggestionsContainer.style.display = 'none';
                    return;
                }

                // Debounce by 600ms to respect OpenStreetMap Nominatim request guidelines
                searchTimeout = setTimeout(async function() {
                    spinner.style.display = 'block';
                    try {
                        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&addressdetails=1&limit=5&countrycodes=my`;
                        const response = await fetch(url, {
                            headers: {
                                'Accept-Language': 'en,ms'
                            }
                        });
                        const results = await response.json();
                        
                        suggestionsContainer.innerHTML = '';
                        if (results && results.length > 0) {
                            results.forEach(item => {
                                const displayName = item.display_name;
                                const parts = displayName.split(',');
                                const title = parts[0];
                                const subtitle = parts.slice(1).join(',').trim();

                                const div = document.createElement('div');
                                div.className = 'suggestion-item';
                                div.innerHTML = `
                                    <span class="suggestion-title">📍 ${escHtml(title)}</span>
                                    <span class="suggestion-subtitle">${escHtml(subtitle)}</span>
                                `;
                                
                                div.addEventListener('click', function() {
                                    locationInput.value = title.trim();
                                    const lat = parseFloat(item.lat);
                                    const lon = parseFloat(item.lon);

                                    latInput.value = lat.toFixed(7);
                                    lngInput.value = lon.toFixed(7);

                                    // Update Leaflet Marker & View
                                    const newLatLng = new L.LatLng(lat, lon);
                                    marker.setLatLng(newLatLng);
                                    map.setView(newLatLng, 16);

                                    suggestionsContainer.style.display = 'none';
                                });
                                suggestionsContainer.appendChild(div);
                            });
                            suggestionsContainer.style.display = 'block';
                        } else {
                            suggestionsContainer.innerHTML = '<div style="padding:15px; text-align:center; color:#888; font-size:12px;">No locations found 🔍</div>';
                            suggestionsContainer.style.display = 'block';
                        }
                    } catch (e) {
                        console.error('Search error:', e);
                    } finally {
                        spinner.style.display = 'none';
                    }
                }, 600);
            });

            // Hide suggestions box on outside click
            document.addEventListener('click', function(e) {
                if (!suggestionsContainer.contains(e.target) && e.target !== locationInput) {
                    suggestionsContainer.style.display = 'none';
                }
            });

            function escHtml(s) {
                return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
            }

            // Client-side validation: Ensure at least one checkbox is checked
            document.getElementById('assignTaskForm').addEventListener('submit', function(e) {
                const checked = document.querySelectorAll('input[name="uids[]"]:checked');
                if (checked.length === 0) {
                    e.preventDefault();
                    alert('Please select at least one staff member to assign!');
                }
            });

            // ── REAL-TIME STAFF CHECKBOX FILTER ──
            const staffSearchInput = document.getElementById('staffSearchInput');
            const staffCheckboxList = document.getElementById('staffCheckboxList');
            const staffItems = document.querySelectorAll('.staff-checkbox-item');

            staffSearchInput.addEventListener('input', function() {
                const filter = this.value.trim().toLowerCase();

                if (filter.length === 0) {
                    staffCheckboxList.style.display = 'none';
                    return;
                }

                staffCheckboxList.style.display = 'grid';

                staffItems.forEach(item => {
                    const staffName = item.getAttribute('data-name');
                    if (staffName.includes(filter)) {
                        item.style.display = 'flex';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });

        // ── NOTIFICATION DRAWER ──
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

        // Sedut data dari API
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
