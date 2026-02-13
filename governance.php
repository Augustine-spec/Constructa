<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.html');
    exit();
}

require_once 'backend/config.php';
$conn = getDatabaseConnection();

// Fetch Policies
$policies = [];
$res = $conn->query("SELECT * FROM gov_policies ORDER BY risk_level DESC");
while($row = $res->fetch_assoc()) {
    $row['conditions'] = json_decode($row['conditions_json'], true);
    $row['actions'] = json_decode($row['action_payload'], true);
    $policies[] = $row;
}

// Global Metrics
$metrics = [
    'compliance_score' => 94,
    'drift_detection' => '0.04%',
    'avg_friction' => 12, // User friction score
    'resilience_status' => 'OPTIMAL'
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Policy Engine | Constructa Governance</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --bg-deep: #050507;
            --bg-glass: rgba(13, 13, 18, 0.7);
            --primary: #3b82f6;
            --primary-glow: rgba(59, 130, 246, 0.4);
            --critical: #ef4444;
            --warning: #f59e0b;
            --success: #10b981;
            --text-high: #f8fafc;
            --text-dim: #94a3b8;
            --border: rgba(255, 255, 255, 0.08);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-deep);
            color: var(--text-high);
            height: 100vh;
            overflow: hidden;
            display: flex;
        }

        #governance-3d-bg {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: -1;
            opacity: 0.2;
        }

        /* Nav Sidebar */
        .sidebar {
            width: 72px;
            background: rgba(0,0,0,0.4);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1.5rem 0;
            gap: 2rem;
            backdrop-filter: blur(20px);
        }

        .nav-icon {
            color: var(--text-dim);
            cursor: pointer;
            transition: 0.3s;
            padding: 12px;
            border-radius: 12px;
            position: relative;
        }

        .nav-icon:hover, .nav-icon.active {
            color: var(--primary);
            background: rgba(59, 130, 246, 0.1);
        }

        /* Workspace Grid */
        .workspace {
            flex: 1;
            display: grid;
            grid-template-columns: 350px 1fr 340px;
            grid-template-rows: 90px 1fr 280px;
            gap: 1.25rem;
            padding: 1.25rem;
        }

        .glass-panel {
            background: var(--bg-glass);
            border: 1px solid var(--border);
            border-radius: 20px;
            backdrop-filter: blur(20px);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }

        /* Top Bar */
        .gov-header {
            grid-column: 1 / span 3;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand-intel { display: flex; align-items: center; gap: 1rem; }
        .brand-intel h1 { font-size: 1.5rem; font-weight: 800; letter-spacing: -0.5px; }
        .brand-intel span { color: var(--primary); }

        .health-stats { display: flex; gap: 2.5rem; }
        .health-item { display: flex; flex-direction: column; }
        .health-label { font-size: 0.65rem; color: var(--text-dim); font-weight: 800; letter-spacing: 1.5px; margin-bottom: 2px; }
        .health-value { font-size: 1.1rem; font-weight: 700; color: var(--primary); }

        /* Left: Policy Domain */
        .policy-domain { grid-row: 2 / span 2; }
        .panel-label {
            padding: 1.25rem;
            border-bottom: 1px solid var(--border);
            font-size: 0.75rem;
            font-weight: 800;
            color: var(--text-dim);
            letter-spacing: 2px;
            display: flex;
            justify-content: space-between;
        }

        .policy-list { flex: 1; overflow-y: auto; padding: 1rem; display: flex; flex-direction: column; gap: 1rem; }

        .policy-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.25rem;
            cursor: pointer;
            transition: 0.3s;
            position: relative;
        }

        .policy-card:hover { border-color: var(--primary); transform: translateX(5px); }
        .policy-card.critical { border-left: 4px solid var(--critical); }
        .policy-card.warning { border-left: 4px solid var(--warning); }

        .policy-meta { display: flex; justify-content: space-between; margin-bottom: 0.5rem; }
        .policy-tag { font-size: 0.6rem; font-weight: 800; padding: 2px 6px; border-radius: 4px; background: rgba(59, 130, 246, 0.1); color: var(--primary); }
        
        /* Center: Adaptive Engine Core */
        .engine-core { grid-column: 2; }
        .viewport-3d { flex: 1; position: relative; background: radial-gradient(circle at center, rgba(59, 130, 246, 0.05) 0%, transparent 70%); }

        .impact-overlay {
            position: absolute;
            bottom: 2rem; left: 2rem; right: 2rem;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }

        .impact-metric {
            background: rgba(0,0,0,0.6);
            border: 1px solid var(--border);
            padding: 1rem;
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }

        /* Right: Compliance & Audit */
        .audit-panel { grid-row: 2; padding: 1.5rem; }
        .compliance-gauge { height: 180px; position: relative; display: flex; align-items: center; justify-content: center; }
        .gauge-val { font-size: 3.5rem; font-weight: 800; color: var(--success); text-shadow: 0 0 20px rgba(16, 185, 129, 0.3); }

        /* Bottom Center: Operational Resilience */
        .resilience-panel { grid-column: 2 / span 2; grid-row: 3; display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; padding: 1.5rem; }

        .rule-terminal {
            background: #000;
            border-radius: 12px;
            padding: 1rem;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.75rem;
            color: var(--success);
            overflow-y: auto;
            position: relative;
        }

        .dry-run-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 0.75rem;
            transition: 0.3s;
        }

        .dry-run-btn:hover { background: #2563eb; transform: scale(1.02); }

        /* Modals */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.85); backdrop-filter: blur(10px);
            display: none; justify-content: center; align-items: center; z-index: 1000;
        }

        .simulation-modal {
            background: var(--bg-deep);
            border: 1px solid var(--border);
            border-radius: 24px;
            width: 900px;
            max-height: 80vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 0 100px rgba(0,0,0,0.9);
        }

        .sim-header { padding: 2rem; border-bottom: 1px solid var(--border); }
        .sim-body { padding: 2rem; display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }

        .outcome-card {
            padding: 1.5rem;
            border-radius: 16px;
            background: rgba(255,255,255,0.02);
            border: 1px solid var(--border);
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 10px; }
    </style>
