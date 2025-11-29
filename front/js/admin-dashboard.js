document.addEventListener('DOMContentLoaded', () => {
    if (!checkAuth() || !checkRole('admin')) return;
    init();
});

function init() {
    const userName = getUserName();
    if (userName) document.getElementById('userName').textContent = userName;
    setupEventListeners();
    loadAllTickets();
    loadUsers();
}

function setupEventListeners() {
    ////Tabs
    document.querySelectorAll('.tab-btn').forEach(btn =>
        btn.addEventListener('click', () => switchTab(btn.dataset.tab))
    );

    ///Acciones generales
    document.getElementById('logoutBtn').addEventListener('click', logout);
    document.getElementById('filterForm').addEventListener('submit', handleFilter);
    document.getElementById('clearFilters').addEventListener('click', clearFilters);

    ////Modales
    ['ticketModal', 'userModal'].forEach(id => {
        const modal = document.getElementById(id);
        const closeBtn = document.getElementById(id === 'ticketModal' ? 'closeTicketModal' : 'closeUserModal');
        closeBtn.addEventListener('click', () => modal.classList.remove('active'));
        modal.addEventListener('click', e => e.target === modal && modal.classList.remove('active'));
    });

    ////Tickets
    document.getElementById('updateStatusBtn').addEventListener('click', handleUpdateStatus);
    document.getElementById('assignTicketBtn').addEventListener('click', handleAssignTicket);
    document.getElementById('addCommentForm').addEventListener('submit', handleAddComment);

    ////Usuarios
    document.getElementById('editUserForm').addEventListener('submit', handleEditUser);
    document.getElementById('cancelEditUser').addEventListener('click', closeUserModal);
    document.getElementById('createUserForm')?.addEventListener('submit', handleCreateUser);
}

function switchTab(tabName) {
    document.querySelectorAll('.tab-btn').forEach(btn =>
        btn.classList.toggle('active', btn.dataset.tab === tabName)
    );
    document.querySelectorAll('.tab-content').forEach(content =>
        content.classList.remove('active')
    );
    document.getElementById(`tab-${tabName}`).classList.add('active');

    if (tabName === 'tickets') loadAllTickets();
    else if (tabName === 'usuarios') loadUsers();
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
        year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}


function getElements(ids) {
    return ids.reduce((acc, id) => ({ ...acc, [id]: document.getElementById(id) }), {});
}

function showLoading(loading, container, noData) {
    loading.style.display = 'flex';
    container.style.display = 'none';
    noData.style.display = 'none';
}

function handleError(action, error, loading, tbody, colspan) {
    console.error(`Error al ${action}:`, error);
    loading.style.display = 'none';
    if (tbody) {
        tbody.innerHTML = `
            <tr><td colspan="${colspan}" class="text-center">
                <div class="alert alert-error">Error al ${action}: ${error.message}</div>
            </td></tr>
        `;
    } else {
        alert(`Error al ${action}: ${error.message}`);
    }
}
function getFilterValues(elementIds, keys) {
    return elementIds.reduce((acc, id, i) => {
        const val = document.getElementById(id).value;
        return val ? { ...acc, [keys[i]]: val } : acc;
    }, {});
}
function populateSelect(id, users, emptyText = 'Todos') {
    document.getElementById(id).innerHTML = `<option value="">${emptyText}</option>` +
        users.map(u => `<option value="${u.id}">${escapeHtml(u.name)}</option>`).join('');
}

function createMetaItem(label, value) {
    return `
        <div class="meta-item">
            <span class="meta-label">${label}</span>
            <span class="meta-value">${value}</span>
        </div>
    `;
}

//acciones
async function updateTicketAndReload(updateFn, successMsg) {
    try {
        await updateFn();
        alert(successMsg);
        const [ticket, history] = await Promise.all([
            getTicketDetails(currentTicketId),
            getTicketHistory(currentTicketId)
        ]);
        document.getElementById('ticketDetails').innerHTML = createTicketDetails(ticket);
        document.getElementById('ticketHistory').innerHTML = history.map(createHistoryItem).join('');
        loadAllTickets();
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

function showCreateUserMessage(text, type) {
    const msg = document.getElementById('createUserMessage');
    msg.textContent = text;
    msg.className = `auth-message ${type} show`;
}

function hideCreateUserMessage() {
    const msg = document.getElementById('createUserMessage');
    msg.className = 'auth-message';
    msg.textContent = '';
}

function setCreateUserLoading(loading) {
    const btn = document.getElementById('createUserSubmitBtn');
    btn.disabled = loading;
    btn.classList.toggle('btn-loading', loading);
}