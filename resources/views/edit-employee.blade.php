<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Edit Employee - DOREMi Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <style>
        .form-container { max-width: 600px; margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 13px; color: #374151; }
        .form-input { 
            width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; 
            box-sizing: border-box; 
        }
        .form-input:focus { border-color: #D6001C; outline: none; }
        .btn-submit { 
            background-color: #D6001C; color: white; width: 100%; padding: 12px; 
            border: none; border-radius: 6px; font-weight: 600; cursor: pointer; 
        }
        .btn-submit:hover { background-color: #b90018; }
        .section-title {
            margin-bottom: 15px; font-size: 14px; color: #D6001C; font-weight: 700; 
            border-bottom: 1px solid #eee; padding-bottom: 10px;
        }

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
        .topbar-right { display: flex; align-items: center; gap: 20px; }
        .icon-btn { background: #f3f4f6; border: none; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; position: relative; }
        .badge { position: absolute; top: 0; right: 0; background: #D6001C; color: white; font-size: 10px; padding: 2px 5px; border-radius: 50%; border: 2px solid white; }
    </style>
</head>
<body>
  <div class="app">
    <aside class="sidebar">
        <div class="brand"><img src="{{ asset('img/Doremi logo.png') }}" alt="logo" class="brand-logo"></div>
        <nav class="menu">
          <a href="{{ route('directory') }}" style="text-decoration: none; color: inherit;">
             <button class="menu-item active"> <span class="icon">⬅️</span> <span class="label">Cancel & Back</span></button>
          </a>
        </nav>
    </aside>

    <main class="main">
      <header class="topbar">
          <h1 class="page-title">Edit Employee Details</h1>
          <div class="topbar-right">
              <div style="position: relative;">
                  <button class="icon-btn" id="notifToggle">
                      🔔 <span class="badge" id="notifBadge" style="display: none;">0</span>
                  </button>
                  
                  <div class="notif-dropdown" id="notifPanel">
                      <div class="notif-header">
                          <span style="font-weight: 700; font-size: 14px;">Notifications</span>
                          <button onclick="markAllRead()" style="background:none; border:none; color:#D6001C; font-size:12px; cursor:pointer;">Mark all read</button>
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
        <div class="panel form-container">
            <div class="panel-header">
                <h3>Update Information: {{ $employee->full_name }}</h3>
            </div>

            <form id="editEmployeeForm" method="POST" action="{{ route('employees.update', $employee->id) }}" style="padding: 20px;">
                @csrf 
                @method('PUT') 
                
                <div class="section-title">Personal Information</div>

                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-input" value="{{ old('full_name', $employee->full_name) }}" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-input" value="{{ old('email', $employee->email) }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone_number" class="form-input" value="{{ old('phone_number', $employee->phone_number) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Address</label>
                    <input type="text" name="address" class="form-input" value="{{ old('address', $employee->address) }}">
                </div>

                <div class="section-title" style="margin-top: 20px;">Job Details</div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <select name="department" class="form-input" required>
                            <option value="Audio" {{ $employee->department == 'Audio' ? 'selected' : '' }}>Audio</option>
                            <option value="Lighting" {{ $employee->department == 'Lighting' ? 'selected' : '' }}>Lighting</option>
                            <option value="Visual" {{ $employee->department == 'Visual' ? 'selected' : '' }}>Visual</option>
                            <option value="Crew" {{ $employee->department == 'Crew' ? 'selected' : '' }}>General Crew</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-input" required>
                            <option value="staff" {{ $employee->role == 'staff' ? 'selected' : '' }}>Staff</option>
                            <option value="supervisor" {{ $employee->role == 'supervisor' ? 'selected' : '' }}>Supervisor</option>
                        </select>
                    </div>
                </div>

                <div style="margin-top: 25px;">
                    <button type="submit" class="btn-submit">Save Changes</button>
                </div>
            </form>
        </div>
      </section>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
      document.getElementById('editEmployeeForm').onsubmit = function(e) {
          e.preventDefault();
          Swal.fire({
              title: 'Confirm Update?',
              text: "Are you sure you want to save these changes to Firestore?",
              icon: 'question',
              showCancelButton: true,
              confirmButtonColor: '#D6001C',
              cancelButtonColor: '#374151',
              confirmButtonText: 'Yes, Update!'
          }).then((result) => {
              if (result.isConfirmed) {
                  this.submit();
              }
          });
      };

      // Notification Dropdown Polling System
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