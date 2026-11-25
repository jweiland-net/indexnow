import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { expect, type FrameLocator, type Page } from '@playwright/test';

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
  // Only strip trailing line breaks: a trailing tab belongs to an empty value of the last column
  return ddev(['exec', 'mysql', '--batch', '--skip-column-names', '-e', query]).replace(/\n+$/, '');
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

const accessLogFile = process.env.TYPO3_ACCESS_LOG ?? '/var/log/apache2/access.log';

/**
 * Size in bytes of the web server access log inside the DDEV web container. Use it as marker for readAccessLogSince().
 */
export function accessLogSize(): number {
  return Number(ddev(['exec', 'stat', '-c', '%s', accessLogFile]).trim());
}

/**
 * Request lines of the web server access log written after the given marker.
 * Unlike exception messages (Guzzle redacts query strings), the access log contains the full request URI.
 */
export function readAccessLogSince(marker: number): string[] {
  return ddev(['exec', 'tail', '-c', `+${marker + 1}`, accessLogFile])
    .split('\n')
    .map((line) => line.match(/"([A-Z]+ \S+) HTTP\/[\d.]+" (\d{3})/))
    .filter((match): match is RegExpMatchArray => match !== null)
    .map((match) => `${match[1]} ${match[2]}`);
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
type FormEdit = (form: FrameLocator, pageUid: number) => Promise<void>;

let markerCounter = 0;

/**
 * Changes the translatable field "nav_title" (Alternative Navigation Title) to a unique value
 */
export const changeNavTitle: FormEdit = async (form, pageUid) => {
  await form
    .locator(`[data-formengine-input-name="data[pages][${pageUid}][nav_title]"]`)
    .fill(`indexnow-e2e-${Date.now()}-${++markerCounter}`);
};

/**
 * Changes the field "layout" (Frontend Layout), which is configured with l10n_mode=exclude.
 * TYPO3 synchronizes its value into all translations of the page.
 */
export function changeFrontendLayout(value: string): FormEdit {
  return async (form, pageUid) => {
    await form.getByRole('tab', { name: 'Appearance' }).click();
    await form.locator(`select[name="data[pages][${pageUid}][layout]"]`).selectOption(value);
  };
}

/**
 * Toggles "Page visible" (field "hidden", displayed inverted) on the "Access" tab
 */
export function setPageVisibility(visible: boolean): FormEdit {
  return async (form, pageUid) => {
    await form.getByRole('tab', { name: 'Access' }).click();
    const checkbox = form.locator(`input[type="checkbox"][data-formengine-input-name="data[pages][${pageUid}][hidden]"]`);
    await checkbox.setChecked(visible);
  };
}

/**
 * Remembers the values of the given page fields and returns a function to restore them
 */
export function snapshotPageFields(pageUids: number[], fieldNames: string[]): () => void {
  const rows = sql(`SELECT uid, ${fieldNames.join(', ')} FROM pages WHERE uid IN (${pageUids.join(', ')})`);

  return () => {
    for (const row of rows.split('\n').filter((line) => line !== '')) {
      const [uid, ...values] = row.split('\t');
      const assignments = fieldNames.map((fieldName, index) => {
        const value = values[index];
        return `${fieldName} = ${value === 'NULL' ? 'NULL' : `'${value.replace(/\\/g, '\\\\').replace(/'/g, "\\'")}'`}`;
      });
      sql(`UPDATE pages SET ${assignments.join(', ')} WHERE uid = ${Number(uid)}`);
    }
  };
}

/**
 * Opens the FormEngine of a page record, applies the given edit and saves it, which runs the DataHandler hook.
 * Without an edit, the record is saved unchanged.
 */
export async function savePageRecord(page: Page, pageUid: number, edit?: FormEdit): Promise<void> {
  const contentFrame = await openRecordForm(page, `/typo3/record/edit?edit[pages][${pageUid}]=edit`);

  if (edit) {
    await edit(contentFrame, pageUid);
  }

  await submitRecordForm(page, contentFrame);
}

/**
 * Creates a new page below the given parent page via FormEngine
 */
export async function createPageRecord(page: Page, parentPageUid: number, title: string): Promise<void> {
  const contentFrame = await openRecordForm(page, `/typo3/record/edit?edit[pages][${parentPageUid}]=new`);
  await contentFrame.locator('[data-formengine-input-name^="data[pages][NEW"][data-formengine-input-name$="[title]"]').fill(title);

  await submitRecordForm(page, contentFrame);
}

async function openRecordForm(page: Page, url: string): Promise<FrameLocator> {
  await page.goto(url);

  // The backend shell renders modules inside the content iframe
  const contentFrame = page.frameLocator('iframe[name="list_frame"]');
  await expect(contentFrame.getByRole('heading', { level: 1 })).toBeVisible();

  return contentFrame;
}

async function submitRecordForm(page: Page, contentFrame: FrameLocator): Promise<void> {
  await Promise.all([
    page.waitForResponse((response) => response.url().includes('/typo3/record/edit') && response.request().method() === 'POST'),
    contentFrame.getByRole('button', { name: 'Save', exact: true }).click(),
  ]);
  await expect(contentFrame.getByRole('heading', { level: 1 })).toBeVisible();
  await expect(contentFrame.locator('.alert-danger, .callout-danger')).toHaveCount(0);
}
