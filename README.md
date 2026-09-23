# samples-wordpress

WordPress の制作サンプル集。各サンプルはサンプル専用の自作テーマ（ブロックテーマ）とデモ記事からなり、WordPress Playground で公開する。

**各サンプルは架空の題材であり、実在の企業・団体・個人とは関係しません。**

## サンプル

一覧ページ: https://norio-io.github.io/samples-wordpress/

| サンプル | 制作種別 | 公開側 | 管理画面 | 対応する静的サンプル |
|---|---|---|---|---|
| みずき歯科クリニック | コーポレートサイト | [Playground で開く](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/norio-io/samples-wordpress/main/sites/corporate/dental-clinic/blueprint.json) | [Playground で開く](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/norio-io/samples-wordpress/main/sites/corporate/dental-clinic/blueprint-admin.json) | [`corporate/dental-clinic/`](https://github.com/norio-io/samples/tree/main/corporate/dental-clinic) |

WordPress Playground はブラウザ内で WordPress を起動するため、開くたびに初期状態から構築される。管理画面で加えた変更は保存されない。

### みずき歯科クリニック

架空の歯科医院のコーポレートサイト。**架空の題材であり、実在の医療機関・団体・個人とは関係しません。** 題材・文面・配色は静的サンプルと同一とし、医院の更新担当者が管理画面から情報を更新できる形に置き換えた。

- ソース: `sites/corporate/dental-clinic/`（テーマ `theme/`、デモ記事 `content/demo.xml`）
- 画面: トップ、医院紹介、診療案内、料金表、初めての方へ、アクセス、お知らせ一覧、お知らせ詳細（3件）。固定ページのスラッグは静的サンプルのパスと一致する

#### 実装した事項

- **診療時間表の一元管理** — 診療時間表を同期パターンとし、トップとアクセスに配置する。管理画面の「パターン」から1か所を変更すると両方に反映される
- **お知らせの更新** — お知らせを投稿として管理する。トップの最新3件（クエリループ）と一覧に自動で反映され、カテゴリー（お知らせ・休診・設備）を札で示す
- **共通部分の編集** — ヘッダー、フッター、予約の帯をテンプレートパーツとし、ナビゲーションとあわせてサイトエディターから編集できる
- **デザインの定義** — 配色・書体・余白を `theme.json` に定義し、同一のスタイルシートを公開側とエディターの双方で読み込む
- **ページ見出しの項目化** — 見出しの英字ラベルはカスタムフィールド（ブロックバインディングで表示）、リード文は抜粋とし、固定ページの編集画面から変更できる
- **現在地の表示** — グローバルナビの該当項目とパンくず（core のパンくずブロック）で示す。お知らせの詳細とカテゴリーの一覧では「お知らせ」を現在地とする
- **リンクの解決** — ナビゲーションとボタンのリンクはブロックバインディングで固定ページの URL へ解決する。Playground のサイト URL が起動ごとに変わっても壊れない

#### 技術的な前提

- プラグインは使用しない。パンくず・同期パターン・ブロックバインディングはいずれも WordPress 本体の機能である
- 写真素材は使用しない。診療科目のアイコン、スタッフのシルエット、アクセスの略図はすべて SVG である

## 構成

| パス | 内容 |
|---|---|
| `sites/<制作種別>/<業種の抽象名>/theme/` | サンプル専用の自作テーマ |
| `sites/<制作種別>/<業種の抽象名>/content/` | デモ記事（WXR）と画像 |
| `sites/<制作種別>/<業種の抽象名>/blueprint.json` | 公開側を開く blueprint |
| `sites/<制作種別>/<業種の抽象名>/blueprint-admin.json` | 管理画面を開く blueprint |
| `pages/` | GitHub Pages の一覧ページ（`main` への統合時に公開する） |
| `scripts/` | 検証用スクリプト |

制作種別と業種の抽象名は、[`norio-io/samples`](https://github.com/norio-io/samples)・[`norio-io/samples-react`](https://github.com/norio-io/samples-react) と共通とする。

## 開発

PHP 8.3 と Node 24.18 以上を要する。

```sh
composer install
./scripts/lint-php.sh   # PHP の構文検査と PHPCS（WordPress Coding Standards）
npm ci
npm run validate        # blueprint のスキーマ検証
npm test                # WordPress Playground の CLI で blueprint を起動し、応答を検査する
```

構成および開発上の取り決めは [CLAUDE.md](./CLAUDE.md) を参照。
