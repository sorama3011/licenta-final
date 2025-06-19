// Admin Dashboard JavaScript
// Handles chart generation and report functionality

// Mock data for reports
const mockData = {
    // Sales data by month for current year
    monthlySales: {
        labels: ['Ian', 'Feb', 'Mar', 'Apr', 'Mai', 'Iun', 'Iul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        data: [5200, 6100, 5800, 8100, 7200, 6500, 7800, 8200, 7500, 8900, 9500, 12000]
    },
    
    // Sales data by week for current month
    weeklySales: {
        labels: ['Săpt 1', 'Săpt 2', 'Săpt 3', 'Săpt 4'],
        data: [1800, 2200, 1950, 2500]
    },
    
    // Sales data by day for current week
    dailySales: {
        labels: ['Luni', 'Marți', 'Miercuri', 'Joi', 'Vineri', 'Sâmbătă', 'Duminică'],
        data: [450, 520, 480, 650, 720, 850, 680]
    },
    
    // Sales data by year
    yearlySales: {
        labels: ['2020', '2021', '2022', '2023', '2024'],
        data: [45000, 62000, 78000, 95000, 32400]
    },
    
    // Top selling products
    topProducts: [
        { name: 'Dulceață de Căpșuni', category: 'Dulcețuri', quantity: 120, total: 2278.80 },
        { name: 'Brânză de Burduf', category: 'Brânzeturi', quantity: 85, total: 2720.00 },
        { name: 'Țuică de Prune', category: 'Băuturi', quantity: 78, total: 3510.00 },
        { name: 'Zacuscă de Buzău', category: 'Conserve', quantity: 95, total: 1472.50 },
        { name: 'Miere de Salcâm', category: 'Dulcețuri', quantity: 110, total: 3135.00 },
        { name: 'Cârnați de Pleșcoi', category: 'Mezeluri', quantity: 72, total: 1799.28 },
        { name: 'Telemea de Ibănești', category: 'Brânzeturi', quantity: 68, total: 1326.00 },
        { name: 'Pălincă de Pere', category: 'Băuturi', quantity: 45, total: 2475.00 },
        { name: 'Gem de Caise', category: 'Dulcețuri', quantity: 65, total: 1397.50 },
        { name: 'Slănină Afumată', category: 'Mezeluri', quantity: 55, total: 1925.00 }
    ],
    
    // Product inventory
    inventory: [
        { name: 'Dulceață de Căpșuni', category: 'Dulcețuri', stock: 25, value: 474.75 },
        { name: 'Brânză de Burduf', category: 'Brânzeturi', stock: 15, value: 480.00 },
        { name: 'Țuică de Prune', category: 'Băuturi', stock: 20, value: 900.00 },
        { name: 'Zacuscă de Buzău', category: 'Conserve', stock: 32, value: 496.00 },
        { name: 'Miere de Salcâm', category: 'Dulcețuri', stock: 0, value: 0.00 },
        { name: 'Cârnați de Pleșcoi', category: 'Mezeluri', stock: 18, value: 449.82 },
        { name: 'Telemea de Ibănești', category: 'Brânzeturi', stock: 22, value: 429.00 },
        { name: 'Pălincă de Pere', category: 'Băuturi', stock: 12, value: 660.00 },
        { name: 'Gem de Caise', category: 'Dulcețuri', stock: 28, value: 602.00 },
        { name: 'Slănină Afumată', category: 'Mezeluri', stock: 9, value: 315.00 }
    ],
    
    // Sales by category
    categorySales: {
        labels: ['Dulcețuri', 'Conserve', 'Mezeluri', 'Brânzeturi', 'Băuturi'],
        data: [7500, 4200, 5800, 6300, 8200]
    },
    
    // Product profitability
    productProfitability: [
        { name: 'Dulceață de Căpșuni', category: 'Dulcețuri', revenue: 2278.80, cost: 1367.28, profit: 911.52, margin: 40 },
        { name: 'Brânză de Burduf', category: 'Brânzeturi', revenue: 2720.00, cost: 1632.00, profit: 1088.00, margin: 40 },
        { name: 'Țuică de Prune', category: 'Băuturi', revenue: 3510.00, cost: 1755.00, profit: 1755.00, margin: 50 },
        { name: 'Zacuscă de Buzău', category: 'Conserve', revenue: 1472.50, cost: 883.50, profit: 589.00, margin: 40 },
        { name: 'Miere de Salcâm', category: 'Dulcețuri', revenue: 3135.00, cost: 1881.00, profit: 1254.00, margin: 40 },
        { name: 'Cârnați de Pleșcoi', category: 'Mezeluri', revenue: 1799.28, cost: 1079.57, profit: 719.71, margin: 40 },
        { name: 'Telemea de Ibănești', category: 'Brânzeturi', revenue: 1326.00, cost: 795.60, profit: 530.40, margin: 40 },
        { name: 'Pălincă de Pere', category: 'Băuturi', revenue: 2475.00, cost: 1237.50, profit: 1237.50, margin: 50 },
        { name: 'Gem de Caise', category: 'Dulcețuri', revenue: 1397.50, cost: 838.50, profit: 559.00, margin: 40 },
        { name: 'Slănină Afumată', category: 'Mezeluri', revenue: 1925.00, cost: 1155.00, profit: 770.00, margin: 40 }
    ],
    
    // Top clients
    topClients: [
        { name: 'Maria Popescu', email: 'maria.popescu@example.com', orders: 12, total: 1247.50 },
        { name: 'Ion Ionescu', email: 'ion.ionescu@example.com', orders: 8, total: 985.75 },
        { name: 'Elena Vasilescu', email: 'elena.vasilescu@example.com', orders: 10, total: 1520.00 },
        { name: 'Andrei Munteanu', email: 'andrei.munteanu@example.com', orders: 5, total: 750.25 },
        { name: 'Cristina Dumitrescu', email: 'cristina.dumitrescu@example.com', orders: 7, total: 925.50 },
        { name: 'Mihai Popa', email: 'mihai.popa@example.com', orders: 6, total: 820.00 },
        { name: 'Ana Radu', email: 'ana.radu@example.com', orders: 9, total: 1150.75 },
        { name: 'George Stanescu', email: 'george.stanescu@example.com', orders: 4, total: 650.00 },
        { name: 'Ioana Diaconu', email: 'ioana.diaconu@example.com', orders: 3, total: 450.25 },
        { name: 'Alexandru Georgescu', email: 'alexandru.georgescu@example.com', orders: 5, total: 725.50 }
    ],
    
    // Order frequency
    orderFrequency: [
        { frequency: 'O dată', clients: 45 },
        { frequency: '2-5 ori', clients: 32 },
        { frequency: '6-10 ori', clients: 18 },
        { frequency: '11+ ori', clients: 5 }
    ],
    
    // New vs returning clients
    clientTypes: {
        labels: ['Noi', 'Recurenți'],
        data: [35, 65]
    },
    
    // Client geographic distribution
    clientRegions: {
        labels: ['București', 'Cluj', 'Iași', 'Timișoara', 'Constanța', 'Brașov', 'Alte'],
        data: [35, 15, 12, 10, 8, 7, 13]
    }
};

// Chart colors
const chartColors = {
    primary: '#8B0000',
    secondary: '#DAA520',
    accent: '#722F37',
    textDark: '#2C1810',
    background: '#FFF8F0',
    border: '#E8D5B7',
    success: '#198754',
    info: '#0dcaf0',
    warning: '#ffc107',
    danger: '#dc3545',
    chartColors: [
        '#8B0000', '#DAA520', '#722F37', '#2C1810', '#E8D5B7',
        '#A52A2A', '#CD853F', '#B22222', '#8B4513', '#D2691E'
    ]
};

// Initialize charts when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Check if Chart.js is loaded
    if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded. Please include the Chart.js library.');
        // Add Chart.js from CDN if not present
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
        script.onload = initializeReports;
        document.head.appendChild(script);
    } else {
        initializeReports();
    }
});

