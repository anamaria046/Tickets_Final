///////CARGA Y VISUALIZACIÓN DE USUARIOS

async function loadUsers() {
    const { usersLoading, usersTableContainer, usersTableBody } = getElements([
        'usersLoading', 'usersTableContainer', 'usersTableBody'
    ]);

    usersLoading.style.display = 'flex';
    usersTableContainer.style.display = 'none';

    try {
        const users = await listUsers();
        allUsers = users;
        usersLoading.style.display = 'none';
        usersTableContainer.style.display = 'block';
        usersTableBody.innerHTML = users.map(createUserRow).join('');
        attachUserEventListeners();
    } catch (error) {
        handleError('cargar usuarios', error, usersLoading, usersTableBody, 6);
    }
}

function createUserRow(u) {
    const roleBadge = u.role === 'admin'
        ? '<span class="badge badge-danger">Admin</span>'
        : '<span class="badge badge-primary">Gestor</span>';

    return `
        <tr>
            <td>${u.id}</td>
            <td>${escapeHtml(u.name)}</td>
            <td>${escapeHtml(u.email)}</td>
            <td>${roleBadge}</td>
            <td>${formatDate(u.created_at)}</td>
            <td>
                <button class="btn btn-primary btn-icon edit-user" data-user-id="${u.id}">Editar</button>
                <button class="btn btn-danger btn-icon delete-user" data-user-id="${u.id}">Eliminar</button>
            </td>
        </tr>
    `;
}

function attachUserEventListeners() {
    document.querySelectorAll('.edit-user').forEach(btn =>
        btn.addEventListener('click', () => openEditUserModal(btn.dataset.userId))
    );
    document.querySelectorAll('.delete-user').forEach(btn =>
        btn.addEventListener('click', () => handleDeleteUser(btn.dataset.userId))
    );
}

///////EDICIÓN DE USUARIOS

function openEditUserModal(userId) {
    const user = allUsers.find(u => u.id == userId);
    if (!user) return;

    ['editUserId', 'editUserName', 'editUserEmail', 'editUserRole'].forEach((id, i) => {
        document.getElementById(id).value = [user.id, user.name, user.email, user.role][i];
    });

    document.getElementById('userModal').classList.add('active');
}

async function handleEditUser(e) {
    e.preventDefault();
    const userId = document.getElementById('editUserId').value;
    const data = {
        name: document.getElementById('editUserName').value.trim(),
        email: document.getElementById('editUserEmail').value.trim(),
        role: document.getElementById('editUserRole').value
    };

    if (!data.name || !data.email || !data.role)
        return alert('Completa todos los campos');

    try {
        await updateUser(userId, { name: data.name, email: data.email });
        const currentUser = allUsers.find(u => u.id == userId);
        if (currentUser?.role !== data.role) await changeUserRole(userId, data.role);

        alert('Usuario actualizado correctamente');
        closeUserModal();
        loadUsers();
    } catch (error) {
        alert('Error al actualizar usuario: ' + error.message);
    }
}

function closeUserModal() {
    document.getElementById('userModal').classList.remove('active');
    document.getElementById('editUserForm').reset();
}

//////ELIMINACIÓN DE USUARIOS

async function handleDeleteUser(userId) {
    const user = allUsers.find(u => u.id == userId);
    if (!user || !confirm(`¿Eliminar a "${user.name}"?`)) return;

    try {
        await deleteUser(userId);
        alert('Usuario eliminado correctamente');
        loadUsers();
    } catch (error) {
        alert('Error al eliminar usuario: ' + error.message);
    }
}

/////CREACIÓN DE USUARIOS


async function handleCreateUser(e) {
    e.preventDefault();
    hideCreateUserMessage();

    const data = {
        name: document.getElementById('newUserName').value.trim(),
        email: document.getElementById('newUserEmail').value.trim(),
        password: document.getElementById('newUserPassword').value.trim(),
        role: document.getElementById('newUserRole').value
    };

    if (!data.name || !data.email || !data.password || !data.role)
        return showCreateUserMessage('Completa todos los campos', 'error');
    if (!isValidEmail(data.email))
        return showCreateUserMessage('Email inválido', 'error');
    if (data.password.length < 6)
        return showCreateUserMessage('Contraseña mínimo 6 caracteres', 'error');
    if (!['gestor', 'admin'].includes(data.role))
        return showCreateUserMessage('Rol inválido', 'error');

    setCreateUserLoading(true);

    try {
        await register(data);
        showCreateUserMessage('Usuario creado exitosamente', 'success');
        e.target.reset();
        setTimeout(() => {
            loadUsers();
            hideCreateUserMessage();
        }, 2000);
    } catch (error) {
        showCreateUserMessage(error.message || 'Error al crear usuario', 'error');
    } finally {
        setCreateUserLoading(false);
    }
}
