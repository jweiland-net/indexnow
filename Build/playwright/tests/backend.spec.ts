import { expect, test } from '@playwright/test';
import { clearStack, fetchStack, loginToBackend, savePageRecord, sql } from './support/typo3';

const baseUrl = (process.env.TYPO3_BASE_URL ?? 'https://typo3-14.ddev.site').replace(/\/$/, '');
const rootPageUid = Number(process.env.INDEXNOW_ROOT_PAGE_UID ?? 1);
const subPageUid = Number(process.env.INDEXNOW_SUB_PAGE_UID ?? 2);
const translatedPageUid = Number(process.env.INDEXNOW_TRANSLATED_PAGE_UID ?? 23);
const noIndexPageUid = Number(process.env.INDEXNOW_NO_INDEX_PAGE_UID ?? 4);

test.describe.serial('Queueing preview URLs on save (DataHandlerHook)', () => {
  test.beforeEach(async ({ page }) => {
    clearStack();
    await loginToBackend(page);
  });

  test('saving the root page queues its frontend URL', async ({ page }) => {
    await savePageRecord(page, rootPageUid);

    expect(fetchStack()).toEqual([{ url: `${baseUrl}/`, targetPid: rootPageUid }]);
  });

  test('saving a sub page queues the URL of that page', async ({ page }) => {
    const slug = sql(`SELECT slug FROM pages WHERE uid = ${subPageUid}`);

    await savePageRecord(page, subPageUid);

    expect(fetchStack()).toEqual([{ url: `${baseUrl}${slug}`, targetPid: subPageUid }]);
  });

  test('saving a page translation queues the URL in the translated language', async ({ page }) => {
    await savePageRecord(page, translatedPageUid);

    const stack = fetchStack();
    expect(stack).toHaveLength(1);
    expect(stack[0].url).toBe(`${baseUrl}/de/`);
  });

  test('saving the same page twice does not create duplicates', async ({ page }) => {
    await savePageRecord(page, rootPageUid);
    await savePageRecord(page, rootPageUid);

    expect(fetchStack()).toHaveLength(1);
  });

  test('pages with "no_index" are not queued (RespectPagesWithNoIndexEventListener)', async ({ page }) => {
    sql(`UPDATE pages SET no_index = 1 WHERE uid = ${noIndexPageUid}`);
    try {
      await savePageRecord(page, noIndexPageUid);

      expect(fetchStack()).toEqual([]);
    } finally {
      sql(`UPDATE pages SET no_index = 0 WHERE uid = ${noIndexPageUid}`);
    }
  });
});
