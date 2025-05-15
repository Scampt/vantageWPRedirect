<?php
class VantageWP_API_Handler {
    private $api_secret;

    public function __construct() {
        $this->api_secret = 'wps_5bb726982c61c339df7cea48bb972fc2f47869ef';
        add_action('rest_api_init', [$this, 'register_api_routes']);
    }

    public function register_api_routes() {
        register_rest_route('wsp-route/v1', '/wsp-view-subscription', [
            'methods' => 'GET',
            'callback' => [$this, 'handle_subscription_request'],
            'permission_callback' => [$this, 'verify_api_access']
        ]);
    }

    public function verify_api_access(WP_REST_Request $request) {
        $consumer_secret = $request->get_param('consumer_secret');
        return $consumer_secret === $this->api_secret;
    }

    public function handle_subscription_request(WP_REST_Request $request) {
        $user_id = $request->get_param('user_id') ?? get_current_user_id();
        
        if (!$user_id) {
            return new WP_REST_Response([
                'code' => 'missing_parameter',
                'message' => 'User ID is required',
                'data' => ['status' => 400]
            ], 400);
        }

        $subscriptions = $this->get_user_subscriptions($user_id);

        return new WP_REST_Response([
            'code' => 200,
            'status' => 'success',
            'data' => $subscriptions
        ], 200);
    }

    public function get_user_subscriptions($user_id) {
        // Implementación real para obtener suscripciones del usuario
        // Esto debería usar las funciones del plugin Subscriptions For WooCommerce
        if (!function_exists('wps_sfw_get_users_subscriptions')) {
            return [];
        }

        $subscriptions = wps_sfw_get_users_subscriptions($user_id);
        $formatted = [];

        foreach ($subscriptions as $subscription) {
            $formatted[] = [
                'subscription_id' => $subscription->get_id(),
                'parent_order_id' => $subscription->get_parent_id(),
                'status' => $subscription->get_status(),
                'product_name' => $this->get_product_name($subscription),
                'recurring_amount' => $subscription->get_total(),
                'payment_method' => $subscription->get_payment_method_title(),
                'user_name' => $subscription->get_billing_first_name() . ' ' . $subscription->get_billing_last_name(),
                'next_payment_date' => $subscription->get_date('next_payment'),
                'subscription_expiry_date' => $subscription->get_date('end')
            ];
        }

        return $formatted;
    }

    public function get_subscriptions($user_id) {
        $response = wp_remote_get("https://tu-api.com/subscriptions?user_id=".$user_id, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->get_api_token()
            ]
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return json_decode(wp_remote_retrieve_body($response), true);
    }

    private function get_product_name($subscription) {
        $items = $subscription->get_items();
        $names = [];
        
        foreach ($items as $item) {
            $names[] = $item->get_name();
        }
        
        return implode(', ', $names);
    }
}