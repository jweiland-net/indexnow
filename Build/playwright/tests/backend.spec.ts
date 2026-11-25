import { expect, test } from '@playwright/test';
import {
  changeFrontendLayout,
  changeNavTitle,
  clearStack,
  createPageRecord,
  fetchStack,
  loginToBackend,
  savePageRecord,
  setPageVisibility,
  snapshotPageFields,
  sql,
} from './support/typo3';

const baseUrl = (process.env.TYPO3_BASE_URL ?? 'https://typo3-14.ddev.site').replace(/\/$/, '');
const rootPageUid = Number(process.env.INDEXNOW_ROOT_PAGE_UID ?? 1);
const subPageUid = Number(process.env.INDEXNOW_SUB_PAGE_UID ?? 2);
const translatedPageUid = Number(process.env.INDEXNOW_TRANSLATED_PAGE_UID ?? 23);
const noIndexPageUid = Number(process.env.INDEXNOW_NO_INDEX_PAGE_UID ?? 4);

test.describe.serial('Queueing preview URLs on save (DataHandlerHook)', () => {
  let restorePageFields: () => void;

  test.beforeAll(() => {
    restorePageFields = snapshotPageFields(
      [rootPageUid, subPageUid, translatedPageUid, noIndexPageUid],
      ['nav_title', 'layout', 'no_index', 'hidden'],
    );
  });

  test.afterAll(() => {
    restorePageFields();
  });

  test.beforeEach(async ({ page }) => {
    clearStack();
    await loginToBackend(page);
  });

  test('changing the root page queues its frontend URL', async ({ page }) => {
    await savePageRecord(page, rootPageUid, changeNavTitle);

    expect(fetchStack()).toEqual([{ url: `${baseUrl}/`, targetPid: rootPageUid }]);
  });

  test('changing a sub page queues the URL of that page', async ({ page }) => {
    const slug = sql(`SELECT slug FROM pages WHERE uid = ${subPageUid}`);

    await savePageRecord(page, subPageUid, changeNavTitle);

    expect(fetchStack()).toEqual([{ url: `${baseUrl}${slug}`, targetPid: subPageUid }]);
  });

  test('a new page is queued as soon as it is made visible', async ({ page }) => {
    const title = `indexnow-e2e-new-page-${Date.now()}`;

    try {
      await createPageRecord(page, rootPageUid, title);

      const [uid, slug, hidden] = sql(`SELECT uid, slug, hidden FROM pages WHERE title = '${title}' AND deleted = 0`).split('\t');
      // TYPO3 creates new pages hidden by default (EXT:core/Configuration/TCA/Overrides/pages.php)
      expect(hidden).toBe('1');
      expect(fetchStack()).toEqual([]);

      await savePageRecord(page, Number(uid), setPageVisibility(true));

      expect(sql(`SELECT hidden FROM pages WHERE uid = ${Number(uid)}`)).toBe('0');
      expect(fetchStack()).toEqual([{ url: `${baseUrl}${slug}`, targetPid: Number(uid) }]);
    } finally {
      sql("DELETE FROM pages WHERE title LIKE 'indexnow-e2e-new-page-%'");
    }
  });

  test('hiding a page queues nothing (RespectHiddenPagesEventListener)', async ({ page }) => {
    await savePageRecord(page, subPageUid, setPageVisibility(false));

    expect(sql(`SELECT hidden FROM pages WHERE uid = ${subPageUid}`)).toBe('1');
    expect(fetchStack()).toEqual([]);
  });

  test('saving a page without changes queues nothing', async ({ page }) => {
    await savePageRecord(page, rootPageUid);

    expect(fetchStack()).toEqual([]);
  });

  test('changing a page translation queues the URL in the translated language', async ({ page }) => {
    sql(`UPDATE pages SET hidden = 0 WHERE uid = ${translatedPageUid}`);

    await savePageRecord(page, translatedPageUid, changeNavTitle);

    expect(fetchStack().map((row) => row.url)).toEqual([`${baseUrl}/de/`]);
  });

  test('changing a hidden page translation queues nothing', async ({ page }) => {
    sql(`UPDATE pages SET hidden = 1 WHERE uid = ${translatedPageUid}`);

    await savePageRecord(page, translatedPageUid, changeNavTitle);

    expect(fetchStack()).toEqual([]);
  });

  test('changing an l10n_mode=exclude field of the default language also queues the translations (#28)', async ({ page }) => {
    sql(`UPDATE pages SET hidden = 0 WHERE uid = ${translatedPageUid}`);
    const currentLayout = sql(`SELECT layout FROM pages WHERE uid = ${rootPageUid}`);

    await savePageRecord(page, rootPageUid, changeFrontendLayout(currentLayout === '1' ? '2' : '1'));

    // Precondition: TYPO3 synchronized the value into the translation
    expect(sql(`SELECT layout FROM pages WHERE uid = ${translatedPageUid}`)).toBe(
      sql(`SELECT layout FROM pages WHERE uid = ${rootPageUid}`),
    );
    expect(fetchStack().map((row) => row.url).sort()).toEqual([`${baseUrl}/`, `${baseUrl}/de/`]);
  });

  test('changing a translatable field of the default language does not queue the translations', async ({ page }) => {
    await savePageRecord(page, rootPageUid, changeNavTitle);

    expect(fetchStack().map((row) => row.url)).toEqual([`${baseUrl}/`]);
  });

  test('changing the same page twice does not create duplicates', async ({ page }) => {
    await savePageRecord(page, rootPageUid, changeNavTitle);
    await savePageRecord(page, rootPageUid, changeNavTitle);

    expect(fetchStack()).toHaveLength(1);
  });

  test('pages with "no_index" are not queued (RespectPagesWithNoIndexEventListener)', async ({ page }) => {
    sql(`UPDATE pages SET no_index = 1 WHERE uid = ${noIndexPageUid}`);

    await savePageRecord(page, noIndexPageUid, changeNavTitle);

    expect(fetchStack()).toEqual([]);
  });
});
