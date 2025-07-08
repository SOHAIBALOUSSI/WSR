<?php
/**
 * Frontend functionality class
 *
 * @package WooCommerce_Shopify_Integration
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Frontend class
 */
class WSI_Frontend {
    
    /**
     * Instance
     * @var WSI_Frontend
     */
    private static $instance = null;
    
    /**
     * Config instance
     * @var WSI_Config
     */
    private $config;
    
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
        $this->config = WSI_Config::get_instance();
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Only initialize if Shopify is configured
        if (!$this->config->is_configured()) {
            return;
        }
        
        // WooCommerce hooks
        add_action('woocommerce_loaded', array($this, 'woocommerce_loaded'));
    }
    
    /**
     * WooCommerce loaded
     */
    public function woocommerce_loaded() {
        add_action('woocommerce_checkout_before_order_review', array($this, 'add_checkout_notice'));
        add_action('woocommerce_before_cart', array($this, 'add_cart_notice'));
    }
    
    /**
     * Add checkout notice
     */
    public function add_checkout_notice() {
        if (!is_checkout()) {
            return;
        }
        
        wc_print_notice(
            '🔒 <strong>' . __('Secure Payment:', 'woocommerce-shopify-integration') . '</strong> ' .
            __('You will be redirected to our secure payment gateway when you proceed to checkout.', 'woocommerce-shopify-integration'),
            'notice'
        );
    }
    
    /**
     * Add cart notice
     */
    public function add_cart_notice() {
        if (!is_cart()) {
            return;
        }
        
        wc_print_notice(
            '🔒 ' . __('Secure checkout powered by Shopify integration.', 'woocommerce-shopify-integration'),
            'notice'
        );
    }
    
    /**
     * Check if current page should have Shopify integration
     */
    public function should_load_integration() {
        return is_cart() || is_checkout() || is_shop() || is_product();
    }
}
