<?php
/**
 * MCT Product - カスタム投稿タイプとメタボックス管理
 *
 * @package My_Comparison_Table
 */

if (!defined('ABSPATH')) {
    exit;
}

class MCT_Product
{

    /**
     * 投稿タイプ名
     */
    const POST_TYPE = 'mct_product';

    /**
     * メタキーのプレフィックス
     */
    const META_PREFIX = '_mct_';

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta'), 10, 2);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * カスタム投稿タイプを登録
     */
    public function register_post_type()
    {
        $labels = array(
            'name' => '比較商品',
            'singular_name' => '比較商品',
            'menu_name' => '比較商品',
            'add_new' => '新規追加',
            'add_new_item' => '新規商品を追加',
            'edit_item' => '商品を編集',
            'new_item' => '新規商品',
            'view_item' => '商品を表示',
            'search_items' => '商品を検索',
            'not_found' => '商品が見つかりません',
            'not_found_in_trash' => 'ゴミ箱に商品はありません',
        );

        $args = array(
            'labels' => $labels,
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => false,
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => 25,
            'menu_icon' => 'dashicons-chart-bar',
            'supports' => array('title', 'thumbnail'),
            'show_in_rest' => true,
        );

        register_post_type(self::POST_TYPE, $args);

        // カスタムタクソノミー（カテゴリー）を登録
        register_taxonomy('mct_category', self::POST_TYPE, array(
            'labels' => array(
                'name' => '商品カテゴリー',
                'singular_name' => '商品カテゴリー',
                'add_new_item' => '新規カテゴリーを追加',
                'edit_item' => 'カテゴリーを編集',
            ),
            'public' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'hierarchical' => true,
            'show_in_rest' => true,
        ));
    }

