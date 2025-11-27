// Capa de servicios para consumir las APIs de los microservicios

/////////Función base para hacer peticiones a las APIs
async function apiRequest(url, options = {}) {
    try {
        const token = getToken();

        /////Configurar headers por defecto
        const headers = {
            'Content-Type': 'application/json',
            ...options.headers
        };

        //////Agregar token si existe
        if (token && !options.skipAuth) {
            headers['Authorization'] = token;
        }

        const config = {
            ...options,
            headers
        };

        const response = await fetch(url, config);

        ////Si es 401 y NO es una petición de login/register, redirigir ocmo devolcer
        if (response.status === 401 && !options.skipAuth) {
            removeToken();
            window.location.href = '../index.html';
            throw new Error('Sesión expirada');
        }

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || 'Error en la petición');
        }

        return data;
    } catch (error) {
        console.error('Error en apiRequest:', error);
        throw error;
    }
}

////////////////FUNCIONES PARA USUARIOS//////////////


///////////////////Iniciar sesión
async function login(email, password) {
    const url = `${API_CONFIG.USERS_API}/login`;
    return await apiRequest(url, {
        method: 'POST',
        body: JSON.stringify({ email, password }),
        skipAuth: true
    });
}

///////////////////Registrar nuevo usuario
async function register(userData) {
    const url = `${API_CONFIG.USERS_API}/register`;
    return await apiRequest(url, {
        method: 'POST',
        body: JSON.stringify(userData),
        skipAuth: true
    });
}

/////////////////////Cerrar sesión
async function logoutAPI() {
    const url = `${API_CONFIG.USERS_API}/logout`;
    return await apiRequest(url, {
        method: 'POST'
    });
}

/////////////////Listar usuarios (admin)
async function listUsers() {
    const url = `${API_CONFIG.USERS_API}/users`;
    return await apiRequest(url, {
        method: 'GET'
    });
}

/////////////////Actualizar usuario (admin)
async function updateUser(id, userData) {
    const url = `${API_CONFIG.USERS_API}/users/${id}`;
    return await apiRequest(url, {
        method: 'PUT',
        body: JSON.stringify(userData)
    });
}

///////////////Cambiar rol (admin)
async function changeUserRole(id, role) {
    const url = `${API_CONFIG.USERS_API}/users/${id}/role`;
    return await apiRequest(url, {
        method: 'PATCH',
        body: JSON.stringify({ role })
    });
}

///////////////Eliminar usuario ( admin)
async function deleteUser(id) {
    const url = `${API_CONFIG.USERS_API}/users/${id}`;
    return await apiRequest(url, {
        method: 'DELETE'
    });
}


///////////////FUNCIONES PARA TICKETS//////////////////////////

////////////Crear ticket (gestores)
async function createTicket(ticketData) {
    const url = `${API_CONFIG.TICKETS_API}/tickets`;
    return await apiRequest(url, {
        method: 'POST',
        body: JSON.stringify(ticketData)
    });
}

////////////Listar mis tickets (gestores)
async function listMyTickets() {
    const url = `${API_CONFIG.TICKETS_API}/tickets/my`;
    return await apiRequest(url, {
        method: 'GET'
    });
}

////////////Listar todos los tickets (admin)
async function listAllTickets() {
    const url = `${API_CONFIG.TICKETS_API}/tickets`;
    return await apiRequest(url, {
        method: 'GET'
    });
}

////////////Obtener detalles de un ticket

async function getTicketDetails(id) {
    const url = `${API_CONFIG.TICKETS_API}/tickets/${id}`;
    return await apiRequest(url, {
        method: 'GET'
    });
}

/////////Actualizar estado de ticket (admins)
async function updateTicketStatus(id, estado) {
    const url = `${API_CONFIG.TICKETS_API}/tickets/${id}/status`;
    return await apiRequest(url, {
        method: 'PUT',
        body: JSON.stringify({ estado })
    });
}

///////////////Asignar ticket a admin (admins)
async function assignTicket(id, adminId) {
    const url = `${API_CONFIG.TICKETS_API}/tickets/${id}/assign`;
    return await apiRequest(url, {
        method: 'PUT',
        body: JSON.stringify({ admin_id: adminId })
    });
}

////////////Agregar comentario a ticket
 
async function addComment(id, mensaje) {
    const url = `${API_CONFIG.TICKETS_API}/tickets/${id}/comments`;
    return await apiRequest(url, {
        method: 'POST',
        body: JSON.stringify({ mensaje })
    });
}

////////////Obtener historial de actividad de un ticket
async function getTicketHistory(id) {
    const url = `${API_CONFIG.TICKETS_API}/tickets/${id}/history`;
    return await apiRequest(url, {
        method: 'GET'
    });
}

///////////Buscar/filtrar tickets (admin)
async function searchTickets(filters) {
    const params = new URLSearchParams(filters);
    const url = `${API_CONFIG.TICKETS_API}/tickets/search?${params}`;
    return await apiRequest(url, {
        method: 'GET'
    });
}