# CLAUDE.md

## 概要

WordPress の制作サンプル集。各サンプルはサンプル専用の自作テーマとデモ記事からなり、WordPress Playground で公開する。

## ディレクトリ構成

```
samples-wordpress/
├── sites/
│   └── <制作種別>/
│       └── <業種の抽象名>/
│           ├── theme/                ← サンプル専用の自作テーマ
│           ├── content/              ← デモ記事（WXR）と画像
│           ├── blueprint.json        ← 公開側を開く
│           └── blueprint-admin.json  ← 管理画面を開く
├── pages/                            ← GitHub Pages の一覧ページ
├── scripts/                          ← 検証用スクリプト
├── .github/workflows/
├── composer.json                     ← PHPCS（WordPress Coding Standards）
└── package.json                      ← WordPress Playground の CLI と blueprint の検証
```

- 命名は静的サンプルのリポジトリ `norio-io/samples`、React のリポジトリ `norio-io/samples-react` と共通とする。同一題材のサンプルは、制作種別と業種の抽象名を一致させる。
  - 例: `norio-io/samples` の `corporate/dental-clinic/` に対し、本リポジトリでは `sites/corporate/dental-clinic/` とする。
- `sites/` は配置のみに用いる入れ物である。

## 技術構成

| 項目 | 採用 |
|---|---|
| WordPress | 最新の安定版 |
| PHP | 8.3 |
| テーマ | ブロックテーマ（`theme.json`）を自作する |
| 公開 | WordPress Playground（blueprint） |
| 静的解析 | PHP の構文検査、PHPCS（WordPress Coding Standards） |
| 起動検査 | WordPress Playground の CLI（Node 24.18 以上） |

- プラグインは使用しない。
- blueprint は `git:directory` リソースで本リポジトリの `main` を参照する。単一のファイル（デモ記事の WXR など）は `url` リソースで `https://raw.githubusercontent.com/norio-io/samples-wordpress/main/…` を参照する。複数のファイル（デモデータと画像、初期設定の PHP など）は、`writeFiles` ステップの `filesTree` に `git:directory` を指定して配置し、`vfs` リソースで参照する。いずれも作業ブランチを参照したまま統合しない（`validate` ジョブで検査する）。
- 本文やテンプレートから投稿 ID で参照するもの（同期パターンの `wp:block`、ナビゲーションの `wp:navigation`）があるため、デモ記事の WXR では投稿 ID を固定する。WordPress の取り込みは空いている ID をそのまま用いる。
- テンプレートパーツのリンクは、ルート相対のパスで書かない。WordPress Playground はサイトの URL にスコープのパスを含むため。ナビゲーションは `core/post-data`、ボタンはテーマのバインディング（`mizuki-dental/page-url` など）で URL を解決する。本文のリンクは、そのページからの相対パスで書く。
- `installTheme` で `git:directory` を用いる場合は、`options.targetFolderName` にテーマのディレクトリ名を指定する。省略するとリポジトリの URL から導いた名前で配置される。WordPress 同梱のテーマ（`twentytwentyfive` など）と同名にすると導入に失敗する。
- ナビゲーションブロックのメニュー展開時の配色は、ブロックの属性 `overlayBackgroundColor` と `overlayTextColor` で指定する。CSS による指定は、コアの `.wp-block-navigation:not(.has-background)` を含むセレクタに詳細度で負けるため。
- 画面に固定する要素（固定の問い合わせボタンなど）の `z-index` は、固定したヘッダーより小さい値とする。ヘッダーが重なりの文脈を作るため、ヘッダー内のメニューはページ全体ではヘッダーの `z-index` で扱われ、それより大きい値の固定要素が展開したメニューの前面に出る。
- 管理画面用の blueprint（`blueprint-admin.json`）では、`runPHP` で管理者の `wp_persisted_preferences` の `core/edit-post` に `welcomeGuide: false` を保存する。WordPress Playground は起動のたびに初期状態へ戻り、編集画面を開くたびにエディターの初回ガイドが表示されるため。
- 入力欄（投稿メタ）の既定値の保存は、`rest_after_insert_{post_type}` で行い、エディターからの保存時に限る。`save_post` は WXR の取り込みでも実行され、取り込み処理が入力欄を保存するより前に動くため、値が重複する。
- 実行コマンド（リポジトリ直下）

  ```sh
  composer install
  ./scripts/lint-php.sh   # 構文検査と PHPCS
  npm ci
  npm run validate        # blueprint のスキーマ検証
  npm test                # blueprint の起動検査
  ```
