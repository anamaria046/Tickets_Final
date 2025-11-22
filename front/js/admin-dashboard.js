// Lógica del Dashboard del Administrador

let currentTicketId = null;
let allUsers = [];
let adminUsers = [];

document.addEventListener('DOMContentLoaded', function () {
    // Verificar autenticación
    if (!checkAuth()) {
        return;
    }

    // Verificar que sea admin
    if (!checkRole('admin')) {
        return;
    }

    // Inicializar
    init();
});

function init() {
    // Mostrar nombre del usuario
    const userName = getUserName();
    if (userName) {
        document.getElementById('userName').textContent = userName;
    }

    // Event listeners
    setupEventListeners();

    // Cargar datos iniciales
    loadAllTickets();
    loadUsers();
}

function setupEventListeners() {
    // Tabs
    const tabBtns = document.querySelectorAll('.tab-btn');
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const tab = this.dataset.tab;
            switchTab(tab);
        });
    });

    // Logout
    document.getElementById('logoutBtn').addEventListener('click', logout);

    // Filtros
    document.getElementById('filterForm').addEventListener('submit', handleFilter);
    document.getElementById('clearFilters').addEventListener('click', clearFilters);

    // Modales
    document.getElementById('closeTicketModal').addEventListener('click', closeTicketModal);
    document.getElementById('closeUserModal').addEventListener('click', closeUserModal);

    document.getElementById('ticketModal').addEventListener('click', function (e) {
        if (e.target === this) closeTicketModal();
    });

    document.getElementById('userModal').addEventListener('click', function (e) {
        if (e.target === this) closeUserModal();
    });

    // Acciones de ticket
    document.getElementById('updateStatusBtn').addEventListener('click', handleUpdateStatus);
    document.getElementById('assignTicketBtn').addEventListener('click', handleAssignTicket);
    document.getElementById('addCommentForm').addEventListener('submit', handleAddComment);

    // Editar usuario
    document.getElementById('editUserForm').addEventListener('submit', handleEditUser);
    document.getElementById('cancelEditUser').addEventListener('click', closeUserModal);
}

function switchTab(tabName) {
    // Actualizar botones
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.tab === tabName) {
            btn.classList.add('active');
        }
    });

    // Actualizar contenido
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    document.getElementById(`tab-${tabName}`).classList.add('active');

    // Cargar datos si es necesario
    if (tabName === 'tickets') {
        loadAllTickets();
    } else if (tabName === 'usuarios') {
        loadUsers();
    }
}


/////////////////GESTIÓN DE TICKETS


