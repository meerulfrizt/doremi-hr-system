{{--
    resources/views/layouts/sidebar.blade.php
    Shared sidebar component — include in any page with @include('layouts.sidebar', ['active' => 'dashboard'])
    The 'active' param highlights the correct menu item.
    ─────────────────────────────────────────────────────────────────────────────
    APPEND-ONLY RULE: The "Company Pulse" section below is the only addition.
    Nothing above it is ever modified.
--}}

{{-- ══════════════════════════════════════════════════════════
     EXISTING SIDEBAR — untouched
══════════════════════════════════════════════════════════ --}}
<aside class="sidebar">
    <div class="brand">
        <img src="{{ asset('img/Doremi logo.png') }}" alt="DOREMi">
    </div>
    <nav class="menu">
        <a href="{{ route('dashboard') }}">
            <button class="menu-item {{ ($active ?? '') === 'dashboard' ? 'active' : '' }}">🏠 Dashboard</button>
        </a>
        <a href="{{ route('attendance') }}">
            <button class="menu-item {{ ($active ?? '') === 'attendance' ? 'active' : '' }}">🕒 Attendance</button>
        </a>

        <div class="menu-group-title">Management</div>
        <a href="{{ route('leave') }}">
            <button class="menu-item {{ ($active ?? '') === 'leave' ? 'active' : '' }}">🗓️ Leave Requests</button>
        </a>
        <a href="{{ route('overtime') }}">
            <button class="menu-item {{ ($active ?? '') === 'overtime' ? 'active' : '' }}">⏱️ Overtime (OT)</button>
        </a>
        <a href="{{ route('flexible') }}">
            <button class="menu-item {{ ($active ?? '') === 'flexible' ? 'active' : '' }}">🔁 Flexible Hours</button>
        </a>
        <a href="{{ route('tasks.assigned') }}">
            <button class="menu-item {{ ($active ?? '') === 'tasks.assigned' ? 'active' : '' }}">📍 Assigned Tasks</button>
        </a>

        <div class="menu-group-title">Reports</div>
        <a href="{{ route('analytics') }}">
            <button class="menu-item {{ ($active ?? '') === 'analytics' ? 'active' : '' }}">📊 Analytics</button>
        </a>
        <a href="{{ route('directory') }}">
            <button class="menu-item {{ ($active ?? '') === 'directory' ? 'active' : '' }}">👥 Directory</button>
        </a>
    </nav>

    {{-- ══════════════════════════════════════════════════════════
         COMPANY PULSE — APPEND-ONLY SECTION (zero changes above)
    ══════════════════════════════════════════════════════════ --}}
    <div id="pulse-wrapper" style="padding: 0 14px 20px;">

        {{-- Toggle button --}}
        <button
            id="pulse-toggle"
            onclick="togglePulse()"
            style="
                width: 100%;
                background: rgba(0,0,0,0.20);
                border: 1px solid rgba(255,255,255,0.18);
                border-radius: 10px;
                color: white;
                padding: 10px 14px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                cursor: pointer;
                font-size: 12px;
                font-weight: 700;
                letter-spacing: 0.5px;
                text-transform: uppercase;
                transition: background 0.2s;
            "
            onmouseover="this.style.background='rgba(0,0,0,0.30)'"
            onmouseout="this.style.background='rgba(0,0,0,0.20)'"
        >
            <span style="display:flex; align-items:center; gap:7px;">
                {{-- Animated pulse dot --}}
                <span style="position:relative; display:inline-flex; width:10px; height:10px;">
                    <span style="
                        position:absolute; inset:0; border-radius:50%;
                        background:rgba(74,222,128,0.7);
                        animation: pulse-ring 1.4s ease-out infinite;
                    "></span>
                    <span style="
                        position:relative; border-radius:50%; width:10px; height:10px;
                        background:#4ade80;
                    "></span>
                </span>
                Company Pulse
            </span>
            <span id="pulse-chevron" style="font-size:10px; transition:transform 0.25s;">▲</span>
        </button>

        {{-- Collapsible card --}}
        <div
            id="pulse-card"
            style="
                background: rgba(0,0,0,0.20);
                border: 1px solid rgba(255,255,255,0.15);
                border-top: none;
                border-radius: 0 0 10px 10px;
                padding: 12px 14px 14px;
                display: block;
                overflow: hidden;
                transition: all 0.3s ease;
            "
        >
            {{-- Stat rows --}}
            <div style="display:flex; flex-direction:column; gap:10px;">

                {{-- Clock-In Today --}}
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span style="color:rgba(255,255,255,0.75); font-size:11px; font-weight:600; display:flex; align-items:center; gap:5px;">
                        <span style="font-size:13px;">🟢</span> Clock-In Today
                    </span>
                    <span
                        id="pulse-clockin"
                        style="
                            color:#fff; font-size:18px; font-weight:800;
                            min-width:30px; text-align:right;
                            transition: opacity 0.3s ease;
                        "
                    >--</span>
                </div>

                <div style="height:1px; background:rgba(255,255,255,0.1);"></div>

                {{-- On Leave --}}
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span style="color:rgba(255,255,255,0.75); font-size:11px; font-weight:600; display:flex; align-items:center; gap:5px;">
                        <span style="font-size:13px;">🟡</span> On Leave
                    </span>
                    <span
                        id="pulse-onleave"
                        style="
                            color:#fff; font-size:18px; font-weight:800;
                            min-width:30px; text-align:right;
                            transition: opacity 0.3s ease;
                        "
                    >--</span>
                </div>

                <div style="height:1px; background:rgba(255,255,255,0.1);"></div>

                {{-- Late Arrivals --}}
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span style="color:rgba(255,255,255,0.75); font-size:11px; font-weight:600; display:flex; align-items:center; gap:5px;">
                        <span style="font-size:13px;">🔴</span> Late Arrivals
                    </span>
                    <span
                        id="pulse-late"
                        style="
                            color:#fff; font-size:18px; font-weight:800;
                            min-width:30px; text-align:right;
                            transition: opacity 0.3s ease;
                        "
                    >--</span>
                </div>

            </div>

            <div style="margin-top:12px; font-size:9px; color:rgba(255,255,255,0.35); text-align:center; font-weight:600; letter-spacing:0.5px;">
                LIVE · UPDATES IN REAL-TIME
            </div>
        </div>

    </div>