function initializeReports() {
    // Set up event listeners for report generation buttons
    const generateSalesReportBtn = document.getElementById('generateSalesReport');
    const generateProductReportBtn = document.getElementById('generateProductReport');
    const generateClientReportBtn = document.getElementById('generateClientReport');
    
    if (generateSalesReportBtn) {
        generateSalesReportBtn.addEventListener('click', generateSalesReport);
    }
    
    if (generateProductReportBtn) {
        generateProductReportBtn.addEventListener('click', generateProductReport);
    }
    
    if (generateClientReportBtn) {
        generateClientReportBtn.addEventListener('click', generateClientReport);
    }
    
    // Show a default chart if available
    const salesChartContainer = document.getElementById('salesChartContainer');
    if (salesChartContainer && !salesChartContainer.querySelector('canvas')) {
        // Create default canvas if it doesn't exist
        const canvas = document.createElement('canvas');
        canvas.id = 'salesChart';
        salesChartContainer.appendChild(canvas);
        
        // Create default monthly sales chart
        createSalesChart('monthly', 'current');
    }
}

/**
 * Generate Sales Report
 */
function generateSalesReport() {
    // Get report options
    const reportType = document.getElementById('salesReportType').value;
    const reportPeriod = document.getElementById('salesReportPeriod').value;
    const reportFormat = document.getElementById('salesReportFormat').value;
    
    // Hide placeholder
    const placeholder = document.getElementById('salesReportPlaceholder');
    if (placeholder) {
        placeholder.style.display = 'none';
    }
    
    // Show/hide chart and table based on format
    const chartContainer = document.getElementById('salesChartContainer');
    const tableContainer = document.getElementById('salesTableContainer');
    
    if (reportFormat === 'chart' || reportFormat === 'both') {
        if (chartContainer) {
            chartContainer.style.display = 'block';
            createSalesChart(reportType, reportPeriod);
        }
    } else {
        if (chartContainer) {
            chartContainer.style.display = 'none';
        }
    }
    
    if (reportFormat === 'table' || reportFormat === 'both') {
        if (tableContainer) {
            tableContainer.style.display = 'block';
            createSalesTable(reportType, reportPeriod);
        }
    } else {
        if (tableContainer) {
            tableContainer.style.display = 'none';
        }
    }
}

