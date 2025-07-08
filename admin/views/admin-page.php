<?php
/**
 * Admin page template
 *
 * @package WooCommerce_Shopify_Integration
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('WooCommerce Shopify Integration', 'woocommerce-shopify-integration'); ?></h1>
    
    <?php if ($is_using_constants): ?>
        <div class="notice notice-info">
            <p>
                <strong><?php _e('Configuration Method:', 'woocommerce-shopify-integration'); ?></strong>
                🔒 <strong><?php _e('PHP Constants (Secure)', 'woocommerce-shopify-integration'); ?></strong>
            </p>
        </div>
    <?php else: ?>
        <div class="notice notice-info">
            <p>
                <strong><?php _e('Configuration Method:', 'woocommerce-shopify-integration'); ?></strong>
                ⚙️ <strong><?php _e('WordPress Options', 'woocommerce-shopify-integration'); ?></strong>
            </p>
        </div>
    <?php endif; ?>
    
    <div class="wsi-admin-content">
        <div class="wsi-main-section">
            <div class="card">
                <h2 class="title"><?php _e('Shopify Configuration', 'woocommerce-shopify-integration'); ?></h2>
                
                <form method="post" action="options.php">
                    <?php
                    settings_fields('wsi_shopify_integration');
                    do_settings_sections('wsi_shopify_integration');
                    ?>
                    
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="wsi_shopify_store_url"><?php _e('Shopify Store URL', 'woocommerce-shopify-integration'); ?></label>
                                </th>
                                <td>
                                    <input type="url" 
                                           id="wsi_shopify_store_url"
                                           name="wsi_shopify_store_url" 
                                           value="<?php echo esc_attr($settings['store_url']); ?>" 
                                           placeholder="https://your-store.myshopify.com" 
                                           class="regular-text"
                                           <?php echo $is_using_constants ? 'readonly' : ''; ?> />
                                    <p class="description">
                                        <?php _e('Your Shopify store URL (e.g., https://your-store.myshopify.com)', 'woocommerce-shopify-integration'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="wsi_shopify_access_token"><?php _e('Shopify Access Token', 'woocommerce-shopify-integration'); ?></label>
                                </th>
                                <td>
                                    <input type="password" 
                                           id="wsi_shopify_access_token"
                                           name="wsi_shopify_access_token" 
                                           value="<?php echo esc_attr($settings['access_token']); ?>" 
                                           class="regular-text"
                                           <?php echo $is_using_constants ? 'readonly' : ''; ?> />
                                    <p class="description">
                                        <?php _e('Your Shopify private app access token', 'woocommerce-shopify-integration'); ?>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <?php if (!$is_using_constants): ?>
                        <?php submit_button(); ?>
                    <?php else: ?>
                        <div class="notice notice-warning inline">
                            <p><?php _e('Configuration is managed via PHP constants. To modify settings, update your wp-config.php file.', 'woocommerce-shopify-integration'); ?></p>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
            
            <?php if ($settings['is_configured']): ?>
            <div class="card">
                <h2 class="title"><?php _e('Connection Tests', 'woocommerce-shopify-integration'); ?></h2>
                
                <div class="wsi-test-section">
                    <p><?php _e('Test your Shopify connection and API access:', 'woocommerce-shopify-integration'); ?></p>
                    
                    <div class="wsi-test-buttons">
                        <button type="button" id="test-connection" class="button button-secondary">
                            <?php _e('Test Connection', 'woocommerce-shopify-integration'); ?>
                        </button>
                        <button type="button" id="test-draft-orders" class="button button-secondary">
                            <?php _e('Test Draft Orders API', 'woocommerce-shopify-integration'); ?>
                        </button>
                    </div>
                    
                    <div id="test-results" class="wsi-test-results"></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="wsi-sidebar">
            <div class="card">
                <h2 class="title"><?php _e('Status', 'woocommerce-shopify-integration'); ?></h2>
                
                <div class="wsi-status-item">
                    <span class="label"><?php _e('Plugin Version:', 'woocommerce-shopify-integration'); ?></span>
                    <span class="value"><?php echo esc_html(WSI_PLUGIN_VERSION); ?></span>
                </div>
                
                <div class="wsi-status-item">
                    <span class="label"><?php _e('Configuration:', 'woocommerce-shopify-integration'); ?></span>
                    <span class="value <?php echo $settings['is_configured'] ? 'status-ok' : 'status-error'; ?>">
                        <?php echo $settings['is_configured'] ? '✅ ' . __('Configured', 'woocommerce-shopify-integration') : '❌ ' . __('Not Configured', 'woocommerce-shopify-integration'); ?>
                    </span>
                </div>
                
                <div class="wsi-status-item">
                    <span class="label"><?php _e('WooCommerce:', 'woocommerce-shopify-integration'); ?></span>
                    <span class="value status-ok">
                        <?php echo class_exists('WooCommerce') ? '✅ ' . __('Active', 'woocommerce-shopify-integration') : '❌ ' . __('Inactive', 'woocommerce-shopify-integration'); ?>
                    </span>
                </div>
                
                <div class="wsi-status-item">
                    <span class="label"><?php _e('PHP Version:', 'woocommerce-shopify-integration'); ?></span>
                    <span class="value"><?php echo esc_html(PHP_VERSION); ?></span>
                </div>
            </div>
            
            <div class="card">
                <h2 class="title"><?php _e('Documentation', 'woocommerce-shopify-integration'); ?></h2>
                
                <p><?php _e('Need help setting up the integration?', 'woocommerce-shopify-integration'); ?></p>
                
                <ul>
                    <li><a href="#" target="_blank"><?php _e('Setup Guide', 'woocommerce-shopify-integration'); ?></a></li>
                    <li><a href="#" target="_blank"><?php _e('Troubleshooting', 'woocommerce-shopify-integration'); ?></a></li>
                    <li><a href="#" target="_blank"><?php _e('Support', 'woocommerce-shopify-integration'); ?></a></li>
                </ul>
            </div>
        </div>
    </div>
</div>
