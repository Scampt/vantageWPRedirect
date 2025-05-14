<?php
class VantageWP_Api_Handler {
    public static function init() {
        add_action('wp_ajax_vantagewp_redirect_to_dashboard', [__CLASS__, 'handle_ajax_redirect']);
        add_action('wp_ajax_vantagewp_update_profile', [__CLASS__, 'update_profile_data']);
    }
    
    public static function handle_ajax_redirect() {
        check_ajax_referer('vantagewp_redirect_nonce', 'nonce');

        $user_id = get_current_user_id();
        $valid_plan_ids = VANTAGE_VALID_PRODUCT_IDS;
        $is_admin = current_user_can('administrator');

        if (!$is_admin) {
            if (!VantageWP_Core::is_sfw_active()) {
                wp_send_json_error(['message' => 'Subscriptions for WooCommerce no está activo']);
            }

            if (!VantageWP_Subscription_Check::has_active_subscription($user_id, $valid_plan_ids)) {
                wp_send_json_error(['message' => 'No tienes una suscripción activa']);
            }
        }

        try {
            $payload = [
                'user_id' => $user_id,
                'email' => get_userdata($user_id)->user_email,
                'is_admin' => $is_admin,
                'exp' => time() + (24 * 60 * 60)
            ];
            
            $secret_key = defined('VANTAGE_JWT_SECRET') ? VANTAGE_JWT_SECRET : 'tu_clave_secreta_123';
            $token = \Firebase\JWT\JWT::encode($payload, $secret_key, 'HS256');
            
            $redirect_url = add_query_arg('token', $token, VANTAGE_DASHBOARD_URL);
            wp_send_json_success(['redirect_url' => $redirect_url]);
            
        } catch (Exception $e) {
            error_log('Error generando JWT: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Error interno']);
        }
    }
    
    public static function update_profile_data() {
        check_ajax_referer('vantagewp_update_profile', '_wpnonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Acceso no autorizado']);
        }
        
        $user_id = get_current_user_id();
        $user_data = get_userdata($user_id);
        
        parse_str($_POST['form_data'], $form_data);
        
        $updated_data = [
            'ID' => $user_id,
            'first_name' => sanitize_text_field($form_data['first_name']),
            'last_name' => sanitize_text_field($form_data['last_name']),
        ];
        
        if ($form_data['user_email'] !== $user_data->user_email) {
            if (email_exists($form_data['user_email']) && email_exists($form_data['user_email']) !== $user_id) {
                wp_send_json_error(['message' => 'Este email ya está registrado']);
            }
            $updated_data['user_email'] = sanitize_email($form_data['user_email']);
        }
        
        $result = wp_update_user($updated_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        } else {
            wp_send_json_success(['message' => 'Datos actualizados correctamente']);
        }
    }
}