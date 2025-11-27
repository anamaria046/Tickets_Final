// Lógica de la página de registro

document.addEventListener('DOMContentLoaded', function () {
    // Si ya está autenticado, redirigir al dashboard correspondiente
    if (isAuthenticated()) {
        const role = getUserRole();
        if (role === 'gestor') {
            window.location.href = 'gestor-dashboard.html';
        } else if (role === 'admin') {
            window.location.href = 'admin-dashboard.html';
        }
        return;
    }

    const registerForm = document.getElementById('registerForm');
    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const roleSelect = document.getElementById('role');
    const submitBtn = document.getElementById('submitBtn');
    const messageDiv = document.getElementById('message');

    registerForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        // Limpiar mensajes previos
        hideMessage();

        // Obtener valores
        const name = nameInput.value.trim();
        const email = emailInput.value.trim();
        const password = passwordInput.value.trim();
        const role = roleSelect.value;

        // Validación básica
        if (!name || !email || !password || !role) {
            showMessage('Por favor completa todos los campos', 'error');
            return;
        }

        // Validar formato de email
        if (!isValidEmail(email)) {
            showMessage('Por favor ingresa un email válido', 'error');
            return;
        }

        // Validar longitud de contraseña
        if (password.length < 6) {
            showMessage('La contraseña debe tener al menos 6 caracteres', 'error');
            return;
        }

        // Validar rol
        if (role !== 'gestor' && role !== 'admin') {
            showMessage('Por favor selecciona un rol válido', 'error');
            return;
        }

        // Mostrar estado de carga
        setLoading(true);

        try {
            // Llamar a la API de registro
            const userData = {
                name: name,
                email: email,
                password: password,
                role: role
            };

            const response = await register(userData);

            // Mostrar mensaje de éxito
            showMessage('Registro exitoso. Redirigiendo al inicio de sesión...', 'success');

            // Redirigir al login después de 2 segundos
            setTimeout(() => {
                window.location.href = '../index.html';
            }, 2000);

        } catch (error) {
            console.error('Error en registro:', error);
            showMessage(error.message || 'Error al registrar usuario. Intenta nuevamente.', 'error');
            setLoading(false);
        }
    });

    // Funciones auxiliares
    function showMessage(text, type) {
        messageDiv.textContent = text;
        messageDiv.className = `auth-message ${type} show`;
    }

    function hideMessage() {
        messageDiv.className = 'auth-message';
        messageDiv.textContent = '';
    }

    function setLoading(loading) {
        if (loading) {
            submitBtn.disabled = true;
            submitBtn.classList.add('btn-loading');
        } else {
            submitBtn.disabled = false;
            submitBtn.classList.remove('btn-loading');
        }
    }

    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
});