<div class="vantagewp-section">
    <h2><span class="dashicons dashicons-cart"></span> Mi Suscripción</h2>
    
    <?php if ($is_admin): ?>
        <div class="vantagewp-notice info">
            <p>Cuentas con acceso completo como administrador.</p>
        </div>
    <?php elseif (!$subscriptions_active): ?>
        <div class="vantagewp-notice error">
            <p>Subscriptions for WooCommerce no está activo. Contacta al administrador.</p>
        </div>
    <?php elseif (empty($subscription_details)): ?>
        <div class="vantagewp-notice warning">
            <p>No tienes una suscripción activa. <a href="<?php echo esc_url(get_permalink(/* ID de página de planes */)); ?>" target="_blank">Adquirir plan</a></p>
        </div>
    <?php else: ?>
        <table class="vantagewp-data-table">
            <tr><th>Plan:</th><td><?php echo esc_html($subscription_details['plan_name']); ?></td></tr>
            <tr><th>Tipo:</th><td><?php echo esc_html($subscription_details['billing_period']); ?></td></tr>
            <tr>
                <th>Estado:</th>
                <td>
                    <span class="vantagewp-status <?php echo esc_attr($subscription_details['status_class']); ?>">
                        <?php echo esc_html($subscription_details['status']); ?>
                    </span>
                </td>
            </tr>
            <tr><th>Próximo pago:</th><td><?php echo esc_html($subscription_details['next_payment']); ?></td></tr>
            <tr><th>ID Suscripción:</th><td><?php echo esc_html($subscription_details['subscription_id']); ?></td></tr>
        </table>
    <?php endif; ?>
</div>