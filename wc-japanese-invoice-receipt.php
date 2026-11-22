<?php
/**
 * Plugin Name: WooCommerce Japanese Invoice & Receipt (納品書・領収書印刷)
 * Plugin URI: https://github.com/Ridoy9147/wc-japanese-invoice-receipt
 * Description: A lightweight, elegant Japanese A4 printable & PDF invoice and receipt system (納品書兼領収書) for WooCommerce orders. Compliant with the Japanese Qualified Invoice System (インボイス制度 - 適格請求書), featuring dual-copy (店舗控え & お客様控え) with cut line, dynamic logo watermarking, sticky print toolbar, bulk printing, and zero external PDF dependencies.
 * Version: 1.0.0
 * Author: Developer Hridoy
 * Author URI: https://github.com/Ridoy9147
 * Text Domain: wc-japanese-invoice-receipt
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 9.3
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WC_JIR_VERSION', '1.0.0' );
define( 'WC_JIR_FILE', __FILE__ );
define( 'WC_JIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'WC_JIR_URL', plugin_dir_url( __FILE__ ) );

/**
 * Declare High-Performance Order Storage (HPOS) compatibility.
 */
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );

/**
 * Check if WooCommerce is active before initializing.
 */
function wc_jir_init() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'wc_jir_woocommerce_missing_notice' );
        return;
    }

    require_once WC_JIR_PATH . 'includes/class-settings.php';
    require_once WC_JIR_PATH . 'includes/class-invoice-renderer.php';
    require_once WC_JIR_PATH . 'includes/class-admin-hooks.php';
    require_once WC_JIR_PATH . 'includes/class-myaccount-hooks.php';

    WC_JIR_Settings::init();
    WC_JIR_Admin_Hooks::init();
    WC_JIR_MyAccount_Hooks::init();
}
add_action( 'plugins_loaded', 'wc_jir_init' );

/**
 * WooCommerce missing admin notice.
 */
function wc_jir_woocommerce_missing_notice() {
    ?>
    <div class="notice notice-error is-dismissible">
        <p><strong><?php esc_html_e( 'WooCommerce Japanese Invoice & Receipt requires WooCommerce to be installed and active.', 'wc-japanese-invoice-receipt' ); ?></strong></p>
    </div>
    <?php
}

/**
 * Plugin action links.
 */
function wc_jir_plugin_action_links( $links ) {
    $settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=wc_jir_settings' ) ) . '">' . esc_html__( 'Settings', 'wc-japanese-invoice-receipt' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_jir_plugin_action_links' );
