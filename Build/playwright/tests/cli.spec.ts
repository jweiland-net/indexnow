import { expect, test } from '@playwright/test';
import {
  accessLogSize,
  apiKey,
  changeNavTitle,
  clearStack,
  fetchStack,
  indexNowLogSize,
  loginToBackend,
  readAccessLogSince,
  readIndexNowLogSince,
  savePageRecord,
  setExtensionConfiguration,
  snapshotPageFields,
  sql,
  typo3,
} from './support/typo3';

const baseUrl = (process.env.TYPO3_BASE_URL ?? 'https://typo3-14.ddev.site').replace(/\/$/, '');
const subPageUid = Number(process.env.INDEXNOW_SUB_PAGE_UID ?? 2);

/**
 * The IndexNow endpoint is pointed to the local TYPO3 instance, so no request leaves the machine:
 *  - The API key verification file "/{apiKey}.txt" answers GET (with query) and POST with 200 (success path).
 *    The site root can not be used: with "cacheHash.enforceValidation" TYPO3 answers unknown query parameters with 404.
 *  - "/indexnow-e2e-missing-endpoint" answers with 404 (error path, logged to var/log/typo3_indexnow_*.log)
 */
const successEndpointPath = `/${apiKey}.txt`;
const successEndpoint = `${baseUrl}${successEndpointPath}`;
const failingEndpoint = `${baseUrl}/indexnow-e2e-missing-endpoint`;

function expectedGetRequest(url: string): string {
  return `GET ${successEndpointPath}?${new URLSearchParams({ url, key: apiKey }).toString()} 200`;
}

test.describe.serial('Sending queued URLs (indexnow:notify)', () => {
  let restorePageFields: () => void;

  test.beforeAll(() => {
    restorePageFields = snapshotPageFields([1, subPageUid], ['nav_title']);
  });

  test.afterAll(() => {
    restorePageFields();
  });

  test.beforeEach(async ({ page }) => {
    clearStack();
    setExtensionConfiguration('notifyBatchMode', '0');
    setExtensionConfiguration('searchEngineEndpoint', successEndpoint);
    await loginToBackend(page);
  });

  test('reports an empty stack', () => {
    expect(typo3('indexnow:notify')).toContain('No URL records available, nothing sent.');
  });

  test('single mode sends one GET request with url and key per URL and empties the stack', async ({ page }) => {
    await savePageRecord(page, 1, changeNavTitle);
    await savePageRecord(page, subPageUid, changeNavTitle);
    const subPageUrl = `${baseUrl}${sql(`SELECT slug FROM pages WHERE uid = ${subPageUid}`)}`;
    expect(fetchStack()).toHaveLength(2);
    const indexNowLogMarker = indexNowLogSize();
    const accessLogMarker = accessLogSize();

    expect(typo3('indexnow:notify')).toContain('Batch sent (2 URLs), all entries deleted.');

    const endpointRequests = readAccessLogSince(accessLogMarker).filter((line) => line.includes(successEndpointPath));
    expect(endpointRequests).toEqual([expectedGetRequest(`${baseUrl}/`), expectedGetRequest(subPageUrl)]);
    expect(readIndexNowLogSince(indexNowLogMarker)).toBe('');
    expect(fetchStack()).toEqual([]);
  });

  test('single mode logs failed requests', async ({ page }) => {
    setExtensionConfiguration('searchEngineEndpoint', failingEndpoint);
    await savePageRecord(page, 1, changeNavTitle);
    const indexNowLogMarker = indexNowLogSize();

    typo3('indexnow:notify');

    const log = readIndexNowLogSince(indexNowLogMarker);
    expect(log).toContain('SingleUrlSubmitter');
    expect(log).toContain('Request to IndexNow results in error');
    expect(log).toContain('GET');
    // Guzzle redacts query strings in exception messages, so the API key must not end up in the log
    expect(log).not.toContain(apiKey);
  });

  test('batch mode sends all URLs of one host with a single POST request', async ({ page }) => {
    setExtensionConfiguration('notifyBatchMode', '1');
    await savePageRecord(page, 1, changeNavTitle);
    await savePageRecord(page, subPageUid, changeNavTitle);
    const indexNowLogMarker = indexNowLogSize();
    const accessLogMarker = accessLogSize();

    expect(typo3('indexnow:notify')).toContain('Batch sent (2 URLs), all entries deleted.');

    const endpointRequests = readAccessLogSince(accessLogMarker).filter((line) => line.includes(successEndpointPath));
    expect(endpointRequests).toEqual([`POST ${successEndpointPath} 200`]);
    expect(readIndexNowLogSince(indexNowLogMarker)).toBe('');
    expect(fetchStack()).toEqual([]);
  });

  test('batch mode logs failed requests', async ({ page }) => {
    setExtensionConfiguration('notifyBatchMode', '1');
    setExtensionConfiguration('searchEngineEndpoint', failingEndpoint);
    await savePageRecord(page, 1, changeNavTitle);
    await savePageRecord(page, subPageUid, changeNavTitle);
    const indexNowLogMarker = indexNowLogSize();

    typo3('indexnow:notify');

    const log = readIndexNowLogSince(indexNowLogMarker);
    expect(log).toContain(`Batch IndexNow error for domain ${new URL(baseUrl).host}`);
    expect(log).toContain('POST');
    expect(log).not.toContain('SingleUrlSubmitter');
  });
});
