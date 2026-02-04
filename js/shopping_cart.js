// ==================== PREMIUM 3D SHOPPING CART FUNCTIONALITY ====================

// Cart State
let cart = JSON.parse(localStorage.getItem('constructa_cart')) || [];
let animationQueue = [];

// Product Icon Mapping for Realistic Previews
const productIcons = {
    'cement': 'fa-box',
    'steel': 'fa-bars',
    'brick': 'fa-th-large',
    'sand': 'fa-mountain',
    'aggregate': 'fa-cubes',
    'pipe': 'fa-grip-lines',
    'tile': 'fa-th',
    'paint': 'fa-paint-roller',
    'door': 'fa-door-closed',
    'window': 'fa-window-maximize',
    'sink': 'fa-sink',
    'toilet': 'fa-toilet',
    'wire': 'fa-plug',
    'switch': 'fa-toggle-on',
    'default': 'fa-cube'
};

// 3D Preview System
let cart3DPreviews = [];
let isThreeJSAvailable = typeof THREE !== 'undefined';

// Product 3D Model Configurations
const product3DModels = {
    'steel': { type: 'cylinder', color: 0x5a6872, metalness: 0.8, roughness: 0.3 },
    'cement': { type: 'box', color: 0x8b8680, metalness: 0.1, roughness: 0.9 },
    'brick': { type: 'brick', color: 0xa53f3f, metalness: 0, roughness: 0.9 },
    'pipe': { type: 'pipe', color: 0xdddddd, metalness: 0.6, roughness: 0.4 },
    'tile': { type: 'tile', color: 0xf5f5f5, metalness: 0.3, roughness: 0.1 },
    'door': { type: 'door', color: 0x8b4513, metalness: 0, roughness: 0.7 },
    'sink': { type: 'sink', color: 0xaaaaaa, metalness: 0.8, roughness: 0.2 },
    'wire': { type: 'wire', color: 0xff6600, metalness: 0.5, roughness: 0.5 },
    'sand': { type: 'sphere', color: 0xdaa520, metalness: 0, roughness: 1 },
    'aggregate': { type: 'aggregate', color: 0x808080, metalness: 0, roughness: 1 },
    'window': { type: 'window', color: 0x87ceeb, metalness: 0.2, roughness: 0.1 },
    'default': { type: 'box', color: 0x888888, metalness: 0.2, roughness: 0.6 }
};

// Get product 3D config based on name
function getProduct3DConfig(productName) {
    const name = productName.toLowerCase();
    for (const [key, config] of Object.entries(product3DModels)) {
        if (name.includes(key)) {
            return config;
        }
    }
    return product3DModels.default;
}

// Get product icon based on name
function getProductIcon(productName) {
    const name = productName.toLowerCase();
    for (const [key, icon] of Object.entries(productIcons)) {
        if (name.includes(key)) {
            return icon;
        }
    }
    return productIcons.default;
}

