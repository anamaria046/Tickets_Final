
let currentTicketId = null;

// Inicialización
document.addEventListener('DOMContentLoaded', () => {
    if (!checkAuth() || !checkRole('gestor')) return;
    init();
});

function init() {
    const userName = getUserName();
    if (userName) document.getElementById('userName').textContent = userName;
    setupEventListeners();
    loadMyTickets();
}

function setupEventListeners() {
    // Navegación
    document.querySelectorAll('.nav-item').forEach(item => 
        item.addEventListener('click', () => switchSection(item.dataset.section))
    );

    // Acciones
    document.getElementById('logoutBtn').addEventListener('click', logout);
    document.getElementById('createTicketForm').addEventListener('submit', handleCreateTicket);
    document.getElementById('addCommentForm').addEventListener('submit', handleAddComment);

    // Modal
    const modal = document.getElementById('ticketModal');
    document.getElementById('closeModal').addEventListener('click', closeTicketModal);
    modal.addEventListener('click', e => e.target === modal && closeTicketModal());
}

function switchSection(sectionName) {
    // Actualizar navegación
    document.querySelectorAll('.nav-item').forEach(item => 
        item.classList.toggle('active', item.dataset.section === sectionName)
    );

    // Actualizar secciones
    document.querySelectorAll('.content-section').forEach(section => 
        section.classList.remove('active')
    );
    document.getElementById(`section-${sectionName}`).classList.add('active');

    // Cargar datos si es necesario
    if (sectionName === 'mis-tickets') loadMyTickets();
}

async function handleCreateTicket(e) {
    e.preventDefault();

    const titulo = document.getElementById('titulo').value.trim();
    const descripcion = document.getElementById('descripcion').value.trim();
    const createBtn = document.getElementById('createBtn');
    const messageDiv = document.getElementById('createMessage');

    if (!titulo || !descripcion) {
        return showMessage(messageDiv, 'Por favor completa todos los campos', 'error');
    }

    setButtonLoading(createBtn, true);

    try {
        await createTicket({ titulo, descripcion });
        showMessage(messageDiv, 'Ticket creado exitosamente', 'success');
        document.getElementById('createTicketForm').reset();
        setTimeout(() => switchSection('mis-tickets'), 1500);
    } catch (error) {
        console.error('Error al crear ticket:', error);
        showMessage(messageDiv, error.message || 'Error al crear el ticket', 'error');
    } finally {
        setButtonLoading(createBtn, false);
    }
}

async function loadMyTickets() {
    const { ticketsLoading, ticketsContainer, noTickets } = getElements([
        'ticketsLoading', 'ticketsContainer', 'noTickets'
    ]);

    ticketsLoading.style.display = 'flex';
    ticketsContainer.innerHTML = '';
    noTickets.style.display = 'none';

    try {
        const tickets = await listMyTickets();
        ticketsLoading.style.display = 'none';

        if (!tickets?.length) {
            noTickets.style.display = 'block';
            return;
        }

        ticketsContainer.innerHTML = tickets.map(createTicketCard).join('');
        attachTicketCardListeners();
    } catch (error) {
        console.error('Error al cargar tickets:', error);
        ticketsLoading.style.display = 'none';
        ticketsContainer.innerHTML = `
            <div class="alert alert-error">
                Error al cargar los tickets: ${error.message}
            </div>
        `;
    }
}

function createTicketCard(ticket) {
    const estadoClass = ticket.estado.replace('_', '-');
    return `
        <div class="ticket-card estado-${estadoClass}" data-ticket-id="${ticket.id}">
            <div class="ticket-header">
                <span class="ticket-id">#${ticket.id}</span>
                ${getEstadoBadge(ticket.estado)}
            </div>
            <h3 class="ticket-title">${escapeHtml(ticket.titulo)}</h3>
            <p class="ticket-description">${escapeHtml(ticket.descripcion)}</p>
            <div class="ticket-footer">
                <span class="ticket-date">📅 ${formatDate(ticket.created_at)}</span>
            </div>
        </div>
    `;
}

function attachTicketCardListeners() {
    document.querySelectorAll('.ticket-card').forEach(card => 
        card.addEventListener('click', () => openTicketModal(card.dataset.ticketId))
    );
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
        const [ticket, history] = await Promise.all([
            getTicketDetails(ticketId),
            getTicketHistory(ticketId)
        ]);
        
        detailsDiv.innerHTML = createTicketDetails(ticket);
        historyDiv.innerHTML = history.map(createHistoryItem).join('');
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
    return `
        <div class="ticket-detail-header">
            <div style="display: flex; justify-content: space-between; align-items: start;">
                <h3 class="ticket-detail-title">${escapeHtml(ticket.titulo)}</h3>
                ${getEstadoBadge(ticket.estado)}
            </div>
            <div class="ticket-meta">
                ${createMetaItem('ID', `#${ticket.id}`)}
                ${createMetaItem('Fecha de Creación', formatDate(ticket.created_at))}
                ${createMetaItem('Estado', formatEstado(ticket.estado))}
            </div>
        </div>
        <div class="ticket-detail-description">
            <strong>Descripción:</strong><br>
            ${escapeHtml(ticket.descripcion)}
        </div>
    `;
}

function createHistoryItem(item) {
    return `
        <div class="history-item">
            <div class="history-header">
                <span class="history-user">👤 Usuario #${item.user_id}</span>
                <span class="history-date">${formatDate(item.created_at)}</span>
            </div>
            <div class="history-message">${escapeHtml(item.mensaje)}</div>
        </div>
    `;
}

async function handleAddComment(e) {
    e.preventDefault();
    if (!currentTicketId) return;

    const comentario = document.getElementById('comentario').value.trim();
    if (!comentario) return alert('Por favor escribe un comentario');

    try {
        await addComment(currentTicketId, comentario);
        e.target.reset();

        const history = await getTicketHistory(currentTicketId);
        document.getElementById('ticketHistory').innerHTML = history.map(createHistoryItem).join('');
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

// UTILIDADES
function getEstadoBadge(estado) {
    const badges = {
        abierto: '<span class="badge badge-primary">Abierto</span>',
        en_progreso: '<span class="badge badge-warning">En Progreso</span>',
        resuelto: '<span class="badge badge-success">Resuelto</span>',
        cerrado: '<span class="badge badge-secondary">Cerrado</span>'
    };
    return badges[estado] || '<span class="badge">Desconocido</span>';
}

function formatEstado(estado) {
    const estados = {
        abierto: 'Abierto',
        en_progreso: 'En Progreso',
        resuelto: 'Resuelto',
        cerrado: 'Cerrado'
    };
    return estados[estado] || estado;
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('es-ES', {
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
    setTimeout(() => element.style.display = 'none', 5000);
}

function setButtonLoading(button, loading) {
    button.disabled = loading;
    button.classList.toggle('btn-loading', loading);
}

// Helpers
function getElements(ids) {
    return ids.reduce((acc, id) => ({ ...acc, [id]: document.getElementById(id) }), {});
}

function createMetaItem(label, value) {
    return `
        <div class="meta-item">
            <span class="meta-label">${label}</span>
            <span class="meta-value">${value}</span>
        </div>
    `;
}