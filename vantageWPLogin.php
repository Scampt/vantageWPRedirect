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

// Cargar el núcleo del plugin
require_once VANTAGE_PLUGIN_DIR . 'includes/class-core.php';

// Inicializar el plugin
VantageWP_Core::init();
