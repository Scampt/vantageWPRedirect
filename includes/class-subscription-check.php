<?php
class VantageWP_Subscription_Check {
    /**
     * Obtiene las suscripciones del usuario usando la API del plugin
     */
    public static function get_sfw_subscriptions($user_id) {
        $cache_key = 'vantagewp_subscriptions_' . $user_id;
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }

        // Configuración de la API
        $api_secret = 'wps_5bb726982c61c339df7cea48bb972fc2f47869ef';
        
        // Posibles endpoints alternativos
        $possible_endpoints = [
            '/wp-json/wsp-route/v1/wsp-view-subscription',
            '/wp-json/wps-route/v1/wps-view-subscription',
            '/wp-json/subscriptions/v1/get'
        ];

        $args = [
            'headers' => [
                'Content-Type' => 'application/json',
                'consumer_secret' => $api_secret
            ],
            'timeout' => 15
        ];

        $response = null;
        
        // Probar cada endpoint hasta encontrar uno que funcione
        foreach ($possible_endpoints as $endpoint) {
            $api_url = site_url($endpoint);
            $response = wp_remote_get($api_url, $args);
            
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                break; // Endpoint correcto encontrado
            }
        }

        // Manejar errores (el resto del código se mantiene igual)
        if (is_wp_error($response)) {
            error_log('Error en la petición a la API: ' . $response->get_error_message());
            return [];
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            error_log("Error en API de suscripciones. Código: $response_code");
            return [];
        }
        
        $data = json_decode($body, true);
        
        // Filtrar por usuario
        $user_data = get_userdata($user_id);
        $user_subscriptions = array_filter($data['data'], function($sub) use ($user_data) {
            return strtolower($sub['user_name']) === strtolower($user_data->user_login);
        });
        
        // Formatear los datos
        $formatted = [];
        foreach ($user_subscriptions as $subscription) {
            $product_id = self::get_product_id_by_name($subscription['product_name']);
            
            $formatted[] = [
                'subscription_id' => $subscription['subscription_id'],
                'product_id'      => $product_id,
                'status'          => $subscription['status'],
                'billing_period'  => self::extract_billing_period($subscription),
                'next_payment_date' => $subscription['next_payment_date'],
                'raw_data'        => $subscription
            ];
        }
        
        error_log("Endpoint probado: $api_url - Código de respuesta: " . wp_remote_retrieve_response_code($response));
        
        set_transient($cache_key, $formatted, 12 * HOUR_IN_SECONDS);
        return $formatted;
    }
    
    private static function get_product_id_by_name($product_name) {
        global $wpdb;
        $product_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} 
                WHERE post_title = %s AND post_type = 'product'",
                $product_name
            )
        );
        return $product_id ? (int)$product_id : 0;
    }
    
    private static function extract_billing_period($subscription) {
        // Extraer período de facturación del nombre del producto o datos de la suscripción
        if (strpos($subscription['product_name'], 'mensual') !== false) return 'month';
        if (strpos($subscription['product_name'], 'anual') !== false) return 'year';
        if (strpos($subscription['product_name'], 'semanal') !== false) return 'week';
        return 'month'; // Valor por defecto
    }

    public static function has_active_subscription($user_id, $valid_plan_ids = []) {
        $subscriptions = self::get_sfw_subscriptions($user_id);
        
        foreach ($subscriptions as $subscription) {
            $status = strtolower($subscription['status'] ?? '');
            $product_id = $subscription['product_id'] ?? 0;
            
            if (in_array($status, ['active', 'pending', 'on-hold'])) {
                if (empty($valid_plan_ids) || in_array($product_id, $valid_plan_ids)) {
                    return true;
                }
            }
        }
        return false;
    }
}