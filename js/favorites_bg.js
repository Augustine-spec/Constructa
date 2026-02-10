/**
 * 3D Background - Favorites Version
 * Exact match of the background used in saved_favorites.php
 */
function initFavoritesBackground(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const scene = new THREE.Scene();
    scene.background = new THREE.Color('#f8fafc');

    const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 1000);
    camera.position.z = 8;
    camera.position.y = 2;

    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

    // Clear container and append
    container.innerHTML = '';
    container.appendChild(renderer.domElement);

    // Lighting
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
    scene.add(ambientLight);
    const mainLight = new THREE.DirectionalLight(0xffffff, 0.8);
    mainLight.position.set(10, 10, 10);
    scene.add(mainLight);
    const blueLight = new THREE.PointLight(0x3d5a49, 0.5);
    blueLight.position.set(-5, 5, 5);
    scene.add(blueLight);

    // Objects
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

    // Hero House (Central Floating Object)
    const houseGroup = new THREE.Group();
    const baseGeo = new THREE.BoxGeometry(2, 2, 2);
    const baseEdges = new THREE.EdgesGeometry(baseGeo);
    const baseLine = new THREE.LineSegments(baseEdges, new THREE.LineBasicMaterial({ color: 0x294033, linewidth: 2 }));
    houseGroup.add(baseLine);
    const roofGeo = new THREE.ConeGeometry(1.5, 1.2, 4);
    const roofEdges = new THREE.EdgesGeometry(roofGeo);
    const roofLine = new THREE.LineSegments(roofEdges, new THREE.LineBasicMaterial({ color: 0x3d5a49, linewidth: 2 }));
    roofLine.position.y = 1.6;
    roofLine.rotation.y = Math.PI / 4;
    houseGroup.add(roofLine);

    const floatGroup = new THREE.Group();
    floatGroup.add(houseGroup);
    floatGroup.position.set(0, 0, 2);
    scene.add(floatGroup);

    // Animation
    let mouseX = 0, mouseY = 0;
    document.addEventListener('mousemove', (event) => {
        mouseX = (event.clientX - window.innerWidth / 2) * 0.001;
        mouseY = (event.clientY - window.innerHeight / 2) * 0.001;
    });

    const animate = () => {
        requestAnimationFrame(animate);
        cityGroup.rotation.y += 0.001;
        floatGroup.rotation.y += 0.005;
        floatGroup.position.y = Math.sin(Date.now() * 0.001) * 0.5 + 0.5;

        // Interactive tilt
        cityGroup.rotation.x += 0.05 * (mouseY - cityGroup.rotation.x);
        cityGroup.rotation.y += 0.05 * (mouseX - cityGroup.rotation.y);

        renderer.render(scene, camera);
    };
    animate();

    window.addEventListener('resize', () => {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    });
}
