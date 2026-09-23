#!/usr/bin/env bash
# sites/ 配下の PHP に構文検査と PHPCS を実行する。
# 対象の PHP がない場合は成功として終了する。
set -euo pipefail

cd "$(dirname "$0")/.."

mapfile -d '' files < <(find sites -type f -name '*.php' -print0)

if [ "${#files[@]}" -eq 0 ]; then
  echo "対象の PHP がないためスキップする"
  exit 0
fi

# 1ファイルの誤りで止めず、すべての構文エラーを出力する。
failed=0
for file in "${files[@]}"; do
  php -l "$file" || failed=1
done
if [ "$failed" -ne 0 ]; then
  exit 1
fi

vendor/bin/phpcs