/**
 * Create Sales Chart
 */
function createSalesChart(reportType, reportPeriod) {
    // Get the canvas element
    const canvasElement = document.getElementById('salesChart');
    if (!canvasElement) {
        console.error('Canvas element with ID "salesChart" not found');
        return;
    }
    
    // Destroy existing chart if it exists
    if (window.salesChart) {
        window.salesChart.destroy();
    }
    
    // Get data based on report type
    let labels, data;
    let chartTitle = '';
    
    switch (reportType) {
        case 'daily':
            labels = mockData.dailySales.labels;
            data = mockData.dailySales.data;
            chartTitle = 'Vânzări Zilnice';
            break;
        case 'weekly':
            labels = mockData.weeklySales.labels;
            data = mockData.weeklySales.data;
            chartTitle = 'Vânzări Săptămânale';
            break;
        case 'monthly':
            labels = mockData.monthlySales.labels;
            data = mockData.monthlySales.data;
            chartTitle = 'Vânzări Lunare';
            break;
        case 'yearly':
            labels = mockData.yearlySales.labels;
            data = mockData.yearlySales.data;
            chartTitle = 'Vânzări Anuale';
            break;
        default:
            labels = mockData.monthlySales.labels;
            data = mockData.monthlySales.data;
            chartTitle = 'Vânzări Lunare';
    }
    
    // Create chart
    window.salesChart = new Chart(canvasElement, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Vânzări (RON)',
                data: data,
                backgroundColor: chartColors.primary,
                borderColor: chartColors.primary,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: chartTitle
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y + ' RON';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value + ' RON';
                        }
                    }
                }
            }
        }
    });
}

/**
 * Create Sales Table
 */
function createSalesTable(reportType, reportPeriod) {
    // Get the table body
    const tableBody = document.getElementById('salesTable')?.querySelector('tbody');
    if (!tableBody) {
        console.error('Table body element not found');
        return;
    }
    
    tableBody.innerHTML = '';
    
    // Get data based on report type
    let data;
    
    switch (reportType) {
        case 'daily':
            data = mockData.dailySales;
            break;
        case 'weekly':
            data = mockData.weeklySales;
            break;
        case 'monthly':
            data = mockData.monthlySales;
            break;
        case 'yearly':
            data = mockData.yearlySales;
            break;
        default:
            data = mockData.monthlySales;
    }
    
    // Create table rows
    for (let i = 0; i < data.labels.length; i++) {
        // Skip future months with 0 values
        if (reportType === 'monthly' && data.data[i] === 0) continue;
        
        const row = document.createElement('tr');
        
        // Calculate mock data for orders and average
        const orders = Math.round(data.data[i] / 100);
        const average = (data.data[i] / orders).toFixed(2);
        
        row.innerHTML = `
            <td>${data.labels[i]}</td>
            <td>${orders}</td>
            <td>${data.data[i].toFixed(2)} RON</td>
            <td>${average} RON</td>
        `;
        
        tableBody.appendChild(row);
    }
}

