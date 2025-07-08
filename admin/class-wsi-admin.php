<?php
/**
 * Admin functionality class
 *
 * @package WooCommerce_Shopify_Integration
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin class
 */
class WSI_Admin {
    
    /**
     * Instance
     * @var WSI_Admin
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_notices', array($this, 'admin_notices'));
        
        // AJAX handlers
        add_action('wp_ajax_wsi_test_connection', array($this, 'ajax_test_connection'));
        add_action('wp_ajax_wsi_test_draft_orders', array($this, 'ajax_test_draft_orders'));
        
        // Plugin action links
        add_filter('plugin_action_links_' . WSI_PLUGIN_BASENAME, array($this, 'plugin_action_links'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('Shopify Integration', 'woocommerce-shopify-integration'),
            __('Shopify Integration', 'woocommerce-shopify-integration'),
            'manage_options',
            'wsi-shopify-integration',
            array($this, 'admin_page')
        );
        
        // Also add to WooCommerce menu if available
        if (class_exists('WooCommerce')) {
            add_submenu_page(
                'woocommerce',
                __('Shopify Integration', 'woocommerce-shopify-integration'),
                __('Shopify Integration', 'woocommerce-shopify-integration'),
                'manage_options',
                'wsi-shopify-integration-woo',
                array($this, 'admin_page')
            );
        }
    }
    
    /**
     * Admin init
     */
    public function admin_init() {
        register_setting('wsi_shopify_integration', 'wsi_shopify_store_url', array(
            'sanitize_callback' => array($this, 'sanitize_store_url')
        ));
        register_setting('wsi_shopify_integration', 'wsi_shopify_access_token', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
    }
    
    /**
     * Sanitize store URL
     */
    public function sanitize_store_url($url) {
        $url = sanitize_url($url);
        
        if (!$this->config->validate_store_url($url)) {
            add_settings_error(
                'wsi_shopify_store_url',
                'invalid_url',
                __('Please enter a valid Shopify store URL (e.g., https://your-store.myshopify.com)', 'woocommerce-shopify-integration')
            );
        }
        
        return $url;
    }
    
    /**
     * Admin notices
     */
    public function admin_notices() {
        // Show configuration notice if not configured
        if (!$this->config->is_configured() && $this->should_show_notice()) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong><?php _e('Shopify Integration:', 'woocommerce-shopify-integration'); ?></strong>
                    <?php _e('Please configure your Shopify connection to enable secure payments.', 'woocommerce-shopify-integration'); ?>
                    <a href="<?php echo esc_url(admin_url('options-general.php?page=wsi-shopify-integration')); ?>">
                        <?php _e('Configure now', 'woocommerce-shopify-integration'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Check if we should show admin notice
     */
    private function should_show_notice() {
        $screen = get_current_screen();
        
        // Show on WooCommerce pages and dashboard
        $show_on_screens = array('dashboard', 'woocommerce_page_wc-admin', 'woocommerce_page_wc-settings');
        
        return in_array($screen->id, $show_on_screens) || strpos($screen->id, 'woocommerce') !== false;
    }
    
    /**
     * Plugin action links
     */
    public function plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=wsi-shopify-integration') . '">' . __('Settings', 'woocommerce-shopify-integration') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'woocommerce-shopify-integration'));
        }
        
        $is_using_constants = $this->config->is_using_constants();
        $settings = $this->config->get_all_settings();
        
        // Include admin page template
        include WSI_PLUGIN_DIR . 'admin/views/admin-page.php';
    }
    
    /**
     * AJAX test connection
     */
    public function ajax_test_connection() {
        check_ajax_referer('wsi_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'woocommerce-shopify-integration'));
        }
        
        $result = $this->api->test_connection();
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX test draft orders
     */
    public function ajax_test_draft_orders() {
        check_ajax_referer('wsi_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'woocommerce-shopify-integration'));
        }
        
        $result = $this->api->test_draft_orders_access();
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success($result);
    }
}