async function loadAllTickets() {
    const loadingDiv = document.getElementById('ticketsLoading');
    const containerDiv = document.getElementById('ticketsTableContainer');
    const noTicketsDiv = document.getElementById('noTickets');
    const tbody = document.getElementById('ticketsTableBody');

    loadingDiv.style.display = 'flex';
    containerDiv.style.display = 'none';
    noTicketsDiv.style.display = 'none';

    try {
        const tickets = await listAllTickets();

        // Cargar usuarios para los filtros
        await loadUsersForFilters();

        loadingDiv.style.display = 'none';

        if (!tickets || tickets.length === 0) {
            noTicketsDiv.style.display = 'block';
            return;
        }

        containerDiv.style.display = 'block';
        tbody.innerHTML = tickets.map(ticket => createTicketRow(ticket)).join('');

        // Agregar event listeners
        attachTicketEventListeners();

    } catch (error) {
        console.error('Error al cargar tickets:', error);
        loadingDiv.style.display = 'none';
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center">
                    <div class="alert alert-error">
                        Error al cargar los tickets: ${error.message}
                    </div>
                </td>
            </tr>
        `;
    }
}

function createTicketRow(ticket) {
    const estadoBadge = `<span class="badge-estado ${ticket.estado}">${formatEstado(ticket.estado)}</span>`;
    const fecha = formatDate(ticket.created_at);
    const creador = ticket.gestor_id || 'N/A';
    const asignado = ticket.admin_id || 'Sin asignar';

    return `
        <tr>
            <td>#${ticket.id}</td>
            <td>${escapeHtml(ticket.titulo)}</td>
            <td>${estadoBadge}</td>
            <td>ID: ${creador}</td>
            <td>${asignado !== 'Sin asignar' ? 'ID: ' + asignado : asignado}</td>
            <td>${fecha}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-primary btn-icon view-ticket" data-ticket-id="${ticket.id}">
                        Ver
                    </button>
                </div>
            </td>
        </tr>
    `;
}

function attachTicketEventListeners() {
    document.querySelectorAll('.view-ticket').forEach(btn => {
        btn.addEventListener('click', function () {
            const ticketId = this.dataset.ticketId;
            openTicketModal(ticketId);
        });
    });
}

async function handleFilter(e) {
    e.preventDefault();

    const estado = document.getElementById('filterEstado').value;
    const gestorId = document.getElementById('filterGestor').value;
    const adminId = document.getElementById('filterAdmin').value;

    const filters = {};
    if (estado) filters.estado = estado;
    if (gestorId) filters.gestor_id = gestorId;
    if (adminId) filters.admin_id = adminId;

    const loadingDiv = document.getElementById('ticketsLoading');
    const containerDiv = document.getElementById('ticketsTableContainer');
    const noTicketsDiv = document.getElementById('noTickets');
    const tbody = document.getElementById('ticketsTableBody');

    loadingDiv.style.display = 'flex';
    containerDiv.style.display = 'none';
    noTicketsDiv.style.display = 'none';

    try {
        const tickets = Object.keys(filters).length > 0
            ? await searchTickets(filters)
            : await listAllTickets();

        loadingDiv.style.display = 'none';

        if (!tickets || tickets.length === 0) {
            noTicketsDiv.style.display = 'block';
            return;
        }

        containerDiv.style.display = 'block';
        tbody.innerHTML = tickets.map(ticket => createTicketRow(ticket)).join('');
        attachTicketEventListeners();

    } catch (error) {
        console.error('Error al filtrar tickets:', error);
        loadingDiv.style.display = 'none';
        alert('Error al filtrar tickets: ' + error.message);
    }
}

function clearFilters() {
    document.getElementById('filterEstado').value = '';
    document.getElementById('filterGestor').value = '';
    document.getElementById('filterAdmin').value = '';
    loadAllTickets();
}

async function loadUsersForFilters() {
    try {
        const users = await listUsers();
        allUsers = users;
        adminUsers = users.filter(u => u.role === 'admin');

        //////filtro de gestores
        const filterGestor = document.getElementById('filterGestor');
        const gestores = users.filter(u => u.role === 'gestor');
        filterGestor.innerHTML = '<option value="">Todos</option>' +
            gestores.map(u => `<option value="${u.id}">${escapeHtml(u.name)}</option>`).join('');

        //////filtro de admins
        const filterAdmin = document.getElementById('filterAdmin');
        filterAdmin.innerHTML = '<option value="">Todos</option>' +
            adminUsers.map(u => `<option value="${u.id}">${escapeHtml(u.name)}</option>`).join('');

        ///////asignación
        const assignAdmin = document.getElementById('assignAdmin');
        assignAdmin.innerHTML = '<option value="">Sin asignar</option>' +
            adminUsers.map(u => `<option value="${u.id}">${escapeHtml(u.name)}</option>`).join('');

    } catch (error) {
        console.error('Error al cargar usuarios:', error);
    }
}

async function openTicketModal(ticketId) {
    currentTicketId = ticketId;
    const modal = document.getElementById('ticketModal');
    const detailsDiv = document.getElementById('ticketDetails');
    const historyDiv = document.getElementById('ticketHistory');

    modal.classList.add('active');
    detailsDiv.innerHTML = '<div class="loading-spinner"></div>';
    historyDiv.innerHTML = '';

    try {
        const ticket = await getTicketDetails(ticketId);
        detailsDiv.innerHTML = createTicketDetails(ticket);

        ///////////Establecer valores en los controles
        document.getElementById('ticketEstado').value = ticket.estado;
        document.getElementById('assignAdmin').value = ticket.admin_id || '';

        // Cargar historial
        const history = await getTicketHistory(ticketId);
        historyDiv.innerHTML = history.map(item => createHistoryItem(item)).join('');

    } catch (error) {
        console.error('Error al cargar detalles:', error);
        detailsDiv.innerHTML = `
            <div class="alert alert-error">
                Error al cargar los detalles: ${error.message}
            </div>
        `;
    }
}

function createTicketDetails(ticket) {
    const estadoBadge = getEstadoBadge(ticket.estado);
    const fecha = formatDate(ticket.created_at);

    return `
        <div class="ticket-detail-header">
            <div style="display: flex; justify-content: space-between; align-items: start;">
                <h3 class="ticket-detail-title">${escapeHtml(ticket.titulo)}</h3>
                ${estadoBadge}
            </div>
            <div class="ticket-meta">
                <div class="meta-item">
                    <span class="meta-label">ID</span>
                    <span class="meta-value">#${ticket.id}</span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Creador</span>
                    <span class="meta-value">ID: ${ticket.gestor_id}</span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Asignado a</span>
                    <span class="meta-value">${ticket.admin_id ? 'ID: ' + ticket.admin_id : 'Sin asignar'}</span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Fecha</span>
                    <span class="meta-value">${fecha}</span>
                </div>
            </div>
        </div>
        <div class="ticket-detail-description">
            <strong>Descripción:</strong><br>
            ${escapeHtml(ticket.descripcion)}
        </div>
    `;
}

async function handleUpdateStatus() {
    if (!currentTicketId) return;

    const nuevoEstado = document.getElementById('ticketEstado').value;

    try {
        await updateTicketStatus(currentTicketId, nuevoEstado);
        alert('Estado actualizado correctamente');

        ///////Recargar detalles
        const ticket = await getTicketDetails(currentTicketId);
        document.getElementById('ticketDetails').innerHTML = createTicketDetails(ticket);

        ///////Recargar historial
        const history = await getTicketHistory(currentTicketId);
        document.getElementById('ticketHistory').innerHTML = history.map(item => createHistoryItem(item)).join('');

        //////Recargar tabla
        loadAllTickets();

    } catch (error) {
        console.error('Error al actualizar estado:', error);
        alert('Error al actualizar estado: ' + error.message);
    }
}

async function handleAssignTicket() {
    if (!currentTicketId) return;

    const adminId = document.getElementById('assignAdmin').value;

    if (!adminId) {
        alert('Por favor selecciona un administrador');
        return;
    }

    try {
        await assignTicket(currentTicketId, adminId);
        alert('Ticket asignado correctamente');

        ////////Recargar detalles
        const ticket = await getTicketDetails(currentTicketId);
        document.getElementById('ticketDetails').innerHTML = createTicketDetails(ticket);

        /////////Recargar historial
        const history = await getTicketHistory(currentTicketId);
        document.getElementById('ticketHistory').innerHTML = history.map(item => createHistoryItem(item)).join('');

        //////Recargar tabla
        loadAllTickets();

    } catch (error) {
        console.error('Error al asignar ticket:', error);
        alert('Error al asignar ticket: ' + error.message);
    }
}

async function handleAddComment(e) {
    e.preventDefault();

    if (!currentTicketId) return;

    const comentario = document.getElementById('comentario').value.trim();

    if (!comentario) {
        alert('Por favor escribe un comentario');
        return;
    }

    try {
        await addComment(currentTicketId, comentario);

        // Limpiar formulario
        e.target.reset();

        // Recargar historial
        const history = await getTicketHistory(currentTicketId);
        document.getElementById('ticketHistory').innerHTML = history.map(item => createHistoryItem(item)).join('');

    } catch (error) {
        console.error('Error al agregar comentario:', error);
        alert('Error al agregar comentario: ' + error.message);
    }
}

function closeTicketModal() {
    document.getElementById('ticketModal').classList.remove('active');
    currentTicketId = null;
    document.getElementById('addCommentForm').reset();
}


////////////////////GESTIÓN DE USUARIOS/////////////////


async function loadUsers() {
    const loadingDiv = document.getElementById('usersLoading');
    const containerDiv = document.getElementById('usersTableContainer');
    const tbody = document.getElementById('usersTableBody');

    loadingDiv.style.display = 'flex';
    containerDiv.style.display = 'none';

    try {
        const users = await listUsers();
        allUsers = users;

        loadingDiv.style.display = 'none';
        containerDiv.style.display = 'block';

        tbody.innerHTML = users.map(user => createUserRow(user)).join('');

        // Agregar event listeners
        attachUserEventListeners();

    } catch (error) {
        console.error('Error al cargar usuarios:', error);
        loadingDiv.style.display = 'none';
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center">
                    <div class="alert alert-error">
                        Error al cargar los usuarios: ${error.message}
                    </div>
                </td>
            </tr>
        `;
    }
}

