<?php
/**
 * Settings Page Handler for WooCommerce Japanese Invoice & Receipt
 *
 * Integrates directly into WooCommerce > Settings > 納品書・領収書
 *
 * @package WC_Japanese_Invoice_Receipt
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_JIR_Settings {

    public static function init() {
        add_filter( 'woocommerce_settings_tabs_array', array( __CLASS__, 'add_settings_tab' ), 50 );
        add_action( 'woocommerce_settings_tabs_wc_jir_settings', array( __CLASS__, 'settings_tab' ) );
        add_action( 'woocommerce_update_options_wc_jir_settings', array( __CLASS__, 'update_settings' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_media_uploader' ) );
    }

    public static function add_settings_tab( $settings_tabs ) {
        $settings_tabs['wc_jir_settings'] = __( '納品書・領収書 (Japanese Invoice)', 'wc-japanese-invoice-receipt' );
        return $settings_tabs;
    }

    public static function settings_tab() {
        woocommerce_admin_fields( self::get_settings() );
    }

    public static function update_settings() {
        woocommerce_update_options( self::get_settings() );
    }

    public static function enqueue_media_uploader( $hook ) {
        if ( isset( $_GET['page'], $_GET['tab'] ) && 'wc-settings' === $_GET['page'] && 'wc_jir_settings' === $_GET['tab'] ) {
            wp_enqueue_media();
        }
    }

    public static function get_settings() {
        $settings = array(
            'section_title' => array(
                'name'     => __( '店舗・事業者情報 (Store & Seller Information)', 'wc-japanese-invoice-receipt' ),
                'type'     => 'title',
                'desc'     => __( '納品書および領収書に印字される店舗情報と適格請求書（インボイス）登録番号を設定します。', 'wc-japanese-invoice-receipt' ),
                'id'       => 'wc_jir_section_store',
            ),
            'store_name' => array(
                'name'     => __( '屋号・会社名 (Store Name)', 'wc-japanese-invoice-receipt' ),
                'type'     => 'text',
                'desc'     => __( '納品書ヘッダーに表示される店名または会社名です。（空欄の場合はサイト名を使用）', 'wc-japanese-invoice-receipt' ),
                'id'       => 'wc_jir_store_name',
                'default'  => get_bloginfo( 'name' ),
                'css'      => 'min-width:350px;',
            ),
            'store_address' => array(
                'name'     => __( '所在地・住所 (Address)', 'wc-japanese-invoice-receipt' ),
                'type'     => 'textarea',
                'desc'     => __( '店舗の郵便番号および所在地を入力してください。', 'wc-japanese-invoice-receipt' ),
                'id'       => 'wc_jir_store_address',
                'default'  => '',
                'css'      => 'min-width:350px; height:70px;',
            ),
            'store_phone' => array(
                'name'     => __( '電話番号 (TEL)', 'wc-japanese-invoice-receipt' ),
                'type'     => 'text',
                'desc'     => __( '店舗の問い合わせ電話番号です。', 'wc-japanese-invoice-receipt' ),
                'id'       => 'wc_jir_store_phone',
                'default'  => '',
                'css'      => 'min-width:250px;',
            ),
            'invoice_number' => array(
                'name'     => __( '適格請求書登録番号 (Invoice Reg. No.)', 'wc-japanese-invoice-receipt' ),
                'type'     => 'text',
                'desc'     => __( 'インボイス制度の登録番号（T + 13桁の半角数字、例: T1234567890123）。印字する場合は入力してください。', 'wc-japanese-invoice-receipt' ),
                'id'       => 'wc_jir_invoice_number',
                'default'  => '',
                'placeholder' => 'T1234567890123',
                'css'      => 'min-width:250px;',
            ),
            'section_store_end' => array(
                'type' => 'sectionend',
                'id'   => 'wc_jir_section_store_end',
            ),

            'section_design' => array(
                'name'     => __( 'デザイン・レイアウト設定 (Layout & Design)', 'wc-japanese-invoice-receipt' ),
                'type'     => 'title',
                'desc'     => __( 'A4印刷フォーマットや透かしロゴ、控えの分割方法を設定します。', 'wc-japanese-invoice-receipt' ),
                'id'       => 'wc_jir_section_design',
            ),
            'doc_title' => array(
                'name'     => __( '書類タイトル (Document Title)', 'wc-japanese-invoice-receipt' ),
                'type'     => 'text',
                'desc'     => __( '納品書の表題です。例:「納品書 兼 領収書」「納品書」「領収書」', 'wc-japanese-invoice-receipt' ),
                'id'       => 'wc_jir_doc_title',
                'default'  => '納品書 兼 領収書',
                'css'      => 'min-width:250px;',
            ),
            'copy_layout' => array(
                'name'     => __( '印刷レイアウト (Print Layout)', 'wc-japanese-invoice-receipt' ),
                'type'     => 'select',
                'desc'     => __( 'A4用紙の分割形式を選択します。', 'wc-japanese-invoice-receipt' ),
                'id'       => 'wc_jir_copy_layout',
                'options'  => array(
                    'dual'   => __( '店舗控え ＋ お客様控え（A4 2枚分割・キリトリ線あり）— 推奨', 'wc-japanese-invoice-receipt' ),
                    'single' => __( '1枚全面レイアウト (Full Page Single Copy)', 'wc-japanese-invoice-receipt' ),
                    'auto'   => __( '自動判定 (商品が3点以下なら2分割、4点以上なら1枚全面)', 'wc-japanese-invoice-receipt' ),
                ),
                'default'  => 'dual',
            ),
            'show_cut_line' => array(
                'name'     => __( 'キリトリ線 (Cut Line)', 'wc-japanese-invoice-receipt' ),
                'type'     => 'checkbox',
                'desc'     => __( '2枚分割時に「— キリトリ線 —」を表示する', 'wc-japanese-invoice-receipt' ),
                'id'       => 'wc_jir_show_cut_line',
                'default'  => 'yes',
            ),
            'custom_logo_url' => array(
                'name'     => __( '専用ロゴ画像URL (Custom Logo URL)', 'wc-japanese-invoice-receipt' ),
                'type'     => 'text',
                'desc'     => __( '納品書専用のロゴ画像URLを指定できます。（空欄の場合はサイトのカスタムロゴを自動使用します）', 'wc-japanese-invoice-receipt' ),
                'id'       => 'wc_jir_custom_logo_url',
                'default'  => '',
                'css'      => 'min-width:400px;',
            ),
            'enable_watermark' => array(
                'name'     => __( 'ロゴ透かし背景 (Logo Watermark)', 'wc-japanese-invoice-receipt' ),
                'type'     => 'checkbox',
                'desc'     => __( '用紙の中央背景にロゴマークを薄く透かし表示（ウォーターマーク）する', 'wc-japanese-invoice-receipt' ),
                'id'       => 'wc_jir_enable_watermark',
                'default'  => 'yes',
            ),
            'show_stamp_box' => array(
                'name'     => __( '押印・署名欄 (Seal / Stamp Boxes)', 'wc-japanese-invoice-receipt' ),
                'type'     => 'checkbox',
                'desc'     => __( '用紙下部に「店舗印 / 担当者印」および「受領印」枠を表示する', 'wc-japanese-invoice-receipt' ),
                'id'       => 'wc_jir_show_stamp_box',
                'default'  => 'yes',
            ),
            'legal_notice' => array(
                'name'     => __( '備考・免責注記 (Legal / Footer Notes)', 'wc-japanese-invoice-receipt' ),
                'type'     => 'textarea',
                'desc'     => __( '納品書下部に印刷される注記文です。', 'wc-japanese-invoice-receipt' ),
                'id'       => 'wc_jir_legal_notice',
                'default'  => '本状はお取引の納品明細および適格請求書（インボイス）の要件を満たす領収明細書です。大切に保管してください。',
                'css'      => 'min-width:400px; height:60px;',
            ),
            'section_design_end' => array(
                'type' => 'sectionend',
                'id'   => 'wc_jir_section_design_end',
            ),

            'section_features' => array(
                'name'     => __( '機能・権限設定 (Functionality & Permissions)', 'wc-japanese-invoice-receipt' ),
                'type'     => 'title',
                'desc'     => __( '印刷ボタンの表示場所や顧客への公開範囲を設定します。', 'wc-japanese-invoice-receipt' ),
                'id'       => 'wc_jir_section_features',
            ),
            'allow_customer_print' => array(
                'name'     => __( 'マイアカウントでの顧客印刷 (Customer Self-Print)', 'wc-japanese-invoice-receipt' ),
                'type'     => 'checkbox',
                'desc'     => __( '購入者がマイアカウントの「注文詳細」画面から納品書・領収書をPDF印刷できるようにする', 'wc-japanese-invoice-receipt' ),
                'id'       => 'wc_jir_allow_customer_print',
                'default'  => 'yes',
            ),
            'auto_open_print' => array(
                'name'     => __( '印刷ダイアログ自動表示 (Auto-Open Print Dialog)', 'wc-japanese-invoice-receipt' ),
                'type'     => 'checkbox',
                'desc'     => __( '納品書画面を開いた際、自動的にブラウザの印刷画面（PDF保存ダイアログ）を開く', 'wc-japanese-invoice-receipt' ),
                'id'       => 'wc_jir_auto_open_print',
                'default'  => 'no',
            ),
            'section_features_end' => array(
                'type' => 'sectionend',
                'id'   => 'wc_jir_section_features_end',
            ),
        );

        return apply_filters( 'wc_jir_settings', $settings );
    }

    /**
     * Helper to get effective logo URL (custom option -> theme logo -> placeholder).
     */
    public static function get_logo_url() {
        $custom = get_option( 'wc_jir_custom_logo_url', '' );
        if ( ! empty( $custom ) ) {
            return $custom;
        }

        $theme_logo_id = get_theme_mod( 'custom_logo' );
        if ( $theme_logo_id ) {
            $logo_src = wp_get_attachment_image_url( $theme_logo_id, 'full' );
            if ( $logo_src ) {
                return $logo_src;
            }
        }

        return '';
    }
}
