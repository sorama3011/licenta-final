document.addEventListener('DOMContentLoaded', async function () {
    await checkAuthStatus();
    await updateNavigation();
});

// ✅ Verifică statusul autentificării
async function checkAuthStatus() {
    const userData = await getUserData();

    if (window.location.pathname.includes('login.html') && userData.loggedIn) {
        redirectBasedOnRole(userData.role);
    }

    if (isProtectedPage() && !userData.loggedIn) {
        localStorage.setItem('redirectAfterLogin', window.location.pathname);
        window.location.href = 'login.html';
    }

    if (userData.loggedIn && !hasAccessToPage(userData.role)) {
        redirectBasedOnRole(userData.role);
    }
}

// ✅ Verifică dacă pagina cere autentificare
function isProtectedPage() {
    const protectedPages = [
        'client-dashboard.html',
        'admin-dashboard.html',
        'employee-dashboard.html',
        'account.html',
        'cart.html',
        'checkout.php'
    ];
    return protectedPages.some(page => window.location.pathname.includes(page));
}

// ✅ Verifică dacă userul are voie pe pagină
function hasAccessToPage(role) {
    const page = window.location.pathname;
    if (page.includes('admin-dashboard.html') && role !== 'administrator') return false;
    if (page.includes('employee-dashboard.html') && role !== 'angajat') return false;
    if (page.includes('client-dashboard.html') && role !== 'client') return false;
    return true;
}

// ✅ Preia userul din sesiune (via PHP)
async function getUserData() {
    try {
        const response = await fetch('session_check.php', { credentials: 'include' });
        const user = await response.json();

        if (user.loggedIn) {
            localStorage.setItem("userData", JSON.stringify(user));
        }

        return user;
    } catch (err) {
        return { loggedIn: false };
    }
}

// ✅ Redirecționează userul în funcție de rol
function redirectBasedOnRole(role) {
    const redirectUrl = localStorage.getItem('redirectAfterLogin');
    if (redirectUrl) {
        localStorage.removeItem('redirectAfterLogin');
        window.location.href = redirectUrl;
        return;
    }

    switch (role) {
        case 'administrator':
            window.location.href = 'admin-dashboard.html';
            break;
        case 'angajat':
            window.location.href = 'employee-dashboard.html';
            break;
        case 'client':
        default:
            window.location.href = 'client-dashboard.html';
            break;
    }
}

// ✅ Update meniu cont în navbar
async function updateNavigation() {
    const userData = await getUserData();
    const accountLinks = document.querySelectorAll('a[href="login.html"]');

    accountLinks.forEach(link => {
        const parentLi = link.closest('li');
        if (parentLi) {
            if (userData.loggedIn) {
                let dashboardLink = 'client-dashboard.html';
                let dashboardText = 'Contul Meu';

                if (userData.role === 'administrator') {
                    dashboardLink = 'admin-dashboard.html';
                    dashboardText = 'Panou Admin';
                } else if (userData.role === 'angajat') {
                    dashboardLink = 'employee-dashboard.html';
                    dashboardText = 'Panou Angajat';
                }

                parentLi.innerHTML = `
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-check"></i> ${userData.name}
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="${dashboardLink}">
                                <i class="bi bi-speedometer2 me-2"></i>${dashboardText}
                            </a></li>
                            ${userData.role === 'client' ? `
                            <li><a class="dropdown-item" href="client-dashboard.html#order-history">
                                <i class="bi bi-clock-history me-2"></i>Istoric Comenzi
                            </a></li>
                            <li><a class="dropdown-item" href="client-dashboard.html#wishlist">
                                <i class="bi bi-heart me-2"></i>Lista de Dorințe
                            </a></li>
                            ` : ''}
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" onclick="logout()">
                                <i class="bi bi-box-arrow-right me-2"></i>Deconectare
                            </a></li>
                        </ul>
                    </div>
                `;
            } else {
                parentLi.innerHTML = `
                    <a class="nav-link" href="login.html">
                        <i class="bi bi-person"></i> Cont
                    </a>
                `;
            }
        }
    });
}

// ✅ Logout – șterge sesiune + localStorage
function logout() {
    fetch('logout.php', { method: 'POST', credentials: 'include' }).then(() => {
        localStorage.removeItem('userData');
        localStorage.removeItem('redirectAfterLogin');
        showNotification('Te-ai deconectat cu succes!', 'info');
        setTimeout(() => {
            window.location.href = 'index.html';
        }, 1500);
    });
}

// ✅ Funcție pentru acțiuni care cer autentificare
async function requireLogin(redirectUrl = null) {
    const userData = await getUserData();
    if (!userData.loggedIn) {
        if (redirectUrl) {
            window.location.href = `login.php?redirect=${encodeURIComponent(redirectUrl)}`;
        } else {
            window.location.href = 'login.php';
        }
        return false;
    }
    return true;
}

// ✅ Verificare rapidă dacă userul e logat
async function isUserLoggedIn() {
    const userData = await getUserData();
    return userData.loggedIn === true;
}
