# samples-wordpress

WordPress の制作サンプル集。各サンプルはサンプル専用の自作テーマ（ブロックテーマ）とデモ記事からなり、WordPress Playground で公開する。

**各サンプルは架空の題材であり、実在の企業・団体・個人とは関係しません。**

## サンプル

一覧ページ: https://norio-io.github.io/samples-wordpress/

| サンプル | 制作種別 | 公開側 | 管理画面 | 対応する静的サンプル |
|---|---|---|---|---|
| みずき歯科クリニック | コーポレートサイト | [Playground で開く](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/norio-io/samples-wordpress/main/sites/corporate/dental-clinic/blueprint.json) | [Playground で開く](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/norio-io/samples-wordpress/main/sites/corporate/dental-clinic/blueprint-admin.json) | [`corporate/dental-clinic/`](https://github.com/norio-io/samples/tree/main/corporate/dental-clinic) |
| ひなた不動産 | 物件検索サイト | [Playground で開く](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/norio-io/samples-wordpress/main/sites/listing/real-estate/blueprint.json) | [Playground で開く](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/norio-io/samples-wordpress/main/sites/listing/real-estate/blueprint-admin.json) | なし |

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

### ひなた不動産

架空の不動産会社の賃貸物件サイト。**架空の題材であり、実在の企業・団体・個人とは関係しません。** 物件を投稿・固定ページ以外のデータとして構造化し、店舗の更新担当者が入力欄を埋めるだけで物件の追加と掲載終了を行える形とした。

- ソース: `sites/listing/real-estate/`（テーマ `theme/`、デモデータ `content/demo.xml`、間取り図 `content/floorplans/`、初期設定 `content/setup.php`）
- 画面: トップ、物件一覧（`/properties/`）、物件詳細（`/properties/<スラッグ>/`）、エリア別一覧（`/area/<スラッグ>/`）、会社概要、問い合わせ

#### 実装した事項

- **物件のデータ構造** — 物件をカスタム投稿タイプとし、エリア・間取りを分類、賃料・面積・築年月・設備・掲載状態などを型付きの入力欄（`register_post_meta`）として REST API に公開する。入力欄はエディターの「物件情報」パネルで編集し、本文は「おすすめポイント」の段落1つに固定する
- **絞り込みと並べ替え** — エリア・間取り・賃料上限・駅徒歩の絞り込みと、新着・賃料の安い順・広い順の並べ替え。条件はすべて URL のクエリで表し、共有・再読み込み・ページ送りで保つ。選択中の条件は解除できるチップで示し、該当がない場合は解除の方法を示す
- **掲載状態** — 「成約済み」にすると、一覧・トップ・エリア別一覧・同じエリアの物件から外れる。詳細の URL に直接来た場合は成約済みと表示し、問い合わせ導線を出さない
- **表示書式の統一** — 賃料の「万円」表記、桁区切り、築年数の算出、新着（掲載14日以内）の判定をテーマで一元化し、ブロックバインディングと動的ブロックから用いる
- **店舗情報の一元管理** — 所在地・電話番号・営業時間・定休日を「設定 → 店舗情報」の1か所で管理し、ヘッダー・フッター・トップ・会社概要・問い合わせ導線へ反映する
- **管理画面** — 物件の一覧画面に賃料・エリア・間取り・掲載状態の列を加える。日本語の物件名から作られる読めないスラッグは `property-<ID>` に置き換える
- **デザイン** — 藍の帯のヘッダー（上部に固定）、検索パネルを主役とする第一画面、賃料を最も大きく示す物件カード（3 / 2 / 1 列）、左段の絞り込み（幅 1024px 未満は折りたたみ）、右段が追従する2段組の物件詳細、画面下部に固定する電話・問い合わせボタン（幅の狭い画面）

#### 技術的な前提

- プラグインは使用しない
- 写真素材は使用しない。間取り図（12件、線の太さ・文字・配色を統一）とアイコンはすべて自作である
- 書体は Noto Sans JP（本文・見出し）と Manrope（数字）をテーマに同梱する。Noto Sans JP は JIS 第1水準の漢字と記号、太さ 400–700 に絞ったサブセットとし、それ以外の文字は端末の書体で表示する
- 間取り図と掲載日はデモデータの取り込み後に `content/setup.php` が設定する。掲載日は取り込んだ日から数えるため、いつ開いても新着の物件がある

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
