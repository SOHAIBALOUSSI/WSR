<?php
/**
 * Core functionality class
 *
 * @package WooCommerce_Shopify_Integration
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Core class
 */
class WSI_Core {
    
    /**
     * Instance
     * @var WSI_Core
     */
    private static $instance = null;
    
    /**
     * Get instance
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
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_scripts() {
        if (is_admin()) {
            return;
        }
        
        // Check if Shopify is configured
        $config = WSI_Config::get_instance();
        if (!$config->is_configured()) {
            return;
        }
        
        wp_enqueue_script(
            'wsi-frontend',
            WSI_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            WSI_PLUGIN_VERSION,
            true
        );
        
        wp_enqueue_style(
            'wsi-frontend',
            WSI_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            WSI_PLUGIN_VERSION
        );
        
        wp_localize_script('wsi-frontend', 'wsi_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wsi_checkout_nonce'),
            'shopify_configured' => true,
            'messages' => array(
                'loading' => __('Creating your secure order...', 'woocommerce-shopify-integration'),
                'success' => __('Order created successfully!', 'woocommerce-shopify-integration'),
                'redirect' => __('Redirecting to secure payment...', 'woocommerce-shopify-integration'),
                'error' => __('An error occurred. Please try again.', 'woocommerce-shopify-integration')
            )
        ));
    }
    
    /**
     * Enqueue admin scripts
     */
    public function admin_enqueue_scripts($hook) {
        // Only load on our admin pages
        if (strpos($hook, 'wsi-shopify-integration') === false) {
            return;
        }
        
        wp_enqueue_script(
            'wsi-admin',
            WSI_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            WSI_PLUGIN_VERSION,
            true
        );
        
        wp_enqueue_style(
            'wsi-admin',
            WSI_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            WSI_PLUGIN_VERSION
        );
        
        wp_localize_script('wsi-admin', 'wsi_admin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wsi_admin_nonce'),
            'messages' => array(
                'testing' => __('Testing connection...', 'woocommerce-shopify-integration'),
                'success' => __('Connection successful!', 'woocommerce-shopify-integration'),
                'error' => __('Connection failed.', 'woocommerce-shopify-integration')
            )
        ));
    }
    
    /**
     * Get plugin settings URL
     */
    public function get_settings_url() {
        return admin_url('options-general.php?page=wsi-shopify-integration');
    }
    
    /**
     * Log message
     */
    public function log($message, $level = 'info') {
        if (WP_DEBUG && WP_DEBUG_LOG) {
            error_log('WSI [' . $level . ']: ' . $message);
        }
    }
}
