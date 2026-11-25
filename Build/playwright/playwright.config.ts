import { defineConfig, devices } from '@playwright/test';

/**
 * End-to-end tests for EXT:indexnow against a running (DDEV) TYPO3 instance.
 *
 * Required environment:
 *   TYPO3_BASE_URL      Frontend/backend base URL (default: https://typo3-14.ddev.site)
 *   TYPO3_PROJECT_DIR   Path of the DDEV project root (default: five levels above this folder)
 *   TYPO3_BE_USER       Backend admin username
 *   TYPO3_BE_PASSWORD   Backend admin password
 *   INDEXNOW_API_KEY    API key configured in the site settings ("indexnow.apiKey") of the root site
 */
export default defineConfig({
  testDir: './tests',
  // Tests share the database table "tx_indexnow_stack" and the extension configuration
  fullyParallel: false,
  workers: 1,
  timeout: 60_000,
  reporter: [['list']],
  globalTeardown: './tests/support/global-teardown.ts',
  use: {
    baseURL: process.env.TYPO3_BASE_URL ?? 'https://typo3-14.ddev.site',
    ignoreHTTPSErrors: true,
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
