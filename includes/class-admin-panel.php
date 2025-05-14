<?php
class VantageWP_Admin_Panel {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu_item']);
        add_action('admin_enqueue_scripts', [$this, 'load_admin_assets']);
    }

    public function add_admin_menu_item() {
        add_menu_page(
            'VantageWP Dashboard',
            'VantageWP',
            'manage_options',
            'vantagewp-dashboard',
            [$this, 'render_admin_page'],
            'dashicons-google',
            6
        );
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Acceso no autorizado.');
        }

        // Cargar plantilla de encabezado
        $this->load_template('shared/header');

        $user_id = get_current_user_id();
        $user_data = get_userdata($user_id);

        // Sección de perfil
        $this->load_template('admin/profile-section', [
            'user_data' => $user_data
        ]);

        // Sección de Google Auth
        $this->load_template('admin/google-auth-section', [
            'user_id' => $user_id
        ]);

        // Sección de suscripción
        $subscription_details = $this->get_subscription_details(
            $user_id, 
            VANTAGE_VALID_PRODUCT_IDS
        );

        $this->load_template('admin/subscription-section', [
            'is_admin' => current_user_can('administrator'),
            'subscriptions_active' => $this->is_sfw_active(),
            'subscription_details' => $subscription_details
        ]);

        // Botón de acción
        if (current_user_can('administrator') || !empty($subscription_details)) {
            echo '<div class="vantagewp-actions">';
            echo '<button id="vantagewp-redirect-button" class="button button-primary">Ir al Dashboard</button>';
            echo '</div>';
        }

        // Cargar plantilla de pie de página
        $this->load_template('shared/footer');
    }

    private function load_template($template, $data = []) {
        $template_path = VANTAGE_PLUGIN_DIR . "templates/{$template}.php";
        
        if (file_exists($template_path)) {
            extract($data);
            include $template_path;
        }
    }

    public function load_admin_assets($hook) {
        if ('toplevel_page_vantagewp-dashboard' !== $hook) {
                return;
            }

            // Usando VANTAGE_PLUGIN_URL para asegurar la ruta correcta
            $admin_css_url = VANTAGE_PLUGIN_URL . 'assets/css/admin.css';
            
            // Forzar recarga del caché durante desarrollo
            $version = WP_DEBUG ? time() : '1.0';
            
            wp_enqueue_style(
                'vantagewp-admin-css',
                $admin_css_url,
                array(),
                $version
            );

            wp_enqueue_script(
                'vantagewp-admin-js',
                VANTAGE_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'),
                $version,
                true
            );

            // Localización de datos
            wp_localize_script('vantagewp-admin-js', 'vantagewp_ajax_data', [
                'nonce' => wp_create_nonce('vantagewp_redirect_nonce'),
                'is_admin' => current_user_can('administrator'),
                'ajax_url' => admin_url('admin-ajax.php')
            ]);
    }

     // Métodos para manejo de suscripciones
    private function get_subscription_details($user_id, $valid_plan_ids) {
        $subscriptions = $this->get_user_subscriptions($user_id);

        foreach ($subscriptions as $subscription) {
            $status = strtolower($subscription['status'] ?? '');
            $product_id = $subscription['product_id'] ?? 0;
            
            if (in_array($status, ['active', 'pending', 'on-hold']) && 
                (empty($valid_plan_ids) || in_array($product_id, $valid_plan_ids))) {
                $product = wc_get_product($product_id);
                
                return [
                    'subscription_id' => $subscription['subscription_id'],
                    'plan_name' => $product ? $product->get_name() : $subscription['product_name'] ?? 'Plan no disponible',
                    'billing_period' => $this->get_billing_period_label($subscription['billing_period'] ?? 'month'),
                    'status' => $this->get_status_label($status),
                    'status_class' => ('active' === $status) ? 'active' : 'pending',
                    'next_payment' => $this->format_next_payment_date($subscription['next_payment_date'] ?? ''),
                    'product_id' => $product_id
                ];
            }
        }

        return [];
    }

    private function get_user_subscriptions($user_id) {
        // Debug: Verificar usuario
        error_log("Buscando suscripciones para usuario ID: $user_id");
        
        // Debug: Verificar si SFW está activo
        error_log("SFW activo: " . ($this->is_sfw_active() ? 'Sí' : 'No'));
        
        $api_response = $this->try_api_subscriptions($user_id);
        error_log("Respuesta API: " . print_r($api_response, true));
        
        if (!empty($api_response)) {
            return $api_response;
        }
        
        if (function_exists('wps_sfw_get_users_subscriptions')) {
            $subscriptions = wps_sfw_get_users_subscriptions($user_id);
            error_log("Suscripciones directas SFW: " . print_r($subscriptions, true));
            return $this->format_sfw_subscriptions($subscriptions);
        }
        
        error_log("No se encontraron suscripciones");
        return [];
    }

    private function try_api_subscriptions($user_id) {
        $api_url = site_url('/wp-json/wsp-route/v1/wsp-view-subscription');
        $response = wp_remote_get(add_query_arg([
            'consumer_secret' => 'wps_5bb726982c61c339df7cea48bb972fc2f47869ef',
            'user_id' => $user_id
        ], $api_url));

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return [];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['data'] ?? [];
    }

    private function format_sfw_subscriptions($subscriptions) {
        $formatted = [];
        
        foreach ($subscriptions as $subscription) {
            $formatted[] = [
                'subscription_id' => $subscription->get_id(),
                'parent_order_id' => $subscription->get_parent_id(),
                'status' => $subscription->get_status(),
                'product_name' => $this->get_product_name_from_subscription($subscription),
                'product_id' => $this->get_product_id_from_subscription($subscription),
                'recurring_amount' => $subscription->get_total(),
                'payment_method' => $subscription->get_payment_method_title(),
                'billing_period' => $subscription->get_billing_period(),
                'next_payment_date' => $subscription->get_date('next_payment'),
                'subscription_expiry_date' => $subscription->get_date('end'),
                'raw_data' => $subscription->get_data()
            ];
        }
        
        return $formatted;
    }

    private function get_product_name_from_subscription($subscription) {
        $items = $subscription->get_items();
        $names = [];
        
        foreach ($items as $item) {
            $names[] = $item->get_name();
        }
        
        return implode(', ', $names);
    }

    private function get_product_id_from_subscription($subscription) {
        $items = $subscription->get_items();
        
        foreach ($items as $item) {
            return $item->get_product_id();
        }
        
        return 0;
    }

    private function format_next_payment_date($date_string) {
        if (empty($date_string)) {
            return __('No disponible', 'vantagewp-login');
        }
        
        try {
            $date = new DateTime($date_string);
            $formatted_date = $date->format(get_option('date_format') . ' ' . get_option('time_format'));
            return esc_html($formatted_date);
        } catch (Exception $e) {
            error_log('Error formateando fecha: ' . $e->getMessage());
            return __('No disponible', 'vantagewp-login');
        }
    }

    private function get_status_label($status) {
        $statuses = [
            'active' => 'Activa',
            'pending' => 'Pendiente',
            'on-hold' => 'En pausa',
            'cancelled' => 'Cancelada',
            'expired' => 'Expirada',
            'wc-active' => 'Activa',
            'wc-pending' => 'Pendiente',
            'wc-on-hold' => 'En pausa',
            'wc-cancelled' => 'Cancelada',
            'wc-expired' => 'Expirada'
        ];
        
        return $statuses[$status] ?? ucfirst(str_replace('wc-', '', $status));
    }

    private function get_billing_period_label($period) {
        $periods = [
            'day' => 'Diario',
            'week' => 'Semanal',
            'month' => 'Mensual',
            'year' => 'Anual'
        ];
        
        return $periods[$period] ?? ucfirst($period);
    }

    private function is_sfw_active() {
        return class_exists('Subscriptions_For_Woocommerce') && 
               (post_type_exists('wps_subscriptions') || 
                post_type_exists('sfw_subscription'));
    }

    public function is_google_connected($user_id) {
        return (bool) get_user_meta($user_id, 'vantagewp_google_connected', true);
    }
}