function createUserRow(user) {
    const roleBadge = user.role === 'admin'
        ? '<span class="badge badge-danger">Admin</span>'
        : '<span class="badge badge-primary">Gestor</span>';
    const fecha = formatDate(user.created_at);

    return `
        <tr>
            <td>${user.id}</td>
            <td>${escapeHtml(user.name)}</td>
            <td>${escapeHtml(user.email)}</td>
            <td>${roleBadge}</td>
            <td>${fecha}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-primary btn-icon edit-user" data-user-id="${user.id}">
                        Editar
                    </button>
                    <button class="btn btn-danger btn-icon delete-user" data-user-id="${user.id}">
                        Eliminar
                    </button>
                </div>
            </td>
        </tr>
    `;
}

function attachUserEventListeners() {
    document.querySelectorAll('.edit-user').forEach(btn => {
        btn.addEventListener('click', function () {
            const userId = this.dataset.userId;
            openEditUserModal(userId);
        });
    });

    document.querySelectorAll('.delete-user').forEach(btn => {
        btn.addEventListener('click', function () {
            const userId = this.dataset.userId;
            handleDeleteUser(userId);
        });
    });
}

function openEditUserModal(userId) {
    const user = allUsers.find(u => u.id == userId);
    if (!user) return;

    document.getElementById('editUserId').value = user.id;
    document.getElementById('editUserName').value = user.name;
    document.getElementById('editUserEmail').value = user.email;
    document.getElementById('editUserRole').value = user.role;

    document.getElementById('userModal').classList.add('active');
}