</aside>

{{-- ── Pulse ring keyframe (injected once) ── --}}
<style>
    @keyframes pulse-ring {
        0%   { transform: scale(1);   opacity: 0.8; }
        80%  { transform: scale(2.2); opacity: 0;   }
        100% { transform: scale(2.2); opacity: 0;   }
    }
</style>

{{-- ── Firebase compat + onSnapshot ── --}}
<script type="module">
    import { initializeApp }          from "https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js";
    import { getFirestore, collection, onSnapshot, query, where }
                                      from "https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js";

    // ── helper: fade number update ──────────────────────────────
    function setStatValue(id, value) {
        const el = document.getElementById(id);
        if (!el) return;
        if (el.textContent === String(value)) return; // no change, no flicker
        el.style.opacity = '0';
        setTimeout(() => {
            el.textContent = value;
            el.style.opacity = '1';
        }, 200);
    }

    // ── Firestore setup ─────────────────────────────────────────
    const app = initializeApp({ projectId: "doremi-admin" }, "pulse-instance");
    const db  = getFirestore(app);

    const todayStr = new Date().toISOString().slice(0, 10); // YYYY-MM-DD

    // ── 1. attendances → clockin count + late count ─────────────
    try {
        const attQ = query(
            collection(db, "attendances"),
            where("date", "==", todayStr)
        );
        onSnapshot(attQ, (snap) => {
            let clockin = 0;
            let late    = 0;
            snap.forEach(doc => {
                const d = doc.data();
                if (d.clock_in_time) clockin++;                          // any clock-in = present
                if ((d.status ?? '').toLowerCase() === 'late') late++;   // status == Late
            });
            setStatValue('pulse-clockin', clockin);
            setStatValue('pulse-late',    late);
        }, () => { /* offline — keep "--" */ });
    } catch(e) { /* silent fail */ }

    // ── 2. leaves → on leave today (status=Approved + date range covers today) ──
    try {
        const leaveQ = query(
            collection(db, "leaves"),
            where("status", "==", "Approved")
        );
        onSnapshot(leaveQ, (snap) => {
            const todayMs = new Date(todayStr).getTime();
            let onLeave   = 0;
            snap.forEach(doc => {
                const d = doc.data();
                // startDate & endDate stored as Firestore Timestamps
                const startMs = d.startDate?.toMillis?.() ?? d.startDate?.seconds * 1000 ?? 0;
                const endMs   = d.endDate?.toMillis?.()   ?? d.endDate?.seconds   * 1000 ?? 0;
                if (startMs <= todayMs + 86400000 && endMs >= todayMs) onLeave++;
            });
            setStatValue('pulse-onleave', onLeave);
        }, () => { /* offline — keep "--" */ });
    } catch(e) { /* silent fail */ }
</script>

{{-- ── Toggle logic (vanilla, no deps) ── --}}
<script>
    let pulseOpen = true;
    function togglePulse() {
        const card    = document.getElementById('pulse-card');
        const chevron = document.getElementById('pulse-chevron');
        pulseOpen = !pulseOpen;
        if (pulseOpen) {
            card.style.display  = 'block';
            chevron.textContent = '▲';
            chevron.style.transform = 'rotate(0deg)';
        } else {
            card.style.display  = 'none';
            chevron.textContent = '▼';
            chevron.style.transform = 'rotate(0deg)';
        }
    }
</script>
