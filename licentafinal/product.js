
// Global variables
let productData = null;
let allProducts = [];

// Fallback product data when database fails
const fallbackProducts = [
    {
        id: 1,
        nume: "Dulceață de Căpșuni de Argeș",
        descriere: "Dulceață tradițională din căpșuni proaspete, preparată după rețeta bunicii.",
        descriere_lunga: "Această dulceață excepțională este preparată din căpșuni proaspete, culese în zorii zilei din livezile din județul Argeș. Rețeta tradițională, transmisă din generație în generație, păstrează gustul autentic și aroma intensă a fructelor.",
        ingrediente: "Căpșuni (60%), zahăr, pectină naturală, acid citric",
        pret: "18.99",
        imagine: "img/dulceata.png",
        categorie: "dulceturi",
        regiune: "Argeș",
        cantitate: "350g",
        stoc: 15,
        producator: "Gospodăria Margareta",
        recomandat: 1,
        restrictie_varsta: 0,
        activ: 1,
        tags: ["artizanal", "fara-aditivi"],
        nutritionalInfo: [
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
        related_products: [
            {
                id: 2,
                nume: "Zacuscă de Casă",
                descriere: "Zacuscă tradițională preparată din legume proaspete",
                pret: "15.99",
                imagine: "img/zacusca.png",
                regiune: "Muntenia",
                cantitate: "300g"
            }
        ]
    },
    {
        id: 2,
        nume: "Zacuscă de Casă",
        descriere: "Zacuscă tradițională preparată din legume proaspete de la producători locali.",
        descriere_lunga: "Zacusca noastră este preparată după rețeta tradițională românească, folosind doar legume proaspete: vinete, ardei kapia, ceapă și roșii. Fără conservanți artificiali.",
        ingrediente: "Vinete (40%), ardei kapia (25%), ceapă (20%), roșii (10%), ulei de floarea soarelui, sare, piper",
        pret: "15.99",
        imagine: "img/zacusca.png",
        categorie: "conserve",
        regiune: "Muntenia",
        cantitate: "300g",
        stoc: 25,
        producator: "Gospodăria Ion",
        recomandat: 0,
        restrictie_varsta: 0,
        activ: 1,
        tags: ["artizanal", "fara-aditivi"],
        nutritionalInfo: [
            {"name": "Valoare energetică", "value": "95 kcal / 398 kJ"},
            {"name": "Grăsimi", "value": "7.5g"},
            {"name": "din care acizi grași saturați", "value": "1.2g", "indented": true},
            {"name": "Glucide", "value": "6.2g"},
            {"name": "din care zaharuri", "value": "4.8g", "indented": true},
            {"name": "Fibre", "value": "2.1g"},
            {"name": "Proteine", "value": "1.8g"},
            {"name": "Sare", "value": "1.1g"}
        ],
        related_products: [
            {
                id: 1,
                nume: "Dulceață de Căpșuni de Argeș",
                descriere: "Dulceață tradițională din căpșuni proaspete",
                pret: "18.99",
                imagine: "img/dulceata.png",
                regiune: "Argeș",
                cantitate: "350g"
            }
        ]
    }
];

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

        // Try to load from API first
        let apiSuccess = false;
        try {
            const response = await fetch(`api/catalog.php?type=product&id=${productId}`);
            
            if (response.ok) {
                const result = await response.json();
                console.log('API Response:', result);
                
                if (result.success && result.product) {
                    productData = result.product;
                    apiSuccess = true;
                    console.log('Product loaded successfully from API');
                }
            }
        } catch (apiError) {
            console.warn('API request failed:', apiError);
        }

        // If API failed, try fallback data
        if (!apiSuccess) {
            console.log('API failed, using fallback data');
            productData = fallbackProducts.find(p => p.id === productId);
            
            if (!productData) {
                // If specific product not found, use the first one
                productData = fallbackProducts[0];
                console.log('Using first fallback product');
            }
        }

        if (!productData) {
            throw new Error('Nu s-au putut încărca datele produsului');
        }

        // Show warning if using fallback
        if (!apiSuccess) {
            showNotification('⚠️ Modul de demonstrație - folosim date de test', 'warning');
        }

        // Render product details
        renderProductDetails();
        
        // Update cart count if function exists
        if (typeof updateCartCount === 'function') {
            updateCartCount();
        }
        
    } catch (error) {
        console.error('Error loading product:', error);
        
        // Last resort - load first fallback product
        console.log('Loading emergency fallback product');
        productData = fallbackProducts[0];
        renderProductDetails();
        showNotification('⚠️ Modul de demonstrație - product de test încărcat', 'info');
    }
});

