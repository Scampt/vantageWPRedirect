<?php
class VantageWP_Google_Auth {
    public static function init() {
        add_action('wp_ajax_vantagewp_google_auth', [__CLASS__, 'handle_google_auth']);
    }
    
    public static function is_connected($user_id) {
        return (bool) get_user_meta($user_id, 'vantagewp_google_connected', true);
    }
    
    public static function handle_google_auth() {
        check_ajax_referer('vantagewp_google_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Acceso no autorizado']);
        }

        $user_id = get_current_user_id();
        
        try {
            $client = new Google\Client();
            $client->setClientId('TU_CLIENT_ID.apps.googleusercontent.com');
            $client->setClientSecret('TU_CLIENT_SECRET');
            $client->setRedirectUri(admin_url('admin.php?page=vantagewp-dashboard'));
            $client->addScope('email');
            $client->addScope('profile');
            
            if (isset($_GET['code'])) {
                $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
                
                if (isset($token['error'])) {
                    throw new Exception($token['error_description'] ?? 'Error en autenticación');
                }
                
                update_user_meta($user_id, 'vantagewp_google_token', $token);
                update_user_meta($user_id, 'vantagewp_google_connected', true);
                
                wp_send_json_success(['message' => 'Cuenta conectada exitosamente']);
            } else {
                $authUrl = $client->createAuthUrl();
                wp_send_json_success(['auth_url' => $authUrl]);
            }
        } catch (Exception $e) {
            error_log('Error en Google Auth: ' . $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
}