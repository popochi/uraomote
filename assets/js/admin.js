/**
 * 比較ランキング表 - 管理画面用JavaScript
 */

(function ($) {
    'use strict';

    $(document).ready(function () {
        // 評価プレビューの更新
        $('#mct_rating').on('input', function () {
            var rating = parseFloat($(this).val()) || 0;
            var stars = '';
            for (var i = 1; i <= 5; i++) {
                if (i <= Math.floor(rating)) {
                    stars += '★';
                } else {
                    stars += '☆';
                }
            }
            $('.mct-rating-preview').text(stars);
        });

        // 価格のフォーマット表示
        $('#mct_price').on('blur', function () {
            var price = parseInt($(this).val()) || 0;
            $(this).val(price);
        });
    });

})(jQuery);