/**
 * Generate Product Report
 */
function generateProductReport() {
    // Get report options
    const reportType = document.getElementById('productReportType').value;
    const reportPeriod = document.getElementById('productReportPeriod').value;
    const reportLimit = parseInt(document.getElementById('productReportLimit').value);
    
    // Hide placeholder
    const placeholder = document.getElementById('productReportPlaceholder');
    if (placeholder) {
        placeholder.style.display = 'none';
    }
    
    // Show chart and table
    const chartContainer = document.getElementById('productChartContainer');
    const tableContainer = document.getElementById('productTableContainer');
    
    if (chartContainer) {
        chartContainer.style.display = 'block';
    }
    
    if (tableContainer) {
        tableContainer.style.display = 'block';
    }
    
    // Create chart and table based on report type
    switch (reportType) {
        case 'bestsellers':
            createBestSellersChart(reportLimit);
            createBestSellersTable(reportLimit);
            break;
        case 'inventory':
            createInventoryChart(reportLimit);
            createInventoryTable(reportLimit);
            break;
        case 'categories':
            createCategorySalesChart();
            createCategorySalesTable();
            break;
        case 'profit':
            createProfitabilityChart(reportLimit);
            createProfitabilityTable(reportLimit);
            break;
        default:
            createBestSellersChart(reportLimit);
            createBestSellersTable(reportLimit);
    }
}

/**
 * Create Best Sellers Chart
 */
function createBestSellersChart(limit) {
    // Get the canvas element
    const canvasElement = document.getElementById('productChart');
    if (!canvasElement) {
        console.error('Canvas element with ID "productChart" not found');
        return;
    }
    
    // Destroy existing chart if it exists
    if (window.productChart) {
        window.productChart.destroy();
    }
    
    // Get top products based on limit
    const topProducts = mockData.topProducts.slice(0, limit);
    
    // Create chart
    window.productChart = new Chart(canvasElement, {
        type: 'bar',
        data: {
            labels: topProducts.map(product => product.name),
            datasets: [{
                label: 'Cantitate Vândută',
                data: topProducts.map(product => product.quantity),
                backgroundColor: chartColors.chartColors.slice(0, limit),
                borderColor: chartColors.chartColors.slice(0, limit),
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Cele Mai Vândute Produse'
                }
            }
        }
    });
}

/**
 * Create Best Sellers Table
 */
