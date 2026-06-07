{{-- ─────────────────────────────────────────────────────────────────────────
     layouts/pulse.blade.php  ·  Live Company Pulse + System Health widget
     Usage: @include('layouts.pulse') — place just before </aside>
     Zero dependencies — brings its own Firebase module + toggle JS.
──────────────────────────────────────────────────────────────────────────── --}}

{{-- ── COMPANY PULSE ── --}}
<div id="pulse-wrapper" style="padding:0 14px 12px;">

    {{-- Toggle button --}}
    <button id="pulse-toggle" onclick="togglePulse()"
        style="width:100%;background:rgba(0,0,0,0.20);border:1px solid rgba(255,255,255,0.18);border-radius:10px;color:white;padding:10px 14px;display:flex;align-items:center;justify-content:space-between;cursor:pointer;font-size:11px;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;transition:background 0.2s;"
        onmouseover="this.style.background='rgba(0,0,0,0.30)'"
        onmouseout="this.style.background='rgba(0,0,0,0.20)'">
        <span style="display:flex;align-items:center;gap:7px;">
            <span style="position:relative;display:inline-flex;width:10px;height:10px;">
                <span style="position:absolute;inset:0;border-radius:50%;background:rgba(74,222,128,0.7);animation:pulse-ring 1.4s ease-out infinite;"></span>
                <span style="position:relative;border-radius:50%;width:10px;height:10px;background:#4ade80;display:block;"></span>
            </span>
            Company Pulse
        </span>
        <span id="pulse-chevron" style="font-size:10px;">▲</span>
    </button>

    {{-- Stats card --}}
    <div id="pulse-card" style="background:rgba(0,0,0,0.20);border:1px solid rgba(255,255,255,0.15);border-top:none;border-radius:0 0 10px 10px;padding:12px 14px 14px;">
        <div style="display:flex;flex-direction:column;gap:10px;">

            <div style="display:flex;justify-content:space-between;align-items:center;">
                <span style="color:rgba(255,255,255,0.75);font-size:11px;font-weight:600;display:flex;align-items:center;gap:5px;"><span>🟢</span>Clock-In Today</span>
                <span id="pulse-clockin" style="color:#fff;font-size:18px;font-weight:800;transition:opacity 0.3s;">--</span>
            </div>

            <div style="height:1px;background:rgba(255,255,255,0.1);"></div>

            <div style="display:flex;justify-content:space-between;align-items:center;">
                <span style="color:rgba(255,255,255,0.75);font-size:11px;font-weight:600;display:flex;align-items:center;gap:5px;"><span>🟡</span>On Leave</span>
                <span id="pulse-onleave" style="color:#fff;font-size:18px;font-weight:800;transition:opacity 0.3s;">--</span>
            </div>

            <div style="height:1px;background:rgba(255,255,255,0.1);"></div>

            <div style="display:flex;justify-content:space-between;align-items:center;">
                <span style="color:rgba(255,255,255,0.75);font-size:11px;font-weight:600;display:flex;align-items:center;gap:5px;"><span>🔴</span>Late Arrivals</span>
                <span id="pulse-late" style="color:#fff;font-size:18px;font-weight:800;transition:opacity 0.3s;">--</span>
            </div>

        </div>
        <div style="margin-top:12px;font-size:9px;color:rgba(255,255,255,0.35);text-align:center;font-weight:600;letter-spacing:0.5px;">LIVE · UPDATES IN REAL-TIME</div>
    </div>
</div>

{{-- ────────────────────────────────────────────────────────────────────────
     SYSTEM HEALTH INDICATOR — append-only, strictly below Company Pulse
──────────────────────────────────────────────────────────────────────────── --}}
<div id="sys-health-bar" style="padding:0 14px 16px; display:flex; align-items:center; gap:7px;">

    {{-- Dot (state changes via JS) --}}
    <span id="sys-health-dot" style="
        position:relative; display:inline-flex; width:7px; height:7px; flex-shrink:0;
    ">
        {{-- ping ring (shown only in "connected" state) --}}
        <span id="sys-health-ring" style="
            position:absolute; inset:0; border-radius:50%;
            background:rgba(156,163,175,0.6);
            animation:health-ping 2s ease-out infinite;
        "></span>
        {{-- solid dot --}}
        <span id="sys-health-core" style="
            position:relative; display:block; width:7px; height:7px;
            border-radius:50%; background:#9ca3af;
        "></span>
    </span>

    {{-- Status text --}}
    <span id="sys-health-text" style="
        font-size:10px; color:rgba(255,255,255,0.50); font-weight:600;
        letter-spacing:0.3px; transition:color 0.4s;
    ">Connecting...</span>
</div>

{{-- ── CSS keyframes ── --}}
<style>
    @keyframes pulse-ring {
        0%   { transform:scale(1);   opacity:0.8; }
        80%  { transform:scale(2.2); opacity:0;   }
        100% { transform:scale(2.2); opacity:0;   }
    }

    /* Health ping — only plays when connected (class toggled by JS) */
    @keyframes health-ping {
        0%   { transform:scale(1);   opacity:0.7; }
        70%  { transform:scale(2.5); opacity:0;   }
        100% { transform:scale(2.5); opacity:0;   }
    }

    /* Paused by default; enabled via class */
    #sys-health-ring { animation-play-state: paused; }
    #sys-health-ring.pinging { animation-play-state: running; }
</style>

