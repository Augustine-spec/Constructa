<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'engineer') {
    header('Location: login.html');
    exit();
}
$username = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Engineer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Requests - Constructa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <style>
        :root {
            --primary: #294033;
            --primary-light: #3d5a49;
            --bg-color: #f6f7f2;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: transparent;
            color: var(--text-main);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* 3D Background Canvas */
        #canvas-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: -1;
            background: #f6f7f2;
            pointer-events: none;
        }

        /* Navbar */
        nav {
            background: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .nav-logo {
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .nav-btn {
            background: white;
            border: 1px solid var(--border-color);
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-weight: 800;
            font-size: 0.85rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--text-main);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.2s ease;
        }

        .nav-btn:hover {
            background: #fff;
            border-color: var(--text-main);
            color: var(--text-main);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .nav-btn i {
            font-size: 1rem;
        }

        /* Main Container */
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 3rem 2rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 0.5rem;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1.5rem;
        }

        .animated-title {
            display: inline-flex;
            gap: 0;
            perspective: 1000px;
        }

        .animated-title span {
            display: inline-block;
            opacity: 0;
            transform: translateY(10px) rotateX(-90deg);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            min-width: 0.2em;
        }

        .animated-title span.visible {
            opacity: 1;
            transform: translateY(0) rotateX(0);
        }

        .page-subtitle {
            color: var(--text-muted);
            font-size: 1.1rem;
        }

        /* Menu Cards Grid */
        .requests-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .menu-card {
            background: #f8fafc;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15), 0 0 1px rgba(0, 0, 0, 0.05);
            cursor: pointer;
            transition: var(--transition);
            border: 1px solid #e2e8f0;
            position: relative;
            overflow: hidden;
        }

        .menu-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            border-color: var(--primary);
        }

        .card-avatar {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background: linear-gradient(135deg, #f97316, #ea580c);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 0.25rem;
        }

        .card-subtitle {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }

        .card-info {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .info-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .info-row i {
            width: 16px;
            color: var(--text-main);
        }

        .card-status {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-pending {
            background: transparent;
            color: #2563eb;
        }

        .status-accepted {
            background: transparent;
            color: #059669;
        }

        .status-rejected {
            background: transparent;
            color: #dc2626;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            max-width: 1000px;
            width: 100%;
            max-height: 90vh;
            overflow: hidden;
            display: flex;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-left {
            flex: 1;
            padding: 2.5rem;
            overflow-y: auto;
        }

        .modal-right {
            width: 350px;
            background: white;
            padding: 2.5rem;
            border-left: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .modal-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-muted);
            cursor: pointer;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: var(--transition);
        }

        .close-btn:hover {
            background: #f1f5f9;
            color: var(--text-main);
        }

        .detail-section {
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 0.75rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 1rem;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        .detail-item {
            background: transparent;
            padding: 0;
        }

        .detail-label {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .detail-value {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-main);
        }

        .description-box {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            border-left: 3px solid var(--primary);
        }

        .description-text {
            color: var(--text-main);
            line-height: 1.6;
            font-size: 0.95rem;
        }

        /* 3D Preview */
        #preview3D {
            width: 100%;
            height: 250px;
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            border-radius: 12px;
            margin-bottom: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .preview-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 0.75rem;
        }

        .homeowner-info {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
        }

        .homeowner-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 0.5rem;
        }

        .homeowner-email {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-top: auto;
        }

        .btn {
            padding: 0.9rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            border: none;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-accept {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-accept:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
        }

        .btn-reject {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .btn-reject:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(239, 68, 68, 0.4);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }

        .no-requests {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .no-requests i {
            font-size: 4rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .modal-content {
                flex-direction: column;
            }
            .modal-right {
                width: 100%;
            }
            .detail-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div id="canvas-container"></div>
    
    <!-- Navbar -->
    <nav>
        <a href="engineer.php" class="nav-logo">
            <i class="far fa-building"></i>
            Constructa
        </a>
        <div class="nav-links">
            <a href="engineer.php" class="nav-btn">
                <i class="fas fa-home"></i> DASHBOARD
            </a>
            <a href="login.html" class="nav-btn">
                <i class="fas fa-sign-out-alt"></i> LOGOUT
            </a>
        </div>
    </nav>

    <div class="main-container">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-clipboard-list"></i> 
                <span id="animated-project-title" class="animated-title">Project Requests</span>
            </h1>
            <p class="page-subtitle">Review and manage incoming project requests from homeowners</p>
        </div>

        <div class="requests-grid">
            <?php
            require_once 'backend/config.php';
            $conn = getDatabaseConnection();
            $engineer_id = $_SESSION['user_id'];
            
            $stmt = $conn->prepare("
                SELECT pr.*, u.name as homeowner_name, u.email as homeowner_email 
                FROM project_requests pr 
                LEFT JOIN users u ON pr.homeowner_id = u.id 
                WHERE pr.engineer_id = ? 
                ORDER BY pr.created_at DESC
            ");
            $stmt->bind_param("i", $engineer_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0):
                while($request = $result->fetch_assoc()):
                    $status_class = 'status-' . $request['status'];
                    $status_text = ucfirst($request['status']);
                    $initials = strtoupper(substr($request['homeowner_name'], 0, 1));
            ?>
                <div class="menu-card <?php echo $status_class; ?>" onclick='openModal(<?php echo json_encode($request); ?>)'>
                    <div class="card-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="card-title"><?php echo htmlspecialchars($request['homeowner_name']); ?></div>
                    <div class="card-subtitle"><?php echo htmlspecialchars($request['project_type']); ?></div>
                    <div class="card-info">
                        <div class="info-row">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo htmlspecialchars($request['location']); ?></span>
                        </div>
                        <div class="info-row">
                            <i class="fas fa-dollar-sign"></i>
                            <span><?php echo htmlspecialchars($request['budget']); ?></span>
                        </div>
                        <div class="info-row">
                            <i class="fas fa-clock"></i>
                            <span><?php echo date('M d, Y', strtotime($request['created_at'])); ?></span>
                        </div>
                    </div>
                    <span class="card-status <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                </div>
            <?php
                endwhile;
            else:
            ?>
                <div class="no-requests" style="grid-column: 1 / -1;">
                    <i class="fas fa-inbox"></i>
                    <h3>No Project Requests Yet</h3>
                    <p style="color: var(--text-muted); margin-top: 0.5rem;">New requests will appear here</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal" id="detailModal">
        <div class="modal-content">
            <div class="modal-left">
                <div class="modal-header">
                    <h2 class="modal-title" id="modalProjectTitle">Project Details</h2>
                    <button class="close-btn" onclick="closeModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="detail-section">
                    <div class="section-title">Project Information</div>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-label">Project Type</div>
                            <div class="detail-value" id="modalProjectType">-</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Location</div>
                            <div class="detail-value" id="modalLocation">-</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Project Size</div>
                            <div class="detail-value" id="modalSize">-</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Budget</div>
                            <div class="detail-value" id="modalBudget">-</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Timeline</div>
                            <div class="detail-value" id="modalTimeline">-</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Contact Phone</div>
                            <div class="detail-value" id="modalPhone">-</div>
                        </div>
                    </div>
                </div>

                <div class="detail-section">
                    <div class="section-title">Project Description</div>
                    <div class="description-box">
                        <p class="description-text" id="modalDescription">-</p>
                    </div>
                </div>
            </div>

            <div class="modal-right">
                <div class="preview-label">3D Preview</div>
                <div id="preview3D"></div>

                <div class="homeowner-info">
                    <div class="homeowner-name" id="modalHomeownerName">-</div>
                    <div class="homeowner-email" id="modalHomeownerEmail">-</div>
                </div>

                <div class="action-buttons">
                    <button class="btn btn-accept" id="acceptBtn" onclick="updateStatus('accepted')">
                        <i class="fas fa-check"></i> Accept Project
                    </button>
                    <button class="btn btn-reject" id="rejectBtn" onclick="updateStatus('rejected')">
                        <i class="fas fa-times"></i> Reject Project
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentRequestId = null;
        let currentStatus = null;

        function openModal(request) {
            currentRequestId = request.id;
            currentStatus = request.status;

            // Populate modal
            document.getElementById('modalProjectTitle').textContent = request.project_title;
            document.getElementById('modalProjectType').textContent = request.project_type;
            document.getElementById('modalLocation').textContent = request.location;
            document.getElementById('modalSize').textContent = request.project_size || 'Not specified';
            document.getElementById('modalBudget').textContent = request.budget;
            document.getElementById('modalTimeline').textContent = request.timeline;
            document.getElementById('modalPhone').textContent = request.contact_phone || 'Not provided';
            document.getElementById('modalDescription').textContent = request.description;
            document.getElementById('modalHomeownerName').textContent = request.homeowner_name;
            document.getElementById('modalHomeownerEmail').textContent = request.homeowner_email;

            // Update button states
            const acceptBtn = document.getElementById('acceptBtn');
            const rejectBtn = document.getElementById('rejectBtn');

            if (request.status === 'accepted') {
                acceptBtn.disabled = true;
                acceptBtn.innerHTML = '<i class="fas fa-check"></i> Already Accepted';
                rejectBtn.disabled = false;
            } else if (request.status === 'rejected') {
                rejectBtn.disabled = true;
                rejectBtn.innerHTML = '<i class="fas fa-times"></i> Already Rejected';
                acceptBtn.disabled = false;
            } else {
                acceptBtn.disabled = false;
                acceptBtn.innerHTML = '<i class="fas fa-check"></i> Accept Project';
                rejectBtn.disabled = false;
                rejectBtn.innerHTML = '<i class="fas fa-times"></i> Reject Project';
            }

            document.getElementById('detailModal').classList.add('active');
            init3DPreview();
        }

        function closeModal() {
            document.getElementById('detailModal').classList.remove('active');
        }

        async function updateStatus(newStatus) {
            const formData = new FormData();
            formData.append('request_id', currentRequestId);
            formData.append('status', newStatus);

            try {
                const response = await fetch('backend/update_request_status.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    if (newStatus === 'accepted') {
                        window.location.href = 'my_projects.php';
                    } else {
                        location.reload();
                    }
                } else {
                    alert(result.message || 'Failed to update status');
                }
            } catch (error) {
                alert('An error occurred. Please try again.');
                console.error(error);
            }
        }

        // Close modal on outside click
        document.getElementById('detailModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // 3D Preview
        function init3DPreview() {
            const container = document.getElementById('preview3D');
            container.innerHTML = ''; // Clear previous
            
            const scene = new THREE.Scene();
            scene.background = new THREE.Color('#e0f2fe');
            
            const camera = new THREE.PerspectiveCamera(50, container.clientWidth / container.clientHeight, 0.1, 1000);
            camera.position.set(5, 5, 8);
            camera.lookAt(0, 0, 0);

            const renderer = new THREE.WebGLRenderer({ antialias: true });
            renderer.setSize(container.clientWidth, container.clientHeight);
            container.appendChild(renderer.domElement);

            const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
            scene.add(ambientLight);
            const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
            directionalLight.position.set(5, 10, 5);
            scene.add(directionalLight);

            const houseMat = new THREE.MeshPhongMaterial({ color: 0x294033, transparent: true, opacity: 0.3 });
            const edgeMat = new THREE.LineBasicMaterial({ color: 0x294033 });

            const baseGeo = new THREE.BoxGeometry(3, 2, 3);
            const baseMesh = new THREE.Mesh(baseGeo, houseMat);
            baseMesh.position.y = 1;
            scene.add(baseMesh);
            
            const baseEdges = new THREE.EdgesGeometry(baseGeo);
            const baseLine = new THREE.LineSegments(baseEdges, edgeMat);
            baseLine.position.copy(baseMesh.position);
            scene.add(baseLine);

            const roofGeo = new THREE.ConeGeometry(2.5, 1.5, 4);
            const roofMesh = new THREE.Mesh(roofGeo, houseMat);
            roofMesh.position.y = 2.75;
            roofMesh.rotation.y = Math.PI / 4;
            scene.add(roofMesh);
            
            const roofEdges = new THREE.EdgesGeometry(roofGeo);
            const roofLine = new THREE.LineSegments(roofEdges, edgeMat);
            roofLine.position.copy(roofMesh.position);
            roofLine.rotation.copy(roofMesh.rotation);
            scene.add(roofLine);

            function animate() {
                requestAnimationFrame(animate);
                baseMesh.rotation.y += 0.005;
                baseLine.rotation.y += 0.005;
                roofMesh.rotation.y += 0.005;
                roofLine.rotation.y += 0.005;
                renderer.render(scene, camera);
            }
            animate();
        }

        // 3D Background
        const initBackground3D = () => {
            const container = document.getElementById('canvas-container');
            if (!container) return;

            const scene = new THREE.Scene();
            scene.background = new THREE.Color('#f6f7f2');
            scene.fog = new THREE.Fog('#f6f7f2', 10, 45);
            const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 1000);
            camera.position.set(0, 5, 10);

            const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            container.appendChild(renderer.domElement);

            const ambientLight = new THREE.AmbientLight(0xffffff, 0.7);
            scene.add(ambientLight);

            const mainLight = new THREE.DirectionalLight(0xffffff, 0.8);
            mainLight.position.set(10, 20, 10);
            scene.add(mainLight);

            const floorGroup = new THREE.Group();
            scene.add(floorGroup);

            const buildMat = new THREE.MeshPhongMaterial({ 
                color: 0x294033, 
                transparent: true, 
                opacity: 0.08, 
                side: THREE.DoubleSide 
            });
            const edgeMat = new THREE.LineBasicMaterial({ 
                color: 0x294033, 
                transparent: true, 
                opacity: 0.15 
            });

            const gridSize = 6;
            const spacing = 4;

            for (let x = -gridSize; x <= gridSize; x++) {
                for (let z = -gridSize; z <= gridSize; z++) {
                    const h = Math.random() * 3 + 1;
                    const geo = new THREE.BoxGeometry(1.2, h, 1.2);
                    const mesh = new THREE.Mesh(geo, buildMat);
                    mesh.position.set(x * spacing, h / 2 - 5, z * spacing);

                    const edges = new THREE.EdgesGeometry(geo);
                    const line = new THREE.LineSegments(edges, edgeMat);
                    line.position.copy(mesh.position);

                    floorGroup.add(mesh);
                    floorGroup.add(line);
                }
            }

            let mouseX = 0, mouseY = 0;
            document.addEventListener('mousemove', (e) => {
                mouseX = (e.clientX - window.innerWidth / 2) * 0.0003;
                mouseY = (e.clientY - window.innerHeight / 2) * 0.0003;
            });

            const animate = () => {
                requestAnimationFrame(animate);
                floorGroup.rotation.y += 0.001;
                floorGroup.rotation.x += 0.05 * (mouseY - floorGroup.rotation.x);
                floorGroup.rotation.y += 0.05 * (mouseX - floorGroup.rotation.y);
                renderer.render(scene, camera);
            };
            animate();

            window.addEventListener('resize', () => {
                camera.aspect = window.innerWidth / window.innerHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(window.innerWidth, window.innerHeight);
            });
        };

        // 3D Title Animation Logic
        const animateProjectTitle = () => {
            const titleContainer = document.getElementById('animated-project-title');
            if (!titleContainer) return;
            
            const text = "Project Requests";
            titleContainer.innerHTML = text.split('').map(char => {
                if (char === ' ') return `<span style="min-width: 0.3em;">&nbsp;</span>`;
                return `<span>${char}</span>`;
            }).join('');
            
            const spans = titleContainer.querySelectorAll('span');
            
            const reveal = () => {
                spans.forEach((span, i) => {
                    setTimeout(() => {
                        span.classList.add('visible');
                    }, i * 150);
                });

                // Reset and loop like the landing page
                setTimeout(() => {
                    spans.forEach((span, i) => {
                        setTimeout(() => {
                            span.classList.remove('visible');
                        }, i * 100);
                    });
                    setTimeout(reveal, (spans.length * 100) + 1000);
                }, (spans.length * 150) + 5000);
            };

            reveal();
        };

        animateProjectTitle();

        if (typeof THREE !== 'undefined') initBackground3D();
    </script>
</body>
</html>