function createBestSellersTable(limit) {
    // Get the table body
    const tableBody = document.getElementById('productTable')?.querySelector('tbody');
    if (!tableBody) {
        console.error('Table body element not found');
        return;
    }
    
    // Get table header
    const tableHeader = document.getElementById('productTable')?.querySelector('thead tr');
    if (tableHeader) {
        tableHeader.innerHTML = `
            <th>Produs</th>
            <th>Categorie</th>
            <th>Cantitate Vândută</th>
            <th>Vânzări Totale</th>
        `;
    }
    
    tableBody.innerHTML = '';
    
    // Get top products based on limit
    const topProducts = mockData.topProducts.slice(0, limit);
    
    // Create table rows
    topProducts.forEach(product => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${product.name}</td>
            <td>${product.category}</td>
            <td>${product.quantity}</td>
            <td>${product.total.toFixed(2)} RON</td>
        `;
        tableBody.appendChild(row);
    });
}

/**
 * Create Inventory Chart
 */
function createInventoryChart(limit) {
    // Get the canvas element
    const canvasElement = document.getElementById('productChart');
    if (!canvasElement) {
        console.error('Canvas element with ID "productChart" not found');
        return;
    }
    
    // Destroy existing chart if it exists
    if (window.productChart) {
        window.productChart.destroy();
    }
    
    // Get products based on limit
    const products = mockData.inventory.slice(0, limit);
    
    // Create chart
    window.productChart = new Chart(canvasElement, {
        type: 'bar',
        data: {
            labels: products.map(product => product.name),
            datasets: [{
                label: 'Stoc Disponibil',
                data: products.map(product => product.stock),
                backgroundColor: products.map(product => product.stock === 0 ? chartColors.danger : chartColors.success),
                borderColor: products.map(product => product.stock === 0 ? chartColors.danger : chartColors.success),
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Stoc Produse'
                }
            }
        }
    });
}

/**
 * Create Inventory Table
 */
function createInventoryTable(limit) {
    // Get the table body
    const tableBody = document.getElementById('productTable')?.querySelector('tbody');
    if (!tableBody) {
        console.error('Table body element not found');
        return;
    }
    
    // Get table header
    const tableHeader = document.getElementById('productTable')?.querySelector('thead tr');
    if (tableHeader) {
        tableHeader.innerHTML = `
            <th>Produs</th>
            <th>Categorie</th>
            <th>Stoc</th>
            <th>Valoare Stoc</th>
            <th>Status</th>
        `;
    }
    
    tableBody.innerHTML = '';
    
    // Get products based on limit
    const products = mockData.inventory.slice(0, limit);
    
    // Create table rows
    products.forEach(product => {
        const row = document.createElement('tr');
        const status = product.stock === 0 ? 
            '<span class="badge bg-danger">Stoc Epuizat</span>' : 
            (product.stock < 10 ? 
                '<span class="badge bg-warning text-dark">Stoc Limitat</span>' : 
                '<span class="badge bg-success">În Stoc</span>');
        
        row.innerHTML = `
            <td>${product.name}</td>
            <td>${product.category}</td>
            <td>${product.stock}</td>
            <td>${product.value.toFixed(2)} RON</td>
            <td>${status}</td>
        `;
        tableBody.appendChild(row);
    });
}

/**
 * Create Category Sales Chart
 */
function createCategorySalesChart() {
    // Get the canvas element
    const canvasElement = document.getElementById('productChart');
    if (!canvasElement) {
        console.error('Canvas element with ID "productChart" not found');
        return;
    }
    
    // Destroy existing chart if it exists
    if (window.productChart) {
        window.productChart.destroy();
    }
    
    // Create chart
    window.productChart = new Chart(canvasElement, {
        type: 'pie',
        data: {
            labels: mockData.categorySales.labels,
            datasets: [{
                data: mockData.categorySales.data,
                backgroundColor: chartColors.chartColors.slice(0, mockData.categorySales.labels.length),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right'
                },
                title: {
                    display: true,
                    text: 'Vânzări pe Categorii'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.parsed;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${context.label}: ${value} RON (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

/**
 * Create Category Sales Table
 */
function createCategorySalesTable() {
    // Get the table body
    const tableBody = document.getElementById('productTable')?.querySelector('tbody');
    if (!tableBody) {
        console.error('Table body element not found');
        return;
    }
    
    // Get table header
    const tableHeader = document.getElementById('productTable')?.querySelector('thead tr');
    if (tableHeader) {
        tableHeader.innerHTML = `
            <th>Categorie</th>
            <th>Vânzări Totale</th>
            <th>Procent</th>
        `;
    }
    
    tableBody.innerHTML = '';
    
    // Calculate total sales
    const totalSales = mockData.categorySales.data.reduce((a, b) => a + b, 0);
    
    // Create table rows
    for (let i = 0; i < mockData.categorySales.labels.length; i++) {
        const row = document.createElement('tr');
        const percentage = Math.round((mockData.categorySales.data[i] / totalSales) * 100);
        
        row.innerHTML = `
            <td>${mockData.categorySales.labels[i]}</td>
            <td>${mockData.categorySales.data[i].toFixed(2)} RON</td>
            <td>${percentage}%</td>
        `;
        tableBody.appendChild(row);
    }
    
    // Add total row
    const totalRow = document.createElement('tr');
    totalRow.className = 'table-active fw-bold';
    totalRow.innerHTML = `
        <td>Total</td>
        <td>${totalSales.toFixed(2)} RON</td>
        <td>100%</td>
    `;
    tableBody.appendChild(totalRow);
}

/**
 * Create Profitability Chart
 */
function createProfitabilityChart(limit) {
    // Get the canvas element
    const canvasElement = document.getElementById('productChart');
    if (!canvasElement) {
        console.error('Canvas element with ID "productChart" not found');
        return;
    }
    
    // Destroy existing chart if it exists
    if (window.productChart) {
        window.productChart.destroy();
    }
    
    // Get products based on limit
    const products = mockData.productProfitability.slice(0, limit);
    
    // Create chart
    window.productChart = new Chart(canvasElement, {
        type: 'bar',
        data: {
            labels: products.map(product => product.name),
            datasets: [{
                label: 'Venit',
                data: products.map(product => product.revenue),
                backgroundColor: chartColors.primary,
                borderColor: chartColors.primary,
                borderWidth: 1
            }, {
                label: 'Cost',
                data: products.map(product => product.cost),
                backgroundColor: chartColors.danger,
                borderColor: chartColors.danger,
                borderWidth: 1
            }, {
                label: 'Profit',
                data: products.map(product => product.profit),
                backgroundColor: chartColors.success,
                borderColor: chartColors.success,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top'
                },
                title: {
                    display: true,
                    text: 'Profitabilitate Produse'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y + ' RON';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value + ' RON';
                        }
                    }
                }
            }
        }
    });
}

/**
 * Create Profitability Table
 */
function createProfitabilityTable(limit) {
    // Get the table body
    const tableBody = document.getElementById('productTable')?.querySelector('tbody');
    if (!tableBody) {
        console.error('Table body element not found');
        return;
    }
    
    // Get table header
    const tableHeader = document.getElementById('productTable')?.querySelector('thead tr');
    if (tableHeader) {
        tableHeader.innerHTML = `
            <th>Produs</th>
            <th>Categorie</th>
            <th>Venit</th>
            <th>Cost</th>
            <th>Profit</th>
            <th>Marjă</th>
        `;
    }
    
    tableBody.innerHTML = '';
    
    // Get products based on limit
    const products = mockData.productProfitability.slice(0, limit);
    
    // Create table rows
    products.forEach(product => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${product.name}</td>
            <td>${product.category}</td>
            <td>${product.revenue.toFixed(2)} RON</td>
            <td>${product.cost.toFixed(2)} RON</td>
            <td>${product.profit.toFixed(2)} RON</td>
            <td>${product.margin}%</td>
        `;
        tableBody.appendChild(row);
    });
}

