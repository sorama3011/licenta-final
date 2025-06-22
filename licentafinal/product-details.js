// Global variables
let productData = null;
let allProducts = [];

// Load product data when page loads
document.addEventListener('DOMContentLoaded', async function() {
    try {
        // Get product ID from URL
        const urlParams = new URLSearchParams(window.location.search);
        const productId = parseInt(urlParams.get('id'));

        if (!productId) {
            showError('ID produs lipsă. Vă rugăm să selectați un produs valid.');
            return;
        }

        console.log('Loading product with ID:', productId);

        // Show loading state
        showLoadingState();

        // Load from products.json
        await loadProductFromJSON(productId);

        if (!productData) {
            throw new Error('Nu s-au putut încărca datele produsului');
        }

        // Render product details
        renderProductDetails();

        // Update cart count if function exists
        if (typeof updateCartCount === 'function') {
            updateCartCount();
        }

    } catch (error) {
        console.error('Error loading product:', error);
        showError('Eroare la încărcarea produsului. Vă rugăm să încercați din nou.');
    }
});

// Show loading state
function showLoadingState() {
    const container = document.querySelector('.container') || document.querySelector('#product-container') || document.body;

    // Find or create a loading container
    let loadingContainer = document.getElementById('loading-container');
    if (!loadingContainer) {
        loadingContainer = document.createElement('div');
        loadingContainer.id = 'loading-container';
        loadingContainer.className = 'text-center py-5';
        loadingContainer.innerHTML = `
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Se încarcă...</span>
            </div>
            <p class="mt-3">Se încarcă produsul...</p>
        `;
        container.appendChild(loadingContainer);
    }
}

// Load product from JSON file
async function loadProductFromJSON(productId) {
    try {
        console.log('Loading from products.json...');
        const response = await fetch('products.json');

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const jsonData = await response.json();
        console.log('JSON Response:', jsonData);

        if (jsonData && Array.isArray(jsonData)) {
            // Find product by ID
            productData = jsonData.find(p => p.id === productId);

            if (productData) {
                // Enhance product data with additional fields
                enhanceProductData();
                console.log('Product loaded successfully from JSON:', productData);
                return true;
            } else {
                console.warn(`Product with ID ${productId} not found in JSON`);
            }
        }
    } catch (error) {
        console.error('Error loading from JSON:', error);
    }

    // Fallback to hardcoded product if JSON fails
    console.log('Using hardcoded fallback product');
    productData = getHardcodedProduct(productId);
    enhanceProductData();
    return false;
}

// Enhance product data with missing fields
function enhanceProductData() {
    if (!productData) return;

    // Ensure all required fields exist
    productData.descriere = productData.descriere || productData.descriere_scurta || 'Produs tradițional românesc de calitate superioară.';
    productData.descriere_lunga = productData.descriere_lunga || productData.descriere || productData.descriere_scurta || 'Acest produs tradițional românesc este preparat după rețete străvechi, transmise din generație în generație.';
    productData.ingrediente = productData.ingrediente || getIngredientsByCategory(productData.categorie);
    productData.cantitate = productData.cantitate || '500g';
    productData.producator = productData.producator || getProducerByRegion(productData.regiune);
    productData.imagine = productData.imagine || `https://via.placeholder.com/600x400/8B0000/FFFFFF?text=${encodeURIComponent(productData.nume)}`;
    productData.stoc = productData.stoc !== undefined ? productData.stoc : 10;

    // Add nutritional information based on category
    productData.nutritionalInfo = getNutritionalInfoByCategory(productData.categorie);

    // Ensure tags exist
    productData.tags = productData.tags || [];

    // Set defaults
    productData.restrictie_varsta = productData.restrictie_varsta || 0;
    productData.recomandat = productData.recomandat || 0;
    productData.activ = productData.activ !== undefined ? productData.activ : 1;

    console.log('Enhanced product data:', productData);
}

// Get ingredients based on product category
function getIngredientsByCategory(categorie) {
    const ingredients = {
        'dulceturi': 'Fructe (60%), zahăr, pectină naturală, acid citric',
        'conserve': 'Legume proaspete (80%), oțet de vin, sare, condimente naturale',
        'branza': 'Lapte pasteurizat, fermenți lactici, cheag, sare',
        'bauturi': 'Fructe distilate (100%), fără aditivi artificiali',
        'mezeluri': 'Carne de porc (90%), condimente naturale, sare, afumat natural'
    };

    return ingredients[categorie] || 'Ingrediente naturale, fără conservanți artificiali';
}

