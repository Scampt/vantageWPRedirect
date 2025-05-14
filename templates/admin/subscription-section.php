<?php
/**
 * Plantilla de sección de suscripciones - Versión corregida
 * Muestra suscripciones para admin/clientes con sistema de fallback
 */
?>

<div class="vantagewp-subscription-section">
    <?php if ($is_admin) : ?>
        <div class="admin-notice">
            <h2><span class="dashicons dashicons-admin-settings"></span> Panel de Administración de Suscripciones</h2>
            <div class="notice notice-info">
                <p>Tienes acceso completo a todas las funciones administrativas.</p>
            </div>
        </div>
    <?php endif; ?>

    <div class="subscription-container">
        <?php if (!empty($subscription_details)) : ?>
            <!-- Tarjeta de Suscripción -->
            <div class="subscription-card">
                <div class="card-header">
                    <h3><?php echo esc_html($subscription_details['plan_name']); ?></h3>
                    <span class="status-badge status-<?php echo esc_attr($subscription_details['status_class']); ?>">
                        <?php echo esc_html($subscription_details['status']); ?>
                    </span>
                </div>

                <div class="card-body">
                    <div class="subscription-row">
                        <div class="row-label">ID Suscripción:</div>
                        <div class="row-value">#<?php echo esc_html($subscription_details['subscription_id']); ?></div>
                    </div>
                    
                    <div class="subscription-row">
                        <div class="row-label">Próximo Pago:</div>
                        <div class="row-value"><?php echo esc_html($subscription_details['next_payment']); ?></div>
                    </div>
                    
                    <div class="subscription-row">
                        <div class="row-label">Ciclo de Facturación:</div>
                        <div class="row-value"><?php echo esc_html($subscription_details['billing_period']); ?></div>
                    </div>
                </div>

                <?php if ($is_admin && !empty($subscription_details['raw_data'])) : ?>
                    <div class="admin-debug-info">
                        <h4><span class="dashicons dashicons-info"></span> Datos Técnicos</h4>
                        <textarea readonly class="debug-textarea"><?php 
                            echo esc_textarea(print_r($subscription_details['raw_data'], true)); 
                        ?></textarea>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($subscriptions_active) : ?>
            <!-- Sin suscripciones activas -->
            <div class="no-subscription">
                <div class="notice notice-warning">
                    <h3><span class="dashicons dashicons-warning"></span> No tienes suscripciones activas</h3>
                    <p>Actualmente no tienes ninguna suscripción activa asociada a tu cuenta.</p>
                    <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="button button-primary">
                        Ver planes disponibles
                    </a>
                </div>
            </div>
        <?php else : ?>
            <!-- Sistema de suscripciones no disponible -->
            <div class="system-unavailable">
                <div class="notice notice-error">
                    <h3><span class="dashicons dashicons-dismiss"></span> Sistema no disponible</h3>
                    <p>El sistema de suscripciones no está disponible en este momento.</p>
                    <p>Por favor, intenta nuevamente más tarde o contacta al soporte técnico.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>