- プロキシ経由で外部へ接続する作業環境で WordPress Playground の CLI を実行する場合は、環境変数 `NODE_USE_ENV_PROXY=1` と `NODE_EXTRA_CA_CERTS=/root/.ccr/ca-bundle.crt` を指定する。Node の `fetch` は既定でプロキシを通らず、WordPress 本体などを取得できないため。
- 起動した CLI のプロセスは、PID を指定して終了する。`pkill -f` はパターンが実行中のシェル自身にも一致し、シェルごと終了させるため。
- 画面の実測は、対象の URL が 200 を返すことを確かめてから行う。存在しないスラッグでは 404 のページが表示され、誤った結果になるため。

## CI / CD

- プルリクエストおよび `main` への統合時に、`.github/workflows/ci.yml` の3ジョブを実行する。

  | ジョブ | 内容 |
  |---|---|
  | `lint` | `sites/` 配下の PHP の構文検査、PHPCS（WordPress Coding Standards） |
  | `validate` | 各 blueprint の JSON スキーマ検証（`@wp-playground/blueprints`）。本リポジトリを参照するリソース（`git:directory`、raw.githubusercontent.com の URL）が `main` を指すこと |
  | `test` | WordPress Playground の CLI で各 blueprint を起動し、ログイン状態でトップと `/wp-admin/` が 200 を返すこと |

- 各ジョブは対象がない場合もスキップとして成功する。
- `test` は、blueprint が参照する `main` を検証対象のコミットへ差し替えて起動する（環境変数 `BLUEPRINT_REF`）。`git:directory` の `ref` と、raw.githubusercontent.com の URL のパスが対象である。`main` のままではプルリクエストの変更を検証できないため。
- `main` への統合時に、`.github/workflows/deploy.yml` の `deploy` ジョブで `pages/` を GitHub Pages（https://norio-io.github.io/samples-wordpress/）へ公開する。公開元は GitHub Actions とする。ビルド工程は持たず、`pages/` をそのまま公開する。
  - 同ジョブは `main` への統合時のみ実行するため、Ruleset の必須ステータスチェックには追加しない。
  - サンプルを追加したときは、一覧ページ（`pages/index.html`）にカードを追加する。カードには名称、制作種別、概要、使用技術のタグ、公開側と管理画面の Playground への導線を置く。
- **CI のジョブ名 `lint` / `validate` / `test` を、Ruleset `main protection` の必須ステータスチェックに登録している。** 定義は `.github/rulesets/main-protection.json` に置く。
- 必須ステータスチェックは、Ruleset `main protection` がジョブ名で参照する。ジョブ名を変更する場合は Ruleset 側の更新が必須であり、一致しない場合はプルリクエストがマージ不能となる。
- 変更されたファイルに応じて起動するジョブ（`paths` 指定のあるワークフロー）は、必須ステータスチェックに追加しない。対象外のプルリクエストではジョブが起動せず、チェックが Expected のまま残ってマージ不能となるため。

## 依存関係の更新

- 依存関係の更新は Renovate が起票する。設定は `renovate.json` に置き、`renovate-config` ジョブ（`.github/workflows/renovate-config.yml`）で `renovate-config-validator` による検証を行う。
- 自動マージは有効にしない。必須ステータスチェックの通過をもって承認とはしない。
- Renovate が作成したプルリクエストには `by: renovate` と `type: dependency-upgrade` の2枚のラベルが付与される。
- 複数パッケージの同時更新が必要で Renovate が扱えない場合は手動で更新する。そのプルリクエストには `type: dependency-upgrade` のみを付与する。

