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

        // Sección de suscripción (COMPLETO)
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

    /**
     * Métodos para manejo de suscripciones (COMPLETOS)
     */
    private function get_subscription_details($user_id, $valid_plan_ids = []) {
        error_log("Productos válidos recibidos: " . print_r($valid_plan_ids, true));
        
        // Si es admin, ignorar validación de productos
        if (current_user_can('administrator')) {
            $valid_plan_ids = [];
            error_log("Usuario es admin, ignorando filtro de productos");
        }
        error_log("Obteniendo detalles para usuario $user_id. Productos válidos: " . print_r($valid_plan_ids, true));
        
        $subscriptions = $this->get_user_subscriptions($user_id);
        error_log("Total suscripciones encontradas: " . count($subscriptions));
        
        foreach ($subscriptions as $subscription) {
            error_log("Procesando suscripción: " . print_r($subscription, true));
            
            $status = strtolower($subscription['status'] ?? '');
            $product_id = $subscription['product_id'] ?? 0;
            
            // Debug: Mostrar información de coincidencia
            $valid_product = empty($valid_plan_ids) || in_array($product_id, $valid_plan_ids);
            error_log("Estado: $status, Producto ID: $product_id, Válido: " . ($valid_product ? 'Sí' : 'No'));
            
            if (in_array($status, ['active', 'pending', 'on-hold']) && $valid_product) {
                $product = wc_get_product($product_id);
                $product_name = $product ? $product->get_name() : ($subscription['product_name'] ?? 'Plan no disponible');
                
                error_log("Suscripción válida encontrada: $product_name (ID: $product_id)");
                
                return [
                    'subscription_id' => $subscription['subscription_id'],
                    'plan_name' => $product_name,
                    'billing_period' => $this->get_billing_period_label($subscription['billing_period'] ?? 'year'),
                    'status' => $this->get_status_label($status),
                    'status_class' => $status,
                    'next_payment' => $this->format_next_payment_date($subscription['next_payment_date'] ?? ''),
                    'product_id' => $product_id,
                    'raw_data' => $subscription // Para debug en plantilla
                ];
            }
        }
        
        error_log("No se encontraron suscripciones válidas");
        return [];
    }

    private function get_user_subscriptions($user_id) {
        // 1. Intento con API
            $api_response = $this->try_api_subscriptions($user_id);
            if (!empty($api_response)) {
                error_log("Suscripciones obtenidas por API");
                return $api_response;
            }

            // 2. Fallback directo a SFW
            if ($this->is_sfw_active()) {
                error_log("Buscando suscripciones directamente en SFW");
                
                // Método alternativo si wps_sfw_get_users_subscriptions no funciona
                $args = [
                    'post_type' => 'wps_subscriptions',
                    'meta_key' => 'wps_customer_id',
                    'meta_value' => $user_id,
                    'posts_per_page' => -1,
                    'post_status' => 'wc-active' // Puedes añadir más estados
                ];
                
                $subscriptions = get_posts($args);
                error_log("Suscripciones encontradas directas: " . count($subscriptions));
                
                return $this->format_raw_subscriptions($subscriptions);
            }
            
            error_log("No se encontraron suscripciones");
            return [];
        }

        private function format_raw_subscriptions($subscriptions) {
            $formatted = [];
            
            foreach ($subscriptions as $sub) {
                $subscription = wc_get_order($sub->ID);
                if (!$subscription) continue;
                
                $product_id = 0;
                $items = $subscription->get_items();
                foreach ($items as $item) {
                    $product_id = $item->get_product_id();
                    break;
                }
                
                $formatted[] = [
                    'subscription_id' => $subscription->get_id(),
                    'parent_order_id' => $subscription->get_parent_id(),
                    'status' => $subscription->get_status(),
                    'product_name' => $subscription->get_name(),
                    'product_id' => $product_id,
                    'recurring_amount' => $subscription->get_total(),
                    'next_payment_date' => $subscription->get_date('next_payment'),
                    'raw_data' => $subscription->get_data()
                ];
            }
            
            return $formatted;
    }

    private function try_api_subscriptions($user_id) {
        $current_user = get_user_by('id', $user_id);
            if (!$current_user) {
                error_log("Usuario no encontrado");
                return [];
            }

            $api_url = site_url('/wp-json/wsp-route/v1/wsp-view-subscription');
            $args = [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode('api:' . 'wps_5bb726982c61c339df7cea48bb972fc2f47869ef')
                ],
                'body' => [
                    'user_email' => $current_user->user_email,
                    'user_login' => $current_user->user_login
                ],
                'timeout' => 15
            ];

            error_log("Enviando a API: " . print_r($args, true));
            
            $response = wp_remote_post($api_url, $args); // Cambiado a POST
            
            if (is_wp_error($response)) {
                error_log("Error en API: " . $response->get_error_message());
                return [];
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            
            error_log("Respuesta API - Código: $status_code, Body: $body");
            
            if ($status_code !== 200) {
                return [];
            }
            
            $data = json_decode($body, true);
            return $data['data'] ?? [];
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