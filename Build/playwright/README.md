# End-to-end tests (Playwright)

Runs against a running TYPO3 13/14 instance (e.g. DDEV) with EXT:indexnow installed.
No request leaves the machine: the tests point the IndexNow endpoint to the local instance.

## Preparation

1. Install and set up the extension: `ddev composer require jweiland/indexnow:@dev && ddev typo3 extension:setup -e indexnow`
2. Add the site set `jweiland/indexnow-default` to the root site and set an API key in its `settings.yaml`:

   ```yaml
   indexnow:
     apiKey: a1b2c3d4e5f60718293a4b5c6d7e8f90
   ```

3. Create a backend admin: `ddev typo3 backend:user:create -u indexnow_e2e -p '<password>' -a -n`

## Run

```bash
cd Build/playwright
npm ci
TYPO3_BE_USER=indexnow_e2e \
TYPO3_BE_PASSWORD='<password>' \
INDEXNOW_API_KEY=a1b2c3d4e5f60718293a4b5c6d7e8f90 \
INDEXNOW_ORIGINAL_ENDPOINT=https://www.bing.com/indexnow \
npx playwright test
```

Optional: `TYPO3_BASE_URL`, `TYPO3_PROJECT_DIR`, `INDEXNOW_ROOT_PAGE_UID` (1), `INDEXNOW_SUB_PAGE_UID` (2),
`INDEXNOW_TRANSLATED_PAGE_UID` (23), `INDEXNOW_NO_INDEX_PAGE_UID` (4).

The global teardown empties `tx_indexnow_stack` and restores `notifyBatchMode`, `searchEngineEndpoint` and `no_index`.
