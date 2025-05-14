<?php
class VantageWP_WooCommerce_Check {
    /**
     * Verifica si el usuario tiene una suscripción activa a cualquier producto de la lista.
     */
    public static function has_active_subscription($user_id, $product_ids = []) {
        if (!function_exists('wcs_user_has_subscription')) {
            return false;
        }

        foreach ($product_ids as $product_id) {
            if (wcs_user_has_subscription($user_id, $product_id, 'active')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtiene el ID del primer plan activo encontrado.
     */
    public static function get_active_subscription_plan($user_id, $product_ids) {
        foreach ($product_ids as $product_id) {
            if (wcs_user_has_subscription($user_id, $product_id, 'active')) {
                return $product_id;
            }
        }
        return false;
    }
}