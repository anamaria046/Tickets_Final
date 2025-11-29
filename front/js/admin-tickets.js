/////////Variables globales compartidas/////
let currentTicketId = null;
let allUsers = [];
let adminUsers = [];


async function loadAllTickets() {
    const { ticketsLoading, ticketsTableContainer, noTickets, ticketsTableBody } = getElements([
        'ticketsLoading', 'ticketsTableContainer', 'noTickets', 'ticketsTableBody'
    ]);

    showLoading(ticketsLoading, ticketsTableContainer, noTickets);

    try {
        const tickets = await listAllTickets();
        await loadUsersForFilters();

        ticketsLoading.style.display = 'none';

        if (!tickets?.length) {
            noTickets.style.display = 'block';
            return;
        }

        ticketsTableContainer.style.display = 'block';
        ticketsTableBody.innerHTML = tickets.map(createTicketRow).join('');
        attachTicketEventListeners();
    } catch (error) {
        handleError('cargar tickets', error, ticketsLoading, ticketsTableBody, 7);
    }
}

function createTicketRow(t) {
    return `
        <tr>
            <td>#${t.id}</td>
            <td>${escapeHtml(t.titulo)}</td>
            <td><span class="badge-estado ${t.estado}">${formatEstado(t.estado)}</span></td>
            <td>${t.gestor?.name || 'N/A'}</td>
            <td>${t.admin?.name || 'Sin asignar'}</td>
            <td>${formatDate(t.created_at)}</td>
            <td>
                <button class="btn btn-primary btn-icon view-ticket" data-ticket-id="${t.id}">Ver</button>
            </td>
        </tr>
    `;
}

function attachTicketEventListeners() {
    document.querySelectorAll('.view-ticket').forEach(btn =>
        btn.addEventListener('click', () => openTicketModal(btn.dataset.ticketId))
    );
}

/////////FILTROS DE TICKETS
async function handleFilter(e) {
    e.preventDefault();
    const filters = getFilterValues(['filterEstado', 'filterGestor', 'filterAdmin'],
        ['estado', 'gestor_id', 'admin_id']);

    const { ticketsLoading, ticketsTableContainer, noTickets, ticketsTableBody } = getElements([
        'ticketsLoading', 'ticketsTableContainer', 'noTickets', 'ticketsTableBody'
    ]);

    showLoading(ticketsLoading, ticketsTableContainer, noTickets);

    try {
        const tickets = Object.keys(filters).length
            ? await searchTickets(filters)
            : await listAllTickets();

        ticketsLoading.style.display = 'none';

        if (!tickets?.length) {
            noTickets.style.display = 'block';
            return;
        }

        ticketsTableContainer.style.display = 'block';
        ticketsTableBody.innerHTML = tickets.map(createTicketRow).join('');
        attachTicketEventListeners();
    } catch (error) {
        handleError('filtrar tickets', error, ticketsLoading);
    }
}

function clearFilters() {
    ['filterEstado', 'filterGestor', 'filterAdmin'].forEach(id =>
        document.getElementById(id).value = ''
    );
    loadAllTickets();
}

async function loadUsersForFilters() {
    try {
        const users = await listUsers();
        allUsers = users;
        adminUsers = users.filter(u => u.role === 'admin');

        populateSelect('filterGestor', users.filter(u => u.role === 'gestor'));
        populateSelect('filterAdmin', adminUsers);
        populateSelect('assignAdmin', adminUsers, 'Sin asignar');
    } catch (error) {
        console.error('Error al cargar usuarios:', error);
    }
}


///////MODAL DE DETALLES DEL TICKET

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
        document.getElementById('ticketEstado').value = ticket.estado;
        document.getElementById('assignAdmin').value = ticket.admin_id || '';

        const history = await getTicketHistory(ticketId);
        historyDiv.innerHTML = history.map(createHistoryItem).join('');
    } catch (error) {
        detailsDiv.innerHTML = `<div class="alert alert-error">Error: ${error.message}</div>`;
    }
}

function createTicketDetails(t) {
    return `
        <div class="ticket-detail-header">
            <div style="display: flex; justify-content: space-between; align-items: start;">
                <h3 class="ticket-detail-title">${escapeHtml(t.titulo)}</h3>
                ${getEstadoBadge(t.estado)}
            </div>
            <div class="ticket-meta">
                ${createMetaItem('ID', `#${t.id}`)}
                ${createMetaItem('Creador', t.gestor?.name || 'N/A')}
                ${createMetaItem('Asignado a', t.admin?.name || 'Sin asignar')}
                ${createMetaItem('Fecha', formatDate(t.created_at))}
            </div>
        </div>
        <div class="ticket-detail-description">
            <strong>Descripción:</strong><br>${escapeHtml(t.descripcion)}
        </div>
    `;
}

function closeTicketModal() {
    document.getElementById('ticketModal').classList.remove('active');
    currentTicketId = null;
    document.getElementById('addCommentForm').reset();
}


//////////ACCIONES SOBRE TICKETS


async function handleUpdateStatus() {
    if (!currentTicketId) return;
    await updateTicketAndReload(
        () => updateTicketStatus(currentTicketId, document.getElementById('ticketEstado').value),
        'Estado actualizado correctamente'
    );
}

async function handleAssignTicket() {
    if (!currentTicketId) return;
    const adminId = document.getElementById('assignAdmin').value;
    if (!adminId) return alert('Selecciona un administrador');

    await updateTicketAndReload(
        () => assignTicket(currentTicketId, adminId),
        'Ticket asignado correctamente'
    );
}

async function handleAddComment(e) {
    e.preventDefault();
    if (!currentTicketId) return;

    const comentario = document.getElementById('comentario').value.trim();
    if (!comentario) return alert('Escribe un comentario');

    try {
        await addComment(currentTicketId, comentario);
        e.target.reset();
        const history = await getTicketHistory(currentTicketId);
        document.getElementById('ticketHistory').innerHTML = history.map(createHistoryItem).join('');
    } catch (error) {
        alert('Error al agregar comentario: ' + error.message);
    }
}