    /**
     * メタボックスを追加
     */
    public function add_meta_boxes()
    {
        add_meta_box(
            'mct_product_details',
            '商品詳細',
            array($this, 'render_meta_box'),
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    /**
     * メタボックスをレンダリング
     *
     * @param WP_Post $post
     */
    public function render_meta_box($post)
    {
        wp_nonce_field('mct_save_meta', 'mct_meta_nonce');

        $rating = get_post_meta($post->ID, self::META_PREFIX . 'rating', true);
        $price = get_post_meta($post->ID, self::META_PREFIX . 'price', true);
        $tags = get_post_meta($post->ID, self::META_PREFIX . 'tags', true);
        $detail_url = get_post_meta($post->ID, self::META_PREFIX . 'detail_url', true);
        $affiliate_url = get_post_meta($post->ID, self::META_PREFIX . 'affiliate_url', true);
        $display_order = get_post_meta($post->ID, self::META_PREFIX . 'display_order', true);
        ?>
        <div class="mct-admin-meta-box">
            <table class="form-table">
                <tr>
                    <th><label for="mct_display_order">表示順位</label></th>
                    <td>
                        <input type="number" id="mct_display_order" name="mct_display_order"
                            value="<?php echo esc_attr($display_order); ?>" min="1" class="small-text">
                        <p class="description">数字が小さいほど上位に表示されます（1が1位）</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="mct_rating">評価（1〜5）</label></th>
                    <td>
                        <input type="number" id="mct_rating" name="mct_rating" value="<?php echo esc_attr($rating); ?>"
                            step="0.1" min="0" max="5" class="small-text">
                        <span class="mct-rating-preview">
                            <?php echo str_repeat('★', floor($rating ?: 0)) . str_repeat('☆', 5 - floor($rating ?: 0)); ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th><label for="mct_price">価格（税込）</label></th>
                    <td>
                        <input type="number" id="mct_price" name="mct_price" value="<?php echo esc_attr($price); ?>" min="0"
                            class="regular-text">
                        <span>円</span>
                    </td>
                </tr>
                <tr>
                    <th><label for="mct_tags">特徴タグ</label></th>
                    <td>
                        <input type="text" id="mct_tags" name="mct_tags" value="<?php echo esc_attr($tags); ?>"
                            class="large-text">
                        <p class="description">カンマ区切りで入力（例：送料無料,初心者向け,キャンペーン中）</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="mct_detail_url">詳細ページURL</label></th>
                    <td>
                        <input type="url" id="mct_detail_url" name="mct_detail_url" value="<?php echo esc_url($detail_url); ?>"
                            class="large-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="mct_affiliate_url">アフィリエイトURL</label></th>
                    <td>
                        <input type="url" id="mct_affiliate_url" name="mct_affiliate_url"
                            value="<?php echo esc_url($affiliate_url); ?>" class="large-text">
                    </td>
                </tr>
            </table>
        </div>
        <style>
            .mct-admin-meta-box .form-table th {
                width: 150px;
            }

            .mct-rating-preview {
                color: #ffd700;
                font-size: 18px;
                margin-left: 10px;
            }
        </style>
        <?php
    }

    /**
     * メタデータを保存
     *
     * @param int     $post_id
     * @param WP_Post $post
     */
    public function save_meta($post_id, $post)
    {
        // Nonce検証
        if (!isset($_POST['mct_meta_nonce']) || !wp_verify_nonce($_POST['mct_meta_nonce'], 'mct_save_meta')) {
            return;
        }

        // 自動保存をスキップ
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // 権限チェック
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // 投稿タイプチェック
        if ($post->post_type !== self::POST_TYPE) {
            return;
        }

        // メタデータを保存
        $fields = array(
            'display_order' => 'intval',
            'rating' => 'floatval',
            'price' => 'intval',
            'tags' => 'sanitize_text_field',
            'detail_url' => 'esc_url_raw',
            'affiliate_url' => 'esc_url_raw',
        );

        foreach ($fields as $field => $sanitize_callback) {
            $key = 'mct_' . $field;
            if (isset($_POST[$key])) {
                $value = call_user_func($sanitize_callback, $_POST[$key]);
                update_post_meta($post_id, self::META_PREFIX . $field, $value);
            }
        }
    }

    /**
     * 管理画面用スクリプトをエンキュー
     *
     * @param string $hook
     */
    public function enqueue_admin_scripts($hook)
    {
        global $post_type;

        if ($post_type === self::POST_TYPE && ($hook === 'post.php' || $hook === 'post-new.php')) {
            wp_enqueue_media();
            wp_enqueue_script(
                'mct-admin',
                MCT_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'),
                MCT_VERSION,
                true
            );
            wp_enqueue_style(
                'mct-admin',
                MCT_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                MCT_VERSION
            );
        }
    }

    /**
     * 商品データを取得
     *
     * @param array $args 取得条件
     * @return array 商品データの配列
     */
    public static function get_products($args = array())
    {
        $defaults = array(
            'post_type' => self::POST_TYPE,
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'meta_value_num',
            'meta_key' => self::META_PREFIX . 'display_order',
            'order' => 'ASC',
        );

        // IDsが指定されている場合
        if (!empty($args['ids'])) {
            $defaults['post__in'] = array_map('intval', explode(',', $args['ids']));
            $defaults['orderby'] = 'post__in';
        }

        // カテゴリーが指定されている場合
        if (!empty($args['category'])) {
            $defaults['tax_query'] = array(
                array(
                    'taxonomy' => 'mct_category',
                    'field' => 'slug',
                    'terms' => sanitize_text_field($args['category']),
                ),
            );
        }

        $query = new WP_Query($defaults);
        $products = array();

        if ($query->have_posts()) {
            $rank = 1;
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();

                $tags_str = get_post_meta($post_id, self::META_PREFIX . 'tags', true);
                $tags = array_filter(array_map('trim', explode(',', $tags_str)));

                $image_url = '';
                if (has_post_thumbnail($post_id)) {
                    $image_url = get_the_post_thumbnail_url($post_id, 'medium');
                }

                $products[] = array(
                    'id' => $post_id,
                    'name' => get_the_title(),
                    'image' => $image_url,
                    'rating' => floatval(get_post_meta($post_id, self::META_PREFIX . 'rating', true)),
                    'price' => intval(get_post_meta($post_id, self::META_PREFIX . 'price', true)),
                    'tags' => $tags,
                    'detail_url' => get_post_meta($post_id, self::META_PREFIX . 'detail_url', true),
                    'affiliate_url' => get_post_meta($post_id, self::META_PREFIX . 'affiliate_url', true),
                    'original_rank' => $rank,
                );
                $rank++;
            }
            wp_reset_postdata();
        }

        return $products;
    }
}