async function handleEditUser(e) {
    e.preventDefault();

    const userId = document.getElementById('editUserId').value;
    const name = document.getElementById('editUserName').value.trim();
    const email = document.getElementById('editUserEmail').value.trim();
    const role = document.getElementById('editUserRole').value;

    if (!name || !email || !role) {
        alert('Por favor completa todos los campos');
        return;
    }

    try {
        // Actualizar datos básicos
        await updateUser(userId, { name, email });

        // Cambiar rol si es diferente
        const currentUser = allUsers.find(u => u.id == userId);
        if (currentUser && currentUser.role !== role) {
            await changeUserRole(userId, role);
        }

        alert('Usuario actualizado correctamente');
        closeUserModal();
        loadUsers();

    } catch (error) {
        console.error('Error al actualizar usuario:', error);
        alert('Error al actualizar usuario: ' + error.message);
    }
}

async function handleDeleteUser(userId) {
    const user = allUsers.find(u => u.id == userId);
    if (!user) return;

    if (!confirm(`¿Estás seguro de eliminar al usuario "${user.name}"?`)) {
        return;
    }

    try {
        await deleteUser(userId);
        alert('Usuario eliminado correctamente');
        loadUsers();

    } catch (error) {
        console.error('Error al eliminar usuario:', error);
        alert('Error al eliminar usuario: ' + error.message);
    }
}

function closeUserModal() {
    document.getElementById('userModal').classList.remove('active');
    document.getElementById('editUserForm').reset();
}

////////////////UTILIDADES

function createHistoryItem(item) {
    const fecha = formatDate(item.created_at);

    return `
        <div class="history-item">
            <div class="history-header">
                <span class="history-user">👤 Usuario #${item.user_id}</span>
                <span class="history-date">${fecha}</span>
            </div>
            <div class="history-message">${escapeHtml(item.mensaje)}</div>
        </div>
    `;
}

function getEstadoBadge(estado) {
    const badges = {
        'abierto': '<span class="badge badge-primary">Abierto</span>',
        'en_progreso': '<span class="badge badge-warning">En Progreso</span>',
        'resuelto': '<span class="badge badge-success">Resuelto</span>',
        'cerrado': '<span class="badge badge-secondary">Cerrado</span>'
    };
    return badges[estado] || '<span class="badge">Desconocido</span>';
}

function formatEstado(estado) {
    const estados = {
        'abierto': 'Abierto',
        'en_progreso': 'En Progreso',
        'resuelto': 'Resuelto',
        'cerrado': 'Cerrado'
    };
    return estados[estado] || estado;
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