{{-- ── Firebase module: Pulse data + Health state detection ── --}}
<script type="module">
    import { initializeApp }  from "https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js";
    import { getFirestore, collection, onSnapshot, query, where }
                              from "https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js";

    // ── helpers ──────────────────────────────────────────────────────────────

    function setStatValue(id, value) {
        const el = document.getElementById(id);
        if (!el || el.textContent === String(value)) return;
        el.style.opacity = '0';
        setTimeout(() => { el.textContent = value; el.style.opacity = '1'; }, 200);
    }

    /**
     * setHealth(state)
     * state: 'connecting' | 'connected' | 'syncing' | 'offline'
     */
    function setHealth(state) {
        const core    = document.getElementById('sys-health-core');
        const ring    = document.getElementById('sys-health-ring');
        const text    = document.getElementById('sys-health-text');
        if (!core || !ring || !text) return;

        const cfg = {
            connecting: { color:'#9ca3af', ringColor:'rgba(156,163,175,0.5)', ping:false, label:'Connecting...',        textOpacity:'0.50' },
            connected:  { color:'#4ade80', ringColor:'rgba(74,222,128,0.6)',  ping:true,  label:'Firestore Connected',  textOpacity:'0.70' },
            syncing:    { color:'#facc15', ringColor:'rgba(250,204,21,0.4)',  ping:false, label:'Syncing...',            textOpacity:'0.60' },
            offline:    { color:'#f87171', ringColor:'rgba(248,113,113,0)',   ping:false, label:'Offline',              textOpacity:'0.60' },
        }[state] ?? cfg.connecting;

        core.style.background       = cfg.color;
        ring.style.background       = cfg.ringColor;
        text.textContent            = cfg.label;
        text.style.color            = `rgba(255,255,255,${cfg.textOpacity})`;
        ring.classList.toggle('pinging', cfg.ping);
    }

    // ── Firebase init ─────────────────────────────────────────────────────────

    const instanceName = 'pulse-' + Math.random().toString(36).slice(2, 7);
    const app      = initializeApp({ projectId: "doremi-admin" }, instanceName);
    const db       = getFirestore(app);
    const todayStr = new Date().toISOString().slice(0, 10);

    // Track how many snapshots have resolved (success or error)
    let resolvedCount = 0;
    const TOTAL_QUERIES = 2; // attendances + leaves

    function onQuerySuccess() {
        resolvedCount++;
        // First success → Connected; subsequent → stay Connected
        setHealth('connected');
    }
    function onQueryError() {
        resolvedCount++;
        // If all queries failed → Offline
        if (resolvedCount >= TOTAL_QUERIES) setHealth('offline');
    }

    // Show "Syncing..." briefly while waiting for first response
    setHealth('connecting');
    setTimeout(() => {
        if (resolvedCount === 0) setHealth('syncing');
    }, 3000); // still waiting after 3s → show Syncing

    // ── 1. Attendances ────────────────────────────────────────────────────────
    try {
        onSnapshot(
            query(collection(db, "attendances"), where("date", "==", todayStr)),
            (snap) => {
                let clockin = 0, late = 0;
                snap.forEach(d => {
                    const f = d.data();
                    if (f.clock_in_time) clockin++;
                    if ((f.status ?? '').toLowerCase() === 'late') late++;
                });
                setStatValue('pulse-clockin', clockin);
                setStatValue('pulse-late',    late);
                onQuerySuccess();
            },
            () => onQueryError()
        );
    } catch(e) { onQueryError(); }

    // ── 2. Leaves ─────────────────────────────────────────────────────────────
    try {
        onSnapshot(
            query(collection(db, "leaves"), where("status", "==", "Approved")),
            (snap) => {
                const todayMs = new Date(todayStr).getTime();
                let onLeave = 0;
                snap.forEach(d => {
                    const f = d.data();
                    let startMs = 0;
                    let endMs = 0;

                    // Parse start date (support both Timestamp and String)
                    if (f.startDate && f.startDate.seconds) {
                        startMs = f.startDate.toMillis?.() ?? (f.startDate.seconds * 1000);
                    } else if (f.start_date) {
                        startMs = new Date(f.start_date).getTime();
                    }

                    // Parse end date (support both Timestamp and String)
                    if (f.endDate && f.endDate.seconds) {
                        endMs = f.endDate.toMillis?.() ?? (f.endDate.seconds * 1000);
                    } else if (f.end_date) {
                        endMs = new Date(f.end_date).getTime();
                    }

                    // Count if today falls between start and end (using MS logic)
                    if (startMs > 0 && endMs > 0) {
                        if (startMs <= todayMs + 86400000 && endMs >= todayMs) {
                            onLeave++;
                        }
                    } else if (startMs > 0 && endMs === 0) {
                        // If no end date, just check start date
                        if (startMs <= todayMs + 86400000 && startMs >= todayMs) {
                            onLeave++;
                        }
                    }
                });
                setStatValue('pulse-onleave', onLeave);
                onQuerySuccess();
            },
            () => onQueryError()
        );
    } catch(e) { onQueryError(); }

    // ── browser offline/online events (secondary signal) ──────────────────────
    window.addEventListener('offline', () => setHealth('offline'));
    window.addEventListener('online',  () => setHealth('syncing'));
</script>

{{-- ── Toggle logic ── --}}
<script>
    let pulseOpen = true;
    function togglePulse() {
        const card    = document.getElementById('pulse-card');
        const chevron = document.getElementById('pulse-chevron');
        pulseOpen     = !pulseOpen;
        card.style.display  = pulseOpen ? 'block' : 'none';
        chevron.textContent = pulseOpen ? '▲' : '▼';
    }
</script>