/**
 * Generate Client Report
 */
function generateClientReport() {
    // Get report options
    const reportType = document.getElementById('clientReportType').value;
    const reportPeriod = document.getElementById('clientReportPeriod').value;
    const reportLimit = parseInt(document.getElementById('clientReportLimit').value);
    
    // Hide placeholder
    const placeholder = document.getElementById('clientReportPlaceholder');
    if (placeholder) {
        placeholder.style.display = 'none';
    }
    
    // Show chart and table
    const chartContainer = document.getElementById('clientChartContainer');
    const tableContainer = document.getElementById('clientTableContainer');
    
    if (chartContainer) {
        chartContainer.style.display = 'block';
    }
    
    if (tableContainer) {
        tableContainer.style.display = 'block';
    }
    
    // Create chart and table based on report type
    switch (reportType) {
        case 'topSpenders':
            createTopSpendersChart(reportLimit);
            createTopSpendersTable(reportLimit);
            break;
        case 'frequency':
            createOrderFrequencyChart();
            createOrderFrequencyTable();
            break;
        case 'newVsReturning':
            createClientTypesChart();
            createClientTypesTable();
            break;
        case 'region':
            createClientRegionsChart();
            createClientRegionsTable();
            break;
        default:
            createTopSpendersChart(reportLimit);
            createTopSpendersTable(reportLimit);
    }
}

/**
 * Create Top Spenders Chart
 */
function createTopSpendersChart(limit) {
    // Get the canvas element
    const canvasElement = document.getElementById('clientChart');
    if (!canvasElement) {
        console.error('Canvas element with ID "clientChart" not found');
        return;
    }
    
    // Destroy existing chart if it exists
    if (window.clientChart) {
        window.clientChart.destroy();
    }
    
    // Get top clients based on limit
    const topClients = mockData.topClients.slice(0, limit);
    
    // Create chart
    window.clientChart = new Chart(canvasElement, {
        type: 'bar',
        data: {
            labels: topClients.map(client => client.name),
            datasets: [{
                label: 'Total Cheltuit (RON)',
                data: topClients.map(client => client.total),
                backgroundColor: chartColors.chartColors.slice(0, limit),
                borderColor: chartColors.chartColors.slice(0, limit),
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Top Clienți după Valoarea Comenzilor'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.parsed.x + ' RON';
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value + ' RON';
                        }
                    }
                }
            }
        }
    });
}

