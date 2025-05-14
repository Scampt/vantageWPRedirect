<?php
class VantageWP_Subscription_Check {
    private $api_handler;

    public function __construct($api_handler) {
        $this->api_handler = $api_handler;
    }

    /**
     * Obtiene las suscripciones del usuario desde Subscriptions For WooCommerce
     */
    public static function get_sfw_subscriptions($user_id) {
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
                'product_name' => self::get_product_name($subscription),
                'product_id' => self::get_product_id($subscription),
                'recurring_amount' => $subscription->get_total(),
                'payment_method' => $subscription->get_payment_method_title(),
                'billing_period' => $subscription->get_billing_period(),
                'next_payment_date' => $subscription->get_date('next_payment'),
                'subscription_expiry_date' => $subscription->get_date('end'),
                'raw_data' => $subscription->get_data() // Datos completos por si acaso
            ];
        }

        return $formatted;
    }

    private static function get_product_name($subscription) {
        $items = $subscription->get_items();
        $names = [];
        
        foreach ($items as $item) {
            $names[] = $item->get_name();
        }
        
        return implode(', ', $names);
    }

    private static function get_product_id($subscription) {
        $items = $subscription->get_items();
        
        foreach ($items as $item) {
            return $item->get_product_id();
        }
        
        return 0;
    }
}