<div class="vantagewp-section">
    <h2><span class="dashicons dashicons-shield"></span> Conexión con Google</h2>

    <?php if (VantageWP_Google_Auth::is_connected($user_id)): ?>
        <div class="vantagewp-notice success">
            <p>✅ Cuenta de Google conectada</p>
            <button id="vantagewp-revoke-google" class="button button-small">Desconectar</button>
        </div>
    <?php else: ?>
        <div class="vantagewp-notice warning">
            <p>Conecta tu cuenta de Google para acceder a todas las funciones</p>
            <button id="vantagewp-connect-google" class="button button-primary">
                <span class="dashicons dashicons-google"></span> Conectar con Google
            </button>
        </div>
    <?php endif; ?>
</div>