<?php
/**
 * Configuration management class
 *
 * @package WooCommerce_Shopify_Integration
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Configuration class
 */
class WSI_Config {
    
    /**
     * Instance
     * @var WSI_Config
     */
    private static $instance = null;
    
    /**
     * Configuration method
     * @var string
     */
    private $config_method;
    
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
        $this->detect_config_method();
    }
    
    /**
     * Detect configuration method
     * Priority: PHP Constants > WordPress Options
     */
    private function detect_config_method() {
        if (defined('WSI_SHOPIFY_STORE_URL') && defined('WSI_SHOPIFY_ACCESS_TOKEN')) {
            $this->config_method = 'constants';
        } else {
            $this->config_method = 'options';
        }
    }
    
    /**
     * Get configuration method
     */
    public function get_config_method() {
        return $this->config_method;
    }
    
    /**
     * Check if using constants
     */
    public function is_using_constants() {
        return $this->config_method === 'constants';
    }
    
    /**
     * Get configuration value
     */
    public function get($key, $default = '') {
        switch ($this->config_method) {
            case 'constants':
                $constant_name = 'WSI_' . strtoupper($key);
                return defined($constant_name) ? constant($constant_name) : $default;
                
            case 'options':
            default:
                return get_option('wsi_' . $key, $default);
        }
    }
    
    /**
     * Set configuration value (only for options method)
     */
    public function set($key, $value) {
        if ($this->config_method === 'options') {
            return update_option('wsi_' . $key, $value);
        }
        return false;
    }
    
    /**
     * Get Shopify store URL
     */
    public function get_store_url() {
        return $this->get('shopify_store_url');
    }
    
    /**
     * Get Shopify access token
     */
    public function get_access_token() {
        return $this->get('shopify_access_token');
    }
    
    /**
     * Check if Shopify is configured
     */
    public function is_configured() {
        $store_url = $this->get_store_url();
        $access_token = $this->get_access_token();
        
        return !empty($store_url) && !empty($access_token);
    }
    
    /**
     * Validate store URL format
     */
    public function validate_store_url($url) {
        return preg_match('/^https:\/\/[a-zA-Z0-9\-]+\.myshopify\.com\/?$/', trim($url));
    }
    
    /**
     * Get all settings
     */
    public function get_all_settings() {
        return array(
            'store_url' => $this->get_store_url(),
            'access_token' => $this->get_access_token(),
            'config_method' => $this->get_config_method(),
            'is_configured' => $this->is_configured(),
            'is_using_constants' => $this->is_using_constants()
        );
    }
    
    /**
     * Get default settings
     */
    public function get_default_settings() {
        return array(
            'shopify_store_url' => '',
            'shopify_access_token' => ''
        );
    }
}