## 開発の進め方

### ブランチの命名

- 作業ブランチは `feature/` を接頭辞とし、`main` へプルリクエストを作成してマージする。
- イシューに紐づく作業は `feature/issue-<イシュー番号>` とする。
- イシューに紐づかない作業は `feature/<短い説明>` とする。
- リモートへ push するブランチは、上記の命名規則に従う作業ブランチのみとする。作業環境の既定ブランチなど、それ以外のブランチを作成・push しない。

### コミットおよびタイトルの書式

`{絵文字} {prefix}: {説明}` とし、説明は日本語の終止形で記載する。コミット、プルリクエストのタイトル、イシューのタイトルのいずれも同一の書式を用いる。

| prefix | emoji | 用途 |
|---|---|---|
| feat | ✨ | 機能の追加、変更 |
| fix | 🐛 | バグ修正 |
| chore | 🛠️ | 設定、依存、ツール系 |
| refactor | ♻️ | 動作を変えないコード変更 |
| docs | 📚 | ドキュメント |
| test | ✅ | テストの追加、修正 |

- `.github/` などの開発インフラの変更は `chore` とする。
- 書式の検査は CI に入れない。必須ステータスチェックの構成を変えないため。

### ラベル

ラベルは `.github/labels.json` で定義し、`.github/workflows/labels.yml` で反映する。`main` への統合時に自動で反映されるほか、Actions から手動でも実行できる。

| 接頭辞 | 用途 |
|---|---|
| `type:` | 作業の種類。イシューとプルリクエストに必ず1枚付与する |
| `status:` | 進行上の状態 |
| `theme:` | 対象領域 |
| `by:` | 作成元（自動化ツール） |

リポジトリ固有の `theme:` ラベルは `labels.json` に追加する。

### レビュー

- レビュースレッドの解決はレビュー者が行う。実装者は解決しない。Ruleset の「会話の解決を必須」は、未解決の指摘を残したままマージされることを防ぐためのものであり、実装した側が自ら解決できる状態では機能しないため。
- 指摘への対応を終えた場合は、スレッドへ返信して反映内容を伝えるにとどめる。
- 伝達事項（検証の結果、判断の理由、制約、未対応の事項など）は、プルリクエストへのコメントで伝える。レビュー者はプルリクエストを確認するため。
- 指摘への返信には、対応したコミットと変更内容を書く。
- 返信は、対応したコミットを push した後に行う。返信の時点で、レビュー者がコミットの差分を確認できるようにするため。
- 表示や動作の修正を伝える返信には、確認した条件（画面幅、URL、操作）と実測値（計算済みのスタイル、最前面の要素、axe の違反件数など）を添える。
- 返信に誤りがあった場合は、同じスレッドに訂正を追記し、元の返信は投稿時のまま残す。

### その他

- 原則として 1イシュー 1プルリクエストとする。実装上の依存により単独で検証できない場合に限り、1プルリクエストで複数のイシューを解決し、本文に `Closes #N` を列挙する。
- コミットメッセージおよびプルリクエストの記述言語は日本語とする。
- コミットには `Co-Authored-By` を残す。コミットメッセージおよびプルリクエストの本文には、セッションURLなど第三者にとって意味を持たない行を含めない。
- プルリクエストの作成時やコメントの投稿時にツールが末尾へ自動で付与する行のうち、セッションURL（`https://claude.ai/code/session_` で始まるリンク）を含む行は、投稿直後に削除する。セッションURLを含まない行（`Generated with [Claude Code](https://claude.com/claude-code)` など）は残してよい。
- 各サンプルは架空の題材である。実在の企業・団体・個人とは関係しない旨を画面上に表示し、顧客名や連絡先などのデータにも実在の個人情報を含めない。
