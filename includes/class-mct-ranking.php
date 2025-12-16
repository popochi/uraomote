<?php
/**
 * ランキング管理クラス
 * カスタム投稿タイプ「mct_ranking」を管理
 */

if (!defined('ABSPATH')) {
    exit;
}

class MCT_Ranking
{

    const POST_TYPE = 'mct_ranking';
    const META_PREFIX = '_mct_ranking_';

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta'), 10, 2);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // ランキング一覧のカラム
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', array($this, 'add_columns'));
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', array($this, 'render_columns'), 10, 2);
    }

    /**
     * カスタム投稿タイプを登録
     */
    public function register_post_type()
    {
        $labels = array(
            'name' => 'ランキング',
            'singular_name' => 'ランキング',
            'menu_name' => 'ランキング',
            'add_new' => '新規追加',
            'add_new_item' => '新しいランキングを追加',
            'edit_item' => 'ランキングを編集',
            'new_item' => '新しいランキング',
            'view_item' => 'ランキングを見る',
            'search_items' => 'ランキングを検索',
            'not_found' => 'ランキングが見つかりません',
            'not_found_in_trash' => 'ゴミ箱にランキングはありません',
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
            'menu_position' => 26,
            'menu_icon' => 'dashicons-awards',
            'supports' => array('title'),
            'show_in_rest' => false,
        );

        register_post_type(self::POST_TYPE, $args);
    }

    /**
     * メタボックスを追加
     */
    public function add_meta_boxes()
    {
        // ショートコード表示
        add_meta_box(
            'mct_ranking_shortcode',
            'ショートコード',
            array($this, 'render_shortcode_meta_box'),
            self::POST_TYPE,
            'side',
            'high'
        );

        // 商品選択
        add_meta_box(
            'mct_ranking_products',
            '商品を選択・並び替え',
            array($this, 'render_products_meta_box'),
            self::POST_TYPE,
            'normal',
            'high'
        );

        // 表示オプション
        add_meta_box(
            'mct_ranking_options',
            '表示オプション',
            array($this, 'render_options_meta_box'),
            self::POST_TYPE,
            'side',
            'default'
        );
    }

    /**
     * ショートコードメタボックス表示
     */
    public function render_shortcode_meta_box($post)
    {
        $shortcode = '[my_comparison_table ranking_id="' . $post->ID . '"]';
        ?>
        <div class="mct-shortcode-box">
            <input type="text" readonly value="<?php echo esc_attr($shortcode); ?>" id="mct-shortcode-input" class="large-text"
                onclick="this.select();">
            <button type="button" class="button button-secondary" id="mct-copy-shortcode"
                data-shortcode="<?php echo esc_attr($shortcode); ?>">
                📋 コピー
            </button>
            <p class="description">このショートコードを記事に貼り付けてください。</p>
        </div>
        <?php
    }

    /**
     * 商品選択メタボックス表示
     */
    public function render_products_meta_box($post)
    {
        wp_nonce_field('mct_save_ranking', 'mct_ranking_nonce');

        $selected_products = get_post_meta($post->ID, self::META_PREFIX . 'products', true);
        if (!is_array($selected_products)) {
            $selected_products = array();
        }

        // 全商品を取得
        $all_products = MCT_Product::get_products();
        ?>
        <div class="mct-ranking-products">
            <div class="mct-selected-products">
                <h4>選択中の商品（ドラッグで並び替え）</h4>
                <ul id="mct-sortable-products" class="mct-sortable">
                    <?php
                    foreach ($selected_products as $product_id) {
                        $product = $this->get_product_by_id($all_products, $product_id);
                        if ($product) {
                            ?>
                            <li data-id="<?php echo esc_attr($product_id); ?>">
                                <span class="dashicons dashicons-menu"></span>
                                <span class="mct-product-name"><?php echo esc_html($product['name']); ?></span>
                                <span class="mct-product-price">¥<?php echo number_format($product['price']); ?></span>
                                <button type="button" class="mct-remove-product button-link">✕</button>
                                <input type="hidden" name="mct_ranking_products[]" value="<?php echo esc_attr($product_id); ?>">
                            </li>
                            <?php
                        }
                    }
                    ?>
                </ul>
                <p class="mct-empty-message" <?php echo !empty($selected_products) ? 'style="display:none;"' : ''; ?>>
                    商品が選択されていません。下のリストから商品を追加してください。
                </p>
            </div>

            <div class="mct-available-products">
                <h4>商品を追加</h4>
                <input type="text" id="mct-product-search" placeholder="商品名で検索..." class="regular-text">
                <ul id="mct-available-products-list">
                    <?php foreach ($all_products as $product): ?>
                        <li data-id="<?php echo esc_attr($product['id']); ?>"
                            data-name="<?php echo esc_attr(strtolower($product['name'])); ?>" <?php echo in_array($product['id'], $selected_products) ? 'class="already-added"' : ''; ?>>
                            <?php if (!empty($product['image'])): ?>
                                <img src="<?php echo esc_url($product['image']); ?>" alt="" class="mct-product-thumb">
                            <?php endif; ?>
                            <span class="mct-product-name"><?php echo esc_html($product['name']); ?></span>
                            <span class="mct-product-price">¥<?php echo number_format($product['price']); ?></span>
                            <button type="button" class="mct-add-product button button-small">
                                <?php echo in_array($product['id'], $selected_products) ? '追加済み' : '+ 追加'; ?>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * 表示オプションメタボックス表示
     */
    public function render_options_meta_box($post)
    {
        $show_sort = get_post_meta($post->ID, self::META_PREFIX . 'show_sort', true);
        $show_filter = get_post_meta($post->ID, self::META_PREFIX . 'show_filter', true);
        $limit = get_post_meta($post->ID, self::META_PREFIX . 'limit', true);

        // デフォルト値
        if ($show_sort === '')
            $show_sort = '1';
        if ($show_filter === '')
            $show_filter = '1';
        if ($limit === '')
            $limit = '10';
        ?>
        <p>
            <label>
                <input type="checkbox" name="mct_show_sort" value="1" <?php checked($show_sort, '1'); ?>>
                並び替えボタンを表示
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" name="mct_show_filter" value="1" <?php checked($show_filter, '1'); ?>>
                絞り込みを表示
            </label>
        </p>
        <p>
            <label for="mct_limit">表示件数:</label>
            <input type="number" name="mct_limit" id="mct_limit" value="<?php echo esc_attr($limit); ?>" min="1" max="100"
                style="width: 60px;"> 件
        </p>
        <?php
    }

    /**
     * メタデータを保存
     */
    public function save_meta($post_id, $post)
    {
        // 検証
        if (!isset($_POST['mct_ranking_nonce']) || !wp_verify_nonce($_POST['mct_ranking_nonce'], 'mct_save_ranking')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if ($post->post_type !== self::POST_TYPE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // 商品リスト
        $products = isset($_POST['mct_ranking_products']) ? array_map('intval', $_POST['mct_ranking_products']) : array();
        update_post_meta($post_id, self::META_PREFIX . 'products', $products);

        // 表示オプション
        update_post_meta($post_id, self::META_PREFIX . 'show_sort', isset($_POST['mct_show_sort']) ? '1' : '0');
        update_post_meta($post_id, self::META_PREFIX . 'show_filter', isset($_POST['mct_show_filter']) ? '1' : '0');
        update_post_meta($post_id, self::META_PREFIX . 'limit', intval($_POST['mct_limit']));
    }

    /**
     * 管理画面用スクリプト読み込み
     */
    public function enqueue_admin_scripts($hook)
    {
        global $post;

        if (($hook === 'post.php' || $hook === 'post-new.php') && isset($post) && $post->post_type === self::POST_TYPE) {
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_style('mct-ranking-admin', MCT_PLUGIN_URL . 'assets/css/ranking-admin.css', array(), MCT_VERSION);
            wp_enqueue_script('mct-ranking-admin', MCT_PLUGIN_URL . 'assets/js/ranking-admin.js', array('jquery', 'jquery-ui-sortable'), MCT_VERSION, true);
        }
    }

    /**
     * 一覧画面のカラムを追加
     */
    public function add_columns($columns)
    {
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['shortcode'] = 'ショートコード';
                $new_columns['product_count'] = '商品数';
            }
        }
        return $new_columns;
    }

    /**
     * カラムの内容を表示
     */
    public function render_columns($column, $post_id)
    {
        switch ($column) {
            case 'shortcode':
                $shortcode = '[my_comparison_table ranking_id="' . $post_id . '"]';
                echo '<code style="background:#f0f0f0; padding:3px 6px; border-radius:3px;">' . esc_html($shortcode) . '</code>';
                break;
            case 'product_count':
                $products = get_post_meta($post_id, self::META_PREFIX . 'products', true);
                $count = is_array($products) ? count($products) : 0;
                echo esc_html($count) . '件';
                break;
        }
    }

    /**
     * ランキングの商品一覧を取得
     */
    public static function get_ranking_products($ranking_id)
    {
        $product_ids = get_post_meta($ranking_id, self::META_PREFIX . 'products', true);
        if (!is_array($product_ids) || empty($product_ids)) {
            return array();
        }

        return MCT_Product::get_products(array('ids' => $product_ids));
    }

    /**
     * ランキングの表示オプションを取得
     */
    public static function get_ranking_options($ranking_id)
    {
        return array(
            'show_sort' => get_post_meta($ranking_id, self::META_PREFIX . 'show_sort', true) === '1',
            'show_filter' => get_post_meta($ranking_id, self::META_PREFIX . 'show_filter', true) === '1',
            'limit' => intval(get_post_meta($ranking_id, self::META_PREFIX . 'limit', true)) ?: 10,
        );
    }

    /**
     * 商品をIDで取得（ヘルパー）
     */
    private function get_product_by_id($products, $id)
    {
        foreach ($products as $product) {
            if ($product['id'] == $id) {
                return $product;
            }
        }
        return null;
    }
}

// インスタンス化
new MCT_Ranking();