</head>
<body>

<canvas id="governance-3d-bg"></canvas>

<nav class="sidebar">
    <div class="nav-icon active"><i data-lucide="shield-check"></i></div>
    <div class="nav-icon"><i data-lucide="network"></i></div>
    <div class="nav-icon"><i data-lucide="user-cog"></i></div>
    <div class="nav-icon" style="margin-top: auto;" onclick="window.location.href='settings.php'"><i data-lucide="settings"></i></div>
</nav>

<main class="workspace">
    <!-- Header -->
    <header class="gov-header glass-panel">
        <div class="brand-intel">
            <h1>GOVERNANCE<span>CORE</span></h1>
            <div style="height: 30px; width: 1px; background: var(--border);"></div>
            <div style="font-family: 'JetBrains Mono'; font-size: 0.75rem; color: var(--success); font-weight: 700;">RULESET: ENGINE_V4_ADAPTIVE</div>
        </div>
        
        <div class="health-stats">
            <div class="health-item">
                <span class="health-label">COMPLIANCE</span>
                <span class="health-value"><?php echo $metrics['compliance_score']; ?>%</span>
            </div>
            <div class="health-item">
                <span class="health-label">DRIFT_DETECTION</span>
                <span class="health-value"><?php echo $metrics['drift_detection']; ?></span>
            </div>
            <div class="health-item">
                <span class="health-label">RESILIENCE</span>
                <span class="health-value"><?php echo $metrics['resilience_status']; ?></span>
            </div>
        </div>
    </header>

    <!-- Policy Domains -->
    <aside class="policy-domain glass-panel">
        <div class="panel-label">
            <span>ACTIVE POLICIES</span>
            <span style="color: var(--primary);"><?php echo count($policies); ?> ENFORCED</span>
        </div>
        <div class="policy-list">
            <?php foreach($policies as $pol): ?>
            <div class="policy-card <?php echo strtolower($pol['risk_level']); ?>" onclick="inspectPolicy('<?php echo $pol['id']; ?>')">
                <div class="policy-meta">
                    <span class="policy-tag"><?php echo $pol['category']; ?></span>
                    <span style="font-size: 0.6rem; color: var(--text-dim); font-family: 'JetBrains Mono';"><?php echo $pol['id']; ?></span>
                </div>
                <h3 style="font-size: 0.9rem; margin-bottom: 0.5rem;"><?php echo $pol['name']; ?></h3>
                <p style="font-size: 0.75rem; color: var(--text-dim); line-height: 1.4;"><?php echo $pol['compliance_mapping']; ?></p>
                <div style="margin-top: 1rem; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.65rem; font-weight: 800; color: <?php echo $pol['effectiveness_score'] > 90 ? 'var(--success)' : 'var(--warning)'; ?>">
                        <?php echo $pol['effectiveness_score']; ?>% EFFICIENCY
                    </span>
                    <i data-lucide="chevron-right" style="width: 1rem; color: var(--text-dim);"></i>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </aside>

    <!-- Core Engine Viewport -->
    <section class="engine-core glass-panel">
        <div class="viewport-3d" id="policy-map">
            <div style="position: absolute; top: 1.5rem; left: 2rem;">
                <h2 style="font-size: 1.1rem; font-weight: 800; letter-spacing: 1px;">ADAPTIVE SYSTEM TOPOGRAPHY</h2>
                <p style="font-size: 0.75rem; color: var(--text-dim);">Real-time policy dependency visualization</p>
            </div>
            
            <div class="impact-overlay">
                <div class="impact-metric">
                    <span class="health-label">THREAT_SURFACE</span>
                    <p style="font-size: 1.25rem; font-weight: 800; color: var(--critical);">-14.2%</p>
                </div>
                <div class="impact-metric">
                    <span class="health-label">USER_FRICTION</span>
                    <p style="font-size: 1.25rem; font-weight: 800; color: var(--warning);">+3.1 Index</p>
                </div>
                <div class="impact-metric">
                    <span class="health-label">LATENCY_DELTA</span>
                    <p style="font-size: 1.25rem; font-weight: 800; color: var(--success);">+12ms</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Compliance Audit -->
    <aside class="audit-panel glass-panel">
        <div class="panel-label">COMPLIANCE READINESS</div>
        <div class="compliance-gauge">
            <canvas id="gaugeCanvas"></canvas>
            <div style="position: absolute; text-align: center;">
                <span class="gauge-val">94</span>
                <p style="font-size: 0.65rem; color: var(--text-dim); font-weight: 800; letter-spacing: 2px;">ISO 27001 INDEX</p>
            </div>
        </div>
        
        <div style="margin-top: 1.5rem; display: flex; flex-direction: column; gap: 1rem;">
            <p style="font-size: 0.75rem; font-weight: 800; color: var(--text-dim); letter-spacing: 1.5px;">DRIFT DETECTION LOG</p>
            <div style="padding: 1rem; background: rgba(0,0,0,0.3); border-radius: 12px; border-left: 3px solid var(--success);">
                <p style="font-size: 0.8rem; font-weight: 600;">POL-SEC-001 Integrity Verified</p>
                <p style="font-size: 0.65rem; color: var(--text-dim); margin-top: 4px;">Last scan: 32 seconds ago</p>
            </div>
            <div style="padding: 1rem; background: rgba(0,0,0,0.3); border-radius: 12px; border-left: 3px solid var(--warning);">
                <p style="font-size: 0.8rem; font-weight: 600;">Global_Timeout Drift Detected</p>
                <p style="font-size: 0.65rem; color: var(--text-dim); margin-top: 4px;">Auto-remediated to baseline.</p>
            </div>
        </div>
    </aside>

    <!-- Resilience & Fail-Safe -->
    <section class="resilience-panel glass-panel">
        <div class="rule-terminal" id="gov-log">
            [AUDIT] Integrity Signature: valid [sha256:8f3c...]<br>
            [CORE] Policy Node: POL-RES-042 adaptive threshold observed.<br>
            [CORE] Current Risk Index: 14.5 (STABLE)<br>
        </div>
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <p style="font-size: 0.7rem; font-weight: 800; color: var(--text-dim); letter-spacing: 1px;">SIMULATION & CHANGE CONTROL</p>
            <button class="dry-run-btn" onclick="openSimulation()">
                <i data-lucide="play-circle"></i> INITIATE DRY RUN SIMULATION
            </button>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div style="padding: 0.75rem; background: rgba(255,255,255,0.02); border-radius: 8px; border: 1px solid var(--border);">
                    <p style="font-size: 0.65rem; color: var(--text-dim);">Change Approvals</p>
                    <p style="font-size: 0.9rem; font-weight: 700;">2 PENDING</p>
                </div>
                <div style="padding: 0.75rem; background: rgba(255,255,255,0.02); border-radius: 8px; border: 1px solid var(--border);">
                    <p style="font-size: 0.65rem; color: var(--text-dim);">Active Overrides</p>
                    <p style="font-size: 0.9rem; font-weight: 700;">0 GLOBAL</p>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Simulation Modal -->