// Get producer based on region
function getProducerByRegion(regiune) {
    const producers = {
        'Muntenia': 'Gospodăria Tradițională SRL',
        'Transilvania': 'Ferma de Familie Transilvania',
        'Maramureș': 'Meșteri Artizani Maramureș',
        'Banat': 'Cooperativa Agricolă Banat',
        'Oltenia': 'Producători Locali Oltenia',
        'Bucovina': 'Gospodării Unite Bucovina',
        'Crișana': 'Tradiții Crișana SRL',
        'Dobrogea': 'Ferme Dobrogea'
    };

    return producers[regiune] || 'Producător local autorizat';
}

// Get nutritional info based on category
function getNutritionalInfoByCategory(categorie) {
    const nutritionalData = {
        'dulceturi': [
            {"name": "Valoare energetică", "value": "245 kcal / 1025 kJ"},
            {"name": "Grăsimi", "value": "0.2g"},
            {"name": "din care acizi grași saturați", "value": "0.1g", "indented": true},
            {"name": "Glucide", "value": "60g"},
            {"name": "din care zaharuri", "value": "58g", "indented": true},
            {"name": "Fibre", "value": "1.2g"},
            {"name": "Proteine", "value": "0.4g"},
            {"name": "Sare", "value": "0.02g"},
            {"name": "Vitamina C", "value": "25mg"}
        ],
        'conserve': [
            {"name": "Valoare energetică", "value": "95 kcal / 398 kJ"},
            {"name": "Grăsimi", "value": "7.5g"},
            {"name": "din care acizi grași saturați", "value": "1.2g", "indented": true},
            {"name": "Glucide", "value": "6.2g"},
            {"name": "din care zaharuri", "value": "4.8g", "indented": true},
            {"name": "Fibre", "value": "2.1g"},
            {"name": "Proteine", "value": "1.8g"},
            {"name": "Sare", "value": "1.1g"}
        ],
        'branza': [
            {"name": "Valoare energetică", "value": "298 kcal / 1247 kJ"},
            {"name": "Grăsimi", "value": "24g"},
            {"name": "din care acizi grași saturați", "value": "15g", "indented": true},
            {"name": "Glucide", "value": "0.7g"},
            {"name": "din care zaharuri", "value": "0.7g", "indented": true},
            {"name": "Proteine", "value": "19g"},
            {"name": "Sare", "value": "1.8g"},
            {"name": "Calciu", "value": "700mg"}
        ],
        'bauturi': [
            {"name": "Valoare energetică", "value": "250 kcal / 1046 kJ"},
            {"name": "Alcool", "value": "40-65%"},
            {"name": "Glucide", "value": "0g"},
            {"name": "Grăsimi", "value": "0g"},
            {"name": "Proteine", "value": "0g"},
            {"name": "Sare", "value": "0g"}
        ],
        'mezeluri': [
            {"name": "Valoare energetică", "value": "380 kcal / 1590 kJ"},
            {"name": "Grăsimi", "value": "32g"},
            {"name": "din care acizi grași saturați", "value": "12g", "indented": true},
            {"name": "Glucide", "value": "1.2g"},
            {"name": "Proteine", "value": "22g"},
            {"name": "Sare", "value": "2.5g"},
            {"name": "Fier", "value": "2.1mg"}
        ]
    };

    return nutritionalData[categorie] || [
        {"name": "Valoare energetică", "value": "Informații indisponibile"},
        {"name": "Grăsimi", "value": "Informații indisponibile"},
        {"name": "Glucide", "value": "Informații indisponibile"},
        {"name": "Proteine", "value": "Informații indisponibile"}
    ];
}

// Hardcoded fallback product
function getHardcodedProduct(id) {
    return {
        id: id,
        nume: "Produs Tradițional Românesc",
        descriere: "Produs tradițional românesc de calitate superioară",
        descriere_lunga: "Acest produs tradițional românesc este preparat după rețete străvechi, transmise din generație în generație. Folosim doar ingrediente naturale și metode tradiționale de preparare.",
        ingrediente: "Ingrediente naturale selectate, fără conservanți artificiali",
        pret: "25.99",
        imagine: "https://via.placeholder.com/600x400/8B0000/FFFFFF?text=Produs+Traditional",
        categorie: "traditionale",
        regiune: "România",
        cantitate: "500g",
        stoc: 10,
        producator: "Producător Local",
        recomandat: 1,
        restrictie_varsta: 0,
        activ: 1,
        tags: ["artizanal", "fara-aditivi"]
    };
}

