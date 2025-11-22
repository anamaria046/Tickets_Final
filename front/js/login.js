// Lógica de la página de login

document.addEventListener('DOMContentLoaded', function () {
    // Si ya está autenticado, redirigir al dashboard correspondiente
    if (isAuthenticated()) {
        const role = getUserRole();
        if (role === 'gestor') {
            window.location.href = 'front/gestor-dashboard.html';
        } else if (role === 'admin') {
            window.location.href = 'front/admin-dashboard.html';
        }
        return;
    }

    const loginForm = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const submitBtn = document.getElementById('submitBtn');
    const messageDiv = document.getElementById('message');

    loginForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        // Limpiar mensajes previos
        hideMessage();

        // Obtener valores
        const email = emailInput.value.trim();
        const password = passwordInput.value.trim();

        // Validación básica
        if (!email || !password) {
            showMessage('Por favor completa todos los campos', 'error');
            return;
        }

        // Validar formato de email
        if (!isValidEmail(email)) {
            showMessage('Por favor ingresa un email válido', 'error');
            return;
        }

        // Mostrar estado de carga
        setLoading(true);

        try {
            // Llamar a la API de login
            const response = await login(email, password);

            // Guardar token y datos del usuario
            if (response.token) {
                saveToken(response.token);
            }

            if (response.user) {
                saveUserInfo(response.user);
            }

            // Mostrar mensaje de éxito
            showMessage('Inicio de sesión exitoso. Redirigiendo...', 'success');

            // Redirigir según el rol
            setTimeout(() => {
                const role = getUserRole();
                if (role === 'gestor') {
                    window.location.href = 'front/gestor-dashboard.html';
                } else if (role === 'admin') {
                    window.location.href = 'front/admin-dashboard.html';
                } else {
                    showMessage('Rol de usuario no reconocido', 'error');
                    setLoading(false);
                }
            }, 1000);

        } catch (error) {
            console.error('Error en login:', error);
            showMessage(error.message || 'Error al iniciar sesión. Verifica tus credenciales.', 'error');
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
