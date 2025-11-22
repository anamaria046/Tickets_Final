// Módulo de autenticación y gestión de tokens

///////Guardar token en localStorage
function saveToken(token) {
    localStorage.setItem('auth_token', token);
}

/**
 * Obtener token almacenado
 */
function getToken() {
    return localStorage.getItem('auth_token');
}

/**
 * Eliminar token (logout)
 */
function removeToken() {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user_role');
    localStorage.removeItem('user_name');
    localStorage.removeItem('user_id');
}

/**
 * Guardar información del usuario
 */
function saveUserInfo(user) {
    if (user.role) localStorage.setItem('user_role', user.role);
    if (user.name) localStorage.setItem('user_name', user.name);
    if (user.id) localStorage.setItem('user_id', user.id);
}

/**
 * Obtener rol del usuario
 */
function getUserRole() {
    return localStorage.getItem('user_role');
}

/**
 * Obtener nombre del usuario
 */
function getUserName() {
    return localStorage.getItem('user_name');
}

/**
 * Obtener ID del usuario
 */
function getUserId() {
    return localStorage.getItem('user_id');
}

/**
 * Verificar si hay sesión activa
 */
function isAuthenticated() {
    return getToken() !== null;
}

/**
 * Verificar autenticación y redirigir si no está autenticado
 */
function checkAuth() {
    if (!isAuthenticated()) {
        window.location.href = '../index.html';
        return false;
    }
    return true;
}

/**
 * Verificar rol y redirigir si no coincide
 */
function checkRole(requiredRole) {
    const userRole = getUserRole();
    if (userRole !== requiredRole) {
        alert('No tienes permisos para acceder a esta página');
        if (userRole === 'gestor') {
            window.location.href = 'gestor-dashboard.html';
        } else if (userRole === 'admin') {
            window.location.href = 'admin-dashboard.html';
        } else {
            window.location.href = '../index.html';
        }
        return false;
    }
    return true;
}

/**
 * Cerrar sesión
 */
async function logout() {
    try {
        // Llamar al endpoint de logout
        const response = await fetch(`${API_CONFIG.USERS_API}/logout`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': getToken()
            }
        });

        // Eliminar token localmente independientemente de la respuesta
        removeToken();
        window.location.href = '../index.html';
    } catch (error) {
        console.error('Error al cerrar sesión:', error);
        // Eliminar token localmente aunque falle la petición
        removeToken();
        window.location.href = '../index.html';
    }
}
