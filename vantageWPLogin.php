<?php
/*
Plugin Name: VantageWP Login Redirect Wordpress Customers
Description: Redirección segura al dashboard para usuarios verificados (Solo con Wordpress).
Version: 1.0
Author: Kevin Quiroz
*/

defined('ABSPATH') || exit;

// Definición de constantes
define('VANTAGE_API_SECRET', 'wps_5bb726982c61c339df7cea48bb972fc2f47869ef');
define('VANTAGE_VALID_PRODUCT_IDS', [3372, 3051, 3371, 3055, 3370, 3058]);
define('VANTAGE_DASHBOARD_URL', 'https://app.vantagewp.io/dashboard');
define('VANTAGE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VANTAGE_PLUGIN_URL', plugin_dir_url(__FILE__));

// Cargar archivos necesarios primero
require_once VANTAGE_PLUGIN_DIR . 'includes/class-core.php';
require_once VANTAGE_PLUGIN_DIR . 'includes/class-admin-panel.php';
require_once VANTAGE_PLUGIN_DIR . 'includes/class-api-handler.php';

// Inicializar el plugin
function vantage_wp_login_init() {
    // Inicializar el núcleo
    VantageWP_Core::init();
    
    // Inicializar el panel de administración solo si es admin
    if (is_admin()) {
        $admin_panel = new VantageWP_Admin_Panel();
        $admin_panel->init(); // Esto registrará los hooks correctamente
    }
}
add_action('plugins_loaded', 'vantage_wp_login_init');

// Registrar hook de activación
register_activation_hook(__FILE__, ['VantageWP_Core', 'activate']);