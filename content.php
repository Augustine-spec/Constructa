<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content & Plans Management | Constructa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="js/architectural_bg.js"></script>
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
                            700: '#0369a1',
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
            z-index: 0;
            pointer-events: none;
        }
        body {
            position: relative;
        }
        header, main {
            position: relative;
            z-index: 1;
        }
        /* Glassmorphism effect for cards */
        .bg-white {
            background: rgba(255, 255, 255, 0.85) !important;
            backdrop-filter: blur(10px);
        }
        /* Navigation buttons - matching login.html */
        .top-nav-btn {
            padding: 0.8rem 1.5rem;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 4px;
            text-decoration: none;
            font-family: 'Outfit', sans-serif;
            font-size: 0.8rem;
            color: #121212;
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
<body class="bg-transparent font-sans">
    <!-- 3D Background -->
    <div id="bg-canvas"></div>

    <!-- Header -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <a href="homeowner.php" class="p-2 hover:bg-slate-100 rounded-full text-slate-600 transition">
                        <i class="fa-solid fa-arrow-left text-lg"></i>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Content & Plans Management</h1>
                        <p class="text-sm text-slate-500">Manage gallery images and design resources</p>
                    </div>
                </div>
                
                <!-- Navigation Buttons -->
                <nav class="flex items-center gap-4">
                    <a href="homeowner.php" class="top-nav-btn">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a href="backend/logout.php" class="top-nav-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl p-6 shadow-sm border border-slate-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500 font-medium">Total Images</p>
                        <p id="total-count" class="text-3xl font-bold text-slate-900 mt-1">0</p>
                    </div>
                    <div class="w-12 h-12 bg-brand-100 rounded-lg flex items-center justify-center">
                        <i class="fa-solid fa-images text-brand-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl p-6 shadow-sm border border-slate-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500 font-medium">Exteriors</p>
                        <p id="exterior-count" class="text-3xl font-bold text-slate-900 mt-1">0</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fa-solid fa-house text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl p-6 shadow-sm border border-slate-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500 font-medium">Interiors</p>
                        <p id="interior-count" class="text-3xl font-bold text-slate-900 mt-1">0</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="fa-solid fa-couch text-purple-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions Bar -->
        <div class="bg-white rounded-xl p-6 shadow-sm border border-slate-200 mb-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <button onclick="filterImages('all')" class="filter-btn active px-4 py-2 rounded-lg font-medium transition text-sm">
                        All
                    </button>
                    <button onclick="filterImages('exterior')" class="filter-btn px-4 py-2 rounded-lg font-medium transition text-sm">
                        Exteriors
                    </button>
                    <button onclick="filterImages('interior')" class="filter-btn px-4 py-2 rounded-lg font-medium transition text-sm">
                        Interiors
                    </button>
                </div>
                
                <button onclick="openAddModal()" class="px-6 py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-medium transition flex items-center gap-2 shadow-lg shadow-brand-200">
                    <i class="fa-solid fa-plus"></i>
                    Add New Image
                </button>
            </div>
        </div>

        <!-- Images Grid -->
        <div id="images-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <!-- Images will be loaded here -->
        </div>

        <!-- Empty State -->
        <div id="empty-state" class="hidden text-center py-16">
            <div class="w-24 h-24 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-image text-slate-400 text-4xl"></i>
            </div>
            <h3 class="text-xl font-bold text-slate-900 mb-2">No images found</h3>
            <p class="text-slate-500 mb-6">Start by adding some images to your gallery</p>
            <button onclick="openAddModal()" class="px-6 py-3 bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-medium transition">
                Add First Image
            </button>
        </div>

    </main>

    <!-- Add Image Modal -->
    <div id="add-modal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl max-w-2xl w-full p-8 shadow-2xl">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-slate-900">Add New Image</h2>
                <button onclick="closeAddModal()" class="w-10 h-10 rounded-full hover:bg-slate-100 flex items-center justify-center transition">
                    <i class="fa-solid fa-xmark text-xl text-slate-600"></i>
                </button>
            </div>

            <form id="add-image-form" class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Image URL</label>
                    <input type="url" id="image-url" required 
                        class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none transition"
                        placeholder="https://images.pexels.com/photos/...">
                    <p class="text-xs text-slate-500 mt-1">Enter the full URL of the image</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Category</label>
                        <select id="category" required 
                            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none transition">
                            <option value="exterior">Exterior</option>
                            <option value="interior">Interior</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Subcategory</label>
                        <input type="text" id="subcategory" 
                            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none transition"
                            placeholder="e.g., living_room, kitchen">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Title</label>
                    <input type="text" id="title" required 
                        class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none transition"
                        placeholder="Modern House Exterior">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Description</label>
                    <textarea id="description" rows="3" 
                        class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none transition resize-none"
                        placeholder="Beautiful house exterior design with modern architecture."></textarea>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="closeAddModal()" 
                        class="flex-1 px-6 py-3 border border-slate-300 text-slate-700 rounded-lg font-medium hover:bg-slate-50 transition">
                        Cancel
                    </button>
                    <button type="submit" 
                        class="flex-1 px-6 py-3 bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-medium transition shadow-lg shadow-brand-200">
                        Add Image
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentFilter = 'all';
        let allImages = [];

        // Load images on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadImages();
        });

        async function loadImages() {
            try {
                const response = await fetch('backend/manage_gallery_images.php?action=get_all');
                const data = await response.json();
                
                if (data.success) {
                    allImages = data.images;
                } else {
                    console.error('Failed to load images:', data.error);
                    allImages = [];
                }
            } catch (error) {
                console.error('Error loading images:', error);
                allImages = [];
            }
            
            // Filter based on current filter
            const filteredImages = currentFilter === 'all' 
                ? allImages 
                : allImages.filter(img => img.category === currentFilter);
            
            renderImages(filteredImages);
            updateStats(allImages); // Always use all images for stats
        }

        function renderImages(images) {
            const grid = document.getElementById('images-grid');
            const emptyState = document.getElementById('empty-state');

            if (images.length === 0) {
                grid.classList.add('hidden');
                emptyState.classList.remove('hidden');
                return;
            }

            grid.classList.remove('hidden');
            emptyState.classList.add('hidden');

            grid.innerHTML = images.map(img => `
                <div class="bg-white rounded-xl overflow-hidden shadow-sm border border-slate-200 hover:shadow-lg transition group">
                    <div class="aspect-video bg-slate-100 relative overflow-hidden">
                        <img src="${img.image_url}" alt="${img.title}" 
                            class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                            onerror="this.src='https://placehold.co/600x400/f3f4f6/9ca3af?text=Image+Error'">
                        <div class="absolute top-2 right-2">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold ${img.category === 'exterior' ? 'bg-green-100 text-green-700' : 'bg-purple-100 text-purple-700'}">
                                ${img.category}
                            </span>
                        </div>
                    </div>
                    <div class="p-4">
                        <h3 class="font-bold text-slate-900 mb-1 truncate">${img.title}</h3>
                        <p class="text-sm text-slate-500 mb-3 line-clamp-2">${img.description || 'No description'}</p>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-slate-400">
                                <i class="fa-solid fa-calendar mr-1"></i>
                                ${new Date(img.created_at).toLocaleDateString()}
                            </span>
                            <button onclick="deleteImage(${img.id})" 
                                class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg text-sm font-medium transition">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function updateStats(images) {
            const total = images.length;
            const exteriors = images.filter(img => img.category === 'exterior').length;
            const interiors = images.filter(img => img.category === 'interior').length;

            document.getElementById('total-count').textContent = total;
            document.getElementById('exterior-count').textContent = exteriors;
            document.getElementById('interior-count').textContent = interiors;
        }

        function filterImages(category) {
            currentFilter = category;
            
            // Update button states
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active', 'bg-brand-600', 'text-white');
                btn.classList.add('bg-slate-100', 'text-slate-700', 'hover:bg-slate-200');
            });
            
            event.target.classList.add('active', 'bg-brand-600', 'text-white');
            event.target.classList.remove('bg-slate-100', 'text-slate-700', 'hover:bg-slate-200');

            // Re-render with local filtering to avoid extra network requests
            const filteredImages = currentFilter === 'all' 
                ? allImages 
                : allImages.filter(img => img.category === currentFilter);
            
            renderImages(filteredImages);
        }

        function openAddModal() {
            document.getElementById('add-modal').classList.remove('hidden');
        }

        function closeAddModal() {
            document.getElementById('add-modal').classList.add('hidden');
            document.getElementById('add-image-form').reset();
        }

        document.getElementById('add-image-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = {
                image_url: document.getElementById('image-url').value,
                category: document.getElementById('category').value,
                subcategory: document.getElementById('subcategory').value,
                title: document.getElementById('title').value,
                description: document.getElementById('description').value
            };

            try {
                const response = await fetch('backend/manage_gallery_images.php?action=add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    closeAddModal();
                    loadImages(); // Reload to show new image
                    // Show success message (optional)
                } else {
                    alert('Failed to add image: ' + (result.error || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while adding the image');
            }
        });

        async function deleteImage(id) {
            if (!confirm('Are you sure you want to delete this image?')) return;
            
            try {
                // Using FormData to send as POST form data since backend expects $_POST['id']
                const formData = new FormData();
                formData.append('id', id);

                const response = await fetch('backend/manage_gallery_images.php?action=delete', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    loadImages(); // Reload list
                } else {
                    alert('Failed to delete image: ' + (result.error || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while deleting the image');
            }
        }

        // Style for active filter button
        const style = document.createElement('style');
        style.textContent = `
            .filter-btn {
                background: #f1f5f9;
                color: #475569;
            }
            .filter-btn:hover {
                background: #e2e8f0;
            }
            .filter-btn.active {
                background: #0284c7;
                color: white;
            }
            .filter-btn.active:hover {
                background: #0369a1;
            }
        `;
        document.head.appendChild(style);

        // Initialize 3D Background
        if (typeof initArchitecturalBackground === 'function') {
            initArchitecturalBackground('bg-canvas');
        }
    </script>

</body>
</html>
