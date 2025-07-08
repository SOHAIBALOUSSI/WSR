<?php
/**
 * Plugin activator class
 *
 * @package WooCommerce_Shopify_Integration
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Activator class
 */
class WSI_Activator {
    
    /**
     * Activate the plugin
     */
    public static function activate() {
        // Create database tables
        self::create_tables();
        
        // Create asset files
        self::create_assets();
        
        // Add plugin capabilities
        self::add_capabilities();
        
        // Set default options
        self::set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create database tables
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table for logs
        $table_name = $wpdb->prefix . 'wsi_logs';
        
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            action varchar(50) NOT NULL,
            status varchar(20) NOT NULL,
            message text,
            user_id int(11),
            ip_address varchar(45),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY action (action),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create asset files
     */
    private static function create_assets() {
        $js_dir = WSI_PLUGIN_DIR . 'assets/js/';
        $css_dir = WSI_PLUGIN_DIR . 'assets/css/';
        
        // Create directories if they don't exist
        if (!file_exists($js_dir)) {
            wp_mkdir_p($js_dir);
        }
        if (!file_exists($css_dir)) {
            wp_mkdir_p($css_dir);
        }
        
        // Create frontend JavaScript
        self::create_frontend_js($js_dir);
        
        // Create admin JavaScript
        self::create_admin_js($js_dir);
        
        // Create frontend CSS
        self::create_frontend_css($css_dir);
        
        // Create admin CSS
        self::create_admin_css($css_dir);
    }
    
    /**
     * Create frontend JavaScript file
     */
    private static function create_frontend_js($dir) {
        $file = $dir . 'frontend.js';
        
        $content = <<<'JS'
jQuery(document).ready(function($) {
    console.log('WSI: Shopify integration loaded');
    
    if (!wsi_ajax.shopify_configured) {
        console.log('WSI: Shopify not configured');
        return;
    }
    
    function createShopifyOrder() {
        if ($('#wsi-processing').length > 0) {
            return;
        }
        
        // Show loading overlay
        $('body').append('<div id="wsi-processing" class="wsi-overlay"><div class="wsi-spinner"></div><div class="wsi-message">' + wsi_ajax.messages.loading + '</div></div>');
        
        $.ajax({
            url: wsi_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wsi_create_draft_order',
                nonce: wsi_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data.invoice_url) {
                    $('#wsi-processing .wsi-message').text(wsi_ajax.messages.success + ' ' + wsi_ajax.messages.redirect);
                    setTimeout(function() {
                        window.location.href = response.data.invoice_url;
                    }, 1000);
                } else {
                    $('#wsi-processing').remove();
                    alert(wsi_ajax.messages.error + ' ' + (response.data || 'Unknown error'));
                }
            },
            error: function() {
                $('#wsi-processing').remove();
                alert(wsi_ajax.messages.error);
            }
        });
    }
    
    function isCheckoutButton($element) {
        var text = $element.text().toLowerCase();
        var href = $element.attr('href') || '';
        var classes = $element.attr('class') || '';
        
        return href.indexOf('/checkout') !== -1 || 
               text.indexOf('checkout') !== -1 ||
               text.indexOf('place order') !== -1 ||
               classes.indexOf('checkout') !== -1 ||
               $element.hasClass('checkout-button') ||
               $element.hasClass('wc-forward');
    }
    
    // Intercept checkout buttons
    $(document).on('click', 'a, button, input[type="submit"]', function(e) {
        if (isCheckoutButton($(this))) {
            e.preventDefault();
            e.stopPropagation();
            createShopifyOrder();
            return false;
        }
    });
    
    // Watch for dynamically added content
    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                $(mutation.addedNodes).find('a, button, input[type="submit"]').each(function() {
                    if (isCheckoutButton($(this))) {
                        console.log('WSI: New checkout button detected');
                    }
                });
            }
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});
JS;
        
        file_put_contents($file, $content);
    }
    
    /**
     * Create admin JavaScript file
     */
    private static function create_admin_js($dir) {
        $file = $dir . 'admin.js';
        
        $content = <<<'JS'
jQuery(document).ready(function($) {
    // Test connection button
    $('#test-connection').on('click', function() {
        testEndpoint('wsi_test_connection', $(this), 'Connection successful!', 'Connection failed.');
    });
    
    // Test draft orders button
    $('#test-draft-orders').on('click', function() {
        testEndpoint('wsi_test_draft_orders', $(this), 'Draft Orders API accessible!', 'Draft Orders API access failed.');
    });
    
    function testEndpoint(action, $button, successMsg, errorMsg) {
        var $results = $('#test-results');
        var originalText = $button.text();
        
        $button.prop('disabled', true).text(wsi_admin_ajax.messages.testing);
        $results.html('<div class="wsi-testing">Testing...</div>');
        
        $.ajax({
            url: wsi_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: action,
                nonce: wsi_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $results.html('<div class="wsi-success">' + successMsg + '</div><pre>' + JSON.stringify(response.data, null, 2) + '</pre>');
                } else {
                    $results.html('<div class="wsi-error">' + errorMsg + ' ' + response.data + '</div>');
                }
            },
            error: function() {
                $results.html('<div class="wsi-error">' + errorMsg + '</div>');
            },
            complete: function() {
                $button.prop('disabled', false).text(originalText);
            }
        });
    }
});
JS;
        
        file_put_contents($file, $content);
    }
    
    /**
     * Create frontend CSS file
     */
    private static function create_frontend_css($dir) {
        $file = $dir . 'frontend.css';
        
        $content = <<<'CSS'
.wsi-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    z-index: 999999;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: white;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.wsi-spinner {
    border: 4px solid rgba(255, 255, 255, 0.3);
    border-top: 4px solid #00d4aa;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: wsi-spin 1s linear infinite;
    margin-bottom: 20px;
}

.wsi-message {
    font-size: 18px;
    text-align: center;
}

@keyframes wsi-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.woocommerce-info.wsi-notice,
.woocommerce-message.wsi-notice {
    border-left-color: #00d4aa !important;
    background: #f0fffe !important;
}

.wsi-notice strong {
    color: #00a085;
}
CSS;
        
        file_put_contents($file, $content);
    }
    
    /**
     * Create admin CSS file
     */
    private static function create_admin_css($dir) {
        $file = $dir . 'admin.css';
        
        $content = <<<'CSS'
.wsi-admin-content {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
    margin-top: 20px;
}

.wsi-main-section .card,
.wsi-sidebar .card {
    background: white;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    margin-bottom: 20px;
    padding: 0;
}

.wsi-main-section .card .title,
.wsi-sidebar .card .title {
    font-size: 14px;
    margin: 0;
    padding: 12px 20px;
    border-bottom: 1px solid #eee;
    background: #f9f9f9;
}

.wsi-main-section .card > *:not(.title),
.wsi-sidebar .card > *:not(.title) {
    padding: 20px;
}

.wsi-test-section {
    padding: 20px;
}

.wsi-test-buttons {
    margin: 15px 0;
}

.wsi-test-buttons .button {
    margin-right: 10px;
}

.wsi-test-results {
    margin-top: 15px;
    padding: 10px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
    min-height: 40px;
}

.wsi-testing {
    color: #666;
    font-style: italic;
}

.wsi-success {
    color: #008000;
    font-weight: bold;
}

.wsi-error {
    color: #d63638;
    font-weight: bold;
}

.wsi-status-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.wsi-status-item:last-child {
    border-bottom: none;
}

.wsi-status-item .label {
    font-weight: 500;
}

.wsi-status-item .value.status-ok {
    color: #008000;
}

.wsi-status-item .value.status-error {
    color: #d63638;
}

input[readonly] {
    background-color: #f1f1f1 !important;
    color: #666 !important;
}

@media (max-width: 782px) {
    .wsi-admin-content {
        grid-template-columns: 1fr;
    }
}
CSS;
        
        file_put_contents($file, $content);
    }
    
    /**
     * Add plugin capabilities
     */
    private static function add_capabilities() {
        $role = get_role('administrator');
        if ($role && !$role->has_cap('manage_shopify_integration')) {
            $role->add_cap('manage_shopify_integration');
        }
        
        $shop_manager = get_role('shop_manager');
        if ($shop_manager && !$shop_manager->has_cap('manage_shopify_integration')) {
            $shop_manager->add_cap('manage_shopify_integration');
        }
    }
    
    /**
     * Set default options
     */
    private static function set_default_options() {
        $defaults = array(
            'wsi_shopify_store_url' => '',
            'wsi_shopify_access_token' => ''
        );
        
        foreach ($defaults as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
    }
}
