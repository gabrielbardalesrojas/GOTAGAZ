<?php
// includes/footer.php
?>
    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-gas-pump me-2"></i>GOTAGAS</h5>
                    <p>Sistema integral para la gestión y distribución de agua y gas.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>© 2025 GOTAGAS. Todos los derechos reservados.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Modals -->
    <?php if (!isset($_SESSION['user_id'])): ?>
        <?php include 'includes/modals.php'; ?>
    <?php endif; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/scripts.js"></script>
</body>
</html>