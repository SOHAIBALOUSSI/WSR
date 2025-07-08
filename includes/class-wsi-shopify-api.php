<?php
/**
 * Shopify API handler class
 *
 * @package WooCommerce_Shopify_Integration
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shopify API class
 */
class WSI_Shopify_API {
    
    /**
     * Instance
     * @var WSI_Shopify_API
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
    }
    
    /**
     * Make API request to Shopify
     */
    private function make_request($endpoint, $method = 'GET', $data = null) {
        $store_url = $this->config->get_store_url();
        $access_token = $this->config->get_access_token();
        
        if (!$store_url || !$access_token) {
            return new WP_Error('missing_config', __('Shopify configuration is missing.', 'woocommerce-shopify-integration'));
        }
        
        $url = rtrim($store_url, '/') . '/admin/api/2023-10/' . ltrim($endpoint, '/');
        
        $args = array(
            'method' => $method,
            'headers' => array(
                'X-Shopify-Access-Token' => $access_token,
                'Content-Type' => 'application/json',
                'User-Agent' => 'WooCommerce-Shopify-Integration/' . WSI_PLUGIN_VERSION
            ),
            'timeout' => 30,
            'sslverify' => true
        );
        
        if ($data && in_array($method, array('POST', 'PUT', 'PATCH'))) {
            $args['body'] = is_array($data) ? json_encode($data) : $data;
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($response_code >= 200 && $response_code < 300) {
            return json_decode($body, true);
        } else {
            $error_data = json_decode($body, true);
            $error_message = isset($error_data['errors']) ? 
                (is_array($error_data['errors']) ? implode(', ', $error_data['errors']) : $error_data['errors']) :
                sprintf(__('HTTP %d error', 'woocommerce-shopify-integration'), $response_code);
            
            return new WP_Error('api_error', $error_message, array('response_code' => $response_code, 'response_body' => $body));
        }
    }
    
    /**
     * Test connection to Shopify
     */
    public function test_connection() {
        $result = $this->make_request('shop.json');
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        if (isset($result['shop'])) {
            return array(
                'success' => true,
                'shop_name' => $result['shop']['name'] ?? 'Unknown',
                'shop_domain' => $result['shop']['domain'] ?? 'Unknown',
                'currency' => $result['shop']['currency'] ?? 'USD',
                'plan_name' => $result['shop']['plan_name'] ?? 'Unknown'
            );
        }
        
        return new WP_Error('invalid_response', __('Invalid response from Shopify.', 'woocommerce-shopify-integration'));
    }
    
    /**
     * Find product by SKU
     */
    public function find_product_by_sku($sku) {
        $result = $this->make_request('products.json?fields=id,title,variants&limit=250');
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        if (isset($result['products'])) {
            foreach ($result['products'] as $product) {
                foreach ($product['variants'] as $variant) {
                    if ($variant['sku'] === $sku) {
                        return array(
                            'product_id' => $product['id'],
                            'variant_id' => $variant['id'],
                            'title' => $product['title'],
                            'sku' => $variant['sku'],
                            'price' => $variant['price'],
                            'inventory_quantity' => $variant['inventory_quantity'] ?? 0
                        );
                    }
                }
            }
        }
        
        return new WP_Error('product_not_found', sprintf(__('Product with SKU "%s" not found in Shopify.', 'woocommerce-shopify-integration'), $sku));
    }
    
    /**
     * Create draft order
     */
    public function create_draft_order($line_items, $customer_email = '') {
        $draft_order_data = array(
            'draft_order' => array(
                'line_items' => $line_items,
                'use_customer_default_address' => true,
                'note' => sprintf(__('Order created from %s', 'woocommerce-shopify-integration'), get_bloginfo('name'))
            )
        );
        
        if (!empty($customer_email)) {
            $draft_order_data['draft_order']['email'] = $customer_email;
        }
        
        $result = $this->make_request('draft_orders.json', 'POST', $draft_order_data);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        if (isset($result['draft_order'])) {
            return array(
                'success' => true,
                'draft_order_id' => $result['draft_order']['id'],
                'invoice_url' => $result['draft_order']['invoice_url'],
                'total_price' => $result['draft_order']['total_price'] ?? '0.00',
                'currency' => $result['draft_order']['currency'] ?? 'USD'
            );
        }
        
        return new WP_Error('draft_order_failed', __('Failed to create draft order in Shopify.', 'woocommerce-shopify-integration'));
    }
    
    /**
     * Test draft orders API access
     */
    public function test_draft_orders_access() {
        $result = $this->make_request('draft_orders.json?limit=1');
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        if (isset($result['draft_orders'])) {
            return array(
                'success' => true,
                'draft_orders_count' => count($result['draft_orders']),
                'permissions' => 'read_draft_orders: ✅'
            );
        }
        
        return new WP_Error('invalid_response', __('Invalid response from Shopify draft orders API.', 'woocommerce-shopify-integration'));
    }
}