// Create 3D Preview for Cart Item
function create3DPreview(container, productName, itemId) {
    if (!isThreeJSAvailable) return null;

    const config = getProduct3DConfig(productName);

    // Scene setup
    const scene = new THREE.Scene();
    scene.background = null;

    // Camera
    const camera = new THREE.PerspectiveCamera(45, 1, 0.1, 100);
    camera.position.set(0, 0.5, 2);
    camera.lookAt(0, 0, 0);

    // Renderer
    const renderer = new THREE.WebGLRenderer({
        antialias: true,
        alpha: true,
        preserveDrawingBuffer: true
    });
    renderer.setSize(80, 80);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;

    // Lighting
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
    scene.add(ambientLight);

    const mainLight = new THREE.DirectionalLight(0xffffff, 1.2);
    mainLight.position.set(2, 3, 2);
    mainLight.castShadow = true;
    scene.add(mainLight);

    const fillLight = new THREE.DirectionalLight(0xffffff, 0.4);
    fillLight.position.set(-2, 1, -2);
    scene.add(fillLight);

    // Create material
    const material = new THREE.MeshStandardMaterial({
        color: config.color,
        metalness: config.metalness,
        roughness: config.roughness
    });

    // Create geometry based on type
    let mesh;
    const group = new THREE.Group();

    switch (config.type) {
        case 'cylinder': // Steel bars
            const geo = new THREE.CylinderGeometry(0.08, 0.08, 1.2, 16);
            mesh = new THREE.Mesh(geo, material);
            mesh.rotation.z = Math.PI / 4;
            group.add(mesh);
            break;

        case 'box': // Cement bags
            const boxGeo = new THREE.BoxGeometry(0.6, 0.3, 0.4);
            mesh = new THREE.Mesh(boxGeo, material);
            group.add(mesh);
            break;

        case 'brick': // Bricks
            const brickGeo = new THREE.BoxGeometry(0.43, 0.13, 0.2);
            mesh = new THREE.Mesh(brickGeo, material);
            group.add(mesh);
            break;

        case 'pipe': // PVC Pipes
            const pipeGeo = new THREE.CylinderGeometry(0.12, 0.12, 1.0, 20);
            mesh = new THREE.Mesh(pipeGeo, material);
            mesh.rotation.z = Math.PI / 2;
            group.add(mesh);
            break;

        case 'tile': // Floor tiles
            const tileGeo = new THREE.BoxGeometry(0.6, 0.05, 0.6);
            mesh = new THREE.Mesh(tileGeo, material);
            group.add(mesh);
            break;

        case 'door': // Wooden door
            const doorGeo = new THREE.BoxGeometry(0.5, 1.0, 0.05);
            mesh = new THREE.Mesh(doorGeo, material);
            // Add door handle
            const handleGeo = new THREE.SphereGeometry(0.03);
            const handleMat = new THREE.MeshStandardMaterial({ color: 0xcccccc, metalness: 0.9 });
            const handle = new THREE.Mesh(handleGeo, handleMat);
            handle.position.set(0.2, 0, 0.03);
            mesh.add(handle);
            group.add(mesh);
            break;

        case 'sink': // Sink
            const sinkGeo = new THREE.BoxGeometry(0.6, 0.15, 0.4);
            mesh = new THREE.Mesh(sinkGeo, material);
            group.add(mesh);
            break;

        case 'wire': // Electrical wire
            const wireGeo = new THREE.TorusGeometry(0.3, 0.05, 16, 32);
            mesh = new THREE.Mesh(wireGeo, material);
            group.add(mesh);
            break;

        case 'sphere': // Sand/aggregate
            const sphereGeo = new THREE.SphereGeometry(0.35, 16, 16);
            mesh = new THREE.Mesh(sphereGeo, material);
            group.add(mesh);
            break;

        case 'aggregate': // Aggregate pile
            for (let i = 0; i < 5; i++) {
                const size = 0.1 + Math.random() * 0.1;
                const aggGeo = new THREE.SphereGeometry(size, 8, 8);
                const aggMesh = new THREE.Mesh(aggGeo, material);
                aggMesh.position.set(
                    (Math.random() - 0.5) * 0.4,
                    (Math.random() - 0.5) * 0.3,
                    (Math.random() - 0.5) * 0.4
                );
                group.add(aggMesh);
            }
            break;

        case 'window': // Window frame
            const frameGeo = new THREE.BoxGeometry(0.6, 0.8, 0.05);
            const frameMat = new THREE.MeshStandardMaterial({ color: 0x8b4513 });
            const frame = new THREE.Mesh(frameGeo, frameMat);
            group.add(frame);

            const glassGeo = new THREE.PlaneGeometry(0.5, 0.7);
            const glassMat = new THREE.MeshStandardMaterial({
                color: config.color,
                transparent: true,
                opacity: 0.3,
                metalness: 0.9,
                roughness: 0.1
            });
            const glass = new THREE.Mesh(glassGeo, glassMat);
            glass.position.z = 0.03;
            group.add(glass);
            break;

        default:
            const defaultGeo = new THREE.BoxGeometry(0.5, 0.5, 0.5);
            mesh = new THREE.Mesh(defaultGeo, material);
            group.add(mesh);
    }

    scene.add(group);

    // Animation
    let animationId;
    const animate = () => {
        animationId = requestAnimationFrame(animate);
        group.rotation.y += 0.01;
        renderer.render(scene, camera);
    };
    animate();

    // Add to container
    container.appendChild(renderer.domElement);

    // Store for cleanup
    const preview = {
        id: itemId,
        scene,
        camera,
        renderer,
        animationId,
        group,
        container
    };

    cart3DPreviews.push(preview);
    return preview;
}

