<div class="vantage-subscriptions-container">
    <h2 class="vantage-section-title"><?php esc_html_e('Your Subscriptions', 'vantage-wp-login'); ?></h2>
    
    <?php 
    error_log('Datos recibidos en template: ' . print_r($subscription_details, true));
    
    if (!empty($subscription_details) && is_array($subscription_details)) : ?>
        <div class="vantage-subscriptions-list">
            <?php foreach ($subscription_details as $sub) : ?>
                <div class="vantage-subscription-card">
                    <div class="vantage-subscription-header">
                        <h3><?php echo esc_html($sub['product_name'] ?? 'Unknown Product'); ?></h3>
                        <span class="vantage-subscription-status <?php echo esc_attr($sub['status'] ?? ''); ?>">
                            <?php echo esc_html($sub['status'] ?? 'N/A'); ?>
                        </span>
                    </div>
                    
                    <div class="vantage-subscription-details">
                        <p><strong>ID:</strong> <?php echo esc_html($sub['subscription_id'] ?? 'N/A'); ?></p>
                        <p><strong>Próximo pago:</strong> <?php echo esc_html($sub['next_payment_date'] ?? 'N/A'); ?></p>
                        <p><strong>Monto:</strong> <?php echo isset($sub['recurring_amount']) ? wc_price($sub['recurring_amount']) : 'N/A'; ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <div class="vantage-no-subscriptions">
            <p><?php esc_html_e('No active subscriptions found.', 'vantage-wp-login'); ?></p>
        </div>
    <?php endif; ?>
</div>