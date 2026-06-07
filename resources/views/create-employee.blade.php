<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Add New Employee - DOREMi Admin</title>
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
        .form-input:focus { border-color: #D6001C; outline: none; box-shadow: 0 0 0 2px rgba(214, 0, 28, 0.1); }
        .btn-submit { background-color: #D6001C; color: white; width: 100%; padding: 12px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .btn-submit:hover { background-color: #b90018; }
        .alert-error { background-color: #fee2e2; color: #991b1b; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 13px; }

        /* NOTIFICATION & TOPBAR SYSTEM */
        :root { --doremi-red: #D6001C; }
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
  <div class="app">
    <aside class="sidebar">
       <div class="brand"><img src="{{ asset('img/Doremi logo.png') }}" alt="logo" class="brand-logo"></div>
       <nav class="menu">
         <a href="{{ route('directory') }}" style="text-decoration: none; color: inherit;">
            <button class="menu-item active"> <span class="icon">⬅️</span> <span class="label">Back to Directory</span></button>
         </a>
       </nav>
    </aside>

    <main class="main">
      <header class="topbar">
          <h1 class="page-title">Register New Employee</h1>
          
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
        <div class="panel form-container">
            <div class="panel-header">
                <h3>Employee Details</h3>
            </div>

            @if ($errors->any())
                <div class="alert-error">
                    <ul style="margin:0; padding-left:20px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="registerEmployeeForm" method="POST" action="{{ route('employee.store') }}" style="padding: 20px;">
                @csrf 
                
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-input" placeholder="e.g. Siti Sarah" value="{{ old('full_name') }}" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-input" placeholder="sarah@doremi.com" value="{{ old('email') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone_number" class="form-input" placeholder="012-3456789" value="{{ old('phone_number') }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Home Address</label>
                    <input type="text" name="address" class="form-input" placeholder="Lot 123, Jalan Doremi..." value="{{ old('address') }}">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">Job Title</label>
                        <input type="text" name="job_title" class="form-input" placeholder="e.g. Audio Technician" value="{{ old('job_title') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <select name="department" class="form-input" required>
                            <option value="Audio" {{ old('department') == 'Audio' ? 'selected' : '' }}>Audio</option>
                            <option value="Lighting" {{ old('department') == 'Lighting' ? 'selected' : '' }}>Lighting</option>
                            <option value="Visual" {{ old('department') == 'Visual' ? 'selected' : '' }}>Visual</option>
                            <option value="Crew" {{ old('department') == 'Crew' ? 'selected' : '' }}>General Crew</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-input" required>
                            <option value="staff" {{ old('role') == 'staff' ? 'selected' : '' }}>Staff</option>
                            <option value="supervisor" {{ old('role') == 'supervisor' ? 'selected' : '' }}>Supervisor</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Join Date</label>
                        <input type="date" name="join_date" class="form-input" required value="{{ old('join_date', date('Y-m-d')) }}">
                    </div>
                </div>

                <div style="margin-top: 20px;">
                    <button type="submit" class="btn-submit">Create Employee Account</button>
                    <p style="text-align: center; font-size: 11px; color: #888; margin-top: 10px;">
                        Data will be saved directly to Cloud Firestore.
                    </p>
                </div>
            </form>
        </div>
      </section>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
      document.getElementById('registerEmployeeForm').onsubmit = function(e) {
          e.preventDefault();
          const form = this;
          
          Swal.fire({
              title: 'Confirm Registration?',
              text: "Ensure all staff details are correct before saving to Firestore.",
              icon: 'warning',
              showCancelButton: true,
              confirmButtonColor: '#D6001C',
              cancelButtonColor: '#374151',
              confirmButtonText: 'Yes, Save it!'
          }).then((result) => {
              if (result.isConfirmed) {
                  // Show loading state
                  Swal.fire({
                      title: 'Registering...',
                      text: 'Please wait while we create the account and sync to Firestore',
                      allowOutsideClick: false,
                      didOpen: () => {
                          Swal.showLoading()
                      }
                  });

                  // Perform fetch request
                  fetch(form.action, {
                      method: 'POST',
                      body: new FormData(form),
                      headers: {
                          'Accept': 'application/json'
                      }
                  })
                  .then(response => response.json().then(data => ({status: response.status, body: data})))
                  .then(res => {
                      if (res.status === 200 && res.body.success) {
                          Swal.fire(
                              'Success!',
                              'Staff has been registered successfully.',
                              'success'
                          ).then(() => {
                              window.location.href = "{{ route('directory') }}";
                          });
                      } else if (res.status === 422) {
                          // Validation error
                          let errorMsg = res.body.message || 'Validation Failed';
                          if (res.body.errors) {
                              errorMsg = Object.values(res.body.errors).flat().join('<br>');
                          }
                          Swal.fire('Validation Error', errorMsg, 'error');
                      } else {
                          // Other API errors (Auth or Firestore)
                          Swal.fire('Error', res.body.message || 'An unexpected error occurred.', 'error');
                      }
                  })
                  .catch(error => {
                      Swal.fire('Error', 'Network error or server unreachable.', 'error');
                      console.error('Error:', error);
                  });
              }
          });
      };

      // Live notification dropdown polling scripts
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

      fetchLiveNotifications();
      setInterval(fetchLiveNotifications, 10000);
  </script>
</body>
</html>