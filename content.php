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
    <div id="add-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-md hidden flex items-center justify-center z-50 p-4 transition-all duration-300">
        <div class="bg-white rounded-3xl max-w-4xl w-full flex flex-col md:flex-row overflow-hidden shadow-[0_20px_60px_-15px_rgba(0,0,0,0.3)] border border-white/20">
            
            <!-- Left: Visual Guide & Preview (35%) -->
            <div class="md:w-5/12 bg-slate-50 p-8 border-r border-slate-100 flex flex-col items-center justify-center text-center">
                <div id="preview-placeholder" class="w-full aspect-square bg-slate-200 rounded-2xl flex flex-col items-center justify-center border-2 border-dashed border-slate-300 text-slate-400 group transition-all duration-500">
                    <i class="fa-solid fa-cloud-arrow-up text-5xl mb-4 group-hover:scale-110 transition"></i>
                    <p class="text-sm font-medium text-slate-600">Image Preview</p>
                    <p class="text-[11px] mt-1 text-slate-400 font-normal">Link will appear here once validated</p>
                </div>
                <div id="image-preview-container" class="hidden w-full aspect-square rounded-2xl overflow-hidden shadow-xl border-4 border-white">
                    <img id="live-preview-img" src="" alt="Preview" class="w-full h-full object-cover">
                </div>
                
                <div class="mt-8 text-left w-full space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="w-6 h-6 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center shrink-0 mt-0.5"><i class="fa-solid fa-check text-[10px]"></i></div>
                        <div>
                            <p class="text-xs font-bold text-slate-800 uppercase tracking-wider">Quality First</p>
                            <p class="text-[11px] text-slate-500">High-resolution assets ensure a premium user experience.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Input Collection (65%) -->
            <div class="md:w-7/12 p-8 flex flex-col">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h2 id="modal-title" class="text-2xl font-bold text-slate-900 tracking-tight text-brand-900">Resource Entry</h2>
                        <div class="flex items-center gap-2 mt-1">
                            <span id="step-1-indicator" class="w-10 h-1 rounded-full bg-brand-600"></span>
                            <span id="step-2-indicator" class="w-10 h-1 rounded-full bg-slate-200"></span>
                        </div>
                    </div>
                    <button onclick="closeAddModal()" class="w-10 h-10 rounded-xl hover:bg-slate-100 flex items-center justify-center transition">
                        <i class="fa-solid fa-xmark text-slate-500"></i>
                    </button>
                </div>

                <form id="add-image-form" class="flex-1 space-y-6">
                    <!-- Step 1: Data entry -->
                    <div id="form-step-input" class="space-y-5">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest pl-1">Image URL</label>
                            <div class="relative group">
                                <input type="url" id="image-url" required 
                                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-brand-500/10 focus:border-brand-500 outline-none transition text-sm font-medium"
                                    placeholder="https://images.pexels.com/photos/..."
                                    oninput="handleUrlInput()">
                                <div id="url-status" class="absolute right-4 top-1/2 -translate-y-1/2 hidden">
                                    <i class="fa-solid fa-circle-check text-green-500"></i>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest pl-1">Category</label>
                                <select id="category" required onchange="handleCategoryChange()"
                                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-brand-500/10 outline-none transition text-sm font-medium cursor-pointer appearance-none">
                                    <option value="exterior">Exterior View</option>
                                    <option value="interior">Interior View</option>
                                </select>
                            </div>
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest pl-1">Refinement</label>
                                <input type="text" id="subcategory" list="sub-options"
                                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-brand-500/10 outline-none transition text-sm font-medium"
                                    placeholder="e.g., Living Room">
                                <datalist id="sub-options"></datalist>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest pl-1">Professional Title</label>
                            <input type="text" id="title" required 
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-brand-500/10 outline-none transition text-sm font-medium"
                                placeholder="Descriptive Name">
                            <p class="text-[10px] text-slate-400 pl-1">Use a title that clearly describes the design aesthetic.</p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest pl-1">Detailed Description</label>
                            <textarea id="description" rows="3" 
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-brand-500/10 outline-none transition text-sm font-medium resize-none shadow-inner"
                                placeholder="Key architectural highlights and materials..."></textarea>
                            <p class="text-[10px] text-slate-400 pl-1">Provide context for the engineering and design choices.</p>
                        </div>
                    </div>

                    <!-- Step 2: Confirmation -->
                    <div id="form-step-confirm" class="hidden space-y-6 animate-in slide-in-from-right duration-300">
                        <div class="bg-brand-50/50 p-6 rounded-2xl border border-brand-100/50">
                            <h4 class="text-xs font-bold text-brand-600 uppercase tracking-widest mb-4">Summary Verification</h4>
                            <div class="grid grid-cols-2 gap-y-4 gap-x-6">
                                <div>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase mb-1">Asset Identity</p>
                                    <p id="summary-title" class="text-sm font-bold text-slate-900">--</p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase mb-1">Classification</p>
                                    <p id="summary-taxonomy" class="text-sm font-bold text-slate-900 italic">--</p>
                                </div>
                                <div class="col-span-2">
                                    <p class="text-[10px] text-slate-400 font-bold uppercase mb-1">Resource Context</p>
                                    <p id="summary-desc" class="text-sm text-slate-700 leading-relaxed font-medium">--</p>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 p-4 bg-amber-50/50 border border-amber-100 rounded-xl">
                            <i class="fa-solid fa-shield-halved text-amber-500"></i>
                            <p class="text-[11px] text-amber-700 font-medium">Review the image preview on the left to ensure the URL is valid and the quality meets platform standards.</p>
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex flex-col sm:flex-row gap-3 pt-6 border-t border-slate-100">
                        <button type="button" id="prev-btn" onclick="goToStep(1)" class="hidden flex-1 px-6 py-3.5 bg-slate-50 text-slate-500 rounded-2xl font-bold hover:bg-slate-100 transition flex items-center justify-center gap-2 border border-slate-200">
                            <i class="fa-solid fa-arrow-left text-[10px]"></i> Back
                        </button>
                        <button type="button" id="cancel-btn" onclick="closeAddModal()" class="flex-1 px-6 py-3.5 bg-slate-50 text-slate-500 rounded-2xl font-bold hover:bg-slate-100 hover:text-red-500 transition border border-slate-100">
                            Cancel
                        </button>
                        <button type="button" id="next-btn" onclick="goToStep(2)" class="flex-1 px-6 py-3.5 bg-brand-600 text-white rounded-2xl font-bold hover:bg-brand-700 transition shadow-lg shadow-brand-200 flex items-center justify-center gap-2">
                            Review Summary <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </button>
                        <button type="submit" id="submit-btn" class="hidden flex-1 px-6 py-3.5 bg-brand-600 text-white rounded-2xl font-bold hover:bg-brand-700 transition shadow-lg shadow-brand-200">
                            Confirm & Publish
                        </button>
                    </div>
                </form>
            </div>
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
            goToStep(1);
            handleCategoryChange();
        }

        function closeAddModal() {
            document.getElementById('add-modal').classList.add('hidden');
            document.getElementById('add-image-form').reset();
            resetPreview();
            goToStep(1);
        }

        // --- NEW MODAL UI LOGIC ---

        function validateTextInput(input, fieldName) {
            const originalValue = input.value;
            // Strict rule: No digits and no arithmetic symbols/math notations
            // Math symbols: + - * / = % < >
            // Digits: 0-9
            const cleanedValue = originalValue.replace(/[0-9\+\-\*\/\=%<>]/g, '');
            
            if (originalValue !== cleanedValue) {
                input.value = cleanedValue;
                showValidationError(input, `Numbers and math operators are not allowed in ${fieldName}`);
                return false;
            }
            return true;
        }

        function showValidationError(input, message) {
            // Add a temporary shake effect
            input.classList.add('border-red-400', 'animate-shake');
            setTimeout(() => {
                input.classList.remove('border-red-400', 'animate-shake');
            }, 500);
        }

        // Attach live listeners
        document.addEventListener('DOMContentLoaded', () => {
            const titleInput = document.getElementById('title');
            const subInput = document.getElementById('subcategory');
            const descInput = document.getElementById('description');

            const fields = [
                { el: titleInput, name: 'Title' },
                { el: subInput, name: 'Sub-Type' },
                { el: descInput, name: 'Description' }
            ];

            fields.forEach(field => {
                if (field.el) {
                    field.el.addEventListener('input', (e) => validateTextInput(e.target, field.name));
                }
            });
        });

        // Shake animation style
        const validationStyle = document.createElement('style');
        validationStyle.textContent = `
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
            .animate-shake { animation: shake 0.2s ease-in-out 0s 2; }
        `;
        document.head.appendChild(validationStyle);

        function handleUrlInput() {
            const url = document.getElementById('image-url').value;
            const previewImg = document.getElementById('live-preview-img');
            const previewPlaceholder = document.getElementById('preview-placeholder');
            const previewContainer = document.getElementById('image-preview-container');
            const statusIcon = document.getElementById('url-status');

            if (!url) {
                resetPreview();
                return;
            }

            // Enhanced validation
            const isImage = url.match(/\.(jpeg|jpg|gif|png|webp|svg|avif)$/i) || 
                            url.includes('images.pexels.com') || 
                            url.includes('images.unsplash.com') ||
                            url.includes('plus.unsplash.com');

            if (isImage) {
                previewImg.src = url;
                previewImg.onload = () => {
                    previewPlaceholder.classList.add('hidden');
                    previewContainer.classList.remove('hidden');
                    statusIcon.classList.remove('hidden');
                    statusIcon.innerHTML = '<i class="fa-solid fa-circle-check text-green-500"></i>';
                };
                previewImg.onerror = () => {
                    resetPreview();
                    statusIcon.classList.remove('hidden');
                    statusIcon.innerHTML = '<i class="fa-solid fa-circle-exclamation text-red-400"></i>';
                };
            } else if (url.length > 10) {
                statusIcon.classList.remove('hidden');
                statusIcon.innerHTML = '<i class="fa-solid fa-circle-exclamation text-amber-400"></i>';
                resetPreview();
            }
        }

        function resetPreview() {
            document.getElementById('live-preview-img').src = '';
            document.getElementById('preview-placeholder').classList.remove('hidden');
            document.getElementById('image-preview-container').classList.add('hidden');
            document.getElementById('url-status').classList.add('hidden');
        }

        function handleCategoryChange() {
            const category = document.getElementById('category').value;
            const suggestions = document.getElementById('sub-options');
            const subInput = document.getElementById('subcategory');

            const options = {
                exterior: ['Modern', 'Traditional', 'Contemporary', 'Minimalist', 'Luxury Villa', 'Coastal', 'Mountain Cabin', 'Industrial', 'Colonial'],
                interior: ['Living Room', 'Modular Kitchen', 'Master Bedroom', 'Luxury Bathroom', 'Home Office', 'Dining Hall', 'Kids Room', 'Studio', 'Walk-in Closet']
            };

            const list = options[category] || [];
            suggestions.innerHTML = list.map(opt => `<option value="${opt}">`).join('');
            subInput.placeholder = category === 'exterior' ? 'e.g., Luxury Villa' : 'e.g., Modular Kitchen';
        }

        function goToStep(step) {
            const stepInput = document.getElementById('form-step-input');
            const stepConfirm = document.getElementById('form-step-confirm');
            const nextBtn = document.getElementById('next-btn');
            const submitBtn = document.getElementById('submit-btn');
            const prevBtn = document.getElementById('prev-btn');
            const cancelBtn = document.getElementById('cancel-btn');
            const step1Ind = document.getElementById('step-1-indicator');
            const step2Ind = document.getElementById('step-2-indicator');
            const modalTitle = document.getElementById('modal-title');

            if (step === 1) {
                stepInput.classList.remove('hidden');
                stepConfirm.classList.add('hidden');
                nextBtn.classList.remove('hidden');
                submitBtn.classList.add('hidden');
                prevBtn.classList.add('hidden');
                cancelBtn.classList.remove('hidden');
                step1Ind.className = 'w-10 h-1 rounded-full bg-brand-600 transition-all';
                step2Ind.className = 'w-10 h-1 rounded-full bg-slate-200 transition-all';
                modalTitle.innerText = 'Resource Entry';
            } else {
                // Populate Summary
                document.getElementById('summary-title').innerText = document.getElementById('title').value || 'Untitled Design';
                const cat = document.getElementById('category').value;
                const subCat = document.getElementById('subcategory').value;
                document.getElementById('summary-taxonomy').innerText = `${cat.charAt(0).toUpperCase() + cat.slice(1)} • ${subCat || 'General'}`;
                document.getElementById('summary-desc').innerText = document.getElementById('description').value || 'No additional summary provided.';

                stepInput.classList.add('hidden');
                stepConfirm.classList.remove('hidden');
                nextBtn.classList.add('hidden');
                submitBtn.classList.remove('hidden');
                prevBtn.classList.add('hidden');
                prevBtn.classList.remove('hidden');
                cancelBtn.classList.add('hidden');
                step1Ind.className = 'w-10 h-1 rounded-full bg-brand-100 transition-all';
                step2Ind.className = 'w-10 h-1 rounded-full bg-brand-600 transition-all';
                modalTitle.innerText = 'Final Review';
            }
        }

        document.getElementById('add-image-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // If we are on step 1, clicking submit (or hitting enter) should move to step 2
            if (!document.getElementById('form-step-input').classList.contains('hidden')) {
                goToStep(2);
                return;
            }

            const formData = {
                image_url: document.getElementById('image-url').value,
                category: document.getElementById('category').value,
                subcategory: document.getElementById('subcategory').value,
                title: document.getElementById('title').value,
                description: document.getElementById('description').value
            };

            const submitBtn = document.getElementById('submit-btn');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Publishing...';

            try {
                const response = await fetch('backend/manage_gallery_images.php?action=add', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
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