// Cleanup 3D Preview
function cleanup3DPreview(itemId) {
    const index = cart3DPreviews.findIndex(p => p.id === itemId);
    if (index !== -1) {
        const preview = cart3DPreviews[index];

        // Stop animation
        if (preview.animationId) {
            cancelAnimationFrame(preview.animationId);
        }

        // Dispose renderer
        if (preview.renderer) {
            preview.renderer.dispose();
            if (preview.container && preview.renderer.domElement) {
                preview.container.removeChild(preview.renderer.domElement);
            }
        }

        // Dispose scene objects
        if (preview.scene) {
            preview.scene.traverse((object) => {
                if (object.geometry) object.geometry.dispose();
                if (object.material) {
                    if (Array.isArray(object.material)) {
                        object.material.forEach(m => m.dispose());
                    } else {
                        object.material.dispose();
                    }
                }
            });
        }

        cart3DPreviews.splice(index, 1);
    }
}

// Cleanup all 3D previews
function cleanupAll3DPreviews() {
    cart3DPreviews.forEach(preview => {
        if (preview.animationId) {
            cancelAnimationFrame(preview.animationId);
        }
        if (preview.renderer) {
            preview.renderer.dispose();
        }
        if (preview.scene) {
            preview.scene.traverse((object) => {
                if (object.geometry) object.geometry.dispose();
                if (object.material) {
                    if (Array.isArray(object.material)) {
                        object.material.forEach(m => m.dispose());
                    } else {
                        object.material.dispose();
                    }
                }
            });
        }
    });
    cart3DPreviews = [];
}

// Initialize cart on page load
function initCart() {
    updateCartUI();
    updateCartBadge();
    initAmbientMotion();
}

// Ambient Motion Effect
function initAmbientMotion() {
    setInterval(() => {
        const items = document.querySelectorAll('.cart-item');
        items.forEach((item, index) => {
            if (!item.matches(':hover')) {
                const delay = index * 100;
                setTimeout(() => {
                    item.style.transform = `translateY(${Math.sin(Date.now() / 1000 + index) * 2}px)`;
                }, delay);
            }
        });
    }, 50);
}

// Toggle Cart Panel with Enhanced Animation
function toggleCart() {
    const panel = document.getElementById('cartPanel');
    const overlay = document.getElementById('cartOverlay');
    const isOpen = panel.classList.contains('open');

    if (isOpen) {
        panel.classList.remove('open');
        overlay.classList.remove('active');
        // Animate items out
        const items = document.querySelectorAll('.cart-item');
        items.forEach((item, index) => {
            setTimeout(() => {
                item.style.opacity = '0';
                item.style.transform = 'translateX(50px)';
            }, index * 50);
        });

        // Cleanup 3D previews when closing
        setTimeout(() => {
            cleanupAll3DPreviews();
        }, 500);
    } else {
        panel.classList.add('open');
        overlay.classList.add('active');

        // Animate title letters
        setTimeout(() => {
            const letters = document.querySelectorAll('.cart-title span');
            letters.forEach((letter, index) => {
                setTimeout(() => {
                    letter.classList.add('letter-visible');
                }, index * 100); // 100ms delay between each letter
            });
        }, 200);

        // Trigger entrance animations
        setTimeout(() => {
            const items = document.querySelectorAll('.cart-item');
            items.forEach((item, index) => {
                item.style.animationDelay = `${index * 0.1}s`;
            });

            // Initialize 3D previews
            init3DPreviewsForVisibleItems();
        }, 100);
    }
}

