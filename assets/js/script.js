/**
 * 比較ランキング表 (My Comparison Table) - JavaScript
 * Vanilla JS で並び替え・絞り込み・カテゴリ切り替え機能を実装
 */

(function () {
    'use strict';

    /**
     * DOMContentLoaded時に初期化
     */
    document.addEventListener('DOMContentLoaded', function () {
        var wrappers = document.querySelectorAll('.my_comparison_wrapper');
        wrappers.forEach(initComparisonTable);
    });

    /**
     * 比較表の初期化
     * @param {HTMLElement} wrapper 
     */
    function initComparisonTable(wrapper) {
        var sortButtons = wrapper.querySelectorAll('.my_comparison_sort_btn');
        var filterCheckboxes = wrapper.querySelectorAll('.my_comparison_filter_checkbox');
        var items = wrapper.querySelectorAll('.my_comparison_item');
        var tableContainer = wrapper.querySelector('.my_comparison_table');
        var noResults = wrapper.querySelector('.my_comparison_no_results');

        // 現在のソート状態
        var currentSort = { field: 'rank', order: 'asc' };

        /**
         * 並び替えボタンのイベント
         */
        sortButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var sortField = this.getAttribute('data-sort');
                var sortOrder = this.getAttribute('data-order');

                // アクティブ状態を更新
                sortButtons.forEach(function (b) { b.classList.remove('active'); });
                this.classList.add('active');

                // ソート状態を更新
                currentSort = { field: sortField, order: sortOrder };

                // ソート実行
                var currentItems = wrapper.querySelectorAll('.my_comparison_item');
                sortItems(tableContainer, currentItems, sortField, sortOrder);

                // 順位を再計算
                updateRanks(wrapper);
            });
        });

        /**
         * 絞り込みチェックボックスのイベント
         */
        filterCheckboxes.forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                var currentItems = wrapper.querySelectorAll('.my_comparison_item');
                var currentCheckboxes = wrapper.querySelectorAll('.my_comparison_filter_checkbox');
                filterItems(wrapper, currentItems, currentCheckboxes, noResults);

                // 絞り込み後も順位を再計算
                updateRanks(wrapper);
            });
        });

        // 初期状態で「ランキング順」をアクティブに
        var defaultBtn = wrapper.querySelector('.my_comparison_sort_btn[data-sort="rank"]');
        if (defaultBtn) {
            defaultBtn.classList.add('active');
        }

        // カテゴリタブの初期化
        initCategoryTabs(wrapper);
    }

    /**
     * カテゴリタブの初期化
     * @param {HTMLElement} wrapper 
     */
    function initCategoryTabs(wrapper) {
        var categoryTabs = wrapper.querySelectorAll('.my_comparison_category_tab');

        if (!categoryTabs.length) {
            return;
        }

        // カテゴリタブのクリック
        categoryTabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                var category = this.getAttribute('data-category');

                // アクティブ状態を更新
                categoryTabs.forEach(function (t) { t.classList.remove('active'); });
                this.classList.add('active');

                // 商品を取得
                loadProductsByCategory(wrapper, category);
            });
        });
    }

    /**
     * カテゴリ別に商品を読み込み
     * @param {HTMLElement} wrapper 
     * @param {string} category 
     */
    function loadProductsByCategory(wrapper, category) {
        var tableContainer = wrapper.querySelector('.my_comparison_table');
        var loading = wrapper.querySelector('.my_comparison_loading');
        var noResults = wrapper.querySelector('.my_comparison_no_results');
        var filterContainer = wrapper.querySelector('.my_comparison_filter_tags');

        // mct_ajax が定義されていない場合（デモ用）
        if (typeof mct_ajax === 'undefined') {
            console.log('AJAX not available (demo mode). Category:', category);
            return;
        }

        // ローディング表示
        if (loading) {
            loading.style.display = 'flex';
        }
        if (tableContainer) {
            tableContainer.style.opacity = '0.5';
        }

        // AJAX リクエスト
        var formData = new FormData();
        formData.append('action', 'mct_get_products');
        formData.append('category', category);
        formData.append('nonce', mct_ajax.nonce);

        fetch(mct_ajax.ajax_url, {
            method: 'POST',
            body: formData
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                // ローディング非表示
                if (loading) {
                    loading.style.display = 'none';
                }
                if (tableContainer) {
                    tableContainer.style.opacity = '1';
                }

                if (data.success && data.data.html) {
                    // 商品を更新
                    tableContainer.innerHTML = data.data.html;

                    // 絞り込みタグを更新
                    if (filterContainer && data.data.tags) {
                        updateFilterTags(wrapper, filterContainer, data.data.tags);
                    }

                    // 該当なしメッセージ
                    if (noResults) {
                        noResults.style.display = data.data.count === 0 ? 'block' : 'none';
                    }

                    // ソートボタンをリセット
                    var sortBtns = wrapper.querySelectorAll('.my_comparison_sort_btn');
                    sortBtns.forEach(function (btn) { btn.classList.remove('active'); });
                    var defaultBtn = wrapper.querySelector('.my_comparison_sort_btn[data-sort="rank"]');
                    if (defaultBtn) {
                        defaultBtn.classList.add('active');
                    }
                }
            })
            .catch(function (error) {
                console.error('Error loading products:', error);
                if (loading) {
                    loading.style.display = 'none';
                }
                if (tableContainer) {
                    tableContainer.style.opacity = '1';
                }
            });
    }

    /**
     * 絞り込みタグを更新
     * @param {HTMLElement} wrapper 
     * @param {HTMLElement} container 
     * @param {Array} tags 
     */
    function updateFilterTags(wrapper, container, tags) {
        container.innerHTML = '';

        tags.forEach(function (tag) {
            var label = document.createElement('label');
            label.className = 'my_comparison_filter_item';

            var checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.className = 'my_comparison_filter_checkbox';
            checkbox.value = tag;

            var text = document.createElement('span');
            text.className = 'my_comparison_filter_text';
            text.textContent = tag;

            label.appendChild(checkbox);
            label.appendChild(text);
            container.appendChild(label);

            // イベントを再登録
            checkbox.addEventListener('change', function () {
                var items = wrapper.querySelectorAll('.my_comparison_item');
                var checkboxes = wrapper.querySelectorAll('.my_comparison_filter_checkbox');
                var noResults = wrapper.querySelector('.my_comparison_no_results');
                filterItems(wrapper, items, checkboxes, noResults);
                updateRanks(wrapper);
            });
        });
    }

    /**
     * 商品を並び替え
     * @param {HTMLElement} container 
     * @param {NodeList} items 
     * @param {string} field 
     * @param {string} order 
     */
    function sortItems(container, items, field, order) {
        var itemsArray = Array.from(items);

        // フェードアウト
        itemsArray.forEach(function (item) {
            item.classList.add('fade-out');
        });

        // アニメーション完了後に並び替え
        setTimeout(function () {
            itemsArray.sort(function (a, b) {
                var aVal, bVal;

                if (field === 'rank') {
                    aVal = parseInt(a.getAttribute('data-original-rank'), 10);
                    bVal = parseInt(b.getAttribute('data-original-rank'), 10);
                } else if (field === 'price') {
                    aVal = parseFloat(a.getAttribute('data-price')) || 0;
                    bVal = parseFloat(b.getAttribute('data-price')) || 0;
                } else if (field === 'rating') {
                    aVal = parseFloat(a.getAttribute('data-rating')) || 0;
                    bVal = parseFloat(b.getAttribute('data-rating')) || 0;
                }

                if (order === 'asc') {
                    return aVal - bVal;
                } else {
                    return bVal - aVal;
                }
            });

            // DOMを再配置
            itemsArray.forEach(function (item, index) {
                item.classList.remove('fade-out');
                item.style.animationDelay = (index * 0.05) + 's';
                container.appendChild(item);
            });

            // アニメーション完了後にdelayをリセット
            setTimeout(function () {
                itemsArray.forEach(function (item) {
                    item.style.animationDelay = '';
                });
            }, 500);
        }, 200);
    }

    /**
     * 商品を絞り込み
     * @param {HTMLElement} wrapper 
     * @param {NodeList} items 
     * @param {NodeList} checkboxes 
     * @param {HTMLElement} noResults 
     */
    function filterItems(wrapper, items, checkboxes, noResults) {
        // 選択されたタグを取得
        var selectedTags = [];
        checkboxes.forEach(function (cb) {
            if (cb.checked) {
                selectedTags.push(cb.value);
            }
        });

        var visibleItems = [];
        var hiddenItems = [];

        items.forEach(function (item) {
            var itemTags = item.getAttribute('data-tags').split(',').filter(function (t) {
                return t.trim() !== '';
            });

            // タグが選択されていない場合は全て表示
            if (selectedTags.length === 0) {
                visibleItems.push(item);
                return;
            }

            // AND条件: 選択された全てのタグを持っているか確認
            var hasAllTags = selectedTags.every(function (tag) {
                return itemTags.includes(tag);
            });

            if (hasAllTags) {
                visibleItems.push(item);
            } else {
                hiddenItems.push(item);
            }
        });

        // 非表示アイテム: スケールダウンしながらフェードアウト
        hiddenItems.forEach(function (item) {
            item.style.transition = 'opacity 0.25s ease-out, transform 0.25s ease-out';
            item.style.opacity = '0';
            item.style.transform = 'scale(0.95)';
        });

        // アニメーション後に非表示
        setTimeout(function () {
            hiddenItems.forEach(function (item) {
                item.classList.add('hidden');
                item.style.display = 'none';
                item.style.transition = '';
                item.style.opacity = '';
                item.style.transform = '';
            });

            // 表示アイテム: 順次フェードイン
            visibleItems.forEach(function (item, index) {
                var wasHidden = item.classList.contains('hidden');
                item.classList.remove('hidden');
                item.style.display = '';

                if (wasHidden) {
                    item.style.opacity = '0';
                    item.style.transform = 'translateY(20px)';

                    setTimeout(function () {
                        item.style.transition = 'opacity 0.4s ease-out, transform 0.4s ease-out';
                        item.style.opacity = '1';
                        item.style.transform = 'translateY(0)';

                        // アニメーション完了後にスタイルをクリア
                        setTimeout(function () {
                            item.style.transition = '';
                            item.style.opacity = '';
                            item.style.transform = '';
                        }, 400);
                    }, index * 60);
                }
            });

            // 該当なしメッセージの表示/非表示
            if (noResults) {
                if (visibleItems.length === 0) {
                    noResults.style.display = 'block';
                    noResults.style.opacity = '0';
                    noResults.style.transform = 'translateY(10px)';
                    setTimeout(function () {
                        noResults.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
                        noResults.style.opacity = '1';
                        noResults.style.transform = 'translateY(0)';
                    }, 50);
                } else {
                    noResults.style.display = 'none';
                }
            }
        }, 250);
    }

    /**
     * 順位番号と王冠を更新
     * @param {HTMLElement} wrapper 
     */
    function updateRanks(wrapper) {
        var tableContainer = wrapper.querySelector('.my_comparison_table');
        var visibleItems = tableContainer.querySelectorAll('.my_comparison_item:not(.hidden)');

        visibleItems.forEach(function (item, index) {
            var newRank = index + 1;

            // 順位番号を更新
            var rankNumber = item.querySelector('.my_comparison_rank_number');
            if (rankNumber) {
                rankNumber.textContent = newRank;
            }

            // data-rank属性を更新
            item.setAttribute('data-rank', newRank);

            // 王冠クラスを更新
            var rankDiv = item.querySelector('.my_comparison_rank');
            if (rankDiv) {
                // 既存のランククラスを削除
                rankDiv.className = rankDiv.className.replace(/my_comparison_rank_\d+/g, '').trim();
                rankDiv.classList.remove('my_comparison_rank_other');

                // 新しいランククラスを追加
                if (newRank <= 3) {
                    rankDiv.classList.add('my_comparison_rank_' + newRank);
                } else {
                    rankDiv.classList.add('my_comparison_rank_other');
                }
            }

            // 王冠要素のクラスを更新
            var crown = item.querySelector('.my_comparison_rank_crown');
            if (crown) {
                crown.className = 'my_comparison_rank_crown';
                if (newRank <= 3) {
                    crown.classList.add('my_comparison_rank_' + newRank);
                } else {
                    crown.classList.add('my_comparison_rank_other');
                }
            }
        });
    }

})();
