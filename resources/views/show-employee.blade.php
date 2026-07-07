<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Employee Profile - DOREMi Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root { --doremi-red: #D6001C; --sidebar-bg: #D6001C; --bg-light: #F9FAFB; }
        
        /* RESET & BASE */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        html, body { height: 100%; width: 100%; overflow: hidden; }

        /* BODY LAYOUT - FORCE FULL WIDTH */
        body { background-color: var(--bg-light); display: flex; }

        /* SIDEBAR - LOCKED WIDTH */
        .sidebar { width: 260px; min-width: 260px; background-color: var(--sidebar-bg); color: white; display: flex; flex-direction: column; height: 100vh; }
        .brand { padding: 40px 20px; display: flex; justify-content: center; align-items: center; }
        .brand img { width: 170px; height: auto; filter: brightness(0) invert(1); }
        .menu { flex: 1; padding: 10px 0; }
        .menu a { text-decoration: none; display: block; }
        .menu-item.active { background: white; color: #111827 !important; font-weight: 700; border-radius: 50px 0 0 50px; margin-left: 15px; width: calc(100% - 15px); padding: 15px 25px; display: flex; align-items: center; gap: 12px; border: none; cursor: pointer; }

        /* MAIN CONTENT - THE FIX FOR FULL WIDTH */
        .main { 
            flex: 1; 
            display: flex; 
            flex-direction: column; 
            height: 100vh; 
            overflow-y: auto; 
            width: 100%;
        }

        /* TOPBAR - SPREAD TO EDGE */
        .topbar { 
            background: white; 
            padding: 15px 40px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            border-bottom: 1px solid #edf2f7; 
            position: sticky; 
            top: 0; 
            z-index: 10;
            width: 100%;
        }
        
        .topbar-right { display: flex; align-items: center; gap: 25px; }
        .icon-btn { background: #f3f4f6; border: none; width: 42px; height: 42px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; position: relative; }
        .badge { position: absolute; top: 0; right: 0; background: var(--doremi-red); color: white; font-size: 10px; padding: 2px 6px; border-radius: 50%; border: 2px solid white; }

        .profile-container { display: flex; align-items: center; gap: 15px; border-left: 1px solid #e5e7eb; padding-left: 25px; }
        .user-avatar { width: 42px; height: 42px; background: #f3f4f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; border: 1px solid #e5e7eb; color: #111827; }
        .admin-info { display: flex; flex-direction: column; line-height: 1.2; }
        .logout-link { color: var(--doremi-red); font-size: 11px; font-weight: 700; cursor: pointer; border: none; background: none; text-align: left; }

        /* CONTENT SECTION - STRETCH TO WIDTH */
        .content { 
            padding: 40px; 
            width: 100%; 
            max-width: none !important; 
        }

        /* HEADER CARD - FULL STRETCH */
        .profile-main-card { 
            background: white; 
            padding: 30px; 
            border-radius: 15px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 30px; 
            width: 100%;
        }
        .info-left { display: flex; align-items: center; gap: 25px; }
        .avatar-circle { width: 90px; height: 90px; background: var(--doremi-red); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 36px; font-weight: 700; text-transform: uppercase; }

        /* LEAVE GRID - 5 COLUMNS (4 balance + 1 AI advisor) */
        .leave-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 25px;
            margin-bottom: 30px;
            width: 100%;
        }
        .leave-box { 
            background: white; 
            padding: 35px 20px; 
            border-radius: 16px; 
            text-align: center; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.04); 
            border-bottom: 6px solid #eee; 
        }
        .val-big { font-size: 42px; font-weight: 800; color: #111; display: block; margin-bottom: 8px; }
        .label-small { font-size: 13px; color: #6b7280; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }

        /* ── FLEXI ADVISOR + KPI ADD-ON STYLES ── */
        .flexi-advisor-card { background:linear-gradient(135deg,#6366f1 0%,#8b5cf6 100%); border-radius:16px; padding:22px 16px; text-align:center; box-shadow:0 4px 15px rgba(99,102,241,.3); color:white; display:flex; flex-direction:column; justify-content:space-between; border-bottom:6px solid rgba(0,0,0,.15); }
        .flexi-advisor-balance { font-size:36px; font-weight:800; display:block; margin-bottom:4px; }
        .flexi-advisor-label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:rgba(255,255,255,.8); }
        .flexi-burnout-badge { background:rgba(255,255,255,.2); border:1px solid rgba(255,255,255,.4); border-radius:6px; padding:4px 8px; font-size:10px; font-weight:700; margin:6px auto 0; display:inline-block; }
        .btn-flexi-advice { background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.4); color:white; padding:7px 14px; border-radius:7px; font-size:11px; font-weight:700; cursor:pointer; margin-top:10px; transition:all .2s; width:100%; }
        .btn-flexi-advice:hover { background:rgba(255,255,255,.3); }
        #flexi-advice-box { margin-top:10px; background:rgba(255,255,255,.12); border-radius:8px; padding:10px; font-size:11px; line-height:1.6; text-align:left; display:none; }
        /* KPI modal */
        #kpi-modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:998; backdrop-filter:blur(3px); }
        #kpi-modal { display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); width:580px; max-width:95vw; max-height:85vh; background:#fff; border-radius:20px; z-index:999; box-shadow:0 20px 60px rgba(0,0,0,.2); overflow:hidden; flex-direction:column; }
        #kpi-modal.show { display:flex; }
        #kpi-modal-header { background:linear-gradient(135deg,#111827,#374151); padding:22px 26px; display:flex; justify-content:space-between; align-items:center; flex-shrink:0; }
        #kpi-modal-header h3 { color:#fff; font-size:16px; font-weight:800; margin:0; }
        #kpi-modal-header p  { color:rgba(255,255,255,.6); font-size:12px; margin:3px 0 0; }
        #kpi-modal-close2 { background:rgba(255,255,255,.15); border:none; color:#fff; width:30px; height:30px; border-radius:50%; cursor:pointer; font-size:16px; }
        #kpi-modal-body { padding:26px; overflow-y:auto; flex:1; }
        .kpi-paragraph { font-size:14px; line-height:1.85; color:#1f2937; background:#f9fafb; border:1px solid #e5e7eb; border-radius:12px; padding:20px; white-space:pre-line; }
        .btn-kpi-generate { background:linear-gradient(135deg,#111827,#374151); color:white; border:none; padding:10px 20px; border-radius:8px; font-weight:700; font-size:13px; cursor:pointer; display:inline-flex; align-items:center; gap:8px; transition:all .2s; box-shadow:0 4px 14px rgba(0,0,0,.25); }
        .btn-kpi-generate:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(0,0,0,.3); }
        .btn-copy-kpi { background:#f3f4f6; border:1px solid #d1d5db; padding:8px 16px; border-radius:7px; font-size:12px; font-weight:700; cursor:pointer; margin-top:14px; width:100%; }
        
        /* TABLE PANEL - FULL WIDTH */
        .history-panel { 
            background: white; 
            border-radius: 16px; 
            padding: 35px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); 
            width: 100%;
        }
        .btn-edit { background: #f3f4f6; border: 1px solid #ddd; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-edit:hover { background: #e5e7eb; }

        /* ── AI INSIGHT ADD-ON STYLES ────────────────────────────────────── */
        .btn-ai-insight {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border: none;
            padding: 10px 22px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(99,102,241,0.35);
        }
        .btn-ai-insight:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(99,102,241,0.45);
        }
        .btn-ai-insight:active { transform: translateY(0); }

        /* Slide-over overlay */
        #ai-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.35);
            z-index: 999;
            backdrop-filter: blur(2px);
            animation: fadeInOverlay 0.25s ease;
        }
        @keyframes fadeInOverlay { from { opacity: 0; } to { opacity: 1; } }

        /* Slide-over panel */
        #ai-panel {
            position: fixed;
            top: 0;
            right: -540px;
            width: 520px;
            height: 100vh;
            background: white;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            box-shadow: -8px 0 40px rgba(0,0,0,0.18);
            transition: right 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 20px 0 0 20px;
        }
        #ai-panel.open { right: 0; }

        #ai-panel-header {
            padding: 28px 30px 20px;
            border-bottom: 1px solid #f0f0f5;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border-radius: 20px 0 0 0;
            flex-shrink: 0;
        }
        #ai-panel-header h2 { color: white; font-size: 18px; font-weight: 800; margin: 0; }
        #ai-panel-header p  { color: rgba(255,255,255,0.75); font-size: 13px; margin: 4px 0 0; }

        .ai-close-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            width: 36px; height: 36px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 18px;
            color: white;
            display: flex; align-items: center; justify-content: center;
            transition: background 0.2s;
            flex-shrink: 0;
            margin-top: -2px;
        }
        .ai-close-btn:hover { background: rgba(255,255,255,0.35); }

        #ai-panel-body {
            flex: 1;
            overflow-y: auto;
            padding: 28px 30px;
        }

        /* Stats row inside panel */
        .ai-stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-bottom: 28px;
        }
        .ai-stat-box {
            background: #f8f9ff;
            border-radius: 12px;
            padding: 18px 14px;
            text-align: center;
            border: 1px solid #ede9fe;
        }
        .ai-stat-num { font-size: 28px; font-weight: 800; display: block; }
        .ai-stat-lbl { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #6b7280; margin-top: 4px; }
        .ai-stat-present { color: #10b981; }
        .ai-stat-late    { color: #f59e0b; }
        .ai-stat-absent  { color: #ef4444; }

        /* Summary box */
        .ai-summary-box {
            background: #fafafa;
            border: 1px solid #ede9fe;
            border-left: 4px solid #6366f1;
            border-radius: 12px;
            padding: 22px 20px;
            font-size: 14px;
            line-height: 1.75;
            color: #374151;
            white-space: pre-wrap;
        }

        /* Loading state */
        .ai-loading {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
            gap: 18px;
        }
        .ai-spinner {
            width: 48px; height: 48px;
            border: 4px solid #ede9fe;
            border-top-color: #6366f1;
            border-radius: 50%;
            animation: spin 0.85s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .ai-loading p { color: #6b7280; font-size: 14px; font-weight: 500; }

        /* Error state */
        .ai-error {
            background: #fff5f5;
            border: 1px solid #fecaca;
            border-radius: 12px;
            padding: 20px;
            color: #dc2626;
            font-size: 14px;
        }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; color: #6b7280; font-size: 14px; border-bottom: 2px solid #f3f4f6; }
        td { padding: 20px 15px; font-size: 15px; border-bottom: 1px solid #f3f4f6; color: #111; }

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
        .user-avatar-small {
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
        <div class="brand"><img src="{{ asset('img/Doremi logo.png') }}" alt="DOREMi"></div>
        <nav class="menu">
            <a href="{{ route('directory') }}"><button class="menu-item active">⬅️ Back to Directory</button></a>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <h1 style="font-size: 22px; font-weight: 800; color: #111;">Employee Profile</h1>
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
                    <div class="user-avatar-small">
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
            <div class="profile-main-card">
                <div class="info-left">
                    <div class="avatar-circle">{{ substr($employee->full_name, 0, 1) }}</div>
                    <div>
                        <h2 style="font-size: 26px; color: #111; font-weight: 800;">{{ $employee->full_name }}</h2>
                        <p style="color: #6b7280; font-size: 15px; margin-top: 4px;">
                            <span style="background:#fff1f2; color:var(--doremi-red); padding:2px 10px; border-radius:20px; font-size:12px; font-weight:700; border:1px solid #fecdd3;">{{ $employee->department }}</span>
                            <span style="background:#f0fdf4; color:#15803d; padding:2px 10px; border-radius:20px; font-size:12px; font-weight:700; border:1px solid #86efac; margin-left:6px;">{{ ucfirst($employee->role ?? 'Staff') }}</span>
                        </p>
                        <div style="display:flex; gap:20px; margin-top:10px; flex-wrap:wrap;">
                            @if($employee->email)
                            <span style="font-size:12px; color:#6b7280; display:flex; align-items:center; gap:5px;">
                                <span style="font-size:14px;">📧</span> {{ $employee->email }}
                            </span>
                            @endif
                            @if($employee->phone_number)
                            <span style="font-size:12px; color:#6b7280; display:flex; align-items:center; gap:5px;">
                                <span style="font-size:14px;">📞</span> {{ $employee->phone_number }}
                            </span>
                            @endif
                            @if($employee->join_date)
                            <span style="font-size:12px; color:#6b7280; display:flex; align-items:center; gap:5px;">
                                <span style="font-size:14px;">📅</span> Joined {{ \Carbon\Carbon::parse($employee->join_date)->format('d M Y') }}
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
                <div style="display:flex; gap:12px; align-items:center;">
                    <button class="btn-edit" onclick="window.location.href='{{ route('employees.edit', $employee->id) }}'">Edit Profile Details</button>
                    <button id="btn-ai-insight" class="btn-ai-insight" onclick="openAIPanel()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1 1 .03 2.699-1.42 2.275l-1.98-.554m-6.4-1.423l-.994.99a2.25 2.25 0 01-3.183 0l-.994-.99"/></svg>
                        AI Insight
                    </button>
                    <button class="btn-kpi-generate" onclick="openKPIModal()">&#128203; Generate KPI Review</button>
                </div>
            </div>

            <div class="leave-grid">
                <div class="leave-box" style="border-bottom-color: #3b82f6;">
                    <span class="val-big" id="al_val">{{ $employee->al_balance }}</span>
                    <span class="label-small">Annual Leave (AL)</span>
                </div>
                <div class="leave-box" style="border-bottom-color: #f59e0b;">
                    <span class="val-big" id="el_val">{{ $employee->el_balance }}</span>
                    <span class="label-small">Emergency Leave (EL)</span>
                </div>
                <div class="leave-box" style="border-bottom-color: #10b981;">
                    <span class="val-big" id="mc_val">{{ $employee->mc_balance }}</span>
                    <span class="label-small">Medical Leave (MC)</span>
                </div>
                <div class="leave-box" style="border-bottom-color: #6366f1;">
                    <span class="val-big" style="color: #6366f1;"><span id="flexi_val">{{ $employee->flexi_credit ?? 0 }}</span><small style="font-size: 18px; margin-left: 4px;">H</small></span>
                    <span class="label-small">Flexi Credit (Balance)</span>
                </div>
                {{-- 5th Card: Flexi AI Advisor --}}
                <div class="flexi-advisor-card">
                    <div>
                        <span class="flexi-advisor-balance" id="flexi_advisor_val">{{ $employee->flexi_credit ?? 0 }}<small style="font-size:16px;">H</small></span>
                        <span class="flexi-advisor-label">Flexi AI Advisor</span>
                        <div id="flexi-burnout-warn" style="display:none;" class="flexi-burnout-badge">🔥 Burnout Risk: HIGH</div>
                    </div>
                    <div>
                        <button class="btn-flexi-advice" id="btn-flexi-advice" onclick="fetchFlexiAdvice()">&#129504; Get AI Advice</button>
                        <div id="flexi-advice-box"></div>
                    </div>
                </div>
            </div>{{-- /leave-grid --}}

            <div class="history-panel">
                <h3 style="margin-bottom: 25px; font-size: 18px; font-weight: 800;">Full Attendance History</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Transaction Date</th>
                            <th>Clock In Time</th>
                            <th>Work Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($history as $log)
                        <tr>
                            <td style="font-weight: 600;">{{ $log->date }}</td>
                            <td style="font-weight: 700; color: #111;">{{ $log->clock_in_time ?: '--:--' }}</td>
                            <td><span style="padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 800; background: #f3f4f6; color: #4b5563; text-transform: uppercase;">{{ $log->status }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="3" style="text-align:center; padding:50px; color:#9ca3af;">No historical records found for this employee.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- ═══════════════════════════════════════════════════════════
         AI INSIGHT ADD-ON — SLIDE-OVER PANEL
         No layout files touched. Injected directly into this view.
    ═══════════════════════════════════════════════════════════ -->

    {{-- Overlay backdrop --}}
    <div id="ai-overlay" onclick="closeAIPanel()"></div>

    {{-- Slide-over panel --}}
    <aside id="ai-panel" role="complementary" aria-label="AI HR Performance Insight">
        <div id="ai-panel-header">
            <div>
                <h2>🤖 AI HR Performance Insight</h2>
                <p>Last 30 days · Powered by Gemini 2.0 Flash</p>
            </div>
            <button class="ai-close-btn" onclick="closeAIPanel()" aria-label="Close panel">✕</button>
        </div>

        <div id="ai-panel-body">
            {{-- Content injected dynamically by JS --}}
        </div>
    </aside>

    <script>
        const EMPLOYEE_ID  = '{{ $employee->id }}';
        const EMPLOYEE_NAME = '{{ addslashes($employee->full_name) }}';
        const AI_ROUTE     = '{{ route("employee.ai.analysis", $employee->id) }}';
        const CSRF_TOKEN   = '{{ csrf_token() }}';

        let alreadyLoaded  = false; // cache result per page visit
        let cachedResult   = null;
        let isRequesting   = false; // GUARD: prevent double-fire race condition

        function openAIPanel() {
            document.getElementById('ai-overlay').style.display = 'block';
            document.getElementById('ai-panel').classList.add('open');
            document.body.style.overflow = 'hidden';

            if (alreadyLoaded && cachedResult) {
                renderResult(cachedResult);
                return;
            }
            // GUARD: only fire if not already in-flight
            if (!isRequesting) {
                fetchAIInsight();
            }
        }

        function closeAIPanel() {
            document.getElementById('ai-overlay').style.display = 'none';
            document.getElementById('ai-panel').classList.remove('open');
            document.body.style.overflow = '';
        }

        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeAIPanel();
        });

        function showLoading() {
            document.getElementById('ai-panel-body').innerHTML = `
                <div class="ai-loading">
                    <div class="ai-spinner"></div>
                    <p>Analyzing <strong>${EMPLOYEE_NAME}</strong>'s attendance&hellip;</p>
                    <p style="font-size:12px;color:#9ca3af;">Fetching 30-day data &amp; querying Gemini 1.5 Flash</p>
                </div>
            `;
        }

        function renderResult(data) {
            const stats = data.stats || {};
            const presentNum = stats.present ?? 0;
            const lateNum    = stats.late    ?? 0;
            const absentNum  = stats.absent  ?? 0;
            const totalNum   = stats.total   ?? 0;

            const isGemini   = data.source === 'gemini';
            const sourceBadge = isGemini
                ? `<span style="display:inline-flex;align-items:center;gap:5px;background:#ede9fe;color:#6366f1;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;">\u2728 Gemini AI</span>`
                : `<span style="display:inline-flex;align-items:center;gap:5px;background:#f3f4f6;color:#6b7280;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;">\u2699\ufe0f Rule-Based Engine</span>`;
            const footerNote = isGemini
                ? 'AI-generated summary \u00b7 Review with official records before HR action.'
                : 'Generated locally (Gemini quota exceeded) \u00b7 Based on attendance statistics.';

            document.getElementById('ai-panel-body').innerHTML = `
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:8px;">
                    <p style="font-size:13px;color:#6b7280;margin:0;">Last <strong>30 days</strong> &middot; <strong>${totalNum}</strong> record(s)</p>
                    ${sourceBadge}
                </div>

                <div class="ai-stats-row">
                    <div class="ai-stat-box">
                        <span class="ai-stat-num ai-stat-present">${presentNum}</span>
                        <span class="ai-stat-lbl">Present / On Time</span>
                    </div>
                    <div class="ai-stat-box">
                        <span class="ai-stat-num ai-stat-late">${lateNum}</span>
                        <span class="ai-stat-lbl">Late Arrivals</span>
                    </div>
                    <div class="ai-stat-box">
                        <span class="ai-stat-num ai-stat-absent">${absentNum}</span>
                        <span class="ai-stat-lbl">Absences</span>
                    </div>
                </div>

                <h4 style="font-size:14px;font-weight:800;color:#111;margin-bottom:12px;">\ud83d\udccb HR Performance Summary</h4>
                <div class="ai-summary-box" style="white-space:pre-line;">${escapeHtml(data.summary)}</div>

                <p style="margin-top:20px;font-size:11px;color:#9ca3af;text-align:center;">${footerNote}</p>
            `;

        }

        function renderError(msg) {
            document.getElementById('ai-panel-body').innerHTML = `
                <div class="ai-error">
                    <strong>⚠️ Unable to generate insight</strong><br><br>
                    ${escapeHtml(msg)}
                </div>
                <button onclick="fetchAIInsight()" style="margin-top:16px;padding:10px 20px;border-radius:8px;background:#6366f1;color:white;border:none;cursor:pointer;font-weight:700;width:100%;">
                    🔄 Retry
                </button>
            `;
        }

        function escapeHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        async function fetchAIInsight() {
            if (isRequesting) return; // GUARD: prevent duplicate calls
            isRequesting = true;
            showLoading();
            try {
                const response = await fetch(AI_ROUTE, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json',
                    },
                });

                const data = await response.json();

                if (!response.ok || data.success === false) {
                    renderError(data.message || 'An unexpected error occurred.');
                    return;
                }

                cachedResult = data;
                alreadyLoaded = true;
                renderResult(data);

            } catch (err) {
                renderError('Network error: ' + err.message);
            } finally {
                isRequesting = false; // always release the guard
            }
        }
    </script>

    <script type="module">
        import { initializeApp } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js";
        import { getFirestore, doc, onSnapshot } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js";

        const firebaseConfig = { projectId: "doremi-admin" };
        const app = initializeApp(firebaseConfig);
        const db = getFirestore(app);

        const userId = "{{ $employee->id }}"; 
        const userDocRef = doc(db, "users", userId);

        onSnapshot(userDocRef, (doc) => {
            if (doc.exists()) {
                const data = doc.data();
                document.getElementById('al_val').innerText = data.al_balance ?? 0;
                document.getElementById('el_val').innerText = data.el_balance ?? 0;
                document.getElementById('mc_val').innerText = data.mc_balance ?? 0;
                // Real-time Flexi Credit Update
                const fc = data.ot_balance ?? 0;
                document.getElementById('flexi_val').innerText = fc;
                document.getElementById('flexi_advisor_val').innerHTML = fc + '<small style="font-size:16px;">H</small>';
                // Auto burnout warning if > 20H
                document.getElementById('flexi-burnout-warn').style.display = fc > 20 ? 'inline-block' : 'none';
            }
        });
    </script>

    <!-- ══ KPI REVIEW MODAL (ADD-ON) ══ -->
    <div id="kpi-modal-overlay" onclick="closeKPIModal()"></div>
    <div id="kpi-modal">
        <div id="kpi-modal-header">
            <div><h3>&#128203; AI KPI Review Generator</h3><p>{{ $employee->full_name }} &bull; {{ $employee->department }}</p></div>
            <button id="kpi-modal-close2" onclick="closeKPIModal()">&#10005;</button>
        </div>
        <div id="kpi-modal-body">
            <p style="font-size:13px;color:#6b7280;margin-bottom:18px;">Aggregates 30-day attendance, 90-day OT, and all leave data to generate a professional HR annual review paragraph.</p>
            <button class="btn-kpi-generate" onclick="fetchKPI()">&#9889; Generate Now</button>
            <div id="kpi-result" style="display:none;margin-top:18px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                    <span style="font-size:12px;font-weight:700;color:#6b7280;">HR PERFORMANCE REVIEW PARAGRAPH</span>
                    <span id="kpi-src-badge"></span>
                </div>
                <div id="kpi-paragraph" class="kpi-paragraph"></div>
                <button class="btn-copy-kpi" onclick="copyKPI()">&#128203; Copy to Clipboard</button>
            </div>
            <div id="kpi-error" style="display:none;background:#fff5f5;border-radius:8px;padding:14px;color:#dc2626;font-size:13px;margin-top:14px;"></div>
        </div>
    </div>

    <script>
        const EMP_ID   = '{{ $employee->id }}';
        const HR_CSRF  = '{{ csrf_token() }}';
        let kpiRequesting   = false;
        let flexiRequesting = false;

        // ─ KPI Modal ─
        function openKPIModal()  { document.getElementById('kpi-modal-overlay').style.display='block'; document.getElementById('kpi-modal').classList.add('show'); }
        function closeKPIModal() { document.getElementById('kpi-modal-overlay').style.display='none';  document.getElementById('kpi-modal').classList.remove('show'); kpiRequesting=false; }
        document.addEventListener('keydown', e => { if(e.key==='Escape'){ closeKPIModal(); } });

        async function fetchKPI() {
            if (kpiRequesting) return;
            kpiRequesting = true;
            document.getElementById('kpi-result').style.display = 'none';
            document.getElementById('kpi-error').style.display  = 'none';
            const btn = document.querySelector('#kpi-modal-body .btn-kpi-generate');
            btn.textContent = '&#9203; Generating…'; btn.disabled = true;
            try {
                const res  = await fetch(`/api/ai/kpi-review/${EMP_ID}`, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':HR_CSRF,'Accept':'application/json'} });
                const data = await res.json();
                if (!res.ok || !data.success) { showKPIError(data.message||'Error generating review.'); return; }
                document.getElementById('kpi-paragraph').textContent = data.paragraph;
                document.getElementById('kpi-src-badge').innerHTML = data.source==='gemini'
                    ? '<span style="background:#ede9fe;color:#6366f1;font-size:10px;font-weight:700;padding:2px 8px;border-radius:12px;">&#10024; Gemini AI</span>'
                    : '<span style="background:#f3f4f6;color:#6b7280;font-size:10px;font-weight:700;padding:2px 8px;border-radius:12px;">&#9881;&#65039; Rule Engine</span>';
                document.getElementById('kpi-result').style.display = 'block';
            } catch(e) { showKPIError('Network error: '+e.message); }
            finally { btn.innerHTML='&#9889; Generate Now'; btn.disabled=false; kpiRequesting=false; }
        }
        function showKPIError(msg) {
            const el = document.getElementById('kpi-error');
            el.innerHTML = '<strong>&#9888;&#65039; Error</strong><br>' + msg;
            el.style.display = 'block';
        }
        function copyKPI() {
            const text = document.getElementById('kpi-paragraph').textContent;
            navigator.clipboard.writeText(text).then(() => { const b=document.querySelector('.btn-copy-kpi'); b.textContent='&#10003; Copied!'; setTimeout(()=>b.innerHTML='&#128203; Copy to Clipboard',2000); });
        }

        // ─ Flexi Advisor ─
        async function fetchFlexiAdvice() {
            if (flexiRequesting) return;
            flexiRequesting = true;
            const btn = document.getElementById('btn-flexi-advice');
            const box = document.getElementById('flexi-advice-box');
            btn.textContent = 'Analyzing…'; btn.disabled = true;
            box.style.display = 'block';
            box.textContent = 'Loading…';
            try {
                const res  = await fetch(`/api/ai/flexi-advice/${EMP_ID}`, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':HR_CSRF,'Accept':'application/json'} });
                const data = await res.json();
                if (!res.ok || !data.success) { box.textContent = 'Error: ' + (data.message||'Unknown'); return; }
                if (data.burnout_risk) document.getElementById('flexi-burnout-warn').style.display = 'inline-block';
                box.textContent = data.advice;
            } catch(e) { box.textContent = 'Network error.'; }
            finally { btn.innerHTML='&#129504; Refresh Advice'; btn.disabled=false; flexiRequesting=false; }
        }

        // Auto-trigger burnout badge on page load if initial balance > 20
        (function() {
            const initialBal = parseFloat('{{ $employee->flexi_credit ?? 0 }}') || 0;
            if (initialBal > 20) document.getElementById('flexi-burnout-warn').style.display = 'inline-block';
        })();

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
        // setInterval(fetchLiveNotifications, 10000); // Stopped to save quota
    </script>
</body>
</html>