<div class="modal-overlay" id="sim-modal">
    <div class="simulation-modal">
        <div class="sim-header">
            <h2 style="font-size: 1.5rem; font-weight: 800;">Policy Impact Simulation</h2>
            <p style="font-size: 0.85rem; color: var(--text-dim);">Running Monte Carlo delta analysis on "Strict Access Windows"</p>
        </div>
        <div class="sim-body">
            <div>
                <h4 style="font-size: 0.8rem; color: var(--primary); margin-bottom: 1rem; letter-spacing: 1px;">PREDICTED OUTCOMES</h4>
                <div class="outcome-card" style="border-left: 4px solid var(--success); margin-bottom: 1rem;">
                    <p style="font-size: 0.75rem; color: var(--text-dim);">Risk Reduction</p>
                    <p style="font-size: 1.25rem; font-weight: 800;">28.4% Surface Area</p>
                </div>
                <div class="outcome-card" style="border-left: 4px solid var(--warning);">
                    <p style="font-size: 0.75rem; color: var(--text-dim);">Operational Friction</p>
                    <p style="font-size: 1.25rem; font-weight: 800;">High (Eng Pool)</p>
                </div>
            </div>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <h4 style="font-size: 0.8rem; color: var(--primary); margin-bottom: 1rem; letter-spacing: 1px;">SYSTEM STABILITY IMPACT</h4>
                <canvas id="simChart" height="150"></canvas>
                <div style="display: flex; gap: 1rem; margin-top: auto;">
                    <button class="dry-run-btn" style="flex: 1; background: transparent; border: 1px solid var(--border);" onclick="closeModal()">CANCEL</button>
                    <button class="dry-run-btn" style="flex: 1; background: var(--critical);">COMMIT WITH OVERRIDE</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();

    // === 3D TOPOGRAPHY ===
    function init3D() {
        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
        const container = document.getElementById('policy-map');
        const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
        renderer.setSize(container.clientWidth, container.clientHeight);
        container.appendChild(renderer.domElement);

        const group = new THREE.Group();
        const geometry = new THREE.SphereGeometry(1, 12, 12);
        const lineMaterial = new THREE.LineBasicMaterial({ color: 0x3b82f6, transparent: true, opacity: 0.2 });

        for(let i=0; i<15; i++) {
            const sphere = new THREE.Mesh(geometry, new THREE.MeshBasicMaterial({ color: 0x3b82f6, wireframe: true, transparent: true, opacity: 0.1 }));
            sphere.position.set(Math.random()*10 - 5, Math.random()*10 - 5, Math.random()*10 - 5);
            group.add(sphere);
            
            // Random connections
            if(i > 0) {
                const points = [group.children[i-1].position, sphere.position];
                const lineGeo = new THREE.BufferGeometry().setFromPoints(points);
                scene.add(new THREE.Line(lineGeo, lineMaterial));
            }
        }
        scene.add(group);
        camera.position.z = 12;

        function animate() {
            requestAnimationFrame(animate);
            group.rotation.y += 0.002;
            group.rotation.x += 0.001;
            renderer.render(scene, camera);
        }
        animate();
    }
    init3D();

    // === COMPLIANCE GAUGE ===
    const gaugeCanvas = document.getElementById('gaugeCanvas');
    const gctx = gaugeCanvas.getContext('2d');
    function drawGauge(val) {
        gctx.clearRect(0,0,300,300);
        gctx.beginPath();
        gctx.arc(150, 100, 70, Math.PI, 2 * Math.PI);
        gctx.strokeStyle = '#1e293b';
        gctx.lineWidth = 12;
        gctx.lineCap = 'round';
        gctx.stroke();

        gctx.beginPath();
        gctx.arc(150, 100, 70, Math.PI, Math.PI + (val/100 * Math.PI));
        gctx.strokeStyle = '#10b981';
        gctx.stroke();
    }
    drawGauge(94);

    // === MODAL CONTROL ===
    function openSimulation() {
        document.getElementById('sim-modal').style.display = 'flex';
        initSimChart();
    }
    function closeModal() {
        document.getElementById('sim-modal').style.display = 'none';
    }

    function initSimChart() {
        const ctx = document.getElementById('simChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['T-5', 'T-4', 'T-3', 'T-2', 'T-1', 'Now'],
                datasets: [{
                    label: 'Predicted Latency (ms)',
                    data: [42, 45, 48, 52, 65, 82],
                    borderColor: '#ef4444',
                    tension: 0.4
                }]
            },
            options: { plugins: { legend: { display: false } }, scales: { y: { display: false }, x: { display: false } } }
        });
    }

    // === TERMINAL LOG ===
    const log = document.getElementById('gov-log');
    const messages = [
        "[POLICY] POL-SEC-001 re-evaluated: RISK_CONTEXT_STABLE",
        "[DRIFT] Baseline comparison check: 100% Alignment.",
        "[INTEL] Identity cluster movement detected: US_EAST_01",
        "[GUARD] Mass deletion safeguard arming for ID:321",
        "[RESILIENCE] Graceful degradation pool status: HEALTHY"
    ];
    let m = 0;
    setInterval(() => {
        log.innerHTML += `<br>[SYS] >> ${messages[m % messages.length]}`;
        log.scrollTop = log.scrollHeight;
        m++;
    }, 5000);

</script>

</body>
</html>
