<?php
/**
 * MCT Settings - 設定画面管理
 *
 * @package My_Comparison_Table
 */

if (!defined('ABSPATH')) {
    exit;
}

class MCT_Settings
{

    /**
     * オプション名
     */
    const OPTION_NAME = 'mct_settings';

    /**
     * デフォルト設定
     */
    private static $defaults = array(
        'primary_color' => '#e74c3c',
        'secondary_color' => '#3498db',
        'button_color' => '#e74c3c',
        'button_text_color' => '#ffffff',
        'name_font_size' => '1.25',
        'price_font_size' => '1.5',
        'show_crown' => '1',
        'show_tags' => '1',
        'detail_button_text' => '詳細を見る',
        'affiliate_button_text' => '公式サイト',
    );

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_color_picker'));
        add_action('wp_head', array($this, 'output_custom_css'));
    }

    /**
     * 設定ページをメニューに追加
     */
    public function add_settings_page()
    {
        add_options_page(
            '比較表設定',
            '比較表設定',
            'manage_options',
            'mct-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * 設定を登録
     */
    public function register_settings()
    {
        register_setting(self::OPTION_NAME, self::OPTION_NAME, array($this, 'sanitize_settings'));

        // カラー設定セクション
        add_settings_section(
            'mct_color_section',
            'カラー設定',
            null,
            'mct-settings'
        );

        add_settings_field('primary_color', 'プライマリカラー', array($this, 'render_color_field'), 'mct-settings', 'mct_color_section', array('field' => 'primary_color', 'description' => 'ボタンや価格表示に使用'));
        add_settings_field('secondary_color', 'サブカラー', array($this, 'render_color_field'), 'mct-settings', 'mct_color_section', array('field' => 'secondary_color', 'description' => 'タグや装飾に使用'));
        add_settings_field('button_color', 'アフィリエイトボタン色', array($this, 'render_color_field'), 'mct-settings', 'mct_color_section', array('field' => 'button_color', 'description' => ''));
        add_settings_field('button_text_color', 'ボタン文字色', array($this, 'render_color_field'), 'mct-settings', 'mct_color_section', array('field' => 'button_text_color', 'description' => ''));

        // フォント設定セクション
        add_settings_section(
            'mct_font_section',
            'フォント設定',
            null,
            'mct-settings'
        );

        add_settings_field('name_font_size', '商品名サイズ (rem)', array($this, 'render_number_field'), 'mct-settings', 'mct_font_section', array('field' => 'name_font_size', 'min' => '0.8', 'max' => '3', 'step' => '0.1'));
        add_settings_field('price_font_size', '価格サイズ (rem)', array($this, 'render_number_field'), 'mct-settings', 'mct_font_section', array('field' => 'price_font_size', 'min' => '0.8', 'max' => '3', 'step' => '0.1'));

        // 表示設定セクション
        add_settings_section(
            'mct_display_section',
            '表示設定',
            null,
            'mct-settings'
        );

        add_settings_field('show_crown', '王冠を表示', array($this, 'render_checkbox_field'), 'mct-settings', 'mct_display_section', array('field' => 'show_crown'));
        add_settings_field('show_tags', 'タグを表示', array($this, 'render_checkbox_field'), 'mct-settings', 'mct_display_section', array('field' => 'show_tags'));

        // ボタン設定セクション
        add_settings_section(
            'mct_button_section',
            'ボタン設定',
            null,
            'mct-settings'
        );

        add_settings_field('detail_button_text', '詳細ボタンテキスト', array($this, 'render_text_field'), 'mct-settings', 'mct_button_section', array('field' => 'detail_button_text'));
        add_settings_field('affiliate_button_text', 'アフィリエイトボタンテキスト', array($this, 'render_text_field'), 'mct-settings', 'mct_button_section', array('field' => 'affiliate_button_text'));
    }

    /**
     * カラーピッカーをエンキュー
     *
     * @param string $hook
     */
    public function enqueue_color_picker($hook)
    {
        if ($hook === 'settings_page_mct-settings') {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
            wp_add_inline_script('wp-color-picker', "
                jQuery(document).ready(function($) {
                    $('.mct-color-picker').wpColorPicker();
                });
            ");
        }
    }

    /**
     * 設定ページをレンダリング
     */
    public function render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <form action="options.php" method="post">
                <?php
                settings_fields(self::OPTION_NAME);
                do_settings_sections('mct-settings');
                submit_button('設定を保存');
                ?>
            </form>

            <hr>

            <h2>ショートコードの使い方</h2>
            <table class="widefat" style="max-width: 800px;">
                <thead>
                    <tr>
                        <th>形式</th>
                        <th>例</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>商品ID指定</td>
                        <td><code>[my_comparison_table ids="123,456,789"]</code></td>
                    </tr>
                    <tr>
                        <td>カテゴリー指定</td>
                        <td><code>[my_comparison_table category="サプリメント"]</code></td>
                    </tr>
                    <tr>
                        <td>JSON形式（従来）</td>
                        <td><code>[my_comparison_table data='[{"name":"商品A",...}]']</code></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * カラーフィールドをレンダリング
     */
    public function render_color_field($args)
    {
        $options = self::get_options();
        $value = isset($options[$args['field']]) ? $options[$args['field']] : '';
        ?>
        <input type="text" class="mct-color-picker"
            name="<?php echo esc_attr(self::OPTION_NAME . '[' . $args['field'] . ']'); ?>"
            value="<?php echo esc_attr($value); ?>">
        <?php if (!empty($args['description'])): ?>
            <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php endif;
    }

    /**
     * 数値フィールドをレンダリング
     */
    public function render_number_field($args)
    {
        $options = self::get_options();
        $value = isset($options[$args['field']]) ? $options[$args['field']] : '';
        ?>
        <input type="number" class="small-text" name="<?php echo esc_attr(self::OPTION_NAME . '[' . $args['field'] . ']'); ?>"
            value="<?php echo esc_attr($value); ?>" min="<?php echo esc_attr($args['min']); ?>"
            max="<?php echo esc_attr($args['max']); ?>" step="<?php echo esc_attr($args['step']); ?>">
        <?php
    }

    /**
     * チェックボックスフィールドをレンダリング
     */
    public function render_checkbox_field($args)
    {
        $options = self::get_options();
        $checked = !empty($options[$args['field']]);
        ?>
        <input type="checkbox" name="<?php echo esc_attr(self::OPTION_NAME . '[' . $args['field'] . ']'); ?>" value="1" <?php checked($checked); ?>>
        <?php
    }

    /**
     * テキストフィールドをレンダリング
     */
    public function render_text_field($args)
    {
        $options = self::get_options();
        $value = isset($options[$args['field']]) ? $options[$args['field']] : '';
        ?>
        <input type="text" class="regular-text" name="<?php echo esc_attr(self::OPTION_NAME . '[' . $args['field'] . ']'); ?>"
            value="<?php echo esc_attr($value); ?>">
        <?php
    }

    /**
     * 設定をサニタイズ
     *
     * @param array $input
     * @return array
     */
    public function sanitize_settings($input)
    {
        $sanitized = array();

        // カラー
        foreach (array('primary_color', 'secondary_color', 'button_color', 'button_text_color') as $key) {
            $sanitized[$key] = sanitize_hex_color($input[$key] ?? self::$defaults[$key]);
        }

        // 数値
        foreach (array('name_font_size', 'price_font_size') as $key) {
            $sanitized[$key] = floatval($input[$key] ?? self::$defaults[$key]);
        }

        // チェックボックス
        foreach (array('show_crown', 'show_tags') as $key) {
            $sanitized[$key] = !empty($input[$key]) ? '1' : '0';
        }

        // テキスト
        foreach (array('detail_button_text', 'affiliate_button_text') as $key) {
            $sanitized[$key] = sanitize_text_field($input[$key] ?? self::$defaults[$key]);
        }

        return $sanitized;
    }

    /**
     * カスタムCSSをヘッドに出力
     */
    public function output_custom_css()
    {
        $options = self::get_options();
        ?>
        <style id="mct-custom-styles">
            :root {
                --mct-primary-color:
                    <?php echo esc_attr($options['primary_color']); ?>
                ;
                --mct-secondary-color:
                    <?php echo esc_attr($options['secondary_color']); ?>
                ;
            }

            .my_comparison_btn_affiliate {
                background: linear-gradient(135deg,
                        <?php echo esc_attr($options['button_color']); ?>
                        ,
                        <?php echo esc_attr(self::adjust_brightness($options['button_color'], -20)); ?>
                    );
                color:
                    <?php echo esc_attr($options['button_text_color']); ?>
                ;
            }

            .my_comparison_name {
                font-size:
                    <?php echo esc_attr($options['name_font_size']); ?>
                    rem;
            }

            .my_comparison_price_value {
                font-size:
                    <?php echo esc_attr($options['price_font_size']); ?>
                    rem;
            }

            <?php if (empty($options['show_crown'])): ?>
                .my_comparison_rank_crown {
                    display: none;
                }

            <?php endif; ?>
            <?php if (empty($options['show_tags'])): ?>
                .my_comparison_tags {
                    display: none;
                }

            <?php endif; ?>
        </style>
        <?php
    }

    /**
     * オプションを取得
     *
     * @return array
     */
    public static function get_options()
    {
        $options = get_option(self::OPTION_NAME, array());
        return wp_parse_args($options, self::$defaults);
    }

    /**
     * 明度を調整
     *
     * @param string $hex
     * @param int    $percent
     * @return string
     */
    private static function adjust_brightness($hex, $percent)
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = max(0, min(255, $r + ($r * $percent / 100)));
        $g = max(0, min(255, $g + ($g * $percent / 100)));
        $b = max(0, min(255, $b + ($b * $percent / 100)));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}
