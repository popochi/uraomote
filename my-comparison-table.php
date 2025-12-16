<?php
/**
 * Plugin Name: 比較ランキング表 (My Comparison Table)
 * Plugin URI: https://example.com/my-comparison-table
 * Description: 絞り込み・並び替え機能付きの比較ランキング表を作成するプラグイン。AFFINGER6対応。
 * Version: 2.1.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL-2.0+
 * Text Domain: my-comparison-table
 */

// 直接アクセス禁止
if (!defined('ABSPATH')) {
    exit;
}

// プラグインの定数定義
define('MCT_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('MCT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MCT_VERSION', '2.1.0');

/**
 * 必要なクラスファイルを読み込み
 */
require_once MCT_PLUGIN_PATH . 'includes/class-mct-product.php';
require_once MCT_PLUGIN_PATH . 'includes/class-mct-settings.php';

/**
 * プラグイン初期化
 */
function mct_init()
{
    new MCT_Product();
    new MCT_Settings();
}
add_action('plugins_loaded', 'mct_init');

/**
 * スタイルとスクリプトのエンキュー
 */
function mct_enqueue_assets()
{
    wp_enqueue_style(
        'mct-style',
        MCT_PLUGIN_URL . 'assets/css/style.css',
        array(),
        MCT_VERSION
    );

    wp_enqueue_script(
        'mct-script',
        MCT_PLUGIN_URL . 'assets/js/script.js',
        array(),
        MCT_VERSION,
        true
    );

    // AJAX用のURLを渡す
    wp_localize_script('mct-script', 'mct_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mct_ajax_nonce'),
    ));
}
add_action('wp_enqueue_scripts', 'mct_enqueue_assets');

/**
 * AJAX: カテゴリ別商品を取得
 */
function mct_ajax_get_products_by_category()
{
    check_ajax_referer('mct_ajax_nonce', 'nonce');

    $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';

    if ($category === 'all' || empty($category)) {
        $products = MCT_Product::get_products();
    } else {
        $products = MCT_Product::get_products(array('category' => $category));
    }

    // 設定を取得
    $settings = MCT_Settings::get_options();

    // HTMLを生成
    ob_start();
    if (!empty($products)) {
        foreach ($products as $index => $item):
            $rank = isset($item['original_rank']) ? $item['original_rank'] : ($index + 1);
            $tags_str = isset($item['tags']) ? implode(',', $item['tags']) : '';
            ?>
            <div class="my_comparison_item" data-rank="<?php echo esc_attr($rank); ?>"
                data-price="<?php echo esc_attr($item['price'] ?? 0); ?>"
                data-rating="<?php echo esc_attr($item['rating'] ?? 0); ?>" data-tags="<?php echo esc_attr($tags_str); ?>"
                data-original-rank="<?php echo esc_attr($rank); ?>">

                <div class="my_comparison_rank">
                    <span class="my_comparison_rank_crown my_comparison_rank_<?php echo $rank <= 3 ? $rank : 'other'; ?>"></span>
                    <span class="my_comparison_rank_number"><?php echo esc_html($rank); ?></span>
                </div>

                <div class="my_comparison_image">
                    <?php if (!empty($item['image'])): ?>
                        <img src="<?php echo esc_url($item['image']); ?>" alt="<?php echo esc_attr($item['name'] ?? ''); ?>">
                    <?php else: ?>
                        <div class="my_comparison_no_image">No Image</div>
                    <?php endif; ?>
                </div>

                <div class="my_comparison_info">
                    <h3 class="my_comparison_name"><?php echo esc_html($item['name'] ?? ''); ?></h3>

                    <div class="my_comparison_rating">
                        <span class="my_comparison_stars">
                            <?php
                            $rating = floatval($item['rating'] ?? 0);
                            for ($i = 1; $i <= 5; $i++):
                                if ($i <= floor($rating)):
                                    echo '<span class="my_comparison_star my_comparison_star_full">★</span>';
                                elseif ($i - 0.5 <= $rating):
                                    echo '<span class="my_comparison_star my_comparison_star_half">★</span>';
                                else:
                                    echo '<span class="my_comparison_star my_comparison_star_empty">☆</span>';
                                endif;
                            endfor;
                            ?>
                        </span>
                        <span class="my_comparison_rating_value"><?php echo esc_html($rating); ?></span>
                    </div>

                    <div class="my_comparison_price">
                        <span class="my_comparison_price_value">¥<?php echo esc_html(number_format($item['price'] ?? 0)); ?></span>
                        <span class="my_comparison_price_tax">（税込）</span>
                    </div>

                    <?php if (!empty($item['tags'])): ?>
                        <div class="my_comparison_tags">
                            <?php foreach ($item['tags'] as $tag): ?>
                                <span class="my_comparison_tag"><?php echo esc_html($tag); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="my_comparison_buttons">
                    <?php if (!empty($item['detail_url'])): ?>
                        <a href="<?php echo esc_url($item['detail_url']); ?>" class="my_comparison_btn my_comparison_btn_detail">
                            <?php echo esc_html($settings['detail_button_text']); ?>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($item['affiliate_url'])): ?>
                        <a href="<?php echo esc_url($item['affiliate_url']); ?>" class="my_comparison_btn my_comparison_btn_affiliate"
                            target="_blank" rel="noopener noreferrer">
                            <?php echo esc_html($settings['affiliate_button_text']); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php
        endforeach;
    }
    $html = ob_get_clean();

    // タグを収集
    $all_tags = array();
    foreach ($products as $item) {
        if (isset($item['tags']) && is_array($item['tags'])) {
            $all_tags = array_merge($all_tags, $item['tags']);
        }
    }
    $all_tags = array_unique(array_values($all_tags));

    wp_send_json_success(array(
        'html' => $html,
        'products' => $products,
        'tags' => $all_tags,
        'count' => count($products),
    ));
}
add_action('wp_ajax_mct_get_products', 'mct_ajax_get_products_by_category');
add_action('wp_ajax_nopriv_mct_get_products', 'mct_ajax_get_products_by_category');

