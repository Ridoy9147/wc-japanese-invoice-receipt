<?php
/**
 * Admin Hooks & Integration for WooCommerce Japanese Invoice & Receipt
 *
 * Adds Order list actions, Meta Box on Order edit screen, and Bulk actions.
 *
 * @package WC_Japanese_Invoice_Receipt
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_JIR_Admin_Hooks {

    public static function init() {
        // Admin-post print handlers
        add_action( 'admin_post_wc_jir_print_invoice', array( __CLASS__, 'handle_print_single_invoice' ) );
        add_action( 'admin_post_wc_jir_bulk_print_invoice', array( __CLASS__, 'handle_print_bulk_invoices' ) );

        // Order actions column in Orders table (Legacy + HPOS)
        add_filter( 'woocommerce_admin_order_actions', array( __CLASS__, 'add_order_action_button' ), 20, 2 );
        add_action( 'admin_head', array( __CLASS__, 'admin_action_icon_style' ) );

        // Meta Box on single order screen (Legacy + HPOS)
        add_action( 'add_meta_boxes', array( __CLASS__, 'add_order_meta_box' ), 25, 2 );

        // Bulk Actions in Orders table (Legacy CPT + HPOS)
        add_filter( 'bulk_actions-edit-shop_order', array( __CLASS__, 'add_bulk_action' ) );
        add_filter( 'bulk_actions-woocommerce_page_wc-orders', array( __CLASS__, 'add_bulk_action' ) );
        add_filter( 'handle_bulk_actions-edit-shop_order', array( __CLASS__, 'handle_bulk_action' ), 10, 3 );
        add_filter( 'handle_bulk_actions-woocommerce_page_wc-orders', array( __CLASS__, 'handle_bulk_action' ), 10, 3 );
    }

    /**
     * Build the secure URL for printing a single order's invoice.
     */
    public static function get_print_url( $order_id, $auto_print = false ) {
        $args = array(
            'action'   => 'wc_jir_print_invoice',
            'order_id' => (int) $order_id,
        );
        if ( $auto_print ) {
            $args['auto'] = 1;
        }
        $url = add_query_arg( $args, admin_url( 'admin-post.php' ) );
        return wp_nonce_url( $url, 'wc_jir_print_' . (int) $order_id );
    }

    /**
     * Build the secure URL for printing multiple orders in bulk.
     */
    public static function get_bulk_print_url( array $order_ids ) {
        $clean_ids = array_map( 'intval', $order_ids );
        $url = add_query_arg( array(
            'action'    => 'wc_jir_bulk_print_invoice',
            'order_ids' => implode( ',', $clean_ids ),
        ), admin_url( 'admin-post.php' ) );
        return wp_nonce_url( $url, 'wc_jir_bulk_print' );
    }

    /**
     * Handle single order print request.
     */
    public static function handle_print_single_invoice() {
        $order_id = isset( $_GET['order_id'] ) ? (int) $_GET['order_id'] : 0;
        if ( ! $order_id || ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'wc_jir_print_' . $order_id ) ) {
            wp_die( esc_html__( '無効または期限切れの印刷リンクです。(Invalid or expired link)', 'wc-japanese-invoice-receipt' ) );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_die( esc_html__( '注文が見つかりませんでした。(Order not found)', 'wc-japanese-invoice-receipt' ) );
        }

        // Check permission: Shop manager/Admin can view any, Customer can view their own
        $can_view = current_user_can( 'manage_woocommerce' );
        if ( ! $can_view && is_user_logged_in() ) {
            $can_view = ( (int) $order->get_customer_id() === get_current_user_id() );
        }

        if ( ! $can_view ) {
            wp_die( esc_html__( 'この納品書を閲覧する権限がありません。(Permission denied)', 'wc-japanese-invoice-receipt' ) );
        }

        $auto = ! empty( $_GET['auto'] );
        WC_JIR_Invoice_Renderer::render_single_order( $order, $auto );
    }

    /**
     * Handle bulk order print request.
     */
    public static function handle_print_bulk_invoices() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( '権限がありません。(Permission denied)', 'wc-japanese-invoice-receipt' ) );
        }

        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'wc_jir_bulk_print' ) ) {
            wp_die( esc_html__( '無効なリクエストです。(Invalid request)', 'wc-japanese-invoice-receipt' ) );
        }

        $raw_ids = isset( $_GET['order_ids'] ) ? sanitize_text_field( wp_unslash( $_GET['order_ids'] ) ) : '';
        $ids     = array_filter( array_map( 'intval', explode( ',', $raw_ids ) ) );

        if ( empty( $ids ) ) {
            wp_die( esc_html__( '印刷対象の注文が選択されていません。(No orders selected)', 'wc-japanese-invoice-receipt' ) );
        }

        $orders = array();
        foreach ( $ids as $oid ) {
            $ord = wc_get_order( $oid );
            if ( $ord ) {
                $orders[] = $ord;
            }
        }

        WC_JIR_Invoice_Renderer::render_orders( $orders, false );
    }

    /**
     * Add print icon button in WooCommerce Orders table.
     */
    public static function add_order_action_button( $actions, $order ) {
        $actions['wc_jir_print'] = array(
            'url'    => self::get_print_url( $order->get_id(), false ),
            'name'   => __( '納品書・領収書印刷 (Print Invoice)', 'wc-japanese-invoice-receipt' ),
            'action' => 'wc-jir-print-icon',
        );
        return $actions;
    }

    /**
     * CSS style for the print icon in the WooCommerce Orders action column.
     */
    public static function admin_action_icon_style() {
        ?>
        <style>
            .wc-action-button-wc-jir-print-icon::after {
                font-family: dashicons !important;
                content: "\f498" !important; /* Dashicons printer */
                color: #dc2626 !important;
            }
        </style>
        <?php
    }

    /**
     * Register Order Meta Box (Works on HPOS and classic CPT).
     */
    public static function add_order_meta_box( $post_type, $post_or_order ) {
        $screen = class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' )
            && wc_get_container()->get( \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
            ? wc_get_page_screen_id( 'shop-order' )
            : 'shop_order';

        add_meta_box(
            'wc_jir_order_invoice_box',
            __( '📄 納品書・領収書 (Japanese Invoice)', 'wc-japanese-invoice-receipt' ),
            array( __CLASS__, 'render_order_meta_box' ),
            $screen,
            'side',
            'high'
        );
    }

    /**
     * Render Order Meta Box contents.
     */
    public static function render_order_meta_box( $post_or_order ) {
        $order = ( $post_or_order instanceof WC_Order ) ? $post_or_order : wc_get_order( $post_or_order->ID );
        if ( ! $order ) {
            return;
        }

        $order_id   = $order->get_id();
        $print_url  = self::get_print_url( $order_id, false );
        $auto_url   = self::get_print_url( $order_id, true );
        $inv_number = get_option( 'wc_jir_invoice_number', '' );
        ?>
        <div style="padding: 6px 0;">
            <p style="margin: 0 0 10px 0; color: #475569; font-size: 12.5px; line-height: 1.5;">
                <?php esc_html_e( 'A4対応の納品書兼領収書（店舗控え・お客様控え）をワンクリックで印刷・PDF保存できます。', 'wc-japanese-invoice-receipt' ); ?>
            </p>
            <?php if ( $inv_number ) : ?>
                <div style="background:#f1f5f9; padding:4px 8px; border-radius:4px; font-size:11px; margin-bottom:12px; color:#334155;">
                    <strong>登録番号:</strong> <?php echo esc_html( $inv_number ); ?>
                </div>
            <?php endif; ?>
            <div style="display:flex; flex-direction:column; gap:8px;">
                <a href="<?php echo esc_url( $print_url ); ?>" target="_blank" class="button button-primary" style="text-align:center; font-weight:700; background:#ef4444; border-color:#ef4444;">
                    <span class="dashicons dashicons-printer" style="vertical-align:middle; margin-top:-2px;"></span>
                    <?php esc_html_e( '納品書・領収書を表示 (View & Print)', 'wc-japanese-invoice-receipt' ); ?>
                </a>
                <a href="<?php echo esc_url( $auto_url ); ?>" target="_blank" class="button" style="text-align:center; font-weight:700;">
                    <span class="dashicons dashicons-pdf" style="vertical-align:middle; margin-top:-2px;"></span>
                    <?php esc_html_e( '直接PDF印刷 (Auto Print PDF)', 'wc-japanese-invoice-receipt' ); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Add bulk action to Orders list.
     */
    public static function add_bulk_action( $actions ) {
        $actions['wc_jir_bulk_print'] = __( '🖨️ 一括納品書印刷 (Bulk Print Invoices)', 'wc-japanese-invoice-receipt' );
        return $actions;
    }

    /**
     * Handle bulk action redirect to printable page.
     */
    public static function handle_bulk_action( $redirect_to, $action, $order_ids ) {
        if ( 'wc_jir_bulk_print' === $action && ! empty( $order_ids ) ) {
            return self::get_bulk_print_url( $order_ids );
        }
        return $redirect_to;
    }
}
