# CLAUDE.md

<!-- リポジトリ生成後に、以下の4節をリポジトリに合わせて記載する。 -->

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
- blueprint は `git:directory` リソースで本リポジトリの `main` を参照する。作業ブランチを参照したまま統合しない（`validate` ジョブで検査する）。
- `installTheme` で `git:directory` を用いる場合は、`options.targetFolderName` にテーマのディレクトリ名を指定する。省略するとリポジトリの URL から導いた名前で配置される。WordPress 同梱のテーマ（`twentytwentyfive` など）と同名にすると導入に失敗する。
- 実行コマンド（リポジトリ直下）

  ```sh
  composer install
  ./scripts/lint-php.sh   # 構文検査と PHPCS
  npm ci
  npm run validate        # blueprint のスキーマ検証
  npm test                # blueprint の起動検査
  ```

## CI / CD

- プルリクエストおよび `main` への統合時に、`.github/workflows/ci.yml` の3ジョブを実行する。

  | ジョブ | 内容 |
  |---|---|
  | `lint` | `sites/` 配下の PHP の構文検査、PHPCS（WordPress Coding Standards） |
  | `validate` | 各 blueprint の JSON スキーマ検証（`@wp-playground/blueprints`）。本リポジトリを参照する `git:directory` の `ref` が `main` であること |
  | `test` | WordPress Playground の CLI で各 blueprint を起動し、ログイン状態でトップと `/wp-admin/` が 200 を返すこと |

- 各ジョブは対象がない場合もスキップとして成功する。
- `test` は、blueprint が参照する `main` を検証対象のコミットへ差し替えて起動する（環境変数 `BLUEPRINT_REF`）。`main` のままではプルリクエストの変更を検証できないため。
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

### その他

- 原則として 1イシュー 1プルリクエストとする。実装上の依存により単独で検証できない場合に限り、1プルリクエストで複数のイシューを解決し、本文に `Closes #N` を列挙する。
- コミットメッセージおよびプルリクエストの記述言語は日本語とする。
- コミットには `Co-Authored-By` を残す。コミットメッセージおよびプルリクエストの本文には、セッションURLなど第三者にとって意味を持たない行を含めない。
- 各サンプルは架空の題材である。実在の企業・団体・個人とは関係しない旨を画面上に表示し、顧客名や連絡先などのデータにも実在の個人情報を含めない。