// Initialize 3D previews for all visible cart items
function init3DPreviewsForVisibleItems() {
    if (!isThreeJSAvailable) return;

    cart.forEach(item => {
        const container = document.querySelector(`[data-3d-preview-id="${item.id}"]`);
        if (container && !cart3DPreviews.find(p => p.id === item.id)) {
            create3DPreview(container, item.name, item.id);
        }
    });
}

// Add to Cart with Premium Feedback
function addToCart(event, cardElement) {
    event.stopPropagation();

    const productId = cardElement.dataset.productId;
    const productName = cardElement.dataset.productName;
    const productPrice = parseFloat(cardElement.dataset.productPrice);
    const productUnit = cardElement.dataset.productUnit;

    // Check if item already in cart
    const existingItem = cart.find(item => item.id === productId);

    if (existingItem) {
        existingItem.quantity += 1;
        showQuantityBurst(existingItem.quantity);
    } else {
        cart.push({
            id: productId,
            name: productName,
            price: productPrice,
            unit: productUnit,
            quantity: 1,
            icon: getProductIcon(productName)
        });
    }

    saveCart();
    updateCartUI();
    updateCartBadge();

    // Premium Visual Feedback
    const btn = event.currentTarget;
    const originalText = btn.innerHTML;
    const originalBg = btn.style.background;

    // Success animation
    btn.innerHTML = '<i class="fas fa-check success-indicator"></i> Added!';
    btn.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
    btn.style.transform = 'scale(1.05)';
    btn.style.boxShadow = '0 8px 20px rgba(16, 185, 129, 0.4)';

    // Flying cart animation
    createFlyingCartAnimation(event.clientX, event.clientY);

    setTimeout(() => {
        btn.innerHTML = originalText;
        btn.style.background = originalBg;
        btn.style.transform = '';
        btn.style.boxShadow = '';
    }, 1500);
}

// Flying Cart Animation
function createFlyingCartAnimation(startX, startY) {
    const flyingIcon = document.createElement('div');
    flyingIcon.innerHTML = '<i class="fas fa-shopping-cart"></i>';
    flyingIcon.style.cssText = `
        position: fixed;
        left: ${startX}px;
        top: ${startY}px;
        font-size: 2rem;
        color: #10b981;
        pointer-events: none;
        z-index: 10000;
        transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    `;
    document.body.appendChild(flyingIcon);

    // Animate to cart button
    setTimeout(() => {
        const cartBtn = document.querySelector('[onclick*="toggleCart"]');
        if (cartBtn) {
            const rect = cartBtn.getBoundingClientRect();
            flyingIcon.style.left = rect.left + 'px';
            flyingIcon.style.top = rect.top + 'px';
            flyingIcon.style.transform = 'scale(0)';
            flyingIcon.style.opacity = '0';
        }
    }, 50);

    setTimeout(() => {
        flyingIcon.remove();
    }, 1000);
}

