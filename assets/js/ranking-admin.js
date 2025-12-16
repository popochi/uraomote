/**
 * ランキング管理画面用JavaScript
 */

(function ($) {
    'use strict';

    $(document).ready(function () {
        initSortable();
        initProductSearch();
        initAddRemoveProducts();
        initCopyShortcode();
    });

    /**
     * ドラッグ&ドロップで並び替え
     */
    function initSortable() {
        $('#mct-sortable-products').sortable({
            handle: '.dashicons-menu',
            placeholder: 'ui-sortable-placeholder',
            forcePlaceholderSize: true,
            update: function () {
                toggleEmptyMessage();
            }
        });
    }

    /**
     * 商品検索
     */
    function initProductSearch() {
        $('#mct-product-search').on('input', function () {
            var query = $(this).val().toLowerCase();

            $('#mct-available-products-list li').each(function () {
                var name = $(this).data('name');
                if (name.indexOf(query) !== -1) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });
    }

    /**
     * 商品の追加・削除
     */
    function initAddRemoveProducts() {
        // 商品を追加
        $(document).on('click', '.mct-add-product', function () {
            var $li = $(this).closest('li');
            var id = $li.data('id');
            var name = $li.find('.mct-product-name').text();
            var price = $li.find('.mct-product-price').text();

            // 既に追加済みの場合はスキップ
            if ($li.hasClass('already-added')) {
                return;
            }

            // 選択リストに追加
            var newItem = '<li data-id="' + id + '">' +
                '<span class="dashicons dashicons-menu"></span>' +
                '<span class="mct-product-name">' + escapeHtml(name) + '</span>' +
                '<span class="mct-product-price">' + escapeHtml(price) + '</span>' +
                '<button type="button" class="mct-remove-product button-link">✕</button>' +
                '<input type="hidden" name="mct_ranking_products[]" value="' + id + '">' +
                '</li>';

            $('#mct-sortable-products').append(newItem);

            // 追加済みとしてマーク
            $li.addClass('already-added');
            $(this).text('追加済み');

            toggleEmptyMessage();

            // フィードバックアニメーション
            $('#mct-sortable-products li:last').hide().slideDown(200);
        });

        // 商品を削除
        $(document).on('click', '.mct-remove-product', function () {
            var $li = $(this).closest('li');
            var id = $li.data('id');

            // 削除アニメーション
            $li.slideUp(200, function () {
                $(this).remove();
                toggleEmptyMessage();
            });

            // 追加ボタンを復活
            $('#mct-available-products-list li[data-id="' + id + '"]')
                .removeClass('already-added')
                .find('.mct-add-product').text('+ 追加');
        });
    }

    /**
     * ショートコードをコピー
     */
    function initCopyShortcode() {
        $('#mct-copy-shortcode').on('click', function () {
            var shortcode = $(this).data('shortcode');
            var $input = $('#mct-shortcode-input');

            // コピー
            $input.select();
            document.execCommand('copy');

            // フィードバック
            var $btn = $(this);
            var originalText = $btn.html();
            $btn.html('✓ コピーしました');
            $btn.css('background', '#4caf50').css('color', '#fff');

            setTimeout(function () {
                $btn.html(originalText);
                $btn.css('background', '').css('color', '');
            }, 2000);
        });
    }

    /**
     * 空メッセージの表示切り替え
     */
    function toggleEmptyMessage() {
        var count = $('#mct-sortable-products li').length;
        if (count === 0) {
            $('.mct-empty-message').show();
        } else {
            $('.mct-empty-message').hide();
        }
    }

    /**
     * HTMLエスケープ
     */
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

})(jQuery);
