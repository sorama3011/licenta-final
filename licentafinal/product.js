
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

        // Fetch product data from consolidated API
        const response = await fetch(`api/catalog.php?type=product&id=${productId}`);
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        const result = await response.json();
        if (!result.success) {
            showError(result.message || 'Produsul nu a fost găsit. Vă rugăm să selectați un produs valid.');
            return;
        }
        productData = result.product;

        if (!productData) {
            showError('Produsul nu a fost găsit. Vă rugăm să selectați un produs valid.');
            return;
        }

        // Render product details
        renderProductDetails();
        // Update cart count
        updateCartCount();
    } catch (error) {
        console.error('Error loading product:', error);
        showError('A apărut o eroare la încărcarea produsului. Vă rugăm să încercați din nou.');
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
    const container = document.querySelector('.container');
    container.innerHTML = `
        <div class="alert alert-danger text-center my-5">
            <i class="bi bi-exclamation-triangle-fill fs-1 d-block mb-3"></i>
            <h3>Eroare</h3>
            <p>${message}</p>
            <a href="products.html" class="btn btn-primary mt-3">
                <i class="bi bi-arrow-left"></i> Înapoi la Produse
            </a>
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
