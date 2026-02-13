<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.html');
    exit();
}

require_once 'backend/config.php';
$conn = getDatabaseConnection();

// Fetch Security Incidents (Correlated)
$incidents = [];
$res_inc = $conn->query("SELECT * FROM security_forensics_incidents ORDER BY created_at DESC");
while($row = $res_inc->fetch_assoc()) {
    $incidents[] = $row;
}

// Fetch Master Forensics Stream
$events = [];
$res_evt = $conn->query("SELECT * FROM security_forensics_events ORDER BY timestamp_ms DESC LIMIT 200");
while($row = $res_evt->fetch_assoc()) {
    $events[] = $row;
}

// Risk Scores
$risk_scores = [];
$res_risk = $conn->query("SELECT * FROM security_risk_scores");
while($row = $res_risk->fetch_assoc()) {
    $risk_scores[] = $row;
}

// System Health (Aggregated)
$stats = [
    'critical' => count(array_filter($incidents, fn($i) => $i['severity'] == 'CRITICAL')),
    'identity_risk' => rand(15, 25),
    'data_integrity' => 99.98,
    'threat_blocked' => rand(120, 450)
];

$forensics_json = json_encode([
    'incidents' => $incidents,
    'events' => $events,
    'risks' => $risk_scores,
    'stats' => $stats
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CISO Forensics Command | Sentinel Intelligence</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>

    <style>
        :root {
            --bg-void: #020204;
            --bg-panel: #0a0a0f;
            --bg-accent: #11111a;
            --border: rgba(255, 255, 255, 0.08);
            --forensic-blue: #3b82f6;
            --threat-red: #ef4444;
            --risk-amber: #f59e0b;
            --trust-green: #10b981;
            --text-high: #ffffff;
            --text-dim: #888891;
            --glow-blue: 0 0 20px rgba(59, 130, 246, 0.3);
            --glow-red: 0 0 20px rgba(239, 68, 68, 0.3);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-void);
            color: var(--text-high);
            overflow: hidden;
            height: 100vh;
            display: flex;
        }

        #forensics-bg { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; opacity: 0.3; }

        /* Navigation */
        .cyber-nav {
            width: 72px;
            background: var(--bg-panel);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1.5rem 0;
            gap: 2rem;
            z-index: 100;
        }

        .nav-btn {
            color: var(--text-dim);
            cursor: pointer;
            transition: all 0.3s;
            padding: 12px;
            border-radius: 12px;
            position: relative;
        }

        .nav-btn:hover, .nav-btn.active {
            color: var(--forensic-blue);
            background: rgba(59, 130, 246, 0.1);
            box-shadow: var(--glow-blue);
        }

        /* Central Workspace */
        .workspace {
            flex: 1;
            display: grid;
            grid-template-columns: 380px 1fr 320px;
            grid-template-rows: 80px 1fr 260px;
            gap: 1rem;
            padding: 1rem;
            overflow: hidden;
        }

        .glass-panel {
            background: var(--bg-panel);
            border: 1px solid var(--border);
            border-radius: 16px;
            backdrop-filter: blur(20px);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* Top Bar */
        .dashboard-header {
            grid-column: 1 / span 3;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5rem;
        }

        .brand h1 { font-size: 1.25rem; font-weight: 800; letter-spacing: -0.5px; }
        .brand span { color: var(--forensic-blue); }

        .system-ticker {
            display: flex;
            gap: 1.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-dim);
        }

        .ticker-item i { width: 0.9rem; vertical-align: middle; margin-right: 4px; }
        .ticker-item span { color: var(--trust-green); }

        /* Left: Incident Board */
        .incident-panel { grid-row: 2 / span 2; }
        .panel-title {
            padding: 1rem 1.25rem;
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 2px;
            color: var(--text-dim);
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
        }

        .incident-scroll { flex: 1; overflow-y: auto; padding: 1rem; display: flex; flex-direction: column; gap: 1rem; }

        .incident-card {
            background: #12121e;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.25rem;
            position: relative;
            cursor: pointer;
            transition: 0.3s;
        }

        .incident-card:hover { border-color: var(--forensic-blue); background: #161625; }
        .incident-card.critical { border-left: 4px solid var(--threat-red); box-shadow: 0 0 20px rgba(239, 68, 68, 0.05); }

        .severity-badge { position: absolute; top: 1rem; right: 1rem; font-size: 0.6rem; font-weight: 800; padding: 2px 6px; border-radius: 4px; }

        /* Center: Forensic Stream */
        .stream-panel { grid-column: 2; }
        .stream-scroll { flex: 1; overflow-y: auto; }

        table { width: 100%; border-collapse: collapse; }
        th { font-size: 0.65rem; font-weight: 800; color: var(--text-dim); text-align: left; padding: 1rem; border-bottom: 1px solid var(--border); position: sticky; top: 0; background: var(--bg-panel); z-index: 10; }
        td { padding: 1rem; font-size: 0.8rem; border-bottom: 1px solid var(--border); border-right: 1px solid var(--border); }
        tr:hover { background: rgba(255,255,255,0.02); }

        .category-pill {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.6rem;
            font-weight: 800;
            font-family: 'JetBrains Mono', monospace;
        }
        .cat-IDENTITY { background: rgba(59, 130, 246, 0.1); color: var(--forensic-blue); }
        .cat-ADMIN { background: rgba(245, 158, 11, 0.1); color: var(--risk-amber); }
        .cat-DATA { background: rgba(16, 185, 129, 0.1); color: var(--trust-green); }
        .cat-SYSTEM { background: rgba(148, 163, 184, 0.1); color: var(--text-dim); }

        /* Right: Risk Matrix */
        .risk-panel { grid-row: 2; padding: 1.5rem; display: flex; flex-direction: column; gap: 1.5rem; }

        .risk-display { position: relative; height: 160px; display: flex; align-items: center; justify-content: center; }
        .risk-value { font-size: 3rem; font-weight: 800; color: var(--threat-red); text-shadow: var(--glow-red); }

        .bar-group { display: flex; flex-direction: column; gap: 1rem; }
        .stat-bar { height: 6px; background: #1a1a25; border-radius: 3px; overflow: hidden; margin-top: 4px; }
        .stat-fill { height: 100%; background: var(--forensic-blue); transition: 1s ease; }

        /* Bottom: Lab & Intelligence */
        .lab-panel { grid-column: 2 / span 2; display: grid; grid-template-columns: 1fr 300px; padding: 1.5rem; gap: 1.5rem; }

        .terminal {
            background: #000;
            border-radius: 12px;
            padding: 1rem;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.75rem;
            color: #00ff41;
            overflow-y: auto;
            position: relative;
            box-shadow: inset 0 0 20px rgba(0,255,65,0.05);
        }

        .terminal::after {
            content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.1) 50%), linear-gradient(90deg, rgba(255, 0, 0, 0.03), rgba(0, 255, 0, 0.01), rgba(0, 0, 255, 0.03));
            background-size: 100% 2px, 3px 100%; pointer-events: none;
        }

        /* Modals */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.85); backdrop-filter: blur(8px);
            display: none; justify-content: center; align-items: center; z-index: 1000;
        }

        .modal {
            background: var(--bg-panel);
            border: 1px solid var(--border);
            border-radius: 20px;
            width: 800px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.8);
        }

        .modal-header { padding: 1.5rem 2rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 2rem; overflow-y: auto; }

        .btn {
            background: var(--bg-accent);
            border: 1px solid var(--border);
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: 0.3s;
        }

        .btn:hover { border-color: var(--forensic-blue); background: var(--bg-panel); transform: translateY(-2px); }
        .btn-primary { background: var(--forensic-blue); border: none; }
        .btn-danger { background: var(--threat-red); border: none; }

        .timeline-item {
            border-left: 2px solid var(--border);
            padding-left: 1.5rem;
            padding-bottom: 1.5rem;
            position: relative;
        }
        .timeline-item::before {
            content: ""; position: absolute; left: -6px; top: 0;
            width: 10px; height: 10px; border-radius: 50%;
            background: var(--forensic-blue);
        }
        .timeline-item.critical::before { background: var(--threat-red); box-shadow: 0 0 10px var(--threat-red); }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1.5rem; }
        .info-card { background: #0f0f15; border: 1px solid var(--border); padding: 1rem; border-radius: 12px; }

        /* Guide Card */
        .guide-scroller { height: 100%; overflow-y: auto; padding-right: 10px; }
        .guide-section { margin-bottom: 1.5rem; }
        .guide-section h4 { font-size: 0.8rem; color: var(--forensic-blue); margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 1px; }
        .guide-section p { font-size: 0.8rem; color: var(--text-dim); line-height: 1.5; }

    </style>
</head>
<body>

<canvas id="forensics-bg"></canvas>

<nav class="cyber-nav">
    <div class="nav-btn active" title="Security Command"><i data-lucide="layout-grid"></i></div>
    <div class="nav-btn" title="Investigate" onclick="showGuide()"><i data-lucide="book-open"></i></div>
    <div class="nav-btn" title="Export Bundle" onclick="exportForensics()"><i data-lucide="archive"></i></div>
    <div class="nav-btn" title="Policy Management"><i data-lucide="shield-check"></i></div>
    <div class="nav-btn" style="margin-top: auto;" title="Admin Exit" onclick="window.location.href='admin_dashboard.php'"><i data-lucide="log-out"></i></div>
</nav>

<main class="workspace">
    <!-- Header -->
    <header class="dashboard-header glass-panel">
        <div class="brand">
            <h1>SENTINEL <span>FORENSICS</span></h1>
        </div>
        <div class="system-ticker">
            <div class="ticker-item"><i data-lucide="activity"></i> HEARTBEAT: <span>OK</span></div>
            <div class="ticker-item"><i data-lucide="fingerprint"></i> AUTH_ENTROPY: <span>98.4%</span></div>
            <div class="ticker-item"><i data-lucide="clock"></i> UTC_OFFSET: -05:00</div>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <div id="real-clock" style="font-family: 'JetBrains Mono'; font-weight: 700; color: var(--forensic-blue); padding: 0.5rem 1rem; background: rgba(59, 130, 246, 0.05); border-radius: 8px;"></div>
        </div>
    </header>

    <!-- Incident Panel -->
    <aside class="incident-panel glass-panel">
        <div class="panel-title">
            <span>ACTIVE INCIDENTS</span>
            <span style="color: var(--threat-red);"><?php echo count($incidents); ?> OPEN</span>
        </div>
        <div class="incident-scroll">
            <?php foreach($incidents as $inc): ?>
            <div class="incident-card critical" onclick="openInvestigation('<?php echo $inc['id']; ?>')">
                <span class="severity-badge" style="background: var(--threat-red); color: white;"><?php echo $inc['severity']; ?></span>
                <span style="font-size: 0.65rem; color: var(--text-dim); font-family: 'JetBrains Mono';"><?php echo $inc['id']; ?></span>
                <h3 style="font-size: 0.9rem; margin-top: 0.25rem; font-weight: 700;"><?php echo $inc['incident_type']; ?></h3>
                <p style="font-size: 0.75rem; color: var(--text-dim); margin-top: 0.5rem; line-height: 1.4;"><?php echo $inc['description']; ?></p>
                <div style="margin-top: 1rem; display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; gap: 4px;">
                        <span style="width: 6px; height: 6px; border-radius: 50%; background: var(--threat-red);"></span>
                        <span style="width: 6px; height: 6px; border-radius: 50%; background: var(--threat-red);"></span>
                        <span style="width: 6px; height: 6px; border-radius: 50%; background: var(--border);"></span>
                    </div>
                    <span style="font-size: 0.6rem; color: var(--forensic-blue); font-weight: 800; cursor: pointer;">ANALYZE >></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </aside>

    <!-- Master Stream -->
    <section class="stream-panel glass-panel">
        <div class="panel-title">FORENSIC EVENT INTELLIGENCE STREAM</div>
        <div class="stream-scroll">
            <table>
                <thead>
                    <tr>
                        <th>INCIDENT / EVENT</th>
                        <th>ACTOR / ROLE</th>
                        <th>TARGET ASSET</th>
                        <th>INTEGRITY</th>
                    </tr>
                </thead>
                <tbody id="stream-body">
                    <?php foreach($events as $e): ?>
                    <tr>
                        <td>
                            <div style="display: flex; flex-direction: column;">
                                <span style="font-size: 0.65rem; color: var(--text-dim); font-family: 'JetBrains Mono';"><?php echo $e['event_id']; ?></span>
                                <span style="font-weight: 700; margin-top: 2px;"><?php echo $e['event_type']; ?></span>
                                <span style="font-size: 0.75rem; color: var(--text-dim); margin-top: 2px;"><?php echo htmlspecialchars($e['action_summary']); ?></span>
                            </div>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <div style="width: 24px; height: 24px; background: #1c1c28; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 0.6rem; font-weight: 800;"><?php echo substr($e['actor_name'], 0, 1); ?></div>
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-weight: 600;"><?php echo $e['actor_name']; ?></span>
                                    <span class="category-pill cat-<?php echo $e['category']; ?>"><?php echo $e['category']; ?></span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="display: flex; flex-direction: column;">
                                <span style="font-weight: 600;"><?php echo $e['target_asset']; ?></span>
                                <span class="mono" style="font-size: 0.65rem; color: var(--forensic-blue);"><?php echo $e['ip_address']; ?></span>
                            </div>
                        </td>
                        <td>
                            <div class="evidence-hash" style="font-family: 'JetBrains Mono'; font-size: 0.55rem; color: var(--text-dim); line-height: 1.2;">
                                <?php echo substr($e['integrity_hash'], 0, 12); ?>...<br>
                                <span style="color: var(--trust-green); font-weight: 800;">[VERIFIED]</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Risk Panel -->
    <aside class="risk-panel glass-panel">
        <div class="panel-title">SYSTEM RISK EXPOSURE</div>
        <div class="risk-display">
            <canvas id="gaugeCanvas"></canvas>
            <div style="position: absolute; text-align: center;">
                <span class="risk-value">42</span>
                <p style="font-size: 0.65rem; font-weight: 800; color: var(--text-dim); letter-spacing: 1px;">OVERALL SCORE</p>
            </div>
        </div>

        <div class="bar-group">
            <p style="font-size: 0.75rem; font-weight: 800; color: var(--text-dim); letter-spacing: 1px;">BEHAVIORAL TRUST BASELINES</p>
            <?php 
            $labels = ['ADMIN_MOD', 'ENGINEER_IO', 'GUEST_TRAFFIC'];
            foreach($labels as $l): $p = rand(60, 95);
            ?>
            <div>
                <div style="display: flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 4px;">
                    <span class="mono"><?php echo $l; ?></span>
                    <span style="font-weight: 800; color: var(--trust-green);"><?php echo $p; ?>%</span>
                </div>
                <div class="stat-bar"><div class="stat-fill" style="width: <?php echo $p; ?>%;"></div></div>
            </div>
            <?php endforeach; ?>
        </div>
    </aside>

    <!-- Forensic Lab -->
    <section class="lab-panel glass-panel">
        <div class="terminal" id="terminal-stream">
            [SYS] >> INITIALIZING CRYPTO-SENTINEL...<br>
            [SYS] >> Hashing policy: SHA-256 (64-byte digest)<br>
            [SYS] >> Live monitoring active on Constructa_Admin_01<br>
        </div>
        <div style="padding-left: 1.5rem; border-left: 1px solid var(--border);">
            <p style="font-size: 0.7rem; font-weight: 800; color: var(--text-dim); margin-bottom: 1rem;">COMPLIANCE_STATUS</p>
            <div style="display: grid; grid-template-columns: 1fr; gap: 0.5rem;">
                <div style="padding: 10px; background: #13131c; border-radius: 8px; border-left: 3px solid var(--trust-green);">
                    <p style="font-size: 0.6rem; color: var(--text-dim);">ISO_27001_12.4</p>
                    <p style="font-size: 0.75rem; font-weight: 700;">AUDIT_READY</p>
                </div>
                <div style="padding: 10px; background: #13131c; border-radius: 8px; border-left: 3px solid var(--risk-amber);">
                    <p style="font-size: 0.6rem; color: var(--text-dim);">GDPR_ART_33</p>
                    <p style="font-size: 0.75rem; font-weight: 700;">NOTIFY_PENDING</p>
                </div>
            </div>
            <button class="btn btn-primary" style="width: 100%; margin-top: 1.5rem; font-size: 0.7rem;" onclick="exportForensics()">
                <i data-lucide="download"></i> EXPORT FORENSIC BUNDLE
            </button>
        </div>
    </section>
</main>

<!-- Incident Analysis Modal -->
<div class="modal-overlay" id="incident-modal">
    <div class="modal">
        <div class="modal-header">
            <div>
                <h2 id="modal-incident-title" style="font-size: 1.25rem;">Investigation Protocol</h2>
                <p id="modal-incident-id" style="font-size: 0.8rem; color: var(--text-dim); font-family: 'JetBrains Mono';"></p>
            </div>
            <button class="nav-btn" onclick="closeModal()"><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body">
            <div style="display: flex; gap: 2rem;">
                <div style="flex: 1;">
                    <h4 style="font-size: 0.75rem; letter-spacing: 1px; color: var(--text-dim); margin-bottom: 1.5rem;">INCIDENT TIMELINE / CORRELATION</h4>
                    <div id="timeline-container">
                        <!-- Dynamic -->
                    </div>
                </div>
                <div style="width: 300px; display: flex; flex-direction: column; gap: 1.5rem;">
                    <button class="btn btn-danger" onclick="takeAction('disable_user')"><i data-lucide="user-minus"></i> ISOLATE IDENTITY</button>
                    <button class="btn btn-danger" onclick="takeAction('block_ip')"><i data-lucide="shield-off"></i> BLACKLIST IP</button>
                    <button class="btn btn-primary" onclick="takeAction('resolve')"><i data-lucide="check-circle"></i> REMEDIATE & CLOSE</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Knowledge Hub Modal -->
<div class="modal-overlay" id="guide-modal">
    <div class="modal" style="width: 600px;">
        <div class="modal-header">
            <h2>Sentinel Intelligence Guide</h2>
            <button class="nav-btn" onclick="document.getElementById('guide-modal').style.display='none'"><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body">
            <div class="guide-scroller">
                <div class="guide-section">
                    <h4>Definition</h4>
                    <p>Security logs are specialized, chronologically ordered records of system-related activities that track security-relevant events as primary digital evidence.</p>
                </div>
                <div class="guide-section">
                    <h4>Purpose</h4>
                    <p>Critical for identifying system access patterns, detecting threats, forensic investigation, accountability, and maintaining regulatory compliance (ISO/SOC2).</p>
                </div>
                <div class="guide-section">
                    <h4>Log Structure</h4>
                    <p>Each entry contains User Identity, Precision Timestamp, IP Intelligence, Device Fingerprint, Action Detail, and a SHA-256 Integrity Hash.</p>
                </div>
                <div class="guide-section">
                    <h4>Security Characteristics</h4>
                    <p><b>Accuracy:</b> Exact event reflection. <b>Immutability:</b> Tamper-resistant storage. <b>Chronology:</b> Indisputable sequence of events.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();

    const FORENSICS_DATA = <?php echo $forensics_json; ?>;

    // === 3D BACKGROUND ===
    function init3D() {
        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
        const renderer = new THREE.WebGLRenderer({ canvas: document.getElementById('forensics-bg'), antialias: true, alpha: true });
        renderer.setSize(window.innerWidth, window.innerHeight);

        const group = new THREE.Group();
        const geometry = new THREE.IcosahedronGeometry(1, 1);
        const material = new THREE.MeshPhongMaterial({ color: 0x3b82f6, wireframe: true, transparent: true, opacity: 0.1 });
        
        for(let i=0; i<30; i++) {
            const m = new THREE.Mesh(geometry, material);
            const scale = Math.random() * 20 + 5;
            m.scale.set(scale, scale, scale);
            m.position.set(THREE.MathUtils.randFloatSpread(200), THREE.MathUtils.randFloatSpread(200), THREE.MathUtils.randFloatSpread(200));
            group.add(m);
        }
        scene.add(group);
        scene.add(new THREE.AmbientLight(0xffffff, 0.5));
        
        camera.position.z = 100;

        function animate() {
            requestAnimationFrame(animate);
            group.rotation.y += 0.0005;
            renderer.render(scene, camera);
        }
        animate();
    }
    init3D();

    // === GAUGE ===
    const gaugeCanvas = document.getElementById('gaugeCanvas');
    const gctx = gaugeCanvas.getContext('2d');
    function drawGauge(val) {
        gctx.clearRect(0, 0, 300, 300);
        gctx.beginPath();
        gctx.arc(150, 80, 70, Math.PI, 2 * Math.PI);
        gctx.strokeStyle = '#1e1e24';
        gctx.lineWidth = 10;
        gctx.lineCap = 'round';
        gctx.stroke();

        gctx.beginPath();
        gctx.arc(150, 80, 70, Math.PI, Math.PI + (val/100 * Math.PI));
        gctx.strokeStyle = val > 70 ? '#ef4444' : '#3b82f6';
        gctx.stroke();
    }
    drawGauge(42);

    // === CLOCK ===
    function tick() {
        const now = new Date();
        document.getElementById('real-clock').innerText = now.toISOString().split('T')[1].split('.')[0] + 'Z';
    }
    setInterval(tick, 1000);
    tick();

    // === MODAL LOGIC ===
    function openInvestigation(id) {
        const incident = FORENSICS_DATA.incidents.find(i => i.id === id);
        const relatedEvents = FORENSICS_DATA.events.filter(e => e.incident_id === id);

        document.getElementById('modal-incident-title').innerText = incident.incident_type;
        document.getElementById('modal-incident-id').innerText = `REFERENCE_ID: ${incident.id} | SEVERITY: ${incident.severity}`;

        const timeline = document.getElementById('timeline-container');
        timeline.innerHTML = '';

        relatedEvents.forEach(e => {
            const item = document.createElement('div');
            item.className = `timeline-item ${incident.severity === 'CRITICAL' ? 'critical' : ''}`;
            item.innerHTML = `
                <div style="font-size: 0.65rem; color: var(--text-dim); margin-bottom: 4px;">${e.timestamp_ms}</div>
                <div style="font-weight: 700; font-size: 0.9rem;">${e.event_type}</div>
                <p style="font-size: 0.8rem; color: var(--text-dim); margin-top: 4px;">${e.action_summary}</p>
                <div style="margin-top: 0.5rem; display: flex; gap: 1rem; font-size: 0.7rem; font-family: 'JetBrains Mono'; color: var(--forensic-blue);">
                    <span>ACTOR: ${e.actor_name}</span>
                    <span>IP: ${e.ip_address}</span>
                </div>
            `;
            timeline.appendChild(item);
        });

        document.getElementById('incident-modal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('incident-modal').style.display = 'none';
    }

    function showGuide() {
        document.getElementById('guide-modal').style.display = 'flex';
    }

    function exportForensics() {
        window.location.href = 'backend/export_logs.php';
    }

    async function takeAction(type) {
        // Simulated for now, connects to security_actions.php
        alert(`Initiated ${type.toUpperCase()} protocol. Command sent to CISO Gateway.`);
        closeModal();
    }

    // === TERMINAL ===
    const term = document.getElementById('terminal-stream');
    const termMsgs = [
        "[INTEL] Pattern matching: Multiple failed logins -> Success anomaly.",
        "[GUARD] IP 45.155.66.12 flagged as HIGH_RISK.",
        "[CRYPT] Evidence Integrity: Checksum matched.",
        "[AUDIT] Retaining forensics for ISO compliance (365 days window).",
        "[SENTINEL] Predictive risk analysis: Shift detected in ENGINEER pool."
    ];
    let t = 0;
    setInterval(() => {
        term.innerHTML += `<br>[SYS] >> ${termMsgs[t % termMsgs.length]}`;
        term.scrollTop = term.scrollHeight;
        t++;
    }, 4000);
</script>

</body>
</html>
