// 各 blueprint を WordPress Playground の公式スキーマで検証する。
// 併せて、本リポジトリを参照するリソース（git:directory と raw.githubusercontent.com の URL）が
// main を指していることを検査する。
// 公開中の blueprint が作業ブランチを参照したまま統合されることを防ぐため。
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { validateBlueprint } from '@wp-playground/blueprints';
import { ROOT, findBlueprints, findOwnResources } from './lib/blueprints.mjs';

const blueprints = findBlueprints();

if (blueprints.length === 0) {
  console.log('対象の blueprint がないためスキップする');
  process.exit(0);
}

let failed = false;

for (const path of blueprints) {
  const errors = [];

  let blueprint;
  try {
    blueprint = JSON.parse(readFileSync(join(ROOT, path), 'utf8'));
  } catch (error) {
    errors.push(`JSON として読み込めない: ${error.message}`);
  }

  if (blueprint !== undefined) {
    const result = validateBlueprint(blueprint);
    if (!result.valid) {
      for (const error of result.errors ?? []) {
        errors.push(`${error.instancePath || '/'} ${error.message}`);
      }
    }
    for (const resource of findOwnResources(blueprint)) {
      if (resource.ref !== 'main') {
        errors.push(`本リポジトリを参照するリソースが main ではない: ${JSON.stringify(resource.ref)}`);
      }
    }
  }

  if (errors.length === 0) {
    console.log(`ok   ${path}`);
  } else {
    failed = true;
    console.error(`fail ${path}`);
    for (const error of errors) {
      console.error(`     ${error}`);
    }
  }
}

process.exit(failed ? 1 : 0);