// Quantity Burst Effect
function showQuantityBurst(quantity) {
    const burst = document.createElement('div');
    burst.textContent = `+${quantity}`;
    burst.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 3rem;
        font-weight: 800;
        color: #10b981;
        pointer-events: none;
        z-index: 10001;
        animation: burstFade 1s ease-out forwards;
    `;
    document.body.appendChild(burst);

    setTimeout(() => burst.remove(), 1000);
}

// Remove from Cart with Animation
function removeFromCart(productId) {
    const itemElement = document.querySelector(`[data-item-id="${productId}"]`);

    if (itemElement) {
        // Cleanup 3D preview first
        cleanup3DPreview(productId);

        // Animate out
        itemElement.style.transform = 'translateX(100px) rotateY(90deg)';
        itemElement.style.opacity = '0';

        setTimeout(() => {
            cart = cart.filter(item => item.id !== productId);
            saveCart();
            updateCartUI();
            updateCartBadge();
        }, 400);
    } else {
        cart = cart.filter(item => item.id !== productId);
        saveCart();
        updateCartUI();
        updateCartBadge();
    }
}

// Update Quantity with Price Animation
function updateQuantity(productId, delta) {
    const item = cart.find(item => item.id === productId);
    if (item) {
        const oldQuantity = item.quantity;
        item.quantity += delta;

        if (item.quantity <= 0) {
            removeFromCart(productId);
        } else {
            saveCart();

            // Animate price change
            const priceElement = document.querySelector(`[data-price-id="${productId}"]`);
            if (priceElement) {
                priceElement.classList.add('updating');
                setTimeout(() => priceElement.classList.remove('updating'), 500);
            }

            updateCartUI();
            updateCartBadge();

            // Haptic-style feedback
            if (delta > 0) {
                playMicroInteraction('increment');
            } else {
                playMicroInteraction('decrement');
            }
        }
    }
}

// Micro-interaction feedback
function playMicroInteraction(type) {
    const panel = document.getElementById('cartPanel');
    if (panel) {
        if (type === 'increment') {
            panel.style.transform = 'scale(1.002)';
        } else {
            panel.style.transform = 'scale(0.998)';
        }
        setTimeout(() => {
            panel.style.transform = '';
        }, 100);
    }
}

// Save Cart to LocalStorage
function saveCart() {
    localStorage.setItem('constructa_cart', JSON.stringify(cart));
}

// Animate Number Changes
function animateNumber(element, start, end, duration = 500) {
    const startTime = performance.now();

    function update(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);

        const easeOut = 1 - Math.pow(1 - progress, 3);
        const current = start + (end - start) * easeOut;

        element.textContent = '₹' + Math.round(current).toLocaleString('en-IN');

        if (progress < 1) {
            requestAnimationFrame(update);
        }
    }

    requestAnimationFrame(update);
}

// Update Cart UI with Enhanced Animations and 3D Previews
function updateCartUI() {
    const container = document.getElementById('cartItemsContainer');
    const totalElement = document.getElementById('cartTotalAmount');
    const subtotalElement = document.getElementById('cartSubtotal');
    const itemCountElement = document.getElementById('cartItemCount');
    const checkoutBtn = document.getElementById('checkoutBtn');

    // Cleanup existing 3D previews
    cleanupAll3DPreviews();

    if (cart.length === 0) {
        container.innerHTML = `
            <div class="cart-empty">
                <i class="fas fa-shopping-cart"></i>
                <p>Your cart is empty</p>
                <p style="font-size: 0.9rem; color: #888;">Add construction materials to get started!</p>
            </div>
        `;
        if (totalElement) totalElement.textContent = '₹0';
        if (subtotalElement) subtotalElement.textContent = '₹0';
        if (itemCountElement) itemCountElement.textContent = '0 items';
        if (checkoutBtn) checkoutBtn.disabled = true;
        return;
    }

    let total = 0;
    let html = '';

    cart.forEach((item, index) => {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;

        html += `
            <div class="cart-item" data-item-id="${item.id}" style="animation-delay: ${index * 0.1}s">
                <div class="cart-item-content">
                    <div class="cart-item-image">
                        ${isThreeJSAvailable ?
                `<div class="cart-item-3d-preview" data-3d-preview-id="${item.id}"></div>` :
                `<i class="fas ${item.icon || 'fa-cube'}"></i>`
            }
                    </div>
                    <div class="cart-item-details">
                        <div class="cart-item-name">${item.name}</div>
                        <div class="cart-item-spec">₹${item.price.toLocaleString('en-IN')} per ${item.unit}</div>
                        <div class="cart-item-controls">
                            <div class="qty-controls">
                                <button class="qty-btn" onclick="updateQuantity('${item.id}', -1)" title="Decrease quantity">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <span class="qty-display">${item.quantity}</span>
                                <button class="qty-btn" onclick="updateQuantity('${item.id}', 1)" title="Increase quantity">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <span class="cart-item-price" data-price-id="${item.id}">₹${itemTotal.toLocaleString('en-IN')}</span>
                        </div>
                    </div>
                </div>
                <button class="cart-item-remove" onclick="removeFromCart('${item.id}')" title="Remove item">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        `;
    });

    container.innerHTML = html;

    // Initialize 3D previews after DOM update
    if (isThreeJSAvailable) {
        setTimeout(() => {
            init3DPreviewsForVisibleItems();
        }, 100);
    }

    // Animate total change
    const currentTotal = parseInt(totalElement?.textContent.replace(/[₹,]/g, '') || '0');
    if (totalElement && currentTotal !== total) {
        animateNumber(totalElement, currentTotal, total);
    } else if (totalElement) {
        totalElement.textContent = '₹' + total.toLocaleString('en-IN');
    }

    if (subtotalElement) subtotalElement.textContent = '₹' + total.toLocaleString('en-IN');
    if (itemCountElement) {
        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        itemCountElement.textContent = `${totalItems} item${totalItems !== 1 ? 's' : ''}`;
    }
    if (checkoutBtn) checkoutBtn.disabled = false;
}

// Update Cart Badge with Animation
function updateCartBadge() {
    const badge = document.getElementById('cartBadge');
    const badgeNav = document.getElementById('cartBadgeNav');
    const badgeNavGuest = document.getElementById('cartBadgeNavGuest');
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);

    const updateBadgeElement = (element) => {
        if (!element) return;

        const oldValue = parseInt(element.textContent) || 0;
        element.textContent = totalItems;

        if (totalItems > 0) {
            element.style.display = 'flex';
            if (totalItems > oldValue) {
                element.style.animation = 'none';
                setTimeout(() => {
                    element.style.animation = 'successPop 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55)';
                }, 10);
            }
        } else {
            element.style.display = 'none';
        }
    };

    updateBadgeElement(badge);

    if (badgeNav) {
        badgeNav.textContent = totalItems;
        if (totalItems > 0) {
            badgeNav.classList.add('show');
        } else {
            badgeNav.classList.remove('show');
        }
    }

    if (badgeNavGuest) {
        badgeNavGuest.textContent = totalItems;
        if (totalItems > 0) {
            badgeNavGuest.classList.add('show');
        } else {
            badgeNavGuest.classList.remove('show');
        }
    }
}

// Proceed to Checkout with Razorpay Integration
function proceedToCheckout() {
    if (cart.length === 0) {
        showNotification('Your cart is empty!', 'warning');
        return;
    }

    const checkoutBtn = document.getElementById('checkoutBtn');
    const originalText = checkoutBtn.innerHTML;
    checkoutBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Initializing Payment...';
    checkoutBtn.disabled = true;

    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const totalInPaise = Math.round(total * 100); // Razorpay requires amount in paise

    const orderData = {
        items: cart,
        total: total,
        timestamp: new Date().toISOString()
    };

    // Create Razorpay order on backend
    fetch('backend/create_razorpay_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ amount: totalInPaise, cart: cart })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.order_id) {
                // Initialize Razorpay checkout
                const options = {
                    key: 'rzp_test_S60Mda5xiv9lpa', // Your Razorpay test key
                    amount: totalInPaise,
                    currency: 'INR',
                    name: 'Constructa',
                    description: 'Construction Materials Purchase',
                    image: 'https://via.placeholder.com/100x100?text=Constructa', // Your logo
                    order_id: data.order_id,
                    handler: function (response) {
                        // Payment successful
                        verifyPayment(response, orderData);
                    },
                    prefill: {
                        name: '',
                        email: '',
                        contact: ''
                    },
                    notes: {
                        order_type: 'material_purchase',
                        item_count: cart.length
                    },
                    theme: {
                        color: '#294033'
                    },
                    modal: {
                        ondismiss: function () {
                            // Payment cancelled
                            checkoutBtn.innerHTML = originalText;
                            checkoutBtn.disabled = false;
                            showNotification('Payment cancelled', 'warning');
                        }
                    }
                };

                const rzp = new Razorpay(options);
                rzp.open();

                // Reset button after opening Razorpay
                checkoutBtn.innerHTML = originalText;
                checkoutBtn.disabled = false;

            } else {
                showNotification('Error initializing payment: ' + (data.message || 'Unknown error'), 'error');
                checkoutBtn.innerHTML = originalText;
                checkoutBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred while initializing payment. Please try again.', 'error');
            checkoutBtn.innerHTML = originalText;
            checkoutBtn.disabled = false;
        });
}

// Verify Payment
function verifyPayment(razorpayResponse, orderData) {
    const checkoutBtn = document.getElementById('checkoutBtn');
    checkoutBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying Payment...';
    checkoutBtn.disabled = true;

    fetch('backend/verify_razorpay_payment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            razorpay_payment_id: razorpayResponse.razorpay_payment_id,
            razorpay_order_id: razorpayResponse.razorpay_order_id,
            razorpay_signature: razorpayResponse.razorpay_signature,
            order_data: orderData
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccessModal(data.order_id, orderData.total);

                // Clear cart
                cart = [];
                saveCart();
                updateCartUI();
                updateCartBadge();

                setTimeout(() => {
                    toggleCart();
                }, 2000);
            } else {
                showNotification('Payment verification failed: ' + data.message, 'error');
                checkoutBtn.innerHTML = '<i class="fas fa-check-circle"></i> Proceed to Checkout';
                checkoutBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred while verifying payment. Please contact support.', 'error');
            checkoutBtn.innerHTML = '<i class="fas fa-check-circle"></i> Proceed to Checkout';
            checkoutBtn.disabled = false;
        });
}

// Show Success Modal
function showSuccessModal(orderId, total) {
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(10px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10002;
        animation: fadeIn 0.3s ease;
    `;

    modal.innerHTML = `
        <div style="
            background: white;
            padding: 3rem;
            border-radius: 20px;
            text-align: center;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: successPop 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        ">
            <div style="
                width: 80px;
                height: 80px;
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 1.5rem;
                animation: successPop 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55) 0.2s backwards;
            ">
                <i class="fas fa-check" style="font-size: 2.5rem; color: white;"></i>
            </div>
            <h2 style="font-size: 1.8rem; margin-bottom: 1rem; color: #121212;">Order Placed Successfully!</h2>
            <p style="font-size: 1rem; color: #555; margin-bottom: 0.5rem;">Order ID: <strong>${orderId}</strong></p>
            <p style="font-size: 1.5rem; font-weight: 700; color: #294033; margin-bottom: 1.5rem;">Total: ₹${total.toLocaleString('en-IN')}</p>
            <p style="font-size: 0.95rem; color: #666;">You will be contacted shortly for delivery details.</p>
        </div>
    `;

    document.body.appendChild(modal);

    setTimeout(() => {
        modal.style.opacity = '0';
        setTimeout(() => modal.remove(), 300);
    }, 3000);
}

// Show Notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    const colors = {
        success: '#10b981',
        error: '#ef4444',
        warning: '#f59e0b',
        info: '#3b82f6'
    };

    notification.style.cssText = `
        position: fixed;
        top: 2rem;
        right: 2rem;
        background: ${colors[type]};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        z-index: 10003;
        animation: slideInRight 0.3s ease;
        font-weight: 600;
    `;
    notification.textContent = message;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    @keyframes burstFade {
        0% { transform: translate(-50%, -50%) scale(0); opacity: 1; }
        100% { transform: translate(-50%, -50%) scale(2); opacity: 0; }
    }
`;
document.head.appendChild(style);

// Initialize cart when page loads
document.addEventListener('DOMContentLoaded', () => {
    initCart();
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    cleanupAll3DPreviews();
});