// Render product details
function renderProductDetails() {
    if (!productData) {
        showError('Nu s-au putut încărca datele produsului.');
        return;
    }

    try {
        // Remove loading container
        const loadingContainer = document.getElementById('loading-container');
        if (loadingContainer) {
            loadingContainer.remove();
        }

        // Update page title and meta description
        document.title = `${productData.nume} - Gusturi Românești`;

        // Update breadcrumb
        const breadcrumb = document.getElementById('product-breadcrumb');
        if (breadcrumb) {
            breadcrumb.textContent = productData.nume;
        }

        // Basic product info
        updateElement('product-name', productData.nume);
        updateElement('product-region', `Produs local din ${productData.regiune}`);
        updateElement('product-price', `${parseFloat(productData.pret).toFixed(2)} RON`);
        updateElement('product-description', productData.descriere);
        updateElement('product-long-description', productData.descriere_lunga);

        // Product image
        const imageElement = document.getElementById('product-image');
        if (imageElement) {
            imageElement.src = productData.imagine;
            imageElement.alt = productData.nume;
        }

        // Handle weight/quantity display
        const weightElement = document.getElementById('product-weight');
        if (weightElement) {
            const weightText = productData.cantitate || '';
            weightElement.textContent = weightText ? `/ ${weightText}` : '';
        }

        // Product details section
        renderProductDetails_();

        // Render nutritional info or product info
        renderInfoTable();

        // Render related products
        renderRelatedProducts();

        console.log('✅ Product details rendered successfully');

    } catch (error) {
        console.error('Error rendering product details:', error);
        showError('Eroare la afișarea detaliilor produsului.');
    }
}

// Helper function to safely update element content
function updateElement(id, content) {
    const element = document.getElementById(id);
    if (element && content) {
        element.textContent = content;
    }
}

// Render product details section
function renderProductDetails_() {
    const detailsContainer = document.getElementById('product-details');
    if (!detailsContainer) return;

    detailsContainer.innerHTML = '';

    // Add origin if available
    if (productData.regiune) {
        detailsContainer.innerHTML += `
            <div class="col-6 mb-2">
                <strong>Origine:</strong> ${productData.regiune}
            </div>
        `;
    }

    // Add category
    if (productData.categorie) {
        detailsContainer.innerHTML += `
            <div class="col-6 mb-2">
                <strong>Categorie:</strong> ${productData.categorie}
            </div>
        `;
    }

    // Add stock information
    if (productData.stoc !== undefined) {
        const stockText = parseInt(productData.stoc) > 0 ? 'În stoc' : 'Stoc epuizat';
        const stockClass = parseInt(productData.stoc) > 0 ? 'text-success' : 'text-danger';
        detailsContainer.innerHTML += `
            <div class="col-6 mb-2">
                <strong>Disponibilitate:</strong> <span class="${stockClass}">${stockText}</span>
            </div>
        `;
    }

    // Add producer if available
    if (productData.producator) {
        detailsContainer.innerHTML += `
            <div class="col-6 mb-2">
                <strong>Producător:</strong> ${productData.producator}
            </div>
        `;
    }
}

