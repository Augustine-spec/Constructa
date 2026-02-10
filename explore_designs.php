<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Constructa Explore | Infinite Design Inspiration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="js/favorites_bg.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            900: '#0c4a6e',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        #bg-canvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            pointer-events: none;
        }
        .masonry-grid {
            column-count: 2;
            column-gap: 1.5rem;
        }
        @media (min-width: 640px) { .masonry-grid { column-count: 3; } }
        @media (min-width: 1024px) { .masonry-grid { column-count: 4; } }
        @media (min-width: 1280px) { .masonry-grid { column-count: 5; } }

        .pin-item {
            break-inside: avoid;
            margin-bottom: 1.5rem;
            position: relative;
            border-radius: 1rem;
            overflow: hidden;
            background: #f3f4f6;
            transition: transform 0.2s;
        }
        .pin-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }
        .pin-overlay {
            background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0) 50%);
            opacity: 0;
            transition: opacity 0.2s;
        }
        .pin-item:hover .pin-overlay {
            opacity: 1;
        }
        /* Hide scrollbar for category list */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        
        .loading-shimmer {
            background: #f6f7f8;
            background-image: linear-gradient(to right, #f6f7f8 0%, #edeef1 20%, #f6f7f8 40%, #f6f7f8 100%);
            background-repeat: no-repeat;
            background-size: 800px 100%; 
            animation-duration: 1s;
            animation-fill-mode: forwards; 
            animation-iteration-count: infinite;
            animation-name: placeholderShimmer;
            animation-timing-function: linear;
        }
        
        @keyframes placeholderShimmer {
            0% { background-position: -468px 0; }
            100% { background-position: 468px 0; }
        }

        /* Nav Buttons matching homeowner.php */
        .top-nav-btn {
            padding: 0.8rem 1.5rem;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 4px;
            text-decoration: none;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.8rem;
            color: #1e293b;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .top-nav-btn:hover {
            background: #294033;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
        }
    </style>
</head>
<body class="bg-transparent text-slate-800">
    <div id="bg-canvas"></div>

    <!-- Navbar -->
    <nav class="fixed top-0 w-full bg-white/90 backdrop-blur-md z-50 border-b border-slate-100">
        <div class="max-w-[1800px] mx-auto px-6 md:px-12 py-5 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="plans_designs.php" class="p-2 hover:bg-slate-100 rounded-full text-slate-600 transition">
                    <i class="fa-solid fa-arrow-left text-lg"></i>
                </a>
                <a href="homeowner.php" class="text-2xl font-bold bg-gradient-to-r from-brand-600 to-indigo-600 bg-clip-text text-transparent">
                    Constructa Explore
                </a>
                </a>
            </div>

            <!-- Navigation Actions -->
            <div class="flex items-center gap-6">
                <a href="homeowner.php" class="top-nav-btn">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="backend/logout.php" class="top-nav-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <!-- Categories / Filters -->
        <div class="max-w-[1800px] mx-auto px-4 md:px-8 py-3 flex gap-3 overflow-x-auto no-scrollbar border-t border-slate-50">
            <button onclick="filterGallery('all')" class="filter-btn active whitespace-nowrap px-5 py-2 rounded-full bg-black text-white text-sm font-medium transition">All</button>
            <button onclick="filterGallery('exterior')" class="filter-btn whitespace-nowrap px-5 py-2 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium transition">Exteriors</button>
            <button onclick="filterGallery('interior')" class="filter-btn whitespace-nowrap px-5 py-2 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium transition">Interiors</button>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="pt-44 px-4 md:px-8 pb-10 max-w-[1800px] mx-auto">
        
        <!-- Gallery Grid -->
        <div id="gallery-grid" class="masonry-grid">
            <!-- Items injected via JS -->
        </div>

        <!-- Loading State -->
        <div id="loading-indicator" class="text-center py-10 hidden">
            <span class="inline-block w-8 h-8 border-4 border-brand-200 border-t-brand-600 rounded-full animate-spin"></span>
            <p class="mt-2 text-slate-500 text-sm">Loading more details...</p>
        </div>
    </main>

    <!-- Image Modal -->
    <div id="image-modal" class="fixed inset-0 z-[100] bg-black/90 backdrop-blur-sm hidden flex items-center justify-center opacity-0 transition-opacity duration-300">
        <button onclick="closeModal()" class="absolute top-5 right-5 w-12 h-12 flex items-center justify-center rounded-full bg-white/10 hover:bg-white/20 text-white transition z-10">
            <i class="fa-solid fa-xmark text-xl"></i>
        </button>

        <div class="relative max-w-7xl w-full h-full md:h-[90vh] md:w-[90vw] flex flex-col md:flex-row bg-white md:rounded-2xl overflow-hidden shadow-2xl">
            <!-- Image Side -->
            <div class="w-full md:w-3/4 h-[60vh] md:h-full bg-black flex items-center justify-center relative group">
                <img id="modal-img" src="" alt="Detail" class="max-w-full max-h-full object-contain">
                

            </div>

            <!-- Details Side -->
            <div class="w-full md:w-1/4 h-full bg-white p-6 md:p-8 flex flex-col overflow-y-auto">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-full bg-slate-200"></div>
                    <div>
                        <h3 class="font-semibold text-slate-900">Constructa AI</h3>
                        <p class="text-xs text-slate-500">Design Generator</p>
                    </div>
                </div>

                <h2 id="modal-title" class="text-2xl font-bold text-slate-800 mb-2 leading-tight">Modern Minimalist Exterior</h2>
                <p id="modal-desc" class="text-slate-500 text-sm mb-6 leading-relaxed">
                    A stunning example of contemporary architecture featuring clean lines, sustainable materials, and seamless indoor-outdoor living. Generated for Constructa.
                </p>

                <div class="mt-auto">
                    <h4 class="font-bold text-slate-900 mb-3 text-sm">Specs</h4>
                    <div class="flex flex-wrap gap-2 mb-8">
                        <span class="px-3 py-1 bg-slate-100 rounded-md text-xs text-slate-600">4k Render</span>
                        <span class="px-3 py-1 bg-slate-100 rounded-md text-xs text-slate-600">Modern</span>
                        <span class="px-3 py-1 bg-slate-100 rounded-md text-xs text-slate-600">Architectural</span>
                    </div>

                    <button id="download-btn" onclick="downloadCurrentImage()" class="block w-full bg-brand-600 hover:bg-brand-700 text-white text-center font-bold py-3.5 rounded-full shadow-lg shadow-brand-200 transition">
                        Download Image
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // === REAL HOUSE IMAGE DATA ===
        // Fetched from database
        let galleryData = <?php
            require_once 'backend/config.php';
            
            $dbImages = [];
            try {
                $conn = getDatabaseConnection();
                $result = $conn->query("SELECT * FROM gallery_images ORDER BY created_at DESC");
                
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $dbImages[] = [
                            'id' => $row['id'],
                            'src' => $row['image_url'],
                            'category' => $row['category'],
                            'tags' => [$row['category'], $row['subcategory']],
                            'title' => $row['title'],
                            'desc' => $row['description']
                        ];
                    }
                }
                $conn->close();
            } catch (Exception $e) {
                // Fallback or error logging
                error_log("Error fetching gallery images: " . $e->getMessage());
            }
            
            echo json_encode($dbImages);
        ?>;

        if (!galleryData || galleryData.length === 0) {
            // Fallback if database is empty or connection failed
            console.warn('No images found in database, using fallback data would go here if implemented.');
            galleryData = [];
        }


        // Shuffle
        galleryData = galleryData.sort(() => Math.random() - 0.5);

        async function generateData() {
            // No-op for now, using hardcoded data
            console.log('Using hardcoded gallery data.');
        }

        // === RENDER GALLERY ===
        const grid = document.getElementById('gallery-grid');

        function renderGallery(filter = 'all') {
            grid.innerHTML = ''; // Clear
            
            // Update buttons
            document.querySelectorAll('.filter-btn').forEach(btn => {
                if (btn.textContent.toLowerCase().includes(filter) || (filter === 'all' && btn.textContent === 'All')) {
                    btn.classList.add('bg-black', 'text-white');
                    btn.classList.remove('bg-slate-100', 'text-slate-700');
                } else {
                    btn.classList.remove('bg-black', 'text-white');
                    btn.classList.add('bg-slate-100', 'text-slate-700');
                }
            });

            // Filter Data
            const items = galleryData.filter(item => {
                if (filter === 'all') return true;
                if (item.category === filter) return true;
                if (item.tags.includes(filter)) return true;
                return false;
            });

            // Create HTML
            items.forEach(item => {
                const el = document.createElement('div');
                el.className = 'pin-item group cursor-zoom-in';
                // Remove random height for now to ensure visibility consistency
                
                el.innerHTML = `
                    <div class="loading-shimmer absolute inset-0 z-0"></div>
                    <img src="${item.src}" alt="${item.title}" loading="lazy" 
                        class="w-full h-auto block relative z-10 transition duration-500" 
                        onerror="this.src='https://placehold.co/600x400/f3f4f6/9ca3af?text=Image+Unavailable'; this.classList.add('opacity-100');"
                        onload="this.previousElementSibling.remove()">
                    
                    <div class="pin-overlay absolute inset-0 z-20 flex flex-col justify-end p-4">
                        <div class="flex justify-between items-end translate-y-4 group-hover:translate-y-0 transition duration-300">
                            <div>
                                <h3 class="text-white font-bold text-shadow-sm truncate pr-2 text-sm">${item.title}</h3>
                                <span class="text-xs text-brand-100"><i class="fas fa-check-circle"></i> Verified</span>
                            </div>
                            <button onclick="event.stopPropagation(); downloadImage('${item.src}')" class="bg-white text-black w-8 h-8 rounded-full flex items-center justify-center hover:bg-brand-500 hover:text-white transition shadow-lg">
                                <i class="fa-solid fa-download text-xs"></i>
                            </button>
                        </div>
                    </div>
                `;
                
                el.onclick = () => openModal(item);
                grid.appendChild(el);
            });
        }

        // === ACTIONS ===
        function filterGallery(cat) {
            renderGallery(cat);
        }

        function downloadImage(url) {
            // Download Pexels images properly
            const filename = 'Constructa_House_' + Date.now() + '.jpg';
            
            fetch(url, {
                mode: 'cors',
                headers: {
                    'Accept': 'image/jpeg,image/png,image/*'
                }
            })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.blob();
                })
                .then(blob => {
                    const blobUrl = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = blobUrl;
                    a.download = filename;
                    a.style.display = 'none';
                    document.body.appendChild(a);
                    a.click();
                    
                    // Cleanup
                    setTimeout(() => {
                        document.body.removeChild(a);
                        window.URL.revokeObjectURL(blobUrl);
                    }, 100);
                })
                .catch(err => {
                    console.error('Download failed:', err);
                    // Fallback: open in new tab if download fails
                    window.open(url, '_blank');
                });
        }

        // === MODAL ===
        const modal = document.getElementById('image-modal');
        const modalImg = document.getElementById('modal-img');
        const modalTitle = document.getElementById('modal-title');
        const modalDesc = document.getElementById('modal-desc');
        const dlBtn = document.getElementById('download-btn');
        let currentImageUrl = ''; // Store current image URL for download

        function openModal(item) {
            modalImg.src = item.src;
            modalTitle.textContent = item.title;
            modalDesc.textContent = item.desc;
            currentImageUrl = item.src; // Store the URL for download
            
            modal.classList.remove('hidden');
            // Small delay for fade in
            setTimeout(() => modal.classList.remove('opacity-0'), 10);
            document.body.style.overflow = 'hidden';
        }
        
        function downloadCurrentImage() {
            if (currentImageUrl) {
                downloadImage(currentImageUrl);
            }
        }

        function closeModal() {
            modal.classList.add('opacity-0');
            setTimeout(() => {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }, 300);
        }
        
        // Close on backdrop click
        modal.onclick = (e) => {
            if (e.target === modal) closeModal();
        }

        // Init
        (async () => {
            await generateData();
            renderGallery();
            
            // Initialize 3D Background - Favorites Version
            if (typeof initFavoritesBackground === 'function') {
                initFavoritesBackground('bg-canvas');
            }
        })();

    </script>
</body>
</html>
