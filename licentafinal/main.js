// Global variables
let cart = JSON.parse(localStorage.getItem('cart')) || [];

// Featured products data
const featuredProducts = [
    {
        id: 1,
        name: "Dulceață de Căpșuni de Argeș",
        price: "18.99",
        weight: "350g",
        region: "Argeș",
        image: "https://via.placeholder.com/300x200/8B0000/FFFFFF?text=Dulceata+Capsuni",
        description: "Dulceață tradițională din căpșuni proaspete de Argeș"
    },
    {
        id: 2,
        name: "Zacuscă de Buzău",
        price: "15.50",
        weight: "450g",
        region: "Buzău",
        image: "https://via.placeholder.com/300x200/8B0000/FFFFFF?text=Zacusca+Buzau",
        description: "Zacuscă tradițională cu vinete și ardei copți"
    },
    {
        id: 3,
        name: "Brânză de Burduf",
        price: "32.00",
        weight: "500g",
        region: "Maramureș",
        image: "https://via.placeholder.com/300x200/8B0000/FFFFFF?text=Branza+Burduf",
        description: "Brânză tradițională de oaie maturată în burduf"
    },
    {
        id: 4,
        name: "Țuică de Prune Hunedoara",
        price: "45.00",
        weight: "500ml",
        region: "Hunedoara",
        image: "https://via.placeholder.com/300x200/8B0000/FFFFFF?text=Tuica+Prune",
        description: "Țuică tradițională de prune, 52% alcool"
    }
];

// Authentication functions
function isUserLoggedIn() {
    const userData = JSON.parse(localStorage.getItem('userData') || '{}');
    return userData.loggedIn === true;
}

function getUserData() {
    return JSON.parse(localStorage.getItem('userData') || '{}');
}

function logout() {
    localStorage.removeItem('userData');
    localStorage.removeItem('redirectAfterLogin');
    updateNavigation();
    showNotification('Te-ai deconectat cu succes!', 'info');

    // Redirect to home page if on account page
    if (window.location.pathname.includes('account.html')) {
        window.location.href = 'index.html';
    }
}

function updateNavigation() {
    const accountLinks = document.querySelectorAll('a[href="login.html"]');
    const isLoggedIn = isUserLoggedIn();
    const userData = getUserData();

    accountLinks.forEach(link => {
        const parentLi = link.closest('li');
        if (parentLi) {
            if (isLoggedIn) {
                // User is logged in - show account menu
                parentLi.innerHTML = `
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-check"></i> ${userData.name || 'Contul Meu'}
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="account.html">
                                <i class="bi bi-person me-2"></i>Contul Meu
                            </a></li>
                            <li><a class="dropdown-item" href="account.html#order-history">
                                <i class="bi bi-clock-history me-2"></i>Istoric Comenzi
                            </a></li>
                            <li><a class="dropdown-item" href="account.html#wishlist">
                                <i class="bi bi-heart me-2"></i>Lista de Dorințe
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" onclick="logout()">
                                <i class="bi bi-box-arrow-right me-2"></i>Deconectare
                            </a></li>
                        </ul>
                    </div>
                `;
            } else {
                // User is not logged in - show login link
                parentLi.innerHTML = `
                    <a class="nav-link" href="login.html">
                        <i class="bi bi-person"></i> Cont
                    </a>
                `;
            }
        }
    });
}

function requireLogin(redirectUrl = null) {
    if (!isUserLoggedIn()) {
        if (redirectUrl) {
            localStorage.setItem('redirectAfterLogin', redirectUrl);
        }
        window.location.href = 'login.html';
        return false;
    }
    return true;
}

// Initialize authentication on page load
document.addEventListener('DOMContentLoaded', function() {
    updateCartCount();
    updateNavigation();

    // Load featured products on homepage
    if (document.getElementById('featured-products')) {
        loadFeaturedProducts();
    }

    // Check if we're on account page and user is not logged in
    if (window.location.pathname.includes('account.html')) {
        if (!requireLogin()) {
            return;
        }
    }

    // Handle hash navigation for account page
    if (window.location.hash && window.location.pathname.includes('account.html')) {
        setTimeout(() => {
            const targetElement = document.querySelector(window.location.hash);
            if (targetElement) {
                targetElement.scrollIntoView({ behavior: 'smooth' });
            }
        }, 100);
    }

    // Add smooth scrolling
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const href = this.getAttribute('href');

            // Skip if href is just '#' (invalid selector)
            if (href === '#') {
                return;
            }

            const target = document.querySelector(href);
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // Add back to top button
    addBackToTopButton();

    // Handle account link clicks
    document.addEventListener('click', function(e) {
        const accountLink = e.target.closest('a[href="account.html"]');
        if (accountLink) {
            e.preventDefault();
            if (requireLogin('account.html')) {
                window.location.href = 'account.html';
            }
        }
    });
});