/**
 * Create Top Spenders Table
 */
function createTopSpendersTable(limit) {
    // Get the table body
    const tableBody = document.getElementById('clientTable')?.querySelector('tbody');
    if (!tableBody) {
        console.error('Table body element not found');
        return;
    }
    
    // Get table header
    const tableHeader = document.getElementById('clientTable')?.querySelector('thead tr');
    if (tableHeader) {
        tableHeader.innerHTML = `
            <th>Client</th>
            <th>Email</th>
            <th>Număr Comenzi</th>
            <th>Total Cheltuit</th>
        `;
    }
    
    tableBody.innerHTML = '';
    
    // Get top clients based on limit
    const topClients = mockData.topClients.slice(0, limit);
    
    // Create table rows
    topClients.forEach(client => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${client.name}</td>
            <td>${client.email}</td>
            <td>${client.orders}</td>
            <td>${client.total.toFixed(2)} RON</td>
        `;
        tableBody.appendChild(row);
    });
}

/**
 * Create Order Frequency Chart
 */
function createOrderFrequencyChart() {
    // Get the canvas element
    const canvasElement = document.getElementById('clientChart');
    if (!canvasElement) {
        console.error('Canvas element with ID "clientChart" not found');
        return;
    }
    
    // Destroy existing chart if it exists
    if (window.clientChart) {
        window.clientChart.destroy();
    }
    
    // Create chart
    window.clientChart = new Chart(canvasElement, {
        type: 'bar',
        data: {
            labels: mockData.orderFrequency.map(item => item.frequency),
            datasets: [{
                label: 'Număr Clienți',
                data: mockData.orderFrequency.map(item => item.clients),
                backgroundColor: chartColors.chartColors.slice(0, mockData.orderFrequency.length),
                borderColor: chartColors.chartColors.slice(0, mockData.orderFrequency.length),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Frecvența Comenzilor'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
}

/**
 * Create Order Frequency Table
 */
function createOrderFrequencyTable() {
    // Get the table body
    const tableBody = document.getElementById('clientTable')?.querySelector('tbody');
    if (!tableBody) {
        console.error('Table body element not found');
        return;
    }
    
    // Get table header
    const tableHeader = document.getElementById('clientTable')?.querySelector('thead tr');
    if (tableHeader) {
        tableHeader.innerHTML = `
            <th>Frecvență Comenzi</th>
            <th>Număr Clienți</th>
            <th>Procent</th>
        `;
    }
    
    tableBody.innerHTML = '';
    
    // Calculate total clients
    const totalClients = mockData.orderFrequency.reduce((sum, item) => sum + item.clients, 0);
    
    // Create table rows
    mockData.orderFrequency.forEach(item => {
        const row = document.createElement('tr');
        const percentage = Math.round((item.clients / totalClients) * 100);
        
        row.innerHTML = `
            <td>${item.frequency}</td>
            <td>${item.clients}</td>
            <td>${percentage}%</td>
        `;
        tableBody.appendChild(row);
    });
    
    // Add total row
    const totalRow = document.createElement('tr');
    totalRow.className = 'table-active fw-bold';
    totalRow.innerHTML = `
        <td>Total</td>
        <td>${totalClients}</td>
        <td>100%</td>
    `;
    tableBody.appendChild(totalRow);
}

/**
 * Create Client Types Chart
 */
function createClientTypesChart() {
    // Get the canvas element
    const canvasElement = document.getElementById('clientChart');
    if (!canvasElement) {
        console.error('Canvas element with ID "clientChart" not found');
        return;
    }
    
    // Destroy existing chart if it exists
    if (window.clientChart) {
        window.clientChart.destroy();
    }
    
    // Create chart
    window.clientChart = new Chart(canvasElement, {
        type: 'doughnut',
        data: {
            labels: mockData.clientTypes.labels,
            datasets: [{
                data: mockData.clientTypes.data,
                backgroundColor: [chartColors.info, chartColors.primary],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right'
                },
                title: {
                    display: true,
                    text: 'Clienți Noi vs. Recurenți'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.parsed;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${context.label}: ${percentage}%`;
                        }
                    }
                }
            }
        }
    });
}

