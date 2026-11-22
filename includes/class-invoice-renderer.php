<?php
/**
 * Invoice HTML & Print Renderer for WooCommerce Japanese Invoice & Receipt
 *
 * Generates an A4 print-optimized Japanese invoice / receipt with dual-copy or single-copy layout.
 *
 * @package WC_Japanese_Invoice_Receipt
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_JIR_Invoice_Renderer {

    /**
     * Render full printable HTML page for a single order.
     *
     * @param WC_Order $order
     * @param bool     $auto_print
     */
    public static function render_single_order( $order, $auto_print = false ) {
        self::render_orders( array( $order ), $auto_print );
    }

    /**
     * Render full printable HTML page for one or multiple orders.
     *
     * @param array $orders Array of WC_Order objects.
     * @param bool  $auto_print
     */
    public static function render_orders( $orders, $auto_print = false ) {
        if ( empty( $orders ) ) {
            wp_die( esc_html__( '注文が見つかりませんでした。(Order not found)', 'wc-japanese-invoice-receipt' ) );
        }

        $logo_url          = WC_JIR_Settings::get_logo_url();
        $watermark_url     = WC_JIR_Settings::get_watermark_url();
        $watermark_opacity = get_option( 'wc_jir_watermark_opacity', '0.05' );
        $watermark_enabled = ( 'yes' === get_option( 'wc_jir_enable_watermark', 'yes' ) && ! empty( $watermark_url ) );
        $doc_title         = get_option( 'wc_jir_doc_title', '納品書 兼 領収書' );
        $layout_opt        = get_option( 'wc_jir_copy_layout', 'dual' );
        $show_cut          = ( 'yes' === get_option( 'wc_jir_show_cut_line', 'yes' ) );
        $auto              = $auto_print || ( 'yes' === get_option( 'wc_jir_auto_open_print', 'no' ) );

        $first_order = reset( $orders );
        $page_title  = $doc_title . ' #' . $first_order->get_order_number();
        if ( count( $orders ) > 1 ) {
            $page_title .= ' 他' . ( count( $orders ) - 1 ) . '件';
        }
        ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html( $page_title ); ?></title>
    <style>
        :root {
            --inv-red: #ef4444;
            --inv-red-d: #dc2626;
            --inv-blue: #1d4ed8;
            --inv-green: #15803d;
            --inv-ink: #0f172a;
            --inv-muted: #64748b;
            --inv-line: #e2e8f0;
            --inv-bg-alt: #f8fafc;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #eef2f6;
            color: var(--inv-ink);
            font-family: "Hiragino Kaku Gothic ProN", "Hiragino Sans", Meiryo, "Yu Gothic", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            font-size: 11.5px;
            line-height: 1.5;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Sticky Screen Toolbar */
        .inv-toolbar {
            position: sticky;
            top: 0;
            z-index: 9999;
            background: #0f172a;
            padding: 12px 16px;
            display: flex;
            gap: 12px;
            justify-content: center;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0,0,0,.15);
        }
        .inv-toolbar button {
            border: none;
            border-radius: 8px;
            padding: 10px 24px;
            font-size: 13.5px;
            font-weight: 800;
            cursor: pointer;
            transition: all .15s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .inv-btn-print {
            background: var(--inv-red);
            color: #ffffff;
        }
        .inv-btn-print:hover { background: var(--inv-red-d); }
        .inv-btn-close {
            background: #334155;
            color: #ffffff;
        }
        .inv-btn-close:hover { background: #475569; }

        /* A4 Sheet Container */
        .inv-sheet {
            max-width: 194mm;
            min-height: 275mm;
            margin: 16px auto;
            background: #ffffff;
            padding: 10mm 11mm;
            box-shadow: 0 6px 24px rgba(0,0,0,.1);
            position: relative;
        }

        /* Multi-order page break */
        .inv-order-wrapper {
            position: relative;
        }
        .inv-order-wrapper + .inv-order-wrapper {
            margin-top: 30px;
        }

        /* Cut Line */
        .inv-cut {
            border: none;
            border-top: 1.5px dashed #94a3b8;
            margin: 7mm 0;
            text-align: center;
        }
        .inv-cut span {
            position: relative;
            top: -9px;
            background: #ffffff;
            padding: 0 12px;
            color: #94a3b8;
            font-size: 9.5px;
            letter-spacing: 2px;
        }

        /* Invoice Copy Block */
        .inv-copy {
            position: relative;
            z-index: 0;
            overflow: hidden;
            padding: 1mm 0;
        }

        <?php if ( $watermark_enabled && ! empty( $watermark_url ) ) : ?>
        .inv-copy::before {
            content: "";
            position: absolute;
            top: 52%;
            left: 50%;
            width: 120mm;
            height: 120mm;
            transform: translate(-50%, -50%);
            background: url('<?php echo esc_url( $watermark_url ); ?>') center / contain no-repeat;
            opacity: <?php echo esc_attr( $watermark_opacity ); ?>;
            z-index: -1;
            pointer-events: none;
        }
        <?php endif; ?>

        /* Header Band */
        .inv-band {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            padding-bottom: 9px;
            border-bottom: 2.5px solid var(--inv-red);
        }
        .inv-band-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .inv-logo {
            max-height: 44px;
            max-width: 150px;
            object-fit: contain;
            flex: none;
        }
        .inv-shop-name {
            font-size: 15px;
            font-weight: 900;
            letter-spacing: .3px;
            color: var(--inv-ink);
        }
        .inv-shop-line {
            font-size: 9.5px;
            color: #475569;
            line-height: 1.4;
        }
        .inv-license {
            font-size: 9.5px;
            color: var(--inv-red-d);
            font-weight: 700;
            margin-top: 2px;
        }
        .inv-band-right {
            text-align: right;
        }
        .inv-doc-title {
            font-size: 21px;
            font-weight: 900;
            letter-spacing: 3px;
            color: var(--inv-ink);
            line-height: 1.1;
        }
        .inv-doc-sub {
            font-size: 8.5px;
            letter-spacing: 2.5px;
            color: var(--inv-muted);
            font-weight: 700;
            margin-top: 2px;
        }

        /* Ribbon */
        .inv-ribbon {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
            flex-wrap: wrap;
        }
        .inv-copy-pill {
            font-weight: 800;
            font-size: 11px;
            color: #ffffff;
            padding: 3px 12px;
            border-radius: 999px;
            letter-spacing: 1px;
        }
        .inv-copy-shop .inv-copy-pill { background: var(--inv-blue); }
        .inv-copy-customer .inv-copy-pill { background: var(--inv-green); }
        .inv-copy-single .inv-copy-pill { background: var(--inv-ink); }

        .inv-ribbon-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .inv-chip {
            font-size: 10.5px;
            color: #334155;
            background: #f1f5f9;
            border: 1px solid var(--inv-line);
            border-radius: 6px;
            padding: 2px 8px;
        }
        .inv-chip em {
            font-style: normal;
            color: #94a3b8;
            margin-right: 4px;
        }
        .inv-chip b {
            color: var(--inv-ink);
        }
        .inv-status {
            font-size: 10.5px;
            font-weight: 800;
            padding: 2px 10px;
            border-radius: 999px;
            background: #dcfce7;
            color: #15803d;
        }

        /* Cards Grid (Billing & Shipping) */
        .inv-grid {
            display: flex;
            gap: 8px;
            margin-top: 10px;
        }
        .inv-card {
            flex: 1;
            border: 1px solid var(--inv-line);
            border-radius: 8px;
            overflow: hidden;
            background: #ffffff;
        }
        .inv-card h4 {
            margin: 0;
            font-size: 10.5px;
            font-weight: 800;
            color: #ffffff;
            background: var(--inv-ink);
            padding: 5px 8px;
            letter-spacing: .5px;
        }
        table.inv-kv {
            width: 100%;
            border-collapse: collapse;
        }
        .inv-kv th, .inv-kv td {
            border-top: 1px solid #f1f5f9;
            padding: 4px 8px;
            font-size: 10.5px;
            text-align: left;
            vertical-align: top;
        }
        .inv-kv tr:first-child th, .inv-kv tr:first-child td { border-top: none; }
        .inv-kv th {
            background: var(--inv-bg-alt);
            color: #475569;
            font-weight: 700;
            width: 30%;
            white-space: nowrap;
        }
        .inv-sub { color: #64748b; font-size: 9.5px; }

        /* Line Items Table */
        .inv-table-wrap {
            margin-top: 10px;
            border: 1px solid var(--inv-line);
            border-radius: 8px;
            overflow: hidden;
        }
        table.inv-items {
            width: 100%;
            border-collapse: collapse;
        }
        .inv-items th {
            background: #f1f5f9;
            color: #334155;
            font-weight: 800;
            font-size: 10.5px;
            padding: 6px 8px;
            border-bottom: 1px solid var(--inv-line);
            text-align: left;
        }
        .inv-items td {
            padding: 5px 8px;
            border-top: 1px solid #f1f5f9;
            font-size: 10.5px;
            vertical-align: middle;
        }
        .inv-items tr:first-child td { border-top: none; }
        .inv-item-name { font-weight: 700; color: var(--inv-ink); }
        .inv-item-meta { font-size: 9px; color: #64748b; margin-top: 1px; }

        /* Total Panel */
        .inv-bottom-section {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            align-items: stretch;
        }
        .inv-notes-box {
            flex: 1.1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .inv-total-box {
            flex: 0.9;
            background: linear-gradient(135deg, #fff7ed 0%, #fef2f2 100%);
            border: 2px solid #fdba74;
            border-radius: 10px;
            padding: 10px 14px;
        }
        .inv-total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 2px 0;
            font-size: 10.5px;
            color: #475569;
        }
        .inv-grand-total {
            border-top: 1.5px solid #fed7aa;
            margin-top: 6px;
            padding-top: 6px;
            display: flex;
            justify-content: space-between;
            align-items: baseline;
        }
        .inv-grand-label {
            font-size: 12.5px;
            font-weight: 900;
            color: #9a3412;
        }
        .inv-grand-amount {
            font-size: 22px;
            font-weight: 900;
            color: var(--inv-red-d);
            white-space: nowrap;
        }
        .inv-tax-note {
            font-size: 9px;
            color: #b45309;
            text-align: right;
            margin-top: 2px;
        }

        .inv-pay-info {
            font-size: 10.5px;
            color: #334155;
            background: #f8fafc;
            border: 1px solid var(--inv-line);
            border-radius: 6px;
            padding: 6px 10px;
            margin-bottom: 6px;
        }
        .inv-legal {
            font-size: 8.5px;
            color: #64748b;
            line-height: 1.5;
            border-left: 2.5px solid var(--inv-line);
            padding: 2px 0 2px 8px;
        }

        /* Stamps / Signature */
        .inv-sign {
            display: flex;
            gap: 10px;
            margin-top: 8px;
        }
        .inv-sign-box {
            flex: 1;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            height: 42px;
            position: relative;
            background: #ffffff;
        }
        .inv-sign-box span {
            position: absolute;
            top: 3px;
            left: 6px;
            font-size: 8.5px;
            color: #64748b;
        }

        /* Print Media Styles */
        @media print {
            body {
                background: #ffffff;
            }
            .inv-toolbar {
                display: none !important;
            }
            .inv-sheet {
                box-shadow: none !important;
                margin: 0 !important;
                max-width: none !important;
                min-height: 0 !important;
                padding: 4mm 2mm !important;
            }
            .inv-order-wrapper {
                page-break-after: always;
            }
            .inv-order-wrapper:last-child {
                page-break-after: auto;
            }
            .inv-copy {
                page-break-inside: avoid;
            }
            .inv-cut {
                page-break-inside: avoid;
            }
        }
        @page {
            size: A4;
            margin: 8mm;
        }
    </style>
</head>
<body>
    <div class="inv-toolbar">
        <button type="button" class="inv-btn-print" onclick="window.print();">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/></svg>
            <?php esc_html_e( '印刷 / PDF保存 (Print / Save as PDF)', 'wc-japanese-invoice-receipt' ); ?>
        </button>
        <button type="button" class="inv-btn-close" onclick="window.close();">
            <?php esc_html_e( '閉じる (Close)', 'wc-japanese-invoice-receipt' ); ?>
        </button>
    </div>

    <div class="inv-sheet">
        <?php foreach ( $orders as $order ) : ?>
            <div class="inv-order-wrapper">
                <?php
                $item_count = count( $order->get_items() );
                $is_dual    = ( 'dual' === $layout_opt ) || ( 'auto' === $layout_opt && $item_count <= 3 );

                if ( $is_dual ) {
                    // Render 2 copies: Shop Copy + Customer Copy
                    self::render_copy_html( $order, '店舗控え (Shop Copy)', 'shop', $logo_url, $doc_title );
                    if ( $show_cut ) {
                        echo '<div class="inv-cut"><span>— キリトリ線 (cut here) —</span></div>';
                    }
                    self::render_copy_html( $order, 'お客様控え (Customer Copy)', 'customer', $logo_url, $doc_title );
                } else {
                    // Full Page Single Copy
                    self::render_copy_html( $order, '納品明細 (Original Copy)', 'single', $logo_url, $doc_title, true );
                }
                ?>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ( $auto ) : ?>
    <script>
        window.addEventListener('load', function() {
            setTimeout(function() { window.print(); }, 400);
        });
    </script>
    <?php endif; ?>
</body>
</html>
        <?php
        exit;
    }

    /**
     * Render one individual copy (Shop copy or Customer copy).
     */
    public static function render_copy_html( $order, $copy_label, $copy_type, $logo_url, $doc_title, $is_full_page = false ) {
        $store_name = get_option( 'wc_jir_store_name', get_bloginfo( 'name' ) );
        $store_addr = get_option( 'wc_jir_store_address', '' );
        $store_tel  = get_option( 'wc_jir_store_phone', '' );
        $invoice_no = get_option( 'wc_jir_invoice_number', '' );
        $show_stamp = ( 'yes' === get_option( 'wc_jir_show_stamp_box', 'yes' ) );
        $legal_text = get_option( 'wc_jir_legal_notice', '' );

        $order_id   = $order->get_id();
        $order_num  = $order->get_order_number();
        $order_date = $order->get_date_created() ? $order->get_date_created()->date_i18n( 'Y年m月d日' ) : date_i18n( 'Y年m月d日' );
        $status_obj = wc_get_order_status_name( $order->get_status() );

        // Customer details
        $billing_name  = $order->get_formatted_billing_full_name() ?: $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $billing_phone = $order->get_billing_phone();
        $billing_email = $order->get_billing_email();
        $billing_addr  = $order->get_formatted_billing_address();
        $shipping_addr = $order->get_formatted_shipping_address() ?: $billing_addr;
        $shipping_name = $order->get_formatted_shipping_full_name() ?: $billing_name;

        // Payment info
        $payment_title = $order->get_payment_method_title() ?: '—';

        // Money values
        $currency_symbol = get_woocommerce_currency_symbol();
        $yen = function( $num ) use ( $currency_symbol ) {
            return $currency_symbol . number_format( (float) $num );
        };
        ?>
        <div class="inv-copy inv-copy-<?php echo esc_attr( $copy_type ); ?>" style="<?php echo $is_full_page ? 'padding:6mm 0;' : ''; ?>">
            
            <!-- Header Band -->
            <div class="inv-band">
                <div class="inv-band-left">
                    <?php if ( ! empty( $logo_url ) ) : ?>
                        <img class="inv-logo" src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $store_name ); ?>">
                    <?php endif; ?>
                    <div class="inv-shop">
                        <div class="inv-shop-name"><?php echo esc_html( $store_name ); ?></div>
                        <?php if ( ! empty( $store_addr ) ) : ?>
                            <div class="inv-shop-line"><?php echo nl2br( esc_html( $store_addr ) ); ?></div>
                        <?php endif; ?>
                        <?php if ( ! empty( $store_tel ) ) : ?>
                            <div class="inv-shop-line">TEL: <?php echo esc_html( $store_tel ); ?></div>
                        <?php endif; ?>
                        <?php if ( ! empty( $invoice_no ) ) : ?>
                            <div class="inv-license">登録番号: <?php echo esc_html( $invoice_no ); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="inv-band-right">
                    <div class="inv-doc-title"><?php echo esc_html( $doc_title ); ?></div>
                    <div class="inv-doc-sub">INVOICE &amp; RECEIPT</div>
                </div>
            </div>

            <!-- Ribbon -->
            <div class="inv-ribbon">
                <span class="inv-copy-pill"><?php echo esc_html( $copy_label ); ?></span>
                <div class="inv-ribbon-meta">
                    <span class="inv-chip"><em>伝票番号</em> <b>#<?php echo esc_html( $order_num ); ?></b></span>
                    <span class="inv-chip"><em>発行日</em> <b><?php echo esc_html( $order_date ); ?></b></span>
                    <span class="inv-status"><?php echo esc_html( $status_obj ); ?></span>
                </div>
            </div>

            <!-- Cards Grid: Billing & Shipping -->
            <div class="inv-grid">
                <section class="inv-card">
                    <h4>ご請求先情報 (Customer / Billing)</h4>
                    <table class="inv-kv">
                        <tr>
                            <th>お名前</th>
                            <td><strong><?php echo esc_html( $billing_name ); ?> 様</strong></td>
                        </tr>
                        <?php if ( ! empty( $billing_phone ) ) : ?>
                            <tr><th>電話番号</th><td><?php echo esc_html( $billing_phone ); ?></td></tr>
                        <?php endif; ?>
                        <?php if ( ! empty( $billing_email ) ) : ?>
                            <tr><th>メール</th><td><?php echo esc_html( $billing_email ); ?></td></tr>
                        <?php endif; ?>
                        <?php if ( ! empty( $billing_addr ) ) : ?>
                            <tr><th>ご住所</th><td><?php echo wp_strip_all_tags( $billing_addr ); ?></td></tr>
                        <?php endif; ?>
                    </table>
                </section>
                <section class="inv-card">
                    <h4>お届け先・配送情報 (Delivery / Shipping)</h4>
                    <table class="inv-kv">
                        <tr>
                            <th>お届け先</th>
                            <td><strong><?php echo esc_html( $shipping_name ); ?> 様</strong></td>
                        </tr>
                        <tr>
                            <th>お届け先住所</th>
                            <td><?php echo wp_strip_all_tags( $shipping_addr ); ?></td>
                        </tr>
                        <tr>
                            <th>配送方法</th>
                            <td><?php echo esc_html( $order->get_shipping_method() ?: '標準配送' ); ?></td>
                        </tr>
                    </table>
                </section>
            </div>

            <!-- Line Items Table -->
            <div class="inv-table-wrap">
                <table class="inv-items">
                    <thead>
                        <tr>
                            <th style="width:55%;">商品名・内容 (Item Description)</th>
                            <th style="width:12%; text-align:center;">数量</th>
                            <th style="width:16%; text-align:right;">単価 (税込)</th>
                            <th style="width:17%; text-align:right;">金額 (税込)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ( $order->get_items() as $item_id => $item ) :
                            $product = $item->get_product();
                            $qty     = $item->get_quantity();
                            $total   = $item->get_total();
                            $tax     = $item->get_total_tax();
                            $total_incl = $total + $tax;
                            $unit_price = $qty > 0 ? ( $total_incl / $qty ) : 0;
                            ?>
                            <tr>
                                <td>
                                    <div class="inv-item-name"><?php echo esc_html( $item->get_name() ); ?></div>
                                    <?php
                                    $meta_data = $item->get_formatted_meta_data( '' );
                                    if ( ! empty( $meta_data ) ) :
                                        echo '<div class="inv-item-meta">';
                                        foreach ( $meta_data as $meta ) {
                                            echo '<span>' . esc_html( $meta->display_key ) . ': ' . esc_html( wp_strip_all_tags( $meta->display_value ) ) . ' </span>';
                                        }
                                        echo '</div>';
                                    endif;
                                    if ( $product && $product->get_sku() ) {
                                        echo '<div class="inv-item-meta">SKU: ' . esc_html( $product->get_sku() ) . '</div>';
                                    }
                                    ?>
                                </td>
                                <td style="text-align:center; font-weight:700;"><?php echo esc_html( $qty ); ?></td>
                                <td style="text-align:right;"><?php echo esc_html( $yen( $unit_price ) ); ?></td>
                                <td style="text-align:right; font-weight:700;"><?php echo esc_html( $yen( $total_incl ) ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Bottom Section (Notes + Totals) -->
            <div class="inv-bottom-section">
                <div class="inv-notes-box">
                    <div>
                        <div class="inv-pay-info">
                            <b>お支払い方法:</b> <?php echo esc_html( $payment_title ); ?>
                            <?php if ( $order->get_customer_note() ) : ?>
                                <br><b>備考:</b> <?php echo esc_html( $order->get_customer_note() ); ?>
                            <?php endif; ?>
                        </div>
                        <?php if ( ! empty( $legal_text ) ) : ?>
                            <div class="inv-legal"><?php echo nl2br( esc_html( $legal_text ) ); ?></div>
                        <?php endif; ?>
                    </div>

                    <?php if ( $show_stamp ) : ?>
                        <div class="inv-sign">
                            <div class="inv-sign-box"><span>お客様受領印</span></div>
                            <div class="inv-sign-box"><span>店舗印 / 担当者</span></div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="inv-total-box">
                    <div class="inv-total-row">
                        <span>商品小計 (税抜)</span>
                        <span><?php echo esc_html( $yen( $order->get_subtotal() ) ); ?></span>
                    </div>
                    <?php if ( (float) $order->get_shipping_total() > 0 ) : ?>
                        <div class="inv-total-row">
                            <span>送料</span>
                            <span><?php echo esc_html( $yen( $order->get_shipping_total() ) ); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ( (float) $order->get_discount_total() > 0 ) : ?>
                        <div class="inv-total-row" style="color:#dc2626;">
                            <span>割引額</span>
                            <span>-<?php echo esc_html( $yen( $order->get_discount_total() ) ); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="inv-total-row">
                        <span>消費税額</span>
                        <span><?php echo esc_html( $yen( $order->get_total_tax() ) ); ?></span>
                    </div>

                    <div class="inv-grand-total">
                        <span class="inv-grand-label">ご請求合計</span>
                        <span class="inv-grand-amount"><?php echo esc_html( $yen( $order->get_total() ) ); ?></span>
                    </div>
                    <div class="inv-tax-note">(内消費税等 10% 対象: <?php echo esc_html( $yen( $order->get_total_tax() ) ); ?>)</div>
                </div>
            </div>

        </div>
        <?php
    }
}
