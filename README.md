# samples-wordpress

WordPress の制作サンプル集。各サンプルはサンプル専用の自作テーマ（ブロックテーマ）とデモ記事からなり、WordPress Playground で公開する。

**各サンプルは架空の題材であり、実在の企業・団体・個人とは関係しません。**

## サンプル

準備中。

## 構成

| パス | 内容 |
|---|---|
| `sites/<制作種別>/<業種の抽象名>/theme/` | サンプル専用の自作テーマ |
| `sites/<制作種別>/<業種の抽象名>/content/` | デモ記事（WXR）と画像 |
| `sites/<制作種別>/<業種の抽象名>/blueprint.json` | 公開側を開く blueprint |
| `sites/<制作種別>/<業種の抽象名>/blueprint-admin.json` | 管理画面を開く blueprint |
| `pages/` | GitHub Pages の一覧ページ |
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
