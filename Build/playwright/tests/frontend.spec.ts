import { expect, test } from '@playwright/test';
import { apiKey } from './support/typo3';

test.describe('API key verification file (ApiKeyVerificationMiddleware)', () => {
  test('serves the configured API key as plain text at /{apiKey}.txt', async ({ request }) => {
    const response = await request.get(`/${apiKey}.txt`);

    expect(response.status()).toBe(200);
    expect(response.headers()['content-type']).toBe('text/plain; charset=utf-8');
    expect(response.headers()['cache-control']).toContain('public, max-age=86400');
    expect(await response.text()).toBe(apiKey);
  });

  test('passes other .txt files through to TYPO3', async ({ request }) => {
    const response = await request.get('/not-the-indexnow-key.txt');

    expect(response.status()).toBe(404);
    expect(await response.text()).not.toBe(apiKey);
  });

  test('does not serve the API key below a sub path', async ({ request }) => {
    const response = await request.get(`/some/folder/${apiKey}.txt`);

    expect(await response.text()).not.toBe(apiKey);
  });

  test('regular pages are still rendered', async ({ page }) => {
    const response = await page.goto('/');

    expect(response?.status()).toBe(200);
    await expect(page.locator('body')).not.toHaveText(apiKey);
  });
});
