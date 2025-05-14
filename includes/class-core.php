<?php
class VantageWP_Core {
    public static function init() {
        // Cargar dependencias
        self::load_dependencies();
        
        // Registrar hooks
        add_action('plugins_loaded', [__CLASS__, 'load_textdomain']);
        add_action('admin_notices', [__CLASS__, 'show_dependency_warnings']);
        
        // Inicializar componentes
        add_action('init', [__CLASS__, 'initialize_components']);
        
        // Registrar hook de activación
        register_activation_hook(__FILE__, [__CLASS__, 'on_activation']);
    }
    
    public static function load_dependencies() {
        require_once VANTAGE_PLUGIN_DIR . 'includes/class-admin-panel.php';
        require_once VANTAGE_PLUGIN_DIR . 'includes/class-subscription-check.php';
        require_once VANTAGE_PLUGIN_DIR . 'includes/class-woocommerce-check.php';
        require_once VANTAGE_PLUGIN_DIR . 'includes/class-google-auth.php';
        require_once VANTAGE_PLUGIN_DIR . 'includes/class-api-handler.php';
    }
    
    public static function load_textdomain() {
        load_plugin_textdomain(
            'vantagewp-login',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }
    
    public static function show_dependency_warnings() {
        if (!current_user_can('manage_options')) return;
        
        if (get_transient('vantagewp_plugin_warning')) {
            $message = !self::is_woocommerce_active() 
                ? __('⚠️ <strong>VantageWP Login Redirect</strong> requiere WooCommerce para funcionar.', 'vantagewp-login')
                : __('⚠️ <strong>VantageWP Login Redirect</strong> requiere Subscriptions for WooCommerce para funcionar.', 'vantagewp-login');
            
            echo '<div class="notice notice-warning"><p>' . $message . ' <a href="' . admin_url('plugins.php') . '">' . __('Actívalos ahora', 'vantagewp-login') . '</a></p></div>';
            delete_transient('vantagewp_plugin_warning');
        }
        
        if (!self::is_sfw_active() && current_user_can('manage_options')) {
            echo '<div class="notice notice-error"><p>' . 
                 __('El plugin de suscripciones no está configurado correctamente. No se encontró el tipo de contenido para suscripciones.', 'vantagewp-login') . 
                 '</p></div>';
        }
    }
    
    public static function initialize_components() {
        if (self::is_woocommerce_active() && self::is_sfw_active()) {
            new VantageWP_Admin_Panel();
        }
    }
    
    public static function on_activation() {
        if (!self::is_woocommerce_active() || !self::is_sfw_active()) {
            set_transient('vantagewp_plugin_warning', true, 5);
        }
    }
    
    public static function is_sfw_active() {
        return class_exists('Subscriptions_For_Woocommerce') && 
               (post_type_exists('wps_subscriptions') || 
                post_type_exists('sfw_subscription'));
    }
    
    public static function is_woocommerce_active() {
        return class_exists('WooCommerce');
    }
}