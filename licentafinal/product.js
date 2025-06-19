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

// Load all products from JSON file

// Render product details
function renderProductDetails() {
    // Update page title and meta description
    document.title = `${productData.nume} - Gusturi Românești`;
    document.querySelector('meta[name="description"]').setAttribute('content', productData.descriere);

    // Update breadcrumb
    document.getElementById('product-breadcrumb').textContent = productData.nume;

    // Basic product info
    document.getElementById('product-name').textContent = productData.nume;
    document.getElementById('product-image').src = productData.imagine;
    document.getElementById('product-image').alt = productData.nume;
    document.getElementById('product-region').textContent = `Produs local din ${productData.regiune}`;
    document.getElementById('product-price').textContent = `${parseFloat(productData.pret).toFixed(2)} RON`;
    document.getElementById('product-weight').textContent = `/ ${productData.greutate}`;
    document.getElementById('product-description').textContent = productData.descriere;
    document.getElementById('product-long-description').textContent = productData.descriere_lunga;
    document.getElementById('product-ingredients').textContent = productData.ingrediente;

    // Product details
    const detailsContainer = document.getElementById('product-details');
    detailsContainer.innerHTML = '';

    // Add origin
    if (productData.detalii.origine) {
        detailsContainer.innerHTML += `
            <div class="col-6">
                <strong>Origine:</strong> ${productData.detalii.origine}
            </div>
        `;
    }

    // Add packaging
    if (productData.detalii.ambalaj) {
        detailsContainer.innerHTML += `
            <div class="col-6">
                <strong>Tip ambalaj:</strong> ${productData.detalii.ambalaj}
            </div>
        `;
    }

    // Add alcohol content if applicable
    if (productData.detalii.concentratie_alcool) {
        detailsContainer.innerHTML += `
            <div class="col-6">
                <strong>Concentrație alcool:</strong> ${productData.detalii.concentratie_alcool}
            </div>
        `;
    }

    // Add expiration date if applicable
    if (productData.detalii.expirare) {
        detailsContainer.innerHTML += `
            <div class="col-6">
                <strong>Expiră la:</strong> ${productData.detalii.expirare}
            </div>
        `;
    }

    // Show age restriction warning if applicable
    if (productData.restrictie_varsta) {
        document.getElementById('age-restriction-warning').style.display = 'block';
    } else {
        document.getElementById('age-restriction-warning').style.display = 'none';
    }

    // Show special badge if applicable
    if (productData.badge_special) {
        document.getElementById('special-badge').style.display = 'block';
        document.getElementById('special-badge-text').textContent = productData.badge_special.text;
        document.getElementById('special-badge-description').textContent = productData.badge_special.description;
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

    // Check if product has nutritional info or product info
    if (productData.informatii_nutritionale) {
        tableTitle.textContent = 'Informații Nutriționale (per 100g)';

        productData.informatii_nutritionale.forEach(item => {
            const row = document.createElement('tr');

            // Add indentation for sub-items if needed
            if (item.indented) {
                row.innerHTML = `
                    <td class="ps-4">${item.nume}</td>
                    <td>${item.valoare}</td>
                `;
            } else {
                row.innerHTML = `
                    <td><strong>${item.nume}</strong></td>
                    <td>${item.valoare}</td>
                `;
            }

            tableBody.appendChild(row);
        });
    } else if (productData.informatii_produs) {
        tableTitle.textContent = 'Informații despre Produs';

        productData.informatii_produs.forEach(item => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><strong>${item.nume}</strong></td>
                <td>${item.valoare}</td>
            `;
            tableBody.appendChild(row);
        });
    }
}

// Render related products
function renderRelatedProducts() {
    const container = document.getElementById('related-products');
    container.innerHTML = '';

    // Get related products data
    const relatedProductsData = productData.relatedProducts
        .map(id => allProducts.find(product => product.id === id))
        .filter(product => product !== undefined);

    // Render each related product
    relatedProductsData.forEach(product => {
        const productCard = document.createElement('div');
        productCard.className = 'col-md-6 col-lg-3';

        productCard.innerHTML = `
            <div class="card product-card h-100 shadow-sm">
                <a href="product.html?id=${product.id}" class="text-decoration-none">
                    <img src="${product.image}" class="card-img-top" alt="${product.name}" style="height: 200px; object-fit: cover;">
                </a>
                <div class="card-body d-flex flex-column">
                    <span class="badge region-badge mb-2 align-self-start">Produs local din ${product.region}</span>
                    <h5 class="card-title">
                        <a href="product.html?id=${product.id}" class="text-decoration-none text-dark">${product.name}</a>
                    </h5>
                    <p class="card-text text-muted small">${product.description.substring(0, 60)}...</p>
                    <div class="mt-auto">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="price">${product.price.toFixed(2)} RON</span>
                            <span class="text-muted small">${product.weight}</span>
                        </div>
                        <button class="btn btn-add-to-cart w-100" onclick="addToCart(${product.id}, '${product.name}', ${product.price}, '${product.image}', '${product.weight}')">
                            <i class="bi bi-basket"></i> Adaugă în Coș
                        </button>
                    </div>
                </div>
            </div>
        `;

        container.appendChild(productCard);
    });
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
            productData.greutate
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
```

```
</replit_final_file>