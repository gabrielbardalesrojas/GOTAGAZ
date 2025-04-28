// assets/js/scripts.js

document.addEventListener('DOMContentLoaded', function() {
    // Manejar el formulario de login
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitForm(this, 'login');
        });
    }
    
    // Manejar el formulario de registro de cliente
    const registerClientForm = document.getElementById('registerClientForm');
    if (registerClientForm) {
        registerClientForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validar que las contraseñas coincidan
            const password = document.getElementById('clientPassword').value;
            const confirmPassword = document.getElementById('clientConfirmPassword').value;
            
            if (password !== confirmPassword) {
                showError('Las contraseñas no coinciden');
                return;
            }
            
            submitForm(this, 'register_client');
        });
    }
    
    // Manejar el formulario de registro de empresa
    const registerCompanyForm = document.getElementById('registerCompanyForm');
    if (registerCompanyForm) {
        registerCompanyForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validar RUC
            const ruc = document.getElementById('companyRuc').value;
            if (ruc.length !== 11 || !/^\d+$/.test(ruc)) {
                showError('El RUC debe tener exactamente 11 dígitos numéricos');
                return;
            }
            
            // Validar que las contraseñas coincidan
            const password = document.getElementById('companyPassword').value;
            const confirmPassword = document.getElementById('companyConfirmPassword').value;
            
            if (password !== confirmPassword) {
                showError('Las contraseñas no coinciden');
                return;
            }
            
            submitForm(this, 'register_company');
        });
    }
    
    // Función para enviar formularios mediante AJAX
    function submitForm(form, action) {
        const formData = new FormData(form);
        formData.append('action', action);
        
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.redirect) {
                    // Si hay redirección (login exitoso)
                    window.location.href = data.redirect;
                } else {
                    // Mostrar mensaje de éxito (registro exitoso)
                    showSuccess(data.message);
                    // Resetear el formulario
                    form.reset();
                    // Cerrar el modal actual
                    const currentModal = bootstrap.Modal.getInstance(form.closest('.modal'));
                    if (currentModal) {
                        currentModal.hide();
                    }
                    // Mostrar modal de login si fue un registro exitoso
                    if (action.startsWith('register_')) {
                        setTimeout(() => {
                            const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
                            loginModal.show();
                        }, 1500);
                    }
                }
            } else {
                // Mostrar mensaje de error
                showError(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Ocurrió un error al procesar la solicitud');
        });
    }
    
    // Función para mostrar errores
    function showError(message) {
        const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
        document.getElementById('errorMessage').textContent = message;
        errorModal.show();
    }
    
    // Función para mostrar mensajes de éxito
    function showSuccess(message) {
        const successModal = new bootstrap.Modal(document.getElementById('successModal'));
        document.getElementById('successMessage').textContent = message;
        successModal.show();
    }
    
    // Crear instancias de los modals para poder utilizarlos
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modalEl => {
        new bootstrap.Modal(modalEl);
    });
    
    // Configurar encadenamiento de modales
    const modalElements = document.querySelectorAll('[data-bs-toggle="modal"]');
    modalElements.forEach(element => {
        element.addEventListener('click', function() {
            const currentModalId = this.closest('.modal')?.id;
            if (currentModalId) {
                const currentModal = bootstrap.Modal.getInstance(document.getElementById(currentModalId));
                if (currentModal) {
                    currentModal.hide();
                }
            }
        });
    });
});

// Prevenir volver atrás después de cerrar sesión
window.addEventListener('load', function() {
    if (window.location.pathname.includes('index.php') || window.location.pathname === '/') {
        window.history.pushState(null, '', window.location.href);
        window.addEventListener('popstate', function() {
            window.history.pushState(null, '', window.location.href);
        });
    }
});