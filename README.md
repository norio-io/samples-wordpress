# samples-template

制作サンプル用リポジトリのテンプレート。本リポジトリから生成したリポジトリは、以下の初期設定を行ったのち、この README をリポジトリの説明に置き換える。

## 生成後の初期設定

テンプレートから複製されるのはファイルのみであり、リポジトリの設定は複製されない。次の設定を生成後に行う。

1. **Ruleset** — Settings → Rules → Rulesets → New ruleset → Import a ruleset で `.github/rulesets/main-protection.json` を取り込む。必須ステータスチェックは含まれていないため、CI を追加した時点でジョブ名を登録する
2. **ラベル** — Actions → Labels → Run workflow を実行する。`.github/labels.json` の内容でラベルを作成し、GitHub 既定のラベルを削除する
3. **Renovate** — Renovate の GitHub App の対象リポジトリに追加する
4. **GitHub Pages** — 公開する場合は Settings → Pages で公開元を GitHub Actions とする
5. **CLAUDE.md** — 「概要」「ディレクトリ構成」「技術構成」「CI / CD」をリポジトリに合わせて記載する

## 同梱ファイル

| パス | 内容 |
|---|---|
| `CLAUDE.md` | 開発の進め方、コミット書式、依存関係の更新方針 |
| `renovate.json` | Renovate の設定 |
| `.github/workflows/renovate-config.yml` | `renovate.json` の検証 |
| `.github/workflows/labels.yml` | ラベルの作成と更新 |
| `.github/labels.json` | ラベル定義 |
| `.github/rulesets/main-protection.json` | `main` の保護規則 |

生成後のリポジトリは、それぞれの必要に応じて変更してよい。本テンプレートとの同期は行わない。
