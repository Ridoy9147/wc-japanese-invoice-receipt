<?php
/**
 * Customer My Account Integration for WooCommerce Japanese Invoice & Receipt
 *
 * Allows customers to download / print their Japanese invoice & receipt from My Account.
 *
 * @package WC_Japanese_Invoice_Receipt
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_JIR_MyAccount_Hooks {

    public static function init() {
        if ( 'yes' !== get_option( 'wc_jir_allow_customer_print', 'yes' ) ) {
            return;
        }

        // Add print button on My Account > View Order page
        add_action( 'woocommerce_order_details_after_order_table', array( __CLASS__, 'render_view_order_print_button' ), 20 );

        // Add action button in Orders list table
        add_filter( 'woocommerce_my_account_my_orders_actions', array( __CLASS__, 'add_my_orders_action' ), 10, 2 );
    }

    /**
     * Add print button on View Order page.
     */
    public static function render_view_order_print_button( $order ) {
        if ( ! $order ) {
            return;
        }

        $print_url = WC_JIR_Admin_Hooks::get_print_url( $order->get_id(), false );
        ?>
        <div class="wc-jir-customer-print-wrap" style="margin: 20px 0; padding: 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
            <div>
                <strong style="display:block; font-size: 14px; color: #0f172a;">納品書・領収書の発行 (Invoice &amp; Receipt)</strong>
                <span style="font-size: 12px; color: #64748b;">適格請求書（インボイス制度）に対応した納品書兼領収書を印刷・PDF保存できます。</span>
            </div>
            <a href="<?php echo esc_url( $print_url ); ?>" target="_blank" class="button" style="font-weight: 700; border-radius: 6px; padding: 8px 18px;">
                📄 納品書・領収書を印刷 (Print PDF)
            </a>
        </div>
        <?php
    }

    /**
     * Add print action to My Account > Orders table.
     */
    public static function add_my_orders_action( $actions, $order ) {
        if ( $order ) {
            $actions['wc_jir_print'] = array(
                'url'  => WC_JIR_Admin_Hooks::get_print_url( $order->get_id(), false ),
                'name' => __( '納品書印刷', 'wc-japanese-invoice-receipt' ),
            );
        }
        return $actions;
    }
}
