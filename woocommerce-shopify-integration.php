<?php
/**
 * Plugin Name: WooCommerce Shopify Integration
 * Plugin URI: https://example.com/woocommerce-shopify-integration
 * Description: Secure integration with Shopify for WooCommerce, enabling secure payments through Shopify checkout.
 * Version: 1.0.1
 * Author: @bat
 * Author URI: https://example.com
 * Text Domain: woocommerce-shopify-integration
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.0
 * Tested up to: 6.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * Requires PHP: 7.4
 * Network: false
 * 
 * @package WooCommerce_Shopify_Integration
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
if (!defined('WSI_PLUGIN_VERSION')) {
    define('WSI_PLUGIN_VERSION', '1.0.1');
}
if (!defined('WSI_MIN_PHP_VERSION')) {
    define('WSI_MIN_PHP_VERSION', '7.4');
}
if (!defined('WSI_PLUGIN_FILE')) {
    define('WSI_PLUGIN_FILE', __FILE__);
}
if (!defined('WSI_PLUGIN_DIR')) {
    define('WSI_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('WSI_PLUGIN_URL')) {
    define('WSI_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('WSI_PLUGIN_BASENAME')) {
    define('WSI_PLUGIN_BASENAME', plugin_basename(__FILE__));
}

/**
 * Check if WooCommerce is active
 */
if (!function_exists('is_plugin_active')) {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

/**
 * Main plugin class
 */
final class WooCommerce_Shopify_Integration {
    
    /**
     * Plugin instance
     * @var WooCommerce_Shopify_Integration
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(WSI_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(WSI_PLUGIN_FILE, array($this, 'deactivate'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Check PHP version
        if (version_compare(PHP_VERSION, WSI_MIN_PHP_VERSION, '<')) {
            add_action('admin_notices', array($this, 'php_version_notice'));
            return;
        }
        
        // Check if WooCommerce is active
        if (!$this->is_woocommerce_active()) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        // Load text domain
        add_action('init', array($this, 'load_textdomain'));
        
        // Include required files
        $this->includes();
        
        // Initialize hooks
        $this->init_hooks();
        
        // Declare HPOS compatibility
        add_action('before_woocommerce_init', array($this, 'declare_hpos_compatibility'));
    }
    
    /**
     * Include required files
     */
    private function includes() {
        require_once WSI_PLUGIN_DIR . 'includes/class-wsi-core.php';
        require_once WSI_PLUGIN_DIR . 'includes/class-wsi-config.php';
        require_once WSI_PLUGIN_DIR . 'includes/class-wsi-shopify-api.php';
        require_once WSI_PLUGIN_DIR . 'includes/class-wsi-cart-handler.php';
        
        if (is_admin()) {
            require_once WSI_PLUGIN_DIR . 'admin/class-wsi-admin.php';
        } else {
            require_once WSI_PLUGIN_DIR . 'public/class-wsi-frontend.php';
        }
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Initialize components
        WSI_Core::get_instance();
        WSI_Config::get_instance();
        WSI_Shopify_API::get_instance();
        WSI_Cart_Handler::get_instance();
        
        if (is_admin()) {
            WSI_Admin::get_instance();
        } else {
            WSI_Frontend::get_instance();
        }
    }
    
    /**
     * Load text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'woocommerce-shopify-integration',
            false,
            dirname(WSI_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Check if WooCommerce is active
     */
    private function is_woocommerce_active() {
        return class_exists('WooCommerce') || is_plugin_active('woocommerce/woocommerce.php');
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Check dependencies
        if (!$this->is_woocommerce_active()) {
            deactivate_plugins(WSI_PLUGIN_BASENAME);
            wp_die(__('WooCommerce Shopify Integration requires WooCommerce to be installed and active.', 'woocommerce-shopify-integration'));
        }
        
        // Include activation class
        require_once WSI_PLUGIN_DIR . 'includes/class-wsi-activator.php';
        WSI_Activator::activate();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Include deactivation class
        require_once WSI_PLUGIN_DIR . 'includes/class-wsi-deactivator.php';
        WSI_Deactivator::deactivate();
    }
    
    /**
     * Declare HPOS compatibility
     */
    public function declare_hpos_compatibility() {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'custom_order_tables', 
                WSI_PLUGIN_FILE, 
                true
            );
        }
    }
    
    /**
     * PHP version notice
     */
    public function php_version_notice() {
        $message = sprintf(
            __('WooCommerce Shopify Integration requires PHP %s or higher. Current version: %s', 'woocommerce-shopify-integration'),
            WSI_MIN_PHP_VERSION,
            PHP_VERSION
        );
        echo '<div class="notice notice-error"><p>' . esc_html($message) . '</p></div>';
    }
    
    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        $message = __('WooCommerce Shopify Integration requires WooCommerce to be installed and active.', 'woocommerce-shopify-integration');
        echo '<div class="notice notice-error"><p>' . esc_html($message) . '</p></div>';
    }
}

// Initialize the plugin
WooCommerce_Shopify_Integration::get_instance();