// Load featured products from database
async function loadFeaturedProducts() {
    const container = document.getElementById('featured-products');
    if (!container) return;

    try {
        const response = await fetch('api/catalog.php?type=featured');
        const result = await response.json();

        if (result.success) {
            container.innerHTML = '';
            result.products.forEach(product => {
                const productCard = createProductCard(product);
                container.appendChild(productCard);
            });
        } else {
            console.error('Error loading featured products:', result.message);
        }
    } catch (error) {
        console.error('Error fetching featured products:', error);
    }
}

// Create product card element
function createProductCard(product) {
    const col = document.createElement('div');
    col.className = 'col-md-6 col-lg-3';

    const productName = product.nume || product.name;
    const productImage = product.imagine || product.image;
    const productRegion = product.regiune || product.region;
    const productDescription = product.descriere || product.description;
    const productPrice = parseFloat(product.pret || product.price);
    const productWeight = product.cantitate || product.weight;

    col.innerHTML = `
        <div class="card product-card h-100 shadow-sm">
            <a href="product.html?id=${product.id}" class="text-decoration-none">
                <img src="${productImage}" class="card-img-top" alt="${productName}" style="height: 200px; object-fit: cover;">
            </a>
            <div class="card-body d-flex flex-column">
                <span class="badge region-badge mb-2 align-self-start">Produs local din ${productRegion}</span>
                <h5 class="card-title">
                    <a href="product.html?id=${product.id}" class="text-decoration-none text-dark">${productName}</a>
                </h5>
                <p class="card-text text-muted small">${productDescription.substring(0, 80)}...</p>
                <div class="mt-auto">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="price">${productPrice.toFixed(2)} RON</span>
                        <span class="text-muted small">${productWeight}</span>
                    </div>
                    <button class="btn btn-add-to-cart w-100" onclick="addToCart(${product.id}, '${productName}', ${productPrice}, '${productImage}', '${productWeight}')" aria-label="Adaugă ${productName} în coș">
                        <i class="bi bi-basket"></i> Adaugă în Coș
                    </button>
                </div>
            </div>
        </div>
    `;

    return col;
}

// Add to cart function
async function addToCart(id, name, price, image, weight) {
    // Check if user is logged in for database cart functionality
    const isLoggedIn = await isUserLoggedIn();

    if (isLoggedIn) {
        // Use database cart
        await addToCartDatabase(id, name, price, image, weight);
    } else {
        // Use localStorage cart for non-logged in users
        addToCartLocal(id, name, price, image, weight);
    }
}

// Add database cart functionality
async function addToCartDatabase(id, name, price, image, weight) {
    try {
        const response = await fetch('api/cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=add_item&produs_id=${id}&cantitate=1`
        });

        const data = await response.json();

        if (data.success) {
            showNotification('Produsul a fost adăugat în coș!', 'success');
            updateCartCount();
        } else {
            if (data.message.includes('autentificat')) {
                // User needs to login
                showNotification('Pentru a adăuga produse în coș, trebuie să te autentifici.', 'warning');
                setTimeout(() => {
                    window.location.href = 'login.html';
                }, 2000);
            } else {
                showNotification(data.message || 'Eroare la adăugarea în coș', 'error');
            }
        }
    } catch (error) {
        console.error('Error adding to cart:', error);
        showNotification('Eroare la adăugarea în coș', 'error');
    }
}

// Add to cart locally (localStorage)
function addToCartLocal(id, name, price, image, weight) {
    const existingItem = cart.find(item => item.id === id);

    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({
            id: id,
            name: name,
            price: parseFloat(price),
            image: image,
            weight: weight,
            quantity: 1
        });
    }

    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartCount();
    updateCartSubtotal(); // Update subtotal for free shipping progress
    showAddToCartNotification(name);

    // Check if we've reached free shipping threshold
    checkFreeShippingThreshold();
}

// Update cart count in navigation
function updateCartCount() {
    const cartCount = document.getElementById('cart-count');
    if (cartCount) {
        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        cartCount.textContent = totalItems;

        if (totalItems > 0) {
            cartCount.style.display = 'inline';
        } else {
            cartCount.style.display = 'none';
        }
    }
}

// Update cart subtotal and store in localStorage
function updateCartSubtotal() {
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    localStorage.setItem('cartSubtotal', subtotal.toString());
    return subtotal;
}

