import { readdirSync, existsSync } from 'node:fs';
import { join, relative } from 'node:path';
import { fileURLToPath } from 'node:url';

export const ROOT = fileURLToPath(new URL('../..', import.meta.url));

export const REPOSITORY = process.env.GITHUB_REPOSITORY ?? 'norio-io/samples-wordpress';

const BLUEPRINT_FILES = ['blueprint.json', 'blueprint-admin.json'];

/**
 * sites/<制作種別>/<業種の抽象名>/ 直下の blueprint を列挙する。
 * 戻り値はリポジトリ直下からの相対パス。
 */
export function findBlueprints() {
  const sites = join(ROOT, 'sites');
  if (!existsSync(sites)) {
    return [];
  }
  const found = [];
  for (const kind of subdirectories(sites)) {
    for (const subject of subdirectories(join(sites, kind))) {
      for (const name of BLUEPRINT_FILES) {
        const path = join(sites, kind, subject, name);
        if (existsSync(path)) {
          found.push(relative(ROOT, path));
        }
      }
    }
  }
  return found.sort();
}

function subdirectories(dir) {
  return readdirSync(dir, { withFileTypes: true })
    .filter((entry) => entry.isDirectory())
    .map((entry) => entry.name);
}

/**
 * blueprint 内の、本リポジトリを参照するリソースを列挙する。
 *
 * - git:directory リソース（ref で版を指定する）
 * - url リソースのうち raw.githubusercontent.com の本リポジトリを指すもの（URL のパスで版を指定する）
 *
 * 戻り値の各要素は、参照している版（ref）と、版を差し替える関数（setRef）を持つ。
 */
export function findOwnResources(value, found = []) {
  if (Array.isArray(value)) {
    for (const item of value) {
      findOwnResources(item, found);
    }
  } else if (value && typeof value === 'object') {
    if (value.resource === 'git:directory' && isOwnRepository(value.url)) {
      found.push({
        ref: value.ref,
        setRef(ref) {
          value.ref = ref;
          value.refType = 'commit';
        },
      });
    } else if (value.resource === 'url' && typeof value.url === 'string' && value.url.toLowerCase().startsWith(RAW_PREFIX.toLowerCase())) {
      const [ref, ...rest] = value.url.slice(RAW_PREFIX.length).split('/');
      found.push({
        ref,
        setRef(newRef) {
          value.url = RAW_PREFIX + [newRef, ...rest].join('/');
        },
      });
    }
    for (const item of Object.values(value)) {
      findOwnResources(item, found);
    }
  }
  return found;
}

const RAW_PREFIX = `https://raw.githubusercontent.com/${REPOSITORY}/`;

function isOwnRepository(url) {
  if (typeof url !== 'string') {
    return false;
  }
  const normalized = url.replace(/\.git$/, '').replace(/\/$/, '').toLowerCase();
  return normalized === `https://github.com/${REPOSITORY}`.toLowerCase();
}