/**
 * Create Client Types Table
 */
function createClientTypesTable() {
    // Get the table body
    const tableBody = document.getElementById('clientTable')?.querySelector('tbody');
    if (!tableBody) {
        console.error('Table body element not found');
        return;
    }
    
    // Get table header
    const tableHeader = document.getElementById('clientTable')?.querySelector('thead tr');
    if (tableHeader) {
        tableHeader.innerHTML = `
            <th>Tip Client</th>
            <th>Număr</th>
            <th>Procent</th>
        `;
    }
    
    tableBody.innerHTML = '';
    
    // Calculate total clients
    const totalClients = mockData.clientTypes.data.reduce((a, b) => a + b, 0);
    
    // Create table rows
    for (let i = 0; i < mockData.clientTypes.labels.length; i++) {
        const row = document.createElement('tr');
        const percentage = Math.round((mockData.clientTypes.data[i] / totalClients) * 100);
        
        row.innerHTML = `
            <td>${mockData.clientTypes.labels[i]}</td>
            <td>${mockData.clientTypes.data[i]}</td>
            <td>${percentage}%</td>
        `;
        tableBody.appendChild(row);
    }
    
    // Add total row
    const totalRow = document.createElement('tr');
    totalRow.className = 'table-active fw-bold';
    totalRow.innerHTML = `
        <td>Total</td>
        <td>${totalClients}</td>
        <td>100%</td>
    `;
    tableBody.appendChild(totalRow);
}

/**
 * Create Client Regions Chart
 */
function createClientRegionsChart() {
    // Get the canvas element
    const canvasElement = document.getElementById('clientChart');
    if (!canvasElement) {
        console.error('Canvas element with ID "clientChart" not found');
        return;
    }
    
    // Destroy existing chart if it exists
    if (window.clientChart) {
        window.clientChart.destroy();
    }
    
    // Create chart
    window.clientChart = new Chart(canvasElement, {
        type: 'pie',
        data: {
            labels: mockData.clientRegions.labels,
            datasets: [{
                data: mockData.clientRegions.data,
                backgroundColor: chartColors.chartColors.slice(0, mockData.clientRegions.labels.length),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right'
                },
                title: {
                    display: true,
                    text: 'Distribuție Geografică Clienți'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.parsed;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${context.label}: ${percentage}%`;
                        }
                    }
                }
            }
        }
    });
}

/**
 * Create Client Regions Table
 */
function createClientRegionsTable() {
    // Get the table body
    const tableBody = document.getElementById('clientTable')?.querySelector('tbody');
    if (!tableBody) {
        console.error('Table body element not found');
        return;
    }
    
    // Get table header
    const tableHeader = document.getElementById('clientTable')?.querySelector('thead tr');
    if (tableHeader) {
        tableHeader.innerHTML = `
            <th>Regiune</th>
            <th>Număr Clienți</th>
            <th>Procent</th>
        `;
    }
    
    tableBody.innerHTML = '';
    
    // Calculate total clients
    const totalClients = mockData.clientRegions.data.reduce((a, b) => a + b, 0);
    
    // Create table rows
    for (let i = 0; i < mockData.clientRegions.labels.length; i++) {
        const row = document.createElement('tr');
        const percentage = Math.round((mockData.clientRegions.data[i] / totalClients) * 100);
        
        row.innerHTML = `
            <td>${mockData.clientRegions.labels[i]}</td>
            <td>${mockData.clientRegions.data[i]}</td>
            <td>${percentage}%</td>
        `;
        tableBody.appendChild(row);
    }
    
    // Add total row
    const totalRow = document.createElement('tr');
    totalRow.className = 'table-active fw-bold';
    totalRow.innerHTML = `
        <td>Total</td>
        <td>${totalClients}</td>
        <td>100%</td>
    `;
    tableBody.appendChild(totalRow);
}

// Export functions for use in other scripts
window.AdminReports = {
    generateSalesReport,
    generateProductReport,
    generateClientReport
};