// Check if we've reached free shipping threshold
function checkFreeShippingThreshold() {
    const FREE_SHIPPING_THRESHOLD = 150;
    const subtotal = updateCartSubtotal();

    // If we just crossed the threshold, trigger celebration
    if (subtotal >= FREE_SHIPPING_THRESHOLD && 
        (localStorage.getItem('freeShippingCelebrated') !== 'true' || 
         parseFloat(localStorage.getItem('previousSubtotal') || '0') < FREE_SHIPPING_THRESHOLD)) {

        celebrateFreeShipping();
        localStorage.setItem('freeShippingCelebrated', 'true');
    }

    // Store previous subtotal to check if we crossed the threshold
    localStorage.setItem('previousSubtotal', subtotal.toString());
}

// Celebrate free shipping with confetti
function celebrateFreeShipping() {
    // Check if confetti library is available
    if (typeof confetti === 'function') {
        confetti({
            particleCount: 100,
            spread: 70,
            origin: { y: 0.6 }
        });

        // Show notification
        showNotification('Felicitări! Ai obținut transport GRATUIT! 🚚', 'success');
    }
}

// Show add to cart notification
function showAddToCartNotification(productName) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = 'alert alert-success alert-custom position-fixed';
    notification.style.cssText = 'top: 100px; right: 20px; z-index: 1050; min-width: 300px;';
    notification.innerHTML = `
        <i class="bi bi-check-circle"></i> <strong>${productName}</strong> a fost adăugat în coș!
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;

    document.body.appendChild(notification);

    // Auto remove after 3 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 3000);
}

// Remove from cart
function removeFromCart(id) {
    cart = cart.filter(item => item.id !== id);
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartCount();
    updateCartSubtotal();

    // Reload cart page if we're on it
    if (window.location.pathname.includes('cart.html')) {
        loadCartItems();
    }
}

// Update cart item quantity
function updateCartQuantity(id, quantity) {
    const item = cart.find(item => item.id === id);
    if (item) {
        item.quantity = parseInt(quantity);
        if (item.quantity <= 0) {
            removeFromCart(id);
        } else {
            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartCount();
            updateCartSubtotal();

            // Update cart total if we're on cart page
            if (window.location.pathname.includes('cart.html')) {
                updateCartTotal();
                updateShippingProgress();
            }
        }
    }
}

// Load cart items (for cart page)
function loadCartItems() {
    const cartContainer = document.getElementById('cart-items');
    if (!cartContainer) return;

    if (cart.length === 0) {
        cartContainer.innerHTML = `
            <div class="text-center py-5">
                <i class="bi bi-basket text-muted" style="font-size: 4rem;"></i>
                <h3 class="mt-3">Coșul este gol</h3>
                <p class="text-muted">Nu aveți încă produse în coș</p>
                <a href="products.html" class="btn btn-primary">Începe Cumpărăturile</a>
            </div>
        `;
        document.getElementById('cart-summary').style.display = 'none';
        return;
    }

    cartContainer.innerHTML = '';

    cart.forEach(item => {
        const cartItem = document.createElement('div');
        cartItem.className = 'cart-item border-bottom py-3';
        cartItem.innerHTML = `
            <div class="row align-items-center">
                <div class="col-md-2">
                    <img src="${item.image}" alt="${item.name}" class="img-fluid rounded">
                </div>
                <div class="col-md-4">
                    <h6>${item.name}</h6>
                    <small class="text-muted">${item.weight}</small>
                </div>
                <div class="col-md-2">
                    <span class="price">${item.price.toFixed(2)} RON</span>
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control form-control-sm" value="${item.quantity}" min="1" 
                           onchange="updateCartQuantity(${item.id}, this.value)" style="max-width: 80px;">
                </div>
                <div class="col-md-2 text-end">
                    <strong>${(item.price * item.quantity).toFixed(2)} RON</strong>
                    <button class="btn btn-outline-danger btn-sm ms-2" onclick="removeFromCart(${item.id})" 
                            aria-label="Elimină ${item.name} din coș">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `;
        cartContainer.appendChild(cartItem);
    });

    updateCartTotal();
    updateShippingProgress();
}

// Update cart total
function updateCartTotal() {
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const shipping = subtotal > 150 ? 0 : 15; // Free shipping over 150 RON
    const total = subtotal + shipping;

    document.getElementById('subtotal').textContent = `${subtotal.toFixed(2)} RON`;
    document.getElementById('shipping').textContent = shipping === 0 ? 'Gratuit' : `${shipping.toFixed(2)} RON`;
    document.getElementById('total').textContent = `${total.toFixed(2)} RON`;
}

// Free Shipping Progress Bar Functions
function updateShippingProgress() {
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const container = document.getElementById('shipping-progress-container');
    const FREE_SHIPPING_THRESHOLD = 150; // RON

    if (!container) return;

    // Store subtotal in localStorage for progress tracking
    localStorage.setItem('cartSubtotal', subtotal.toString());

    if (subtotal >= FREE_SHIPPING_THRESHOLD) {
        // Free shipping achieved
        container.innerHTML = `
            <div class="alert alert-success mb-0 d-flex align-items-center">
                <i class="bi bi-truck me-2 fs-5"></i>
                <div class="flex-grow-1">
                    <strong>Felicitări! Ai livrare gratuită! 🚚</strong>
                    <div class="progress mt-2" style="height: 8px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                    </div>
                </div>
            </div>
        `;

        // Check if we should celebrate (only if we just crossed the threshold)
        if (localStorage.getItem('freeShippingCelebrated') !== 'true') {
            celebrateFreeShipping();
            localStorage.setItem('freeShippingCelebrated', 'true');
        }
    } else {
        // Calculate remaining amount
        const remaining = FREE_SHIPPING_THRESHOLD - subtotal;
        const progressPercentage = Math.min((subtotal / FREE_SHIPPING_THRESHOLD) * 100, 100);

        container.innerHTML = `
            <div class="alert alert-info mb-0 d-flex align-items-center">
                <i class="bi bi-truck me-2 fs-5"></i>
                <div class="flex-grow-1">
                    <strong>Adaugă produse de încă ${remaining.toFixed(2)} RON pentru livrare gratuită!</strong>
                    <div class="progress mt-2" style="height: 8px;">
                        <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated" 
                             role="progressbar" 
                             style="width: ${progressPercentage}%"
                             aria-valuenow="${progressPercentage}" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                        </div>
                    </div>
                    <small class="text-muted">
                        ${subtotal.toFixed(2)} RON / ${FREE_SHIPPING_THRESHOLD} RON
                    </small>
                </div>
            </div>
        `;

        // Reset celebration flag if we're below threshold
        localStorage.setItem('freeShippingCelebrated', 'false');
    }
}

// Add back to top button
function addBackToTopButton() {
    const backToTop = document.createElement('button');
    backToTop.className = 'back-to-top';
    backToTop.innerHTML = '<i class="bi bi-arrow-up"></i>';
    backToTop.setAttribute('aria-label', 'Înapoi sus');

    document.body.appendChild(backToTop);

    window.addEventListener('scroll', () => {
        if (window.pageYOffset > 300) {
            backToTop.style.display = 'block';
        } else {
            backToTop.style.display = 'none';
        }
    });

    backToTop.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;

    const inputs = form.querySelectorAll('input[required], textarea[required]');
    let isValid = true;

    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
        }
    });

    return isValid;
}

// Newsletter subscription
async function subscribeNewsletter(email) {
    if (!email || !email.includes('@')) {
        showNotification('Vă rugăm introduceți o adresă de email validă.', 'danger');
        return false;
    }

    try {
        const response = await fetch('api/newsletter.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=subscribe&email=${encodeURIComponent(email)}`
        });

        const result = await response.json();

        if (result.success) {
            showNotification('Te-ai abonat cu succes la newsletter!', 'success');
            return true;
        } else {
            showNotification(result.message || 'A apărut o eroare la abonare.', 'danger');
            return false;
        }
    } catch (error) {
        console.error('Newsletter subscription error:', error);
        showNotification('A apărut o eroare la abonare. Vă rugăm să încercați din nou.', 'danger');
        return false;
    }
}

// Generic notification function
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-custom position-fixed`;
    notification.style.cssText = 'top: 100px; right: 20px; z-index: 1050; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 4000);
}

// Search functionality
function searchProducts(query) {
    // This would typically search through all products
    // For now, we'll just redirect to products page
    window.location.href = `products.html?search=${encodeURIComponent(query)}`;
}

// Initialize search
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('input[type="search"]');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchProducts(this.value);
            }
        });
    }
});

// Proceed to checkout function
function proceedToCheckout() {
    if (cart.length === 0) {
        alert('Coșul este gol. Adăugați produse pentru a continua.');
        return;
    }

    // Check if user is logged in
    if (!isUserLoggedIn()) {
        // Store current page as redirect after login
        localStorage.setItem('redirectAfterLogin', 'checkout.php');
        showNotification('Pentru a finaliza comanda, trebuie să te autentifici.', 'warning');
        setTimeout(() => {
            window.location.href = 'login.php';
        }, 2000);
        return;
    }

    // Redirect to checkout page
    window.location.href = 'checkout.php';rn;
    }

    // Redirect to checkout page
    window.location.href = 'checkout.php';
}