// Render product details
function renderProductDetails() {
    // Update page title and meta description
    document.title = `${productData.nume} - Gusturi Românești`;
    const metaDescription = document.querySelector('meta[name="description"]');
    if (metaDescription) {
        metaDescription.setAttribute('content', productData.descriere || '');
    }

    // Update breadcrumb
    document.getElementById('product-breadcrumb').textContent = productData.nume;

    // Basic product info
    document.getElementById('product-name').textContent = productData.nume;
    document.getElementById('product-image').src = productData.imagine;
    document.getElementById('product-image').alt = productData.nume;
    document.getElementById('product-region').textContent = `Produs local din ${productData.regiune}`;
    document.getElementById('product-price').textContent = `${parseFloat(productData.pret).toFixed(2)} RON`;
    
    // Handle weight/quantity display
    const weightText = productData.cantitate || productData.greutate || '';
    document.getElementById('product-weight').textContent = weightText ? `/ ${weightText}` : '';
    
    // Product descriptions
    document.getElementById('product-description').textContent = productData.descriere || '';
    document.getElementById('product-long-description').textContent = productData.descriere_lunga || productData.descriere || '';
    document.getElementById('product-ingredients').textContent = productData.ingrediente || 'Informații indisponibile';

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
    } else if (productData.valoare_energetica || productData.grasimi || productData.glucide || productData.proteine) {
        // Use database nutritional data
        tableTitle.textContent = 'Informații Nutriționale (per 100g)';
        
        const nutritionalData = [
            { name: 'Valoare energetică', value: productData.valoare_energetica },
            { name: 'Grăsimi', value: productData.grasimi },
            { name: 'din care acizi grași saturați', value: productData.grasimi_saturate, indented: true },
            { name: 'Glucide', value: productData.glucide },
            { name: 'din care zaharuri', value: productData.zaharuri, indented: true },
            { name: 'Fibre', value: productData.fibre },
            { name: 'Proteine', value: productData.proteine },
            { name: 'Sare', value: productData.sare }
        ].filter(item => item.value && item.value !== '0g' && item.value !== '0');

        nutritionalData.forEach(item => {
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
        
        if (productData.data_adaugarii) {
            const addedDate = new Date(productData.data_adaugarii).toLocaleDateString('ro-RO');
            productInfo.push({ nume: 'Adăugat în catalog', valoare: addedDate });
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
    if (productData.ingrediente_nutritionale || productData.ingrediente) {
        const ingredientsRow = document.createElement('tr');
        ingredientsRow.innerHTML = `
            <td colspan="2" class="pt-4">
                <h6 class="mb-2">Ingrediente:</h6>
                <p class="mb-0 text-muted">${productData.ingrediente_nutritionale || productData.ingrediente}</p>
            </td>
        `;
        tableBody.appendChild(ingredientsRow);
    }
}

// Render related products
function renderRelatedProducts() {
    const container = document.getElementById('related-products');
    container.innerHTML = '';

    // Check if we have related products from the API
    if (productData.related_products && productData.related_products.length > 0) {
        productData.related_products.forEach(product => {
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
                <p class="card-text text-muted small">${(product.descriere || '').substring(0, 60)}...</p>
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
function addToCartFromDetail() {
    const quantity = parseInt(document.getElementById('quantity').value);

    for (let i = 0; i < quantity; i++) {
        addToCart(
            productData.id, 
            productData.nume,
            productData.pret,
            productData.imagine,
            productData.cantitate || productData.greutate || ''
        );
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
    const urlParams = new URLSearchParams(window.location.search);
    const productId = urlParams.get('id');
    
    // Try to load fallback product instead of showing error
    console.log('Error occurred, attempting fallback...');
    const fallbackProduct = fallbackProducts.find(p => p.id === parseInt(productId)) || fallbackProducts[0];
    
    if (fallbackProduct) {
        productData = fallbackProduct;
        renderProductDetails();
        showNotification('⚠️ Produsul a fost încărcat în modul de demonstrație', 'warning');
        return;
    }
    
    // Only show error as last resort
    const container = document.querySelector('.container');
    container.innerHTML = `
        <div class="alert alert-danger text-center my-5">
            <i class="bi bi-exclamation-triangle-fill fs-1 d-block mb-3"></i>
            <h3>Eroare</h3>
            <p>${message}</p>
            ${productId ? `<p class="small text-muted">ID produs: ${productId}</p>` : ''}
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
