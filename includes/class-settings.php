<?php
/**
 * Settings Page Handler for WooCommerce Japanese Invoice & Receipt
 *
 * Integrates directly into WooCommerce > Settings > 納品書・領収書
 * Includes WordPress Media Uploader for Header Logo and Watermark Logo.
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

        // Custom field type for Media Library Image Uploader
        add_action( 'woocommerce_admin_field_wc_jir_image_uploader', array( __CLASS__, 'render_image_uploader_field' ) );
        add_action( 'woocommerce_update_option_wc_jir_image_uploader', array( __CLASS__, 'save_image_uploader_field' ) );

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
            add_action( 'admin_footer', array( __CLASS__, 'render_media_script' ) );
        }
    }

    /**
     * Inline JS for WordPress Media Library dialog.
     */
    public static function render_media_script() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $(document).on('click', '.wc-jir-upload-btn', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var targetId = $btn.data('target');
                var dialogTitle = $btn.data('title') || '画像を選択';

                var frame = wp.media({
                    title: dialogTitle,
                    button: { text: 'この画像を使用' },
                    multiple: false,
                    library: { type: 'image' }
                });

                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#' + targetId).val(attachment.url);
                    var $wrap = $('#' + targetId + '_wrap');
                    $wrap.find('.wc-jir-preview-box img').attr('src', attachment.url);
                    $wrap.find('.wc-jir-preview-box').show();
                    $wrap.find('.wc-jir-remove-btn').show();
                });

                frame.open();
            });

            $(document).on('click', '.wc-jir-remove-btn', function(e) {
                e.preventDefault();
                var targetId = $(this).data('target');
                $('#' + targetId).val('');
                var $wrap = $('#' + targetId + '_wrap');
                $wrap.find('.wc-jir-preview-box img').attr('src', '');
                $wrap.find('.wc-jir-preview-box').hide();
                $(this).hide();
            });
        });
        </script>
        <?php
    }

    /**
     * Render the Custom Image Uploader Field in WooCommerce Settings.
     */
    public static function render_image_uploader_field( $value ) {
        $option_value = get_option( $value['id'], $value['default'] ?? '' );
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
                <?php echo wc_help_tip( $value['desc'] ?? '' ); ?>
            </th>
            <td class="forminp forminp-wc-jir-image-uploader">
                <div class="wc-jir-image-uploader-wrap" id="<?php echo esc_attr( $value['id'] ); ?>_wrap">
                    <input type="hidden" name="<?php echo esc_attr( $value['id'] ); ?>" id="<?php echo esc_attr( $value['id'] ); ?>" value="<?php echo esc_attr( $option_value ); ?>">

                    <div class="wc-jir-preview-box" style="margin-bottom: 10px; <?php echo empty( $option_value ) ? 'display:none;' : ''; ?>">
                        <img src="<?php echo esc_url( $option_value ); ?>" style="max-height: 90px; max-width: 260px; object-fit: contain; border: 1.5px solid #cbd5e1; border-radius: 8px; padding: 6px; background: #ffffff; box-shadow: 0 2px 6px rgba(0,0,0,.06);" alt="Preview">
                    </div>

                    <div style="display:flex; gap:10px; align-items:center;">
                        <button type="button" class="button button-secondary wc-jir-upload-btn" data-target="<?php echo esc_attr( $value['id'] ); ?>" data-title="<?php echo esc_attr( $value['title'] ); ?>">
                            <span class="dashicons dashicons-upload" style="vertical-align:middle; margin-top:-2px;"></span>
                            <?php esc_html_e( '画像を選択 / アップロード (Select Image)', 'wc-japanese-invoice-receipt' ); ?>
                        </button>
                        <button type="button" class="button button-link-delete wc-jir-remove-btn" data-target="<?php echo esc_attr( $value['id'] ); ?>" style="<?php echo empty( $option_value ) ? 'display:none;' : ''; ?> color:#dc2626; text-decoration:none;">
                            <?php esc_html_e( '削除 (Remove)', 'wc-japanese-invoice-receipt' ); ?>
                        </button>
                    </div>

                    <?php if ( ! empty( $value['desc'] ) ) : ?>
                        <p class="description" style="margin-top:6px;"><?php echo esc_html( $value['desc'] ); ?></p>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php
    }

    /**
     * Save the Custom Image Uploader Field.
     */
    public static function save_image_uploader_field( $value ) {
        if ( isset( $_POST[ $value['id'] ] ) ) {
            update_option( $value['id'], esc_url_raw( wp_unslash( $_POST[ $value['id'] ] ) ) );
        }
    }

    public static function get_settings() {
        $settings = array(
            'section_store' => array(
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

            'section_branding' => array(
                'name'     => __( 'ロゴ＆ウォーターマーク設定 (Logo & Watermark)', 'wc-japanese-invoice-receipt' ),
                'type'     => 'title',
                'desc'     => __( '納品書のヘッダーロゴおよび中央背景の透かし（ウォーターマーク）ロゴ画像を設定します。メディアライブラリからアップロードまたは選択可能です。', 'wc-japanese-invoice-receipt' ),
                'id'       => 'wc_jir_section_branding',
            ),
            'header_logo_url' => array(
                'title'    => __( 'ヘッダー用ロゴ画像 (Header Logo)', 'wc-japanese-invoice-receipt' ),
                'type'     => 'wc_jir_image_uploader',
                'desc'     => __( '納品書の左上ヘッダーに印字されるロゴ画像です。空欄の場合はサイトのカスタムロゴを自動使用します。', 'wc-japanese-invoice-receipt' ),
                'id'       => 'wc_jir_header_logo_url',
                'default'  => '',
            ),
            'enable_watermark' => array(
                'name'     => __( '透かしウォーターマーク (Enable Watermark)', 'wc-japanese-invoice-receipt' ),
                'type'     => 'checkbox',
                'desc'     => __( '納品書の中央背景にロゴマークを薄く透かし表示する', 'wc-japanese-invoice-receipt' ),
                'id'       => 'wc_jir_enable_watermark',
                'default'  => 'yes',
            ),
            'watermark_logo_url' => array(
                'title'    => __( '透かし専用ロゴ画像 (Watermark Logo - Optional)', 'wc-japanese-invoice-receipt' ),
                'type'     => 'wc_jir_image_uploader',
                'desc'     => __( '中央背景に透かしとして表示する専用ロゴです。空欄の場合は上記のヘッダー用ロゴがそのまま使用されます。', 'wc-japanese-invoice-receipt' ),
                'id'       => 'wc_jir_watermark_logo_url',
                'default'  => '',
            ),
            'watermark_opacity' => array(
                'name'     => __( '透かしの濃さ・不透明度 (Watermark Opacity)', 'wc-japanese-invoice-receipt' ),
                'type'     => 'select',
                'desc'     => __( '背景透かしロゴの薄さ（不透明度）を選択します。', 'wc-japanese-invoice-receipt' ),
                'id'       => 'wc_jir_watermark_opacity',
                'options'  => array(
                    '0.03' => __( '3% (とても薄め - 文字の邪魔をしない推奨)', 'wc-japanese-invoice-receipt' ),
                    '0.05' => __( '5% (標準 - 最適なバランス / 推奨)', 'wc-japanese-invoice-receipt' ),
                    '0.08' => __( '8% (ややはっきり)', 'wc-japanese-invoice-receipt' ),
                    '0.12' => __( '12% (くっきり)', 'wc-japanese-invoice-receipt' ),
                    '0.18' => __( '18% (濃いめ)', 'wc-japanese-invoice-receipt' ),
                ),
                'default'  => '0.05',
            ),
            'section_branding_end' => array(
                'type' => 'sectionend',
                'id'   => 'wc_jir_section_branding_end',
            ),

            'section_design' => array(
                'name'     => __( 'デザイン・レイアウト設定 (Layout & Design)', 'wc-japanese-invoice-receipt' ),
                'type'     => 'title',
                'desc'     => __( 'A4印刷フォーマットや控えの分割方法、印鑑枠を設定します。', 'wc-japanese-invoice-receipt' ),
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
     * Helper to get effective header logo URL.
     */
    public static function get_logo_url() {
        $custom = get_option( 'wc_jir_header_logo_url', '' );
        if ( ! empty( $custom ) ) {
            return $custom;
        }

        // Backward compatibility
        $legacy = get_option( 'wc_jir_custom_logo_url', '' );
        if ( ! empty( $legacy ) ) {
            return $legacy;
        }

        // Try WordPress custom logo
        $theme_logo_id = get_theme_mod( 'custom_logo' );
        if ( $theme_logo_id ) {
            $logo_src = wp_get_attachment_image_url( $theme_logo_id, 'full' );
            if ( $logo_src ) {
                return $logo_src;
            }
        }

        // Try WooCommerce email header image
        $wc_email_logo = get_option( 'woocommerce_email_header_image' );
        if ( ! empty( $wc_email_logo ) ) {
            return $wc_email_logo;
        }

        // Try site icon (favicon)
        $site_icon_id = get_option( 'site_icon' );
        if ( $site_icon_id ) {
            $site_icon_src = wp_get_attachment_image_url( $site_icon_id, 'full' );
            if ( $site_icon_src ) {
                return $site_icon_src;
            }
        }

        return '';
    }

    /**
     * Helper to get effective watermark logo URL.
     */
    public static function get_watermark_url() {
        $watermark_custom = get_option( 'wc_jir_watermark_logo_url', '' );
        if ( ! empty( $watermark_custom ) ) {
            return $watermark_custom;
        }
        return self::get_logo_url();
    }
}
