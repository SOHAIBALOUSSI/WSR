<?php
/**
 * Cart handler class
 *
 * @package WooCommerce_Shopify_Integration
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cart handler class
 */
class WSI_Cart_Handler {
    
    /**
     * Instance
     * @var WSI_Cart_Handler
     */
    private static $instance = null;
    
    /**
     * Config instance
     * @var WSI_Config
     */
    private $config;
    
    /**
     * API instance
     * @var WSI_Shopify_API
     */
    private $api;
    
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
        $this->api = WSI_Shopify_API::get_instance();
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // AJAX handler for creating draft orders
        add_action('wp_ajax_wsi_create_draft_order', array($this, 'ajax_create_draft_order'));
        add_action('wp_ajax_nopriv_wsi_create_draft_order', array($this, 'ajax_create_draft_order'));
    }
    
    /**
     * AJAX handler for creating draft orders
     */
    public function ajax_create_draft_order() {
        // Verify nonce
        if (!check_ajax_referer('wsi_checkout_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed.', 'woocommerce-shopify-integration'));
        }
        
        // Check if WooCommerce is available
        if (!class_exists('WooCommerce') || !function_exists('WC')) {
            wp_send_json_error(__('WooCommerce not available.', 'woocommerce-shopify-integration'));
        }
        
        try {
            $cart = WC()->cart;
            if (!$cart || $cart->is_empty()) {
                wp_send_json_error(__('Cart is empty.', 'woocommerce-shopify-integration'));
            }
            
            // Check configuration
            if (!$this->config->is_configured()) {
                wp_send_json_error(__('Shopify configuration missing.', 'woocommerce-shopify-integration'));
            }
            
            // Prepare cart items
            $line_items = array();
            foreach ($cart->get_cart() as $cart_item) {
                $product = $cart_item['data'];
                $sku = $product->get_sku();
                
                if (empty($sku)) {
                    wp_send_json_error(
                        sprintf(__('Product "%s" does not have a SKU.', 'woocommerce-shopify-integration'), $product->get_name())
                    );
                }
                
                // Find Shopify product
                $shopify_product = $this->api->find_product_by_sku($sku);
                
                if (is_wp_error($shopify_product)) {
                    wp_send_json_error($shopify_product->get_error_message());
                }
                
                $line_items[] = array(
                    'variant_id' => $shopify_product['variant_id'],
                    'quantity' => $cart_item['quantity']
                );
            }
            
            // Get customer email if available
            $customer_email = '';
            if (is_user_logged_in()) {
                $current_user = wp_get_current_user();
                $customer_email = $current_user->user_email;
            }
            
            // Create draft order
            $result = $this->api->create_draft_order($line_items, $customer_email);
            
            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            }
            
            // Clear cart on success
            $cart->empty_cart();
            
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            wp_send_json_error(__('Internal error: ', 'woocommerce-shopify-integration') . $e->getMessage());
        }
    }
    
    /**
     * Validate cart items against Shopify
     */
    public function validate_cart_items() {
        if (!class_exists('WooCommerce') || !function_exists('WC')) {
            return false;
        }
        
        $cart = WC()->cart;
        if (!$cart || $cart->is_empty()) {
            return false;
        }
        
        foreach ($cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            $sku = $product->get_sku();
            
            if (empty($sku)) {
                return false;
            }
            
            $shopify_product = $this->api->find_product_by_sku($sku);
            
            if (is_wp_error($shopify_product)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get cart summary for Shopify
     */
    public function get_cart_summary() {
        if (!class_exists('WooCommerce') || !function_exists('WC')) {
            return array();
        }
        
        $cart = WC()->cart;
        if (!$cart || $cart->is_empty()) {
            return array();
        }
        
        $summary = array(
            'items' => array(),
            'total_items' => $cart->get_cart_contents_count(),
            'total_price' => $cart->get_total(''),
            'currency' => get_woocommerce_currency()
        );
        
        foreach ($cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            $summary['items'][] = array(
                'name' => $product->get_name(),
                'sku' => $product->get_sku(),
                'quantity' => $cart_item['quantity'],
                'price' => $product->get_price()
            );
        }
        
        return $summary;
    }
}
