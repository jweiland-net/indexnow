import { clearStack, setExtensionConfiguration, sql } from './typo3';

/**
 * Restore the state the tests may have changed
 */
export default async function globalTeardown(): Promise<void> {
  clearStack();
  setExtensionConfiguration('notifyBatchMode', '0');
  if (process.env.INDEXNOW_ORIGINAL_ENDPOINT) {
    setExtensionConfiguration('searchEngineEndpoint', process.env.INDEXNOW_ORIGINAL_ENDPOINT);
  }
  if (process.env.INDEXNOW_NO_INDEX_PAGE_UID) {
    sql(`UPDATE pages SET no_index = 0 WHERE uid = ${Number(process.env.INDEXNOW_NO_INDEX_PAGE_UID)}`);
  }
}
