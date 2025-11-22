// Lógica del Dashboard del Gestor

let currentTicketId = null;

document.addEventListener('DOMContentLoaded', function () {
    // Verificar autenticación
    if (!checkAuth()) {
        return;
    }

    // Verificar que sea gestor
    if (!checkRole('gestor')) {
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

    // Cargar tickets inicialmente
    loadMyTickets();
}

function setupEventListeners() {
    // Navegación
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', function () {
            const section = this.dataset.section;
            switchSection(section);
        });
    });

    // Logout
    document.getElementById('logoutBtn').addEventListener('click', logout);

    // Crear ticket
    document.getElementById('createTicketForm').addEventListener('submit', handleCreateTicket);

    // Modal
    document.getElementById('closeModal').addEventListener('click', closeTicketModal);
    document.getElementById('ticketModal').addEventListener('click', function (e) {
        if (e.target === this) {
            closeTicketModal();
        }
    });

    // Agregar comentario
    document.getElementById('addCommentForm').addEventListener('submit', handleAddComment);
}

function switchSection(sectionName) {
    // Actualizar navegación
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
        if (item.dataset.section === sectionName) {
            item.classList.add('active');
        }
    });

    // Actualizar secciones
    document.querySelectorAll('.content-section').forEach(section => {
        section.classList.remove('active');
    });
    document.getElementById(`section-${sectionName}`).classList.add('active');

    // Cargar datos si es necesario
    if (sectionName === 'mis-tickets') {
        loadMyTickets();
    }
}

async function handleCreateTicket(e) {
    e.preventDefault();

    const titulo = document.getElementById('titulo').value.trim();
    const descripcion = document.getElementById('descripcion').value.trim();
    const createBtn = document.getElementById('createBtn');
    const messageDiv = document.getElementById('createMessage');

    if (!titulo || !descripcion) {
        showMessage(messageDiv, 'Por favor completa todos los campos', 'error');
        return;
    }

    setButtonLoading(createBtn, true);

    try {
        const ticketData = { titulo, descripcion };
        await createTicket(ticketData);

        showMessage(messageDiv, 'Ticket creado exitosamente', 'success');

        // Limpiar formulario
        document.getElementById('createTicketForm').reset();

        // Recargar tickets
        setTimeout(() => {
            switchSection('mis-tickets');
        }, 1500);

    } catch (error) {
        console.error('Error al crear ticket:', error);
        showMessage(messageDiv, error.message || 'Error al crear el ticket', 'error');
    } finally {
        setButtonLoading(createBtn, false);
    }
}

async function loadMyTickets() {
    const loadingDiv = document.getElementById('ticketsLoading');
    const containerDiv = document.getElementById('ticketsContainer');
    const noTicketsDiv = document.getElementById('noTickets');

    loadingDiv.style.display = 'flex';
    containerDiv.innerHTML = '';
    noTicketsDiv.style.display = 'none';

    try {
        const tickets = await listMyTickets();

        loadingDiv.style.display = 'none';

        if (!tickets || tickets.length === 0) {
            noTicketsDiv.style.display = 'block';
            return;
        }

        containerDiv.innerHTML = tickets.map(ticket => createTicketCard(ticket)).join('');

        // Agregar event listeners a las cards
        document.querySelectorAll('.ticket-card').forEach(card => {
            card.addEventListener('click', function () {
                const ticketId = this.dataset.ticketId;
                openTicketModal(ticketId);
            });
        });

    } catch (error) {
        console.error('Error al cargar tickets:', error);
        loadingDiv.style.display = 'none';
        containerDiv.innerHTML = `
            <div class="alert alert-error">
                Error al cargar los tickets: ${error.message}
            </div>
        `;
    }
}

function createTicketCard(ticket) {
    const estadoClass = ticket.estado.replace('_', '-');
    const estadoBadge = getEstadoBadge(ticket.estado);
    const fecha = formatDate(ticket.created_at);

    return `
        <div class="ticket-card estado-${estadoClass}" data-ticket-id="${ticket.id}">
            <div class="ticket-header">
                <span class="ticket-id">#${ticket.id}</span>
                ${estadoBadge}
            </div>
            <h3 class="ticket-title">${escapeHtml(ticket.titulo)}</h3>
            <p class="ticket-description">${escapeHtml(ticket.descripcion)}</p>
            <div class="ticket-footer">
                <span class="ticket-date">📅 ${fecha}</span>
            </div>
        </div>
    `;
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
        // Cargar detalles del ticket
        const ticket = await getTicketDetails(ticketId);
        detailsDiv.innerHTML = createTicketDetails(ticket);

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
                    <span class="meta-label">Fecha de Creación</span>
                    <span class="meta-value">${fecha}</span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Estado</span>
                    <span class="meta-value">${formatEstado(ticket.estado)}</span>
                </div>
            </div>
        </div>
        <div class="ticket-detail-description">
            <strong>Descripción:</strong><br>
            ${escapeHtml(ticket.descripcion)}
        </div>
    `;
}

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

async function handleAddComment(e) {
    e.preventDefault();

    if (!currentTicketId) return;

    const comentario = document.getElementById('comentario').value.trim();
    const form = e.target;

    if (!comentario) {
        alert('Por favor escribe un comentario');
        return;
    }

    try {
        await addComment(currentTicketId, comentario);

        // Limpiar formulario
        form.reset();

        // Recargar historial
        const history = await getTicketHistory(currentTicketId);
        const historyDiv = document.getElementById('ticketHistory');
        historyDiv.innerHTML = history.map(item => createHistoryItem(item)).join('');

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

// Utilidades
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

function showMessage(element, text, type) {
    element.textContent = text;
    element.className = `alert alert-${type}`;
    element.style.display = 'block';

    setTimeout(() => {
        element.style.display = 'none';
    }, 5000);
}

function setButtonLoading(button, loading) {
    if (loading) {
        button.disabled = true;
        button.classList.add('btn-loading');
    } else {
        button.disabled = false;
        button.classList.remove('btn-loading');
    }
}
