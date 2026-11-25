import { expect, test } from '@playwright/test';
import {
  clearStack,
  fetchStack,
  indexNowLogSize,
  loginToBackend,
  readIndexNowLogSince,
  savePageRecord,
  setExtensionConfiguration,
  typo3,
} from './support/typo3';

const baseUrl = (process.env.TYPO3_BASE_URL ?? 'https://typo3-14.ddev.site').replace(/\/$/, '');

/**
 * The IndexNow endpoint is pointed to the local TYPO3 instance, so no request leaves the machine:
 *  - "{baseUrl}/" answers with 200 (success path)
 *  - "{baseUrl}/indexnow-e2e-missing-endpoint" answers with 404 (error path, logged to var/log/typo3_indexnow_*.log)
 */
const successEndpoint = `${baseUrl}/`;
const failingEndpoint = `${baseUrl}/indexnow-e2e-missing-endpoint`;

test.describe.serial('Sending queued URLs (indexnow:notify)', () => {
  test.beforeEach(async ({ page }) => {
    clearStack();
    setExtensionConfiguration('notifyBatchMode', '0');
    setExtensionConfiguration('searchEngineEndpoint', successEndpoint);
    await loginToBackend(page);
  });

  test('reports an empty stack', () => {
    expect(typo3('indexnow:notify')).toContain('No URL records available, nothing sent.');
  });

  test('single mode sends each URL and empties the stack', async ({ page }) => {
    await savePageRecord(page, 1);
    await savePageRecord(page, 2);
    expect(fetchStack()).toHaveLength(2);

    expect(typo3('indexnow:notify')).toContain('Batch sent (2 URLs), all entries deleted.');
    expect(fetchStack()).toEqual([]);
  });

  test('single mode uses GET requests with url and key parameters', async ({ page }) => {
    setExtensionConfiguration('searchEngineEndpoint', failingEndpoint);
    await savePageRecord(page, 1);
    const logMarker = indexNowLogSize();

    typo3('indexnow:notify');

    const log = readIndexNowLogSince(logMarker);
    expect(log).toContain('Request to IndexNow results in error');
    expect(log).toContain('GET');
    expect(log).toContain(`url=${encodeURIComponent(`${baseUrl}/`)}`);
  });

  test('batch mode sends all URLs of one host with a single POST request', async ({ page }) => {
    setExtensionConfiguration('notifyBatchMode', '1');
    setExtensionConfiguration('searchEngineEndpoint', failingEndpoint);
    await savePageRecord(page, 1);
    await savePageRecord(page, 2);
    const logMarker = indexNowLogSize();

    typo3('indexnow:notify');

    const log = readIndexNowLogSince(logMarker);
    expect(log).toContain(`Batch IndexNow error for domain ${new URL(baseUrl).host}`);
    expect(log).toContain('POST');
    expect(log).not.toContain('Request to IndexNow results in error');
  });

  test('batch mode succeeds against an endpoint answering 200', async ({ page }) => {
    setExtensionConfiguration('notifyBatchMode', '1');
    await savePageRecord(page, 1);
    await savePageRecord(page, 2);

    expect(typo3('indexnow:notify')).toContain('Batch sent (2 URLs), all entries deleted.');
    expect(fetchStack()).toEqual([]);
  });
});
