<!-- includes/modals.php -->


<!-- Modal Login -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="loginModalLabel">Iniciar Sesión</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="loginForm" action="controllers/auth_controller.php" method="post">
                    <input type="hidden" name="action" value="login">
                    
                    <div class="mb-3">
                        <label for="loginEmail" class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control" id="loginEmail" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="loginPassword" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="loginPassword" name="password" required>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="rememberMe" name="remember">
                        <label class="form-check-label" for="rememberMe">Recordarme</label>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Ingresar</button>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <p class="text-center w-100">¿No tienes cuenta? <a href="#" data-bs-toggle="modal" data-bs-target="#registerTypeModal" data-bs-dismiss="modal">Regístrate aquí</a></p>
            </div>
        </div>
    </div>
</div>

<!-- Modal para elegir tipo de registro -->
<div class="modal fade" id="registerTypeModal" tabindex="-1" aria-labelledby="registerTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="registerTypeModalLabel">Selecciona tipo de registro</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="row">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <div class="d-grid">
                            <button class="btn btn-outline-primary btn-lg py-3" data-bs-toggle="modal" data-bs-target="#registerClientModal" data-bs-dismiss="modal">
                                <i class="fas fa-user fa-2x mb-2"></i><br>
                                Cliente
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-grid">
                            <button class="btn btn-outline-primary btn-lg py-3" data-bs-toggle="modal" data-bs-target="#registerCompanyModal" data-bs-dismiss="modal">
                                <i class="fas fa-building fa-2x mb-2"></i><br>
                                Empresa
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <p class="text-center w-100">¿Ya tienes cuenta? <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal" data-bs-dismiss="modal">Inicia sesión aquí</a></p>
            </div>
        </div>
    </div>
</div>

<!-- Modal Registro Cliente -->
<div class="modal fade" id="registerClientModal" tabindex="-1" aria-labelledby="registerClientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="registerClientModalLabel">Registro de Cliente</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="registerClientForm" action="controllers/auth_controller.php" method="post">
                    <input type="hidden" name="action" value="register_client">
                    
                    <div class="mb-3">
                        <label for="clientName" class="form-label">Nombre Completo</label>
                        <input type="text" class="form-control" id="clientName" name="nombre" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="clientEmail" class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control" id="clientEmail" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="clientPassword" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="clientPassword" name="password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="clientConfirmPassword" class="form-label">Confirmar Contraseña</label>
                        <input type="password" class="form-control" id="clientConfirmPassword" name="confirm_password" required>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Registrarme</button>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <p class="text-center w-100">¿Ya tienes cuenta? <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal" data-bs-dismiss="modal">Inicia sesión aquí</a></p>
            </div>
        </div>
    </div>
</div>

<!-- Modal Registro Empresa -->
<div class="modal fade" id="registerCompanyModal" tabindex="-1" aria-labelledby="registerCompanyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="registerCompanyModalLabel">Registro de Empresa</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="registerCompanyForm" action="controllers/auth_controller.php" method="post">
                    <input type="hidden" name="action" value="register_company">
                    
                    <div class="mb-3">
                        <label for="companyRuc" class="form-label">RUC (11 dígitos)</label>
                        <input type="text" class="form-control" id="companyRuc" name="ruc" maxlength="11" pattern="\d{11}" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="companyEmail" class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control" id="companyEmail" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="companyPassword" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="companyPassword" name="password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="companyConfirmPassword" class="form-label">Confirmar Contraseña</label>
                        <input type="password" class="form-control" id="companyConfirmPassword" name="confirm_password" required>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Registrar Empresa</button>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <p class="text-center w-100">¿Ya tienes cuenta? <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal" data-bs-dismiss="modal">Inicia sesión aquí</a></p>
            </div>
        </div>
    </div>
</div>

<!-- Modal Error -->
<div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="errorModalLabel">Error</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="errorMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Success -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="successModalLabel">Éxito</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="successMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>