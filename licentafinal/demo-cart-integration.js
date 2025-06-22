
// Realistic cart integration for demo checkout
// This makes the demo look like it's connected to the real database

class DemoCartIntegration {
    constructor() {
        this.apiUrl = 'api/catalog.php'; // Your real API
        this.fallbackProducts = [
            {id: 1, nume: "Dulceață de Căpșuni", pret: 18.99, cantitate: 2, imagine: "img/dulceata.png", categorie: "dulceturi"},
            {id: 3, nume: "Zacuscă de Buzău", pret: 15.50, cantitate: 1, imagine: "img/zacusca.png", categorie: "conserve"},
            {id: 7, nume: "Miere de Salcâm", pret: 28.50, cantitate: 1, imagine: "img/placeholder.png", categorie: "dulceturi"},
            {id: 12, nume: "Brânză de Burduf", pret: 35.99, cantitate: 1, imagine: "img/placeholder.png", categorie: "branza"},
            {id: 8, nume: "Țuică de Prună", pret: 45.00, cantitate: 1, imagine: "img/placeholder.png", categorie: "bauturi"}
        ];
    }

    // Try to load real cart, fallback to demo data
    async loadRealisticCart() {
        try {
            // First try to get from localStorage (real cart)
            const localCart = localStorage.getItem('cart');
            if (localCart && localCart !== '[]') {
                const cart = JSON.parse(localCart);
                return await this.enhanceCartWithProductData(cart);
            }

            // Try to load from session/API
            const response = await fetch('api/cart.php?action=get_cart');
            if (response.ok) {
                const result = await response.json();
                if (result.success && result.items && result.items.length > 0) {
                    return result.items;
                }
            }
        } catch (error) {
            console.log('Using demo cart data due to:', error.message);
        }

        // Use realistic demo data
        return this.generateRealisticDemoCart();
    }

    // Enhance cart items with full product data
    async enhanceCartWithProductData(cartItems) {
        const enhancedItems = [];
        
        for (const item of cartItems) {
            try {
                // Try to get real product data
                const response = await fetch(`${this.apiUrl}?type=product&id=${item.id}`);
                if (response.ok) {
                    const result = await response.json();
                    if (result.success && result.product) {
                        enhancedItems.push({
                            ...result.product,
                            cantitate: item.cantitate || 1
                        });
                        continue;
                    }
                }
            } catch (error) {
                console.log('Could not load product', item.id);
            }

            // Fallback to item data or demo data
            enhancedItems.push({
                id: item.id,
                nume: item.nume || `Produs ${item.id}`,
                pret: item.pret || 25.99,
                cantitate: item.cantitate || 1,
                imagine: item.imagine || 'img/placeholder.png'
            });
        }

        return enhancedItems;
    }

    // Generate realistic demo cart with variety
    generateRealisticDemoCart() {
        const numItems = Math.floor(Math.random() * 3) + 2; // 2-4 items
        const selectedProducts = this.shuffleArray([...this.fallbackProducts]).slice(0, numItems);
        
        return selectedProducts.map(product => ({
            ...product,
            cantitate: Math.floor(Math.random() * 2) + 1 // 1-2 quantity
        }));
    }

    // Shuffle array utility
    shuffleArray(array) {
        const shuffled = [...array];
        for (let i = shuffled.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
        }
        return shuffled;
    }

    // Generate realistic user data
    generateUserData() {
        const firstNames = ['Ion', 'Maria', 'Alexandru', 'Elena', 'Andrei', 'Ana', 'Mihai', 'Ioana'];
        const lastNames = ['Popescu', 'Ionescu', 'Gheorghe', 'Cristescu', 'Marinescu', 'Popa', 'Stan', 'Radu'];
        const streets = ['Strada Florilor', 'Bd. Unirii', 'Str. Mihai Viteazu', 'Aleea Rozelor', 'Calea Victoriei', 'Str. Stefan cel Mare'];
        const cities = ['București', 'Cluj-Napoca', 'Timișoara', 'Iași', 'Constanța', 'Craiova', 'Brașov', 'Galați'];

        const firstName = firstNames[Math.floor(Math.random() * firstNames.length)];
        const lastName = lastNames[Math.floor(Math.random() * lastNames.length)];
        const street = streets[Math.floor(Math.random() * streets.length)];
        const city = cities[Math.floor(Math.random() * cities.length)];

        return {
            name: `${firstName} ${lastName}`,
            address: `${street}, nr. ${Math.floor(Math.random() * 200) + 1}`,
            city: city,
            sector: Math.floor(Math.random() * 6) + 1,
            postalCode: `0${Math.floor(Math.random() * 90000) + 10000}`,
            phone: `+40 7${Math.floor(Math.random() * 90000000) + 10000000}`
        };
    }

    // Simulate database connection delays
    async simulateApiDelay(min = 300, max = 1500) {
        const delay = Math.floor(Math.random() * (max - min + 1)) + min;
        await new Promise(resolve => setTimeout(resolve, delay));
    }
}

// Export for use in demo-checkout.html
window.DemoCartIntegration = DemoCartIntegration;
