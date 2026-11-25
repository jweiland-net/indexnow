import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { expect, type Page } from '@playwright/test';

export const projectDir = path.resolve(process.env.TYPO3_PROJECT_DIR ?? path.join(__dirname, '../../../../../../..'));
export const apiKey = requireEnv('INDEXNOW_API_KEY');

export interface StackRow {
  url: string;
  targetPid: number;
}

function requireEnv(name: string): string {
  const value = process.env[name];
  if (!value) {
    throw new Error(`Environment variable ${name} is required`);
  }

  return value;
}

function ddev(args: string[]): string {
  return execFileSync('ddev', args, { cwd: projectDir, encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] });
}

export function sql(query: string): string {
  return ddev(['exec', 'mysql', '--batch', '--skip-column-names', '-e', query]).trim();
}

export function typo3(...args: string[]): string {
  return ddev(['typo3', ...args]);
}

export function clearStack(): void {
  sql('DELETE FROM tx_indexnow_stack');
}

export function fetchStack(): StackRow[] {
  const result = sql('SELECT url, target_pid FROM tx_indexnow_stack ORDER BY uid');

  return result === ''
    ? []
    : result.split('\n').map((line) => {
        const [url, targetPid] = line.split('\t');
        return { url, targetPid: Number(targetPid) };
      });
}

export function setExtensionConfiguration(key: string, value: string): void {
  typo3('configuration:set', `EXTENSIONS/indexnow/${key}`, value);
}

export function getExtensionConfiguration(key: string): string {
  return typo3('configuration:show', `EXTENSIONS/indexnow/${key}`).trim().replace(/^'|'$/g, '');
}

/**
 * Returns all lines of the IndexNow log file(s) (var/log/typo3_indexnow_*.log) written after the given marker
 */
export function readIndexNowLogSince(marker: number): string {
  const logDir = path.join(projectDir, 'var/log');
  if (!fs.existsSync(logDir)) {
    return '';
  }

  return fs
    .readdirSync(logDir)
    .filter((file) => file.startsWith('typo3_indexnow_') && file.endsWith('.log'))
    .map((file) => fs.readFileSync(path.join(logDir, file), 'utf8').slice(marker))
    .join('\n');
}

export function indexNowLogSize(): number {
  const logDir = path.join(projectDir, 'var/log');
  if (!fs.existsSync(logDir)) {
    return 0;
  }

  return fs
    .readdirSync(logDir)
    .filter((file) => file.startsWith('typo3_indexnow_') && file.endsWith('.log'))
    .reduce((size, file) => size + fs.statSync(path.join(logDir, file)).size, 0);
}

export async function loginToBackend(page: Page): Promise<void> {
  await page.goto('/typo3/');
  await page.fill('#t3-username', requireEnv('TYPO3_BE_USER'));
  await page.fill('#t3-password', requireEnv('TYPO3_BE_PASSWORD'));
  await page.click('#t3-login-submit');
  await expect(page).toHaveURL(/\/typo3\/module\//);
}

/**
 * Opens the FormEngine of a page record and saves it unchanged, which runs the DataHandler hook
 */
export async function savePageRecord(page: Page, pageUid: number): Promise<void> {
  await page.goto(`/typo3/record/edit?edit[pages][${pageUid}]=edit`);

  // The backend shell renders modules inside the content iframe
  const contentFrame = page.frameLocator('iframe[name="list_frame"]');
  await expect(contentFrame.getByRole('heading', { level: 1 })).toBeVisible();

  await Promise.all([
    page.waitForResponse((response) => response.url().includes('/typo3/record/edit') && response.request().method() === 'POST'),
    contentFrame.getByRole('button', { name: 'Save', exact: true }).click(),
  ]);
  await expect(contentFrame.getByRole('heading', { level: 1 })).toBeVisible();
  await expect(contentFrame.locator('.alert-danger, .callout-danger')).toHaveCount(0);
}
