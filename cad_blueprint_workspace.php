<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'engineer') {
    header('Location: login.html');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CAD Blueprint Workspace - Constructa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        :root {
            --bg-color: #f6f7f2;
            --sidebar-bg: #ffffff;
            --accent-emerald: #1E7F5C;
            --accent-mint: #DFF6EC;
            --text-main: #1F2937;
            --text-light: #6B7280;
            --border-color: #E5E7EB;
            --glass-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --primary-green: #294033;
            --transition-smooth: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }

        body {
            background-color: var(--bg-color);
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        #canvas-container {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            z-index: -1; pointer-events: none;
        }

        /* Top Header */
        header {
            height: 60px;
            background: white;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            z-index: 10;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        /* Shared Button Style from engineer.php */
        .nav-btn {
            background: white;
            border: 1px solid rgba(0, 0, 0, 0.08);
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-main);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition-smooth);
            text-transform: uppercase;
            cursor: pointer;
        }

        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(41, 64, 51, 0.15);
            background: var(--primary-green);
            color: white;
            border-color: var(--primary-green);
        }
        
        .nav-btn i { font-size: 1rem; }

        .header-left { display: flex; align-items: center; gap: 1rem; }
        
        .doc-title { font-weight: 700; font-size: 1.1rem; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem; }
        .doc-badge { 
            font-size: 0.7rem; background: var(--accent-mint); color: var(--accent-emerald); 
            padding: 2px 8px; border-radius: 12px; font-weight: 600; text-transform: uppercase;
        }

        .header-right { display: flex; align-items: center; gap: 1rem; }

        /* Main Workspace layout */
        .workspace {
            display: flex;
            flex: 1;
            height: calc(100vh - 60px);
        }

        /* Tool Sidebar (Left) */
        .tool-panel {
            width: 80px;
            background: white;
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1.5rem 0;
            gap: 1.5rem;
            z-index: 5;
        }

        .tool-btn {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-light);
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid transparent;
            position: relative;
        }
        .tool-btn:hover {
            background: var(--accent-mint);
            color: var(--accent-emerald);
        }
        .tool-btn.active {
            background: var(--accent-emerald);
            color: white;
            box-shadow: 0 4px 12px rgba(30, 127, 92, 0.3);
        }
        /* Tooltip */
        .tool-btn::after {
            content: attr(title);
            position: absolute;
            left: 60px;
            background: #1f2937;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s;
            white-space: nowrap;
        }
        .tool-btn:hover::after { opacity: 1; }

        /* Canvas Area */
        .canvas-container {
            flex: 1;
            background: rgba(243, 244, 246, 0.4); /* Transparent for 3D bg */
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .canvas-wrapper {
            background: white;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }

        /* Properties Sidebar (Right) */
        .properties-panel {
            width: 280px;
            background: white;
            border-left: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            z-index: 5;
        }

        .panel-section {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .section-header {
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-light);
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .prop-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.8rem;
            font-size: 0.9rem;
        }
        .prop-label { color: var(--text-main); font-weight: 500; }
        .prop-input {
            width: 100px;
            padding: 4px 8px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            text-align: right;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
        }

        .layer-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .layer-item:hover { background: #F9FAFB; }
        .layer-vis { color: var(--text-light); font-size: 0.9rem; }
        .layer-vis.active { color: var(--accent-emerald); }
        .layer-name { font-size: 0.9rem; flex: 1; }

        /* Zoom Controls */
        .zoom-controls {
            position: absolute;
            bottom: 20px;
            right: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: flex;
            gap: 1px;
            padding: 4px;
            z-index: 100;
        }
        .zoom-btn {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border-radius: 4px;
            color: var(--text-main);
        }
        .zoom-btn:hover { background: #F3F4F6; }

        /* Notification Toast */
        .toast {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: #1F2937;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 0.9rem;
            opacity: 0;
            transition: all 0.3s;
            z-index: 100;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        .toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }
        .toast i { color: #34D399; }
        
        .active-users {
            display: flex;
            align-items: center;
            background: #F3F4F6;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.8rem;
            gap: 6px;
        }
        .user-dot { width: 8px; height: 8px; background: #10B981; border-radius: 50%; box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2); }
        
        /* Title Animation */
        .typing-title {
            display: inline-block;
            overflow: hidden;
            white-space: nowrap;
            border-right: 3px solid var(--accent-emerald);
            width: 0;
            max-width: fit-content;
            animation: 
                typing-cycle 6s steps(30, end) infinite,
                blink .75s step-end infinite;
            font-family: 'JetBrains Mono', monospace;
            vertical-align: middle;
        }
        
        @keyframes typing-cycle {
            0% { width: 0; }
            30% { width: 100%; } /* Typed out */
            80% { width: 100%; } /* Pause */
            95% { width: 0; }    /* Deleting */
            100% { width: 0; }
        }
        
        @keyframes blink {
            from, to { border-color: transparent }
            50% { border-color: var(--accent-emerald) }
        }
    </style>
</head>
<body>
    <div id="canvas-container"></div>

    <header>
        <div class="header-left">
            <a href="engineer.php" class="nav-btn"><i class="fas fa-arrow-left"></i> Dashboard</a>
            <div style="width: 1px; height: 24px; background: var(--border-color); margin: 0 0.5rem;"></div>
            <div class="doc-title">
                <i class="fas fa-drafting-compass" style="color:var(--accent-emerald)"></i>
                <span class="typing-title">Constructa CAD Workspace</span>
            </div>
        </div>
        


        <div class="header-right">
            <button class="nav-btn" onclick="exportPDF()"><i class="fas fa-file-export"></i> Export PDF</button>
            <a href="login.html" class="nav-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>

    <div class="workspace">
        
        <!-- TOOLS -->
        <div class="tool-panel">
            <div class="tool-btn active" id="tool-select" title="Select Tool (V)" onclick="setTool('select')">
                <i class="fas fa-mouse-pointer"></i>
            </div>
            <div class="tool-btn" id="tool-wall" title="Draw Wall (W)" onclick="setTool('wall')">
                <i class="fas fa-square-full"></i>
            </div>
            <div class="tool-btn" id="tool-room" title="Room Box (R)" onclick="setTool('room')">
                <i class="fas fa-th-large"></i>
            </div>
            <div class="tool-btn" id="tool-line" title="Line Tool (L)" onclick="setTool('line')">
                <i class="fas fa-pen"></i>
            </div>
            <div class="tool-btn" id="tool-text" title="Text Label (T)" onclick="setTool('text')">
                <i class="fas fa-font"></i>
            </div>
            <div class="tool-btn" id="tool-measure" title="Measure (M)" onclick="setTool('measure')">
                <i class="fas fa-ruler-combined"></i>
            </div>
            
            <div style="flex:1"></div>
            
            <div class="tool-btn" title="Undo (Ctrl+Z)" onclick="undo()">
                <i class="fas fa-undo"></i>
            </div>
            <div class="tool-btn" title="Clear Canvas" onclick="clearCanvas()">
                <i class="fas fa-trash"></i>
            </div>
        </div>

        <!-- CANVAS -->
        <div class="canvas-container">
            <div class="canvas-wrapper">
                <canvas id="c"></canvas>
            </div>
            
            <div class="zoom-controls">
                <div class="zoom-btn" onclick="zoomIn()"><i class="fas fa-plus"></i></div>
                <div class="zoom-btn" id="zoom-display" onclick="zoomReset()" style="width: 50px;">100%</div>
                <div class="zoom-btn" onclick="zoomOut()"><i class="fas fa-minus"></i></div>
            </div>
        </div>

        <!-- SIDEBAR -->
        <div class="properties-panel">
            <div class="panel-section">
                <div class="section-header">Properties</div>
                <div id="prop-content">
                    <div style="color:var(--text-light); font-size:0.9rem; text-align:center; padding:1rem;">
                        Select an element to edit properties
                    </div>
                </div>
            </div>

            <div class="panel-section">
                <div class="section-header">
                    Blueprint Pages
                    <i class="fas fa-plus-circle" style="cursor:pointer; color:var(--accent-emerald)" title="Add New Page" onclick="addLayer()"></i>
                </div>
                <div class="layer-list" id="layer-list-container">
                    <!-- Dynamic Pages injected here -->
                </div>
            </div>

            <div class="panel-section" style="border-bottom:none; flex:1;">
                <div class="section-header">Comments</div>
                <div style="background:#F9FAFB; padding:1rem; border-radius:8px; font-size:0.85rem; color:var(--text-light);">
                    <div style="margin-bottom:0.5rem;"><strong>Admin:</strong> Please check the load-bearing wall thickness on Grid B2.</div>
                    <div style="font-size:0.75rem; color:#9CA3AF;">2 hours ago</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast" id="toast">
        <i class="fas fa-check-circle"></i>
        <span>Blueprint saved successfully to cloud.</span>
    </div>

    <script>
        // --- 3D Background Initialization ---
        const initBackground3D = () => {
            const container = document.getElementById('canvas-container');
            if (!container) return;
            const scene = new THREE.Scene();
            scene.background = new THREE.Color('#f6f7f2');
            scene.fog = new THREE.Fog('#f6f7f2', 10, 45);
            const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 1000);
            camera.position.z = 8;
            camera.position.y = 2;
            const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            container.appendChild(renderer.domElement);
            
            // Lighting from engineer.php
            const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
            scene.add(ambientLight);
            const mainLight = new THREE.DirectionalLight(0xffffff, 0.8);
            mainLight.position.set(10, 10, 10);
            scene.add(mainLight);
            const blueLight = new THREE.PointLight(0x3d5a49, 0.5);
            blueLight.position.set(-5, 5, 5);
            scene.add(blueLight);

            const cityGroup = new THREE.Group();
            scene.add(cityGroup);
            const buildingMaterial = new THREE.MeshPhongMaterial({ color: 0x294033, transparent: true, opacity: 0.1, side: THREE.DoubleSide });
            const edgeMaterial = new THREE.LineBasicMaterial({ color: 0x294033, transparent: true, opacity: 0.3 });
            const gridSize = 10;
            const spacing = 3;
            for (let x = -gridSize; x < gridSize; x++) {
                for (let z = -gridSize; z < gridSize; z++) {
                    const height = Math.random() * 2 + 0.5;
                    const building = new THREE.Group();
                    const geometry = new THREE.BoxGeometry(1, height, 1);
                    const mesh = new THREE.Mesh(geometry, buildingMaterial);
                    mesh.position.y = height / 2;
                    const edges = new THREE.EdgesGeometry(geometry);
                    const line = new THREE.LineSegments(edges, edgeMaterial);
                    line.position.y = height / 2;
                    building.add(mesh);
                    building.add(line);
                    building.position.set(x * spacing, -2, z * spacing);
                    cityGroup.add(building);
                }
            }

            let mouseX = 0;
            let mouseY = 0;
            document.addEventListener('mousemove', (event) => {
                mouseX = (event.clientX - window.innerWidth / 2) * 0.001;
                mouseY = (event.clientY - window.innerHeight / 2) * 0.001;
            });

            let scrollY = 0;
            const animate = () => {
                requestAnimationFrame(animate);
                cityGroup.rotation.y += 0.001;
                cityGroup.rotation.x += 0.05 * (mouseY - cityGroup.rotation.x);
                cityGroup.rotation.y += 0.05 * (mouseX - cityGroup.rotation.y);
                camera.position.y = 2 - scrollY * 2;
                camera.position.z = 8 + scrollY * 5;
                renderer.render(scene, camera);
            };
            animate();

            window.addEventListener('resize', () => {
                camera.aspect = window.innerWidth / window.innerHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(window.innerWidth, window.innerHeight);
            });
        };

        // Initialize 3D Background
        initBackground3D();

        // --- Fabric.js Initialization ---
        const canvas = new fabric.Canvas('c', {
            width: 800,
            height: 600,
            backgroundColor: '#ffffff'
        });
        window.canvas = canvas; // Ensure global access

        // Grid Background
        const gridSize = 20;
        const gridColor = '#f0f0f0';
        const gridGroup = new fabric.Group([], { selectable: false, evented: false });
        for (let i = 0; i < (800 / gridSize); i++) {
            gridGroup.addWithUpdate(new fabric.Line([i * gridSize, 0, i * gridSize, 600], { stroke: gridColor, selectable: false }));
            gridGroup.addWithUpdate(new fabric.Line([0, i * gridSize, 800, i * gridSize], { stroke: gridColor, selectable: false }));
        }
        canvas.add(gridGroup);
        canvas.sendToBack(gridGroup);

        // --- Multi-Page Blueprint System ---
        let layers = [
            { id: 'p1', name: 'Structural Plan', data: null },
            { id: 'p2', name: 'Electrical Layout', data: null },
            { id: 'p3', name: 'Interior Design', data: null }
        ];
        let activeLayerId = 'p1';

        function renderLayerList() {
            const container = document.getElementById('layer-list-container');
            if(!container) return;
            container.innerHTML = '';
            
            layers.forEach(layer => {
                const isActive = layer.id === activeLayerId;
                const activeStyle = isActive ? 'background: var(--accent-mint); border: 1px solid var(--accent-emerald);' : 'border: 1px solid transparent;';
                const activeIcon = isActive ? 'fa-file-signature' : 'fa-file-alt';
                
                const div = document.createElement('div');
                div.className = 'layer-item';
                div.style = activeStyle;
                div.innerHTML = `
                    <i class="fas ${activeIcon}" style="color:${isActive ? 'var(--accent-emerald)' : 'var(--text-light)'}; width:20px; text-align:center;"></i>
                    <span class="layer-name" style="${isActive ? 'font-weight:700; color:var(--accent-emerald)' : ''}" onclick="switchPage('${layer.id}')">${layer.name}</span>
                    <i class="fas fa-trash-alt" style="font-size:0.8rem; color:#9CA3AF; width:20px; text-align:center; opacity: ${isActive ? '0' : '1'}" onclick="deletePage('${layer.id}', event)"></i>
                `;
                container.appendChild(div);
            });
        }

        async function switchPage(id) {
            if (activeLayerId === id) return;

            // 1. Save current page state
            const currentIdx = layers.findIndex(l => l.id === activeLayerId);
            if (currentIdx !== -1) {
                const objects = canvas.getObjects().filter(obj => obj !== gridGroup);
                layers[currentIdx].data = JSON.stringify(objects);
            }

            // 2. Clear Canvas (except grid)
            canvas.getObjects().forEach(obj => {
                if (obj !== gridGroup) canvas.remove(obj);
            });

            // 3. Update Active ID
            activeLayerId = id;
            renderLayerList();

            // 4. Load New Page State
            const targetPage = layers.find(l => l.id === id);
            if (targetPage && targetPage.data) {
                const objectsData = JSON.parse(targetPage.data);
                fabric.util.enlivenObjects(objectsData, (enlivenedObjects) => {
                    enlivenedObjects.forEach(obj => {
                        canvas.add(obj);
                    });
                    canvas.renderAll();
                });
            }
            
            canvas.discardActiveObject();
            canvas.requestRenderAll();
        }

        function addLayer() {
            const name = prompt("Enter Page Name:", "Sheet " + (layers.length + 1));
            if(name) {
                const id = 'p_' + Date.now();
                layers.push({ id: id, name: name, data: null });
                switchPage(id);
            }
        }

        function deletePage(id, e) {
            e.stopPropagation();
            if (layers.length <= 1) return;
            if (confirm("Are you sure you want to delete this page?")) {
                layers = layers.filter(l => l.id !== id);
                if(activeLayerId === id) switchPage(layers[0].id);
                renderLayerList();
            }
        }

        function setActiveLayer(id) {
            switchPage(id);
        }
        
        // Init Layers
        renderLayerList();

        let activeTool = 'select';
        
        function setTool(tool) {
            activeTool = tool;
            document.querySelectorAll('.tool-btn').forEach(btn => btn.classList.remove('active'));
            document.getElementById('tool-' + tool).classList.add('active');
            
            canvas.discardActiveObject();
            canvas.requestRenderAll();
            
            if(tool === 'select') {
                canvas.selection = true;
                canvas.defaultCursor = 'default';
            } else {
                canvas.selection = false;
                canvas.defaultCursor = 'crosshair';
            }
        }

        // --- Drawing Logic ---
        let isDrawing = false;
        let origX, origY;
        let activeObj;

        canvas.on('mouse:down', function(o) {
            if (activeTool === 'select') return;
            
            isDrawing = true;
            const pointer = canvas.getPointer(o.e);
            origX = pointer.x;
            origY = pointer.y;

            if (activeTool === 'wall') {
                const rect = new fabric.Rect({
                    left: origX, top: origY, width: 0, height: 0,
                    fill: '#1f2937', opacity: 0.8
                });
                canvas.add(rect);
                activeObj = rect;
            } else if (activeTool === 'room') {
                const rect = new fabric.Rect({
                    left: origX, top: origY, width: 0, height: 0,
                    fill: 'transparent', stroke: '#1f2937', strokeWidth: 2
                });
                canvas.add(rect);
                activeObj = rect;
            } else if (activeTool === 'line' || activeTool === 'measure') {
                const points = [pointer.x, pointer.y, pointer.x, pointer.y];
                const line = new fabric.Line(points, {
                    strokeWidth: 2, stroke: (activeTool==='measure' ? '#EF4444' : '#1f2937'),
                    strokeDashArray: (activeTool==='measure' ? [5, 5] : null)
                });
                canvas.add(line);
                activeObj = line;
            } else if (activeTool === 'text') {
                const text = new fabric.IText('Label', {
                    left: pointer.x, top: pointer.y,
                    fontFamily: 'Inter', fontSize: 16, fill: '#1f2937'
                });
                canvas.add(text);
                activeObj = null;
                isDrawing = false;
                setTool('select');
            }
        });

        canvas.on('mouse:move', function(o) {
            if (!isDrawing) return;
            const pointer = canvas.getPointer(o.e);

            if (activeTool === 'wall' || activeTool === 'room') {
                if(origX > pointer.x){ activeObj.set({ left: Math.abs(pointer.x) }); }
                if(origY > pointer.y){ activeObj.set({ top: Math.abs(pointer.y) }); }
                activeObj.set({ width: Math.abs(origX - pointer.x) });
                activeObj.set({ height: Math.abs(origY - pointer.y) });
            } else if (activeTool === 'line' || activeTool === 'measure') {
                activeObj.set({ x2: pointer.x, y2: pointer.y });
            }
            canvas.renderAll();
        });

        canvas.on('mouse:up', function(o) {
            isDrawing = false;
            // Snap to grid logic could go here
            if (activeTool !== 'select') {
                setTool('select');
            }
        });

        // --- Selection & Properties ---
        canvas.on('selection:created', updateProps);
        canvas.on('selection:updated', updateProps);
        canvas.on('selection:cleared', () => {
            document.getElementById('prop-content').innerHTML = '<div style="color:var(--text-light); font-size:0.9rem; text-align:center; padding:1rem;">Select an element to edit properties</div>';
        });

        function updateProps() {
            const obj = canvas.getActiveObject();
            if (!obj) return;
            
            // Generate Layer Options
            let layerOptions = '';
            layers.forEach(l => {
                const selected = (obj.layerId === l.id) ? 'selected' : '';
                layerOptions += `<option value="${l.id}" ${selected}>${l.name}</option>`;
            });

            let html = `
                <div class="prop-row"><span class="prop-label">Type</span> <span style="font-weight:700">${obj.type}</span></div>
                <div class="prop-row"><span class="prop-label">Width</span> <input class="prop-input" value="${obj.width?.toFixed(2) || '-'}" onchange="updateObjVal('width', this.value)"></div>
                <div class="prop-row"><span class="prop-label">Height</span> <input class="prop-input" value="${obj.height?.toFixed(2) || '-'}" onchange="updateObjVal('height', this.value)"></div>
                <div class="prop-row"><span class="prop-label">Color</span> <input type="color" value="${obj.fill}" onchange="updateObjVal('fill', this.value)"></div>
            `;
            document.getElementById('prop-content').innerHTML = html;
        }

        window.updateObjVal = (prop, val) => {
            const obj = canvas.getActiveObject();
            if (obj) {
                if (prop === 'width' || prop === 'height') val = parseFloat(val);
                obj.set(prop, val);
                canvas.requestRenderAll();
            }
        };

        // --- Zooming ---
        function updateZoomDisplay() {
            const zoom = Math.round(canvas.getZoom() * 100);
            document.getElementById('zoom-display').innerText = zoom + '%';
        }

        function zoomIn() {
            const center = canvas.getCenter();
            let newZoom = canvas.getZoom() * 1.1;
            if (newZoom > 1) newZoom = 1; // Cap at 100%
            
            canvas.zoomToPoint(new fabric.Point(center.left, center.top), newZoom);
            canvas.requestRenderAll();
            updateZoomDisplay();
        }

        function zoomOut() {
            const center = canvas.getCenter();
            let newZoom = canvas.getZoom() * 0.9;
            if (newZoom < 0.1) newZoom = 0.1; // Min 10%

            canvas.zoomToPoint(new fabric.Point(center.left, center.top), newZoom);
            canvas.requestRenderAll();
            updateZoomDisplay();
        }

        function zoomReset() {
            canvas.setViewportTransform([1, 0, 0, 1, 0, 0]);
            canvas.setZoom(1);
            canvas.requestRenderAll();
            updateZoomDisplay();
        }

        // --- Panning (Space + Drag) ---
        let isDragging = false;
        let lastPosX, lastPosY;

        canvas.on('mouse:down', function(opt) {
            const evt = opt.e;
            if (evt.altKey || evt.shiftKey) { // Or add dedicated pan tool
                isDragging = true;
                canvas.selection = false;
                lastPosX = evt.clientX;
                lastPosY = evt.clientY;
            }
        });
        canvas.on('mouse:move', function(opt) {
            if (isDragging) {
                const e = opt.e;
                const vpt = canvas.viewportTransform;
                vpt[4] += e.clientX - lastPosX;
                vpt[5] += e.clientY - lastPosY;
                canvas.requestRenderAll();
                lastPosX = e.clientX;
                lastPosY = e.clientY;
            }
        });
        canvas.on('mouse:up', function(opt) {
            canvas.setViewportTransform(canvas.viewportTransform);
            isDragging = false;
            if(activeTool === 'select') canvas.selection = true;
        });

        // --- Actions ---
        function undo() {
            // Simplified undo
            const objs = canvas.getObjects();
            if (objs.length > 0) {
                canvas.remove(objs[objs.length - 1]);
            }
        }

        function clearCanvas() {
            canvas.clear();
            // Re-add grid
            canvas.add(gridGroup);
            canvas.sendToBack(gridGroup);
        }
        
        function exportPDF() {
            const toast = document.getElementById('toast');
            
            // 1. Validation: Check if canvas is empty
            // Fabric stores a background line grid if initialized, so we check for user objects
            // Simple check: getObjects() usually returns all.
            // Our grid lines are objects too. We need to distinguish them or filter.
            // In init: we added grid lines with events: false, selectable: false.
            // Let's filter objects that are NOT part of the grid.
            // Grid lines are 'line' type. We can tag them or iterate. 
            // Better: fabric canvas.clear() removes everything, creating it adds grid.
            // Let's check objects count. (800/20 + 600/20) = 40+30 = 70 grid lines.
            // If count > 70 (approx), we have content. 
            
            // Or simpler: Check if there's any object that is selectable or has a specific type like 'rect', 'i-text'.
            const userObjects = canvas.getObjects().filter(obj => obj.selectable === true); // Grid lines are not selectable
            
            if (userObjects.length === 0) {
                toast.innerHTML = '<i class="fas fa-exclamation-circle" style="color:#EF4444"></i><span>Canvas is empty! Draw something to export.</span>';
                toast.classList.add('show');
                setTimeout(() => toast.classList.remove('show'), 3000);
                return;
            }

            // 2. Export
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF({ orientation: 'landscape' });
            
            // Temporarily set background color to white for capture (incase it's transparent)
            const originalBg = canvas.backgroundColor;
            canvas.backgroundColor = "#ffffff";
            
            // Render all just in case
            canvas.renderAll();
            
            const imgData = canvas.toDataURL({
                format: 'png',
                quality: 1.0,
                multiplier: 2 // High res
            });
            
            // Restore bg
            canvas.backgroundColor = originalBg;
            canvas.requestRenderAll();

            const imgProps = pdf.getImageProperties(imgData);
            const pdfWidth = pdf.internal.pageSize.getWidth();
            const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
            
            pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
            pdf.save('Constructa_Blueprint.pdf');
            
            // Success Message
            toast.innerHTML = '<i class="fas fa-check-circle"></i><span>Blueprint exported successfully.</span>';
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }

        // --- Responsive Resize ---
        window.addEventListener('resize', () => {
           // simple fit logic if needed
        });

    </script>
</body>
</html>