/**
 * カテゴリ一覧を取得
 */
function mct_get_categories()
{
    $terms = get_terms(array(
        'taxonomy' => 'mct_category',
        'hide_empty' => true,
    ));

    $categories = array();
    if (!is_wp_error($terms)) {
        foreach ($terms as $term) {
            $categories[] = array(
                'slug' => $term->slug,
                'name' => $term->name,
                'count' => $term->count,
            );
        }
    }
    return $categories;
}

/**
 * ショートコード [my_comparison_table] の処理
 * 
 * @param array $atts ショートコード属性
 * @return string HTML出力
 */
function mct_comparison_table_shortcode($atts)
{
    $atts = shortcode_atts(array(
        'data' => '',
        'ids' => '',
        'category' => '',
        'show_filter' => 'true',
        'show_sort' => 'true',
        'show_category_modal' => 'true',
    ), $atts, 'my_comparison_table');

    // データ取得方法を判定
    $data = array();
    $current_category = '';

    // 1. 商品ID指定
    if (!empty($atts['ids'])) {
        $data = MCT_Product::get_products(array('ids' => $atts['ids']));
    }
    // 2. カテゴリー指定
    elseif (!empty($atts['category'])) {
        $data = MCT_Product::get_products(array('category' => $atts['category']));
        $current_category = $atts['category'];
    }
    // 3. JSON形式（従来方式）
    elseif (!empty($atts['data'])) {
        $data = json_decode(html_entity_decode($atts['data']), true);
    }
    // 4. 指定なしの場合は全商品
    else {
        $data = MCT_Product::get_products();
    }

    if (!is_array($data) || empty($data)) {
        return '<p class="my_comparison_error">比較データが見つかりません。</p>';
    }

    // 設定を取得
    $settings = MCT_Settings::get_options();

    // カテゴリ一覧を取得
    $categories = mct_get_categories();

    // すべてのタグを収集（絞り込み用）
    $all_tags = array();
    foreach ($data as $item) {
        if (isset($item['tags']) && is_array($item['tags'])) {
            $all_tags = array_merge($all_tags, $item['tags']);
        }
    }
    $all_tags = array_unique($all_tags);

    // ユニークIDを生成
    $table_id = 'mct_' . uniqid();

    // HTML出力開始
    ob_start();
    ?>
    <div class="my_comparison_wrapper" id="<?php echo esc_attr($table_id); ?>"
        data-items='<?php echo esc_attr(json_encode($data, JSON_UNESCAPED_UNICODE)); ?>'
        data-current-category="<?php echo esc_attr($current_category); ?>">

        <?php if ($atts['show_category_modal'] === 'true' && !empty($categories)): ?>
            <!-- カテゴリ切り替えタブ -->
            <div class="my_comparison_category_tabs">
                <div class="my_comparison_category_tabs_list">
                    <button type="button"
                        class="my_comparison_category_tab <?php echo empty($current_category) ? 'active' : ''; ?>"
                        data-category="all">
                        すべて
                    </button>
                    <?php foreach ($categories as $cat): ?>
                        <button type="button"
                            class="my_comparison_category_tab <?php echo $current_category === $cat['slug'] ? 'active' : ''; ?>"
                            data-category="<?php echo esc_attr($cat['slug']); ?>">
                            <?php echo esc_html($cat['name']); ?>
                            <span class="my_comparison_category_tab_count"><?php echo esc_html($cat['count']); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($atts['show_sort'] === 'true' || $atts['show_filter'] === 'true'): ?>
            <div class="my_comparison_controls">

                <?php if ($atts['show_sort'] === 'true'): ?>
                    <!-- 並び替えボタン -->
                    <div class="my_comparison_sort">
                        <span class="my_comparison_sort_label">並び替え：</span>
                        <button type="button" class="my_comparison_sort_btn" data-sort="rank" data-order="asc">
                            ランキング順
                        </button>
                        <button type="button" class="my_comparison_sort_btn" data-sort="price" data-order="asc">
                            価格が安い順
                        </button>
                        <button type="button" class="my_comparison_sort_btn" data-sort="price" data-order="desc">
                            価格が高い順
                        </button>
                        <button type="button" class="my_comparison_sort_btn" data-sort="rating" data-order="desc">
                            評価が高い順
                        </button>
                    </div>
                <?php endif; ?>

                <?php if ($atts['show_filter'] === 'true' && !empty($all_tags)): ?>
                    <!-- 絞り込みチェックボックス -->
                    <div class="my_comparison_filter">
                        <span class="my_comparison_filter_label">こだわり検索：</span>
                        <div class="my_comparison_filter_tags">
                            <?php foreach ($all_tags as $tag): ?>
                                <label class="my_comparison_filter_item">
                                    <input type="checkbox" class="my_comparison_filter_checkbox" value="<?php echo esc_attr($tag); ?>">
                                    <span class="my_comparison_filter_text"><?php echo esc_html($tag); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        <?php endif; ?>

        <!-- ローディング -->
        <div class="my_comparison_loading" style="display: none;">
            <div class="my_comparison_loading_spinner"></div>
            <span>読み込み中...</span>
        </div>

        <!-- 比較表本体 -->
        <div class="my_comparison_table">
            <?php foreach ($data as $index => $item):
                $rank = isset($item['original_rank']) ? $item['original_rank'] : ($index + 1);
                $tags_str = isset($item['tags']) ? implode(',', $item['tags']) : '';
                ?>
                <div class="my_comparison_item" data-rank="<?php echo esc_attr($rank); ?>"
                    data-price="<?php echo esc_attr($item['price'] ?? 0); ?>"
                    data-rating="<?php echo esc_attr($item['rating'] ?? 0); ?>" data-tags="<?php echo esc_attr($tags_str); ?>"
                    data-original-rank="<?php echo esc_attr($rank); ?>">

                    <!-- 順位 -->
                    <div class="my_comparison_rank">
                        <span
                            class="my_comparison_rank_crown my_comparison_rank_<?php echo $rank <= 3 ? $rank : 'other'; ?>"></span>
                        <span class="my_comparison_rank_number"><?php echo esc_html($rank); ?></span>
                    </div>

                    <!-- 商品画像 -->
                    <div class="my_comparison_image">
                        <?php if (!empty($item['image'])): ?>
                            <img src="<?php echo esc_url($item['image']); ?>" alt="<?php echo esc_attr($item['name'] ?? ''); ?>">
                        <?php else: ?>
                            <div class="my_comparison_no_image">No Image</div>
                        <?php endif; ?>
                    </div>

                    <!-- 商品情報 -->
                    <div class="my_comparison_info">
                        <h3 class="my_comparison_name"><?php echo esc_html($item['name'] ?? ''); ?></h3>

                        <!-- 評価 -->
                        <div class="my_comparison_rating">
                            <span class="my_comparison_stars">
                                <?php
                                $rating = floatval($item['rating'] ?? 0);
                                for ($i = 1; $i <= 5; $i++):
                                    if ($i <= floor($rating)):
                                        echo '<span class="my_comparison_star my_comparison_star_full">★</span>';
                                    elseif ($i - 0.5 <= $rating):
                                        echo '<span class="my_comparison_star my_comparison_star_half">★</span>';
                                    else:
                                        echo '<span class="my_comparison_star my_comparison_star_empty">☆</span>';
                                    endif;
                                endfor;
                                ?>
                            </span>
                            <span class="my_comparison_rating_value"><?php echo esc_html($rating); ?></span>
                        </div>

                        <!-- 価格 -->
                        <div class="my_comparison_price">
                            <span
                                class="my_comparison_price_value">¥<?php echo esc_html(number_format($item['price'] ?? 0)); ?></span>
                            <span class="my_comparison_price_tax">（税込）</span>
                        </div>

                        <!-- 特徴タグ -->
                        <?php if (!empty($item['tags'])): ?>
                            <div class="my_comparison_tags">
                                <?php foreach ($item['tags'] as $tag): ?>
                                    <span class="my_comparison_tag"><?php echo esc_html($tag); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- ボタン -->
                    <div class="my_comparison_buttons">
                        <?php if (!empty($item['detail_url'])): ?>
                            <a href="<?php echo esc_url($item['detail_url']); ?>"
                                class="my_comparison_btn my_comparison_btn_detail">
                                <?php echo esc_html($settings['detail_button_text']); ?>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($item['affiliate_url'])): ?>
                            <a href="<?php echo esc_url($item['affiliate_url']); ?>"
                                class="my_comparison_btn my_comparison_btn_affiliate" target="_blank" rel="noopener noreferrer">
                                <?php echo esc_html($settings['affiliate_button_text']); ?>
                            </a>
                        <?php endif; ?>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>

        <!-- 該当なしメッセージ -->
        <div class="my_comparison_no_results" style="display: none;">
            条件に一致する商品がありません。
        </div>

    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('my_comparison_table', 'mct_comparison_table_shortcode');

/**
 * プラグイン有効化時の処理
 */
function mct_activate()
{
    $product = new MCT_Product();
    $product->register_post_type();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'mct_activate');

/**
 * プラグイン無効化時の処理
 */
function mct_deactivate()
{
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'mct_deactivate');