// Render nutritional info or product info table
function renderInfoTable() {
    const tableBody = document.getElementById('info-table-body');
    const tableTitle = document.getElementById('info-table-title');

    if (!tableBody) return;

    tableBody.innerHTML = '';

    // Check if we have nutritional information
    if (productData.nutritionalInfo && productData.nutritionalInfo.length > 0) {
        if (tableTitle) tableTitle.textContent = 'Informații Nutriționale (per 100g)';

        // Render nutritional information
        productData.nutritionalInfo.forEach(item => {
            const row = document.createElement('tr');
            const nameClass = item.indented ? 'ps-3' : '';
            row.innerHTML = `
                <td class="${nameClass}"><strong>${item.name}</strong></td>
                <td>${item.value}</td>
            `;
            tableBody.appendChild(row);
        });
    } else {
        // Show basic product information
        if (tableTitle) tableTitle.textContent = 'Informații despre Produs';

        const productInfo = [];

        if (productData.categorie) {
            productInfo.push({ nume: 'Categorie', valoare: productData.categorie });
        }

        if (productData.regiune) {
            productInfo.push({ nume: 'Regiunea de origine', valoare: productData.regiune });
        }

        if (productData.cantitate) {
            productInfo.push({ nume: 'Cantitate/Greutate', valoare: productData.cantitate });
        }

        if (productData.producator) {
            productInfo.push({ nume: 'Producător', valoare: productData.producator });
        }

        // Add tags if available
        if (productData.tags && productData.tags.length > 0) {
            productInfo.push({ nume: 'Caracteristici speciale', valoare: productData.tags.join(', ') });
        }

        // Render the product information
        productInfo.forEach(item => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><strong>${item.nume}</strong></td>
                <td>${item.valoare}</td>
            `;
            tableBody.appendChild(row);
        });
    }

    // Add ingredients section if available
    if (productData.ingrediente) {
        const ingredientsRow = document.createElement('tr');
        ingredientsRow.innerHTML = `
            <td colspan="2" class="pt-4">
                <h6 class="mb-2">Ingrediente:</h6>
                <p class="mb-0 text-muted">${productData.ingrediente}</p>
            </td>
        `;
        tableBody.appendChild(ingredientsRow);
    }
}

// Render related products
async function renderRelatedProducts() {
    const container = document.getElementById('related-products');
    if (!container) return;

    container.innerHTML = '<div class="col-12 text-center"><div class="spinner-border text-primary" role="status"></div></div>';

    // Get related products
    const relatedProducts = await getRelatedProductsFromJSON(productData.id, productData.categorie);

    container.innerHTML = '';

    if (relatedProducts && relatedProducts.length > 0) {
        relatedProducts.forEach(product => {
            const productCard = createRelatedProductCard(product);
            container.appendChild(productCard);
        });
    } else {
        // Show message if no related products
        container.innerHTML = `
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="bi bi-info-circle me-2"></i>
                    Nu sunt disponibile produse similare momentan.
                </div>
            </div>
        `;
    }
}

// Get related products from JSON data
async function getRelatedProductsFromJSON(currentId, categorie) {
    try {
        const response = await fetch('products.json');
        if (response.ok) {
            const allProducts = await response.json();
            return allProducts
                .filter(p => p.categorie === categorie && p.id !== currentId)
                .slice(0, 4);
        }
    } catch (error) {
        console.warn('Could not load related products:', error);
    }
    return [];
}

// Create related product card
function createRelatedProductCard(product) {
    const productCard = document.createElement('div');
    productCard.className = 'col-md-6 col-lg-3 mb-3';

    productCard.innerHTML = `
        <div class="card product-card h-100 shadow-sm">
            <a href="product.html?id=${product.id}" class="text-decoration-none">
                <img src="${product.imagine}" class="card-img-top" alt="${product.nume}" style="height: 200px; object-fit: cover;">
            </a>
            <div class="card-body d-flex flex-column">
                <span class="badge bg-primary mb-2 align-self-start">${product.regiune}</span>
                <h5 class="card-title">
                    <a href="product.html?id=${product.id}" class="text-decoration-none text-dark">${product.nume}</a>
                </h5>
                <p class="card-text text-muted small">${(product.descriere || product.descriere_scurta || '').substring(0, 60)}...</p>
                <div class="mt-auto">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="fw-bold text-primary">${parseFloat(product.pret).toFixed(2)} RON</span>
                        <span class="text-muted small">${product.cantitate || ''}</span>
                    </div>
                    <button class="btn btn-primary w-100" onclick="addToCart(${product.id}, '${product.nume}', ${product.pret}, '${product.imagine}', '${product.cantitate || ''}')">
                        <i class="bi bi-basket"></i> Adaugă în Coș
                    </button>
                </div>
            </div>
        </div>
    `;

    return productCard;
}

// Add to cart from product detail page
function addToCartFromDetail() {
    const quantityElement = document.getElementById('quantity');
    const quantity = quantityElement ? parseInt(quantityElement.value) : 1;

    // Check if user is logged in
    if (!isUserLoggedIn()) {
        showNotification('Pentru a adăuga produse în coș, trebuie să te autentifici.', 'warning');
        setTimeout(() => {
            localStorage.setItem('redirectAfterLogin', window.location.href);
            window.location.href = 'login.html';
        }, 2000);
        return;
    }

    // Use the regular addToCart function
    if (typeof addToCart === 'function') {
        addToCart(productData.id, productData.nume, productData.pret, productData.imagine, productData.cantitate || '');
    } else {
        showNotification(`${productData.nume} a fost adăugat în coș!`, 'success');
    }
}

// Show error message
function showError(message) {
    const container = document.querySelector('.container') || document.body;
    container.innerHTML = `
        <div class="alert alert-danger text-center my-5">
            <i class="bi bi-exclamation-triangle-fill fs-1 d-block mb-3"></i>
            <h3>Eroare</h3>
            <p>${message}</p>
            <div class="mt-4">
                <a href="products.html" class="btn btn-primary me-2">
                    <i class="bi bi-arrow-left"></i> Înapoi la Produse
                </a>
                <button class="btn btn-secondary" onclick="location.reload()">
                    <i class="bi bi-arrow-clockwise"></i> Reîncarcă
                </button>
            </div>
        </div>
    `;
}

// Generic notification function
function showNotification(message, type = 'info') {
    // Check if the function is already defined in main.js
    if (typeof window.showNotification === 'function') {
        window.showNotification(message, type);
        return;
    }

    // If not defined, create our own implementation
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

// Helper function to check if user is logged in
function isUserLoggedIn() {
    return localStorage.getItem('user') !== null || localStorage.getItem('isLoggedIn') === 'true';
}