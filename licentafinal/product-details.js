
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
        document.body.innerHTML = `
            <div class="container mt-5 text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Se încarcă...</span>
                </div>
                <p class="mt-3">Se încarcă produsul...</p>
            </div>
        `;

        // Load from products.json first (demo version)
        let jsonSuccess = false;
        try {
            console.log('Loading from products.json...');
            const response = await fetch('products.json');
            
            if (response.ok) {
                const jsonData = await response.json();
                console.log('JSON Response:', jsonData);
                
                if (jsonData && Array.isArray(jsonData)) {
                    // Find product by ID
                    productData = jsonData.find(p => p.id === productId);
                    
                    if (productData) {
                        // Enhance product data with additional fields for consistency
                        await enhanceProductData();
                        jsonSuccess = true;
                        console.log('Product loaded successfully from JSON');
                    }
                }
            }
        } catch (jsonError) {
            console.warn('JSON request failed:', jsonError);
        }

        // If JSON failed, try API as fallback
        if (!jsonSuccess) {
            console.log('JSON failed, trying API...');
            try {
                const response = await fetch(`api/catalog.php?type=product&id=${productId}`);
                
                if (response.ok) {
                    const result = await response.json();
                    console.log('API Response:', result);
                    
                    if (result.success && result.product) {
                        productData = result.product;
                        await enhanceProductData();
                        console.log('Product loaded from API as fallback');
                    }
                }
            } catch (apiError) {
                console.warn('API request also failed:', apiError);
            }
        }

        // If both failed, use hardcoded fallback
        if (!productData) {
            console.log('Using hardcoded fallback product');
            productData = getHardcodedProduct(productId);
            await enhanceProductData();
        }

        if (!productData) {
            throw new Error('Nu s-au putut încărca datele produsului');
        }

        // Restore the original page structure and render product details
        await restorePageStructure();
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

// Enhance product data with missing fields for consistency
async function enhanceProductData() {
    if (!productData) return;
    
    // Ensure all required fields exist
    productData.descriere = productData.descriere || productData.descriere_scurta || 'Produs tradițional românesc de calitate superioară.';
    productData.descriere_lunga = productData.descriere_lunga || productData.descriere || productData.descriere_scurta || 'Acest produs tradițional românesc este preparat după rețete străvechi, transmise din generație în generație.';
    productData.ingrediente = productData.ingrediente || getIngredientsByCategory(productData.categorie);
    productData.cantitate = productData.cantitate || '500g';
    productData.producator = productData.producator || getProducerByRegion(productData.regiune);
    productData.imagine = productData.imagine || `img/placeholder.png`;
    productData.stoc = productData.stoc !== undefined ? productData.stoc : 10;
    
    // Add nutritional information based on category
    productData.nutritionalInfo = getNutritionalInfoByCategory(productData.categorie);
    
    // Add related products from same category
    productData.related_products = await getRelatedProductsFromJSON(productData.id, productData.categorie);
    
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

// Restore the original page structure
async function restorePageStructure() {
    // Load the original product.html content
    try {
        const response = await fetch('product.html');
        if (response.ok) {
            const htmlText = await response.text();
            // Extract body content (excluding scripts and head)
            const parser = new DOMParser();
            const doc = parser.parseFromString(htmlText, 'text/html');
            const bodyContent = doc.body.innerHTML;
            document.body.innerHTML = bodyContent;
        }
    } catch (error) {
        console.warn('Could not restore page structure, using fallback');
        // Use a basic fallback structure
        document.body.innerHTML = `
            <div class="container mt-5">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.html">Acasă</a></li>
                        <li class="breadcrumb-item"><a href="products.html">Produse</a></li>
                        <li class="breadcrumb-item active" id="product-breadcrumb">Produs</li>
                    </ol>
                </nav>
                <div class="row">
                    <div class="col-md-6">
                        <img id="product-image" src="" alt="" class="img-fluid">
                    </div>
                    <div class="col-md-6">
                        <h1 id="product-name"></h1>
                        <p id="product-region" class="text-muted"></p>
                        <p id="product-description"></p>
                        <div class="d-flex align-items-center mb-3">
                            <span id="product-price" class="h4 text-primary me-2"></span>
                            <span id="product-weight" class="text-muted"></span>
                        </div>
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary" onclick="addToCartFromDetail()">
                                <i class="bi bi-basket"></i> Adaugă în Coș
                            </button>
                        </div>
                    </div>
                </div>
                <div class="mt-5">
                    <h3>Informații despre Produs</h3>
                    <div id="product-details" class="row"></div>
                    <p id="product-long-description"></p>
                    
                    <h4 id="info-table-title">Informații Nutriționale</h4>
                    <table class="table">
                        <tbody id="info-table-body"></tbody>
                    </table>
                    
                    <h3>Produse Similare</h3>
                    <div id="related-products" class="row"></div>
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

// Hardcoded fallback product
function getHardcodedProduct(id) {
    return {
        id: id,
        nume: "Produs Tradițional Românesc",
        descriere: "Produs tradițional românesc de calitate superioară",
        descriere_lunga: "Acest produs tradițional românesc este preparat după rețete străvechi, transmise din generație în generație. Folosim doar ingrediente naturale și metode tradiționale de preparare.",
        ingrediente: "Ingrediente naturale selectate, fără conservanți artificiali",
        pret: "25.99",
        imagine: "img/placeholder.png",
        categorie: "traditionale",
        regiune: "România",
        cantitate: "500g",
        stoc: 10,
        producator: "Producător Local",
        recomandat: 1,
        restrictie_varsta: 0,
        activ: 1,
        tags: ["artizanal", "fara-aditivi"],
        nutritionalInfo: [
            {"name": "Valoare energetică", "value": "200 kcal / 837 kJ"},
            {"name": "Grăsimi", "value": "5g"},
            {"name": "Glucide", "value": "30g"},
            {"name": "Proteine", "value": "8g"}
        ],
        related_products: []
    };
}

// Render product details
function renderProductDetails() {
    if (!productData) {
        showError('Nu s-au putut încărca datele produsului.');
        return;
    }

    try {
        // Update page title and meta description
        document.title = `${productData.nume} - Gusturi Românești`;
        const metaDescription = document.querySelector('meta[name="description"]');
        if (metaDescription) {
            metaDescription.setAttribute('content', productData.descriere || '');
        }

        // Update breadcrumb
        const breadcrumb = document.getElementById('product-breadcrumb');
        if (breadcrumb) {
            breadcrumb.textContent = productData.nume;
        }

        // Basic product info
        const nameElement = document.getElementById('product-name');
        const imageElement = document.getElementById('product-image');
        const regionElement = document.getElementById('product-region');
        const priceElement = document.getElementById('product-price');
        const weightElement = document.getElementById('product-weight');
        const descriptionElement = document.getElementById('product-description');
        const longDescriptionElement = document.getElementById('product-long-description');

        if (nameElement) nameElement.textContent = productData.nume;
        if (imageElement) {
            imageElement.src = productData.imagine;
            imageElement.alt = productData.nume;
        }
        if (regionElement) regionElement.textContent = `Produs local din ${productData.regiune}`;
        if (priceElement) priceElement.textContent = `${parseFloat(productData.pret).toFixed(2)} RON`;
        
        // Handle weight/quantity display
        const weightText = productData.cantitate || '';
        if (weightElement) weightElement.textContent = weightText ? `/ ${weightText}` : '';
        
        // Product descriptions
        if (descriptionElement) descriptionElement.textContent = productData.descriere || '';
        if (longDescriptionElement) longDescriptionElement.textContent = productData.descriere_lunga || productData.descriere || '';
        
        // Ingredients - check if element exists
        const ingredientsElement = document.getElementById('product-ingredients');
        if (ingredientsElement) {
            ingredientsElement.textContent = productData.ingrediente || 'Informații indisponibile';
        }

    // Product details section
    const detailsContainer = document.getElementById('product-details');
    detailsContainer.innerHTML = '';

    // Add origin if available
    if (productData.regiune) {
        detailsContainer.innerHTML += `
            <div class="col-6">
                <strong>Origine:</strong> ${productData.regiune}
            </div>
        `;
    }

    // Add category
    if (productData.categorie) {
        detailsContainer.innerHTML += `
            <div class="col-6">
                <strong>Categorie:</strong> ${productData.categorie}
            </div>
        `;
    }

    // Add stock information
    if (productData.stoc !== undefined) {
        const stockText = parseInt(productData.stoc) > 0 ? 'În stoc' : 'Stoc epuizat';
        const stockClass = parseInt(productData.stoc) > 0 ? 'text-success' : 'text-danger';
        detailsContainer.innerHTML += `
            <div class="col-6">
                <strong>Disponibilitate:</strong> <span class="${stockClass}">${stockText}</span>
            </div>
        `;
    }

    // Add producer if available
    if (productData.producator) {
        detailsContainer.innerHTML += `
            <div class="col-6">
                <strong>Producător:</strong> ${productData.producator}
            </div>
        `;
    }

    // Show age restriction warning if applicable
    if (productData.restrictie_varsta || (productData.tags && productData.tags.includes('18+'))) {
        document.getElementById('age-restriction-warning').style.display = 'block';
    } else {
        document.getElementById('age-restriction-warning').style.display = 'none';
    }

    // Show special badges if product is recommended or has special tags
    if (productData.recomandat == 1 || (productData.tags && productData.tags.length > 0)) {
        document.getElementById('special-badge').style.display = 'block';
        if (productData.recomandat == 1) {
            document.getElementById('special-badge-text').textContent = 'Produs Recomandat';
            document.getElementById('special-badge-description').textContent = 'Acest produs este recomandat de echipa noastră.';
        } else if (productData.tags && productData.tags.length > 0) {
            document.getElementById('special-badge-text').textContent = 'Produs Special';
            document.getElementById('special-badge-description').textContent = productData.tags.join(', ');
        }
    } else {
        document.getElementById('special-badge').style.display = 'none';
    }

    // Render nutritional info or product info
    renderInfoTable();

    // Render related products
    renderRelatedProducts();
}

// Render nutritional info or product info table
function renderInfoTable() {
    const tableBody = document.getElementById('info-table-body');
    const tableTitle = document.getElementById('info-table-title');
    tableBody.innerHTML = '';

    // Check if we have nutritional information
    if (productData.nutritionalInfo && productData.nutritionalInfo.length > 0) {
        tableTitle.textContent = 'Informații Nutriționale (per 100g)';
        
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
        tableTitle.textContent = 'Informații despre Produs';

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

        // If no product info available, show a message
        if (productInfo.length === 0) {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td colspan="2" class="text-center text-muted">
                    Informații suplimentare indisponibile momentan
                </td>
            `;
            tableBody.appendChild(row);
        }
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

// Create related product card
function createRelatedProductCard(product) {
    const productCard = document.createElement('div');
    productCard.className = 'col-md-6 col-lg-3';

    productCard.innerHTML = `
        <div class="card product-card h-100 shadow-sm">
            <a href="product.html?id=${product.id}" class="text-decoration-none">
                <img src="${product.imagine}" class="card-img-top" alt="${product.nume}" style="height: 200px; object-fit: cover;">
            </a>
            <div class="card-body d-flex flex-column">
                <span class="badge region-badge mb-2 align-self-start">Produs local din ${product.regiune}</span>
                <h5 class="card-title">
                    <a href="product.html?id=${product.id}" class="text-decoration-none text-dark">${product.nume}</a>
                </h5>
                <p class="card-text text-muted small">${(product.descriere || product.descriere_scurta || '').substring(0, 60)}...</p>
                <div class="mt-auto">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="price">${parseFloat(product.pret).toFixed(2)} RON</span>
                        <span class="text-muted small">${product.cantitate || ''}</span>
                    </div>
                    <button class="btn btn-add-to-cart w-100" onclick="addToCart(${product.id}, '${product.nume}', ${product.pret}, '${product.imagine}', '${product.cantitate || ''}')">
                        <i class="bi bi-basket"></i> Adaugă în Coș
                    </button>
                </div>
            </div>
        </div>
    `;

    return productCard;
}

// Add to cart from product detail page
async function addToCartFromDetail() {
    const quantity = parseInt(document.getElementById('quantity').value);

    // Check if user is logged in
    if (!isUserLoggedIn()) {
        showNotification('Pentru a adăuga produse în coș, trebuie să te autentifici.', 'warning');
        setTimeout(() => {
            localStorage.setItem('redirectAfterLogin', window.location.href);
            window.location.href = 'login.html';
        }, 2000);
        return;
    }

    try {
        // Add to database via API
        const formData = new FormData();
        formData.append('action', 'add_item');
        formData.append('produs_id', productData.id);
        formData.append('cantitate', quantity);

        const response = await fetch('api/cart.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showNotification(`${productData.nume} a fost adăugat în coș!`, 'success');
            
            // Update localStorage for immediate UI feedback
            if (typeof addToCart === 'function') {
                // Update local cart without making another API call
                const existingItem = cart.find(item => item.id == productData.id);
                if (existingItem) {
                    existingItem.quantity += quantity;
                } else {
                    cart.push({
                        id: parseInt(productData.id),
                        name: productData.nume,
                        price: parseFloat(productData.pret),
                        image: productData.imagine,
                        weight: productData.cantitate || '',
                        quantity: quantity
                    });
                }
                localStorage.setItem('cart', JSON.stringify(cart));
                if (typeof updateCartCount === 'function') {
                    updateCartCount();
                }
            }
        } else {
            showNotification('Eroare: ' + result.message, 'danger');
        }
    } catch (error) {
        console.error('Error adding to cart:', error);
        showNotification('A apărut o eroare la adăugarea în coș. Încearcă din nou.', 'danger');
    }
}

// Add to favorites
function addToFavorites() {
    showNotification('Produsul a fost adăugat la favorite!', 'success');
}

// Share product
function shareProduct() {
    if (navigator.share) {
        navigator.share({
            title: `${productData.nume} - Gusturi Românești`,
            text: `Descoperă acest produs tradițional românesc: ${productData.nume}!`,
            url: window.location.href
        });
    } else {
        navigator.clipboard.writeText(window.location.href).then(() => {
            showNotification('Link-ul produsului a fost copiat în clipboard!', 'info');
        });
    }
}

// Show error message
function showError(message) {
    const container = document.querySelector('.container');
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

// Generic notification function (if not already defined in main.js)
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
