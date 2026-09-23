// 各 blueprint を WordPress Playground の CLI で起動し、
// トップと /wp-admin/ が 200 を返すことを検査する。
//
// 環境変数 BLUEPRINT_REF にコミットを指定した場合、本リポジトリを参照する
// git:directory リソースの ref をそのコミットへ差し替える。blueprint は main を
// 参照するため、差し替えなければプルリクエストの変更を検証できない。
import { spawn } from 'node:child_process';
import { mkdtempSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { ROOT, findBlueprints, findOwnGitResources } from './lib/blueprints.mjs';

const PORT = 9400;
const BASE_URL = `http://127.0.0.1:${PORT}`;
const PATHS = ['/', '/wp-admin/'];
const BOOT_TIMEOUT_MS = 10 * 60 * 1000;
const MAX_REDIRECTS = 10;

const blueprints = findBlueprints();

if (blueprints.length === 0) {
  console.log('対象の blueprint がないためスキップする');
  process.exit(0);
}

const ref = process.env.BLUEPRINT_REF;
const workdir = mkdtempSync(join(tmpdir(), 'samples-wordpress-'));
let failed = false;

try {
  for (const [index, path] of blueprints.entries()) {
    console.log(`\n== ${path}`);
    const blueprintPath = join(workdir, `${index}.json`);
    writeFileSync(blueprintPath, JSON.stringify(prepare(path)));
    const errors = await run(blueprintPath);
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
} finally {
  rmSync(workdir, { recursive: true, force: true });
}

process.exit(failed ? 1 : 0);

function prepare(path) {
  const blueprint = JSON.parse(readFileSync(join(ROOT, path), 'utf8'));
  // 公開側の blueprint でも管理画面を検査できるよう、ログイン状態で起動する。
  // CLI の --login は blueprint を指定した場合に効かないため、blueprint 側で指定する。
  blueprint.login = true;
  if (ref) {
    for (const resource of findOwnGitResources(blueprint)) {
      resource.ref = ref;
      resource.refType = 'commit';
    }
  }
  return blueprint;
}

async function run(blueprintPath) {
  const server = spawn(
    join(ROOT, 'node_modules/.bin/wp-playground-cli'),
    ['server', `--blueprint=${blueprintPath}`, `--port=${PORT}`],
    { cwd: ROOT, stdio: ['ignore', 'pipe', 'pipe'], detached: true },
  );
  server.stdout.pipe(process.stdout);
  server.stderr.pipe(process.stderr);

  try {
    await waitForReady(server);
    const cookies = new Map();
    const errors = [];
    for (const path of PATHS) {
      const error = await check(path, cookies);
      if (error) {
        errors.push(error);
      } else {
        console.log(`200  ${path}`);
      }
    }
    return errors;
  } catch (error) {
    return [error.message];
  } finally {
    await stop(server);
  }
}

function waitForReady(server) {
  return new Promise((resolve, reject) => {
    const timer = setTimeout(() => {
      reject(new Error(`${BOOT_TIMEOUT_MS / 1000} 秒以内に起動しなかった`));
    }, BOOT_TIMEOUT_MS);
    server.stdout.on('data', (chunk) => {
      if (chunk.toString().includes('Ready!')) {
        clearTimeout(timer);
        resolve();
      }
    });
    server.on('exit', (code) => {
      clearTimeout(timer);
      reject(new Error(`起動前に終了した（終了コード ${code}）`));
    });
  });
}

// リダイレクトを Cookie を引き継いで追い、最終的に要求したパスで 200 を返すことを確かめる。
// CLI は起動直後の最初の要求を同一 URL へリダイレクトし、自動ログインは Cookie で行うため。
async function check(path, cookies) {
  let url = new URL(path, BASE_URL);
  for (let i = 0; i <= MAX_REDIRECTS; i++) {
    const response = await fetch(url, {
      redirect: 'manual',
      headers: { cookie: [...cookies].map(([name, value]) => `${name}=${value}`).join('; ') },
    });
    for (const header of response.headers.getSetCookie()) {
      const [pair] = header.split(';');
      const separator = pair.indexOf('=');
      cookies.set(pair.slice(0, separator).trim(), pair.slice(separator + 1).trim());
    }
    await response.arrayBuffer();

    const location = response.headers.get('location');
    if (response.status >= 300 && response.status < 400 && location) {
      url = new URL(location, url);
      continue;
    }
    if (response.status !== 200) {
      return `${path} が ${response.status} を返した（${url.pathname}）`;
    }
    if (url.pathname !== path) {
      return `${path} が ${url.pathname}${url.search} へリダイレクトされた`;
    }
    return null;
  }
  return `${path} のリダイレクトが ${MAX_REDIRECTS} 回を超えた`;
}

function stop(server) {
  if (server.exitCode !== null) {
    return Promise.resolve();
  }
  return new Promise((resolve) => {
    server.once('exit', resolve);
    // CLI はワーカーを子プロセスとして起動するため、プロセスグループごと終了させる。
    try {
      process.kill(-server.pid, 'SIGTERM');
    } catch {
      // 直前に終了していた場合は exit を待つのみとする。
    }
  });
}
