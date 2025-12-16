=== 比較ランキング表 (My Comparison Table) ===
Contributors: Your Name
Tags: comparison, ranking, table, filter, sort
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

絞り込み・並び替え機能付きの比較ランキング表を作成するプラグインです。AFFINGER6対応。

== Description ==

このプラグインは、商品やサービスの比較ランキング表を簡単に作成できます。

**主な機能:**
* 価格順・評価順での並び替え
* タグによる絞り込み検索
* 並び替え時に順位・王冠が自動更新
* レスポンシブ対応（スマホではカード形式）

== Installation ==

1. `my-comparison-table` フォルダを `/wp-content/plugins/` ディレクトリにアップロード
2. WordPress管理画面の「プラグイン」メニューから有効化
3. 投稿や固定ページでショートコードを使用

== Usage ==

ショートコード `[my_comparison_table]` を使用してデータをJSON形式で渡します。

例:
`[my_comparison_table data='[{"name":"商品A","image":"画像URL","rating":4.5,"price":2980,"tags":["送料無料"],"detail_url":"#","affiliate_url":"#"}]']`

**オプション:**
* `show_filter="true"` - 絞り込み機能を表示（デフォルト: true）
* `show_sort="true"` - 並び替えボタンを表示（デフォルト: true）

== Changelog ==

= 1.0.0 =
* 初回リリース
