..  include:: /Includes.rst.txt

..  _changelog:

=========
ChangeLog
=========

Version 1.0.0
=============

*   [TASK] Drop TYPO3 12 support, the extension is compatible with TYPO3 13.4 LTS and 14.3 LTS only
*   [TASK] Require PHP 8.2 and declare dependency to EXT:backend
*   [TASK] Remove deprecated ``ProvidePreviewUrlEvent::setPreviewUrl()`` and ``getPreviewUrl()``
*   [TASK] Register event listener via ``#[AsEventListener]`` and DataHandler hook via ``#[Autoconfigure]``
*   [TASK] Depend on ``Psr\EventDispatcher\EventDispatcherInterface`` instead of the core implementation
*   [TASK] Add PHPStan (level 9) configuration
*   [TASK] Test PHP 8.5 in CI and ``runTests.sh``, remove deprecated PHPUnit ``cacheResult`` setting
*   [TASK] Pass release data as environment variables in the TER release workflow and limit its permissions
*   [REFACTOR] Split ``DataHandlerHook`` into single-responsibility services
    (``BackendContextValidator``, ``RecordResolver``, ``PageUidResolver``, ``LanguageResolver``,
    ``PagePreviewUrlBuilder``, ``PreviewUrlCollector``)
*   [REFACTOR] Split ``SearchEngineNotifier`` into ``SingleUrlSubmitter`` and ``BatchUrlSubmitter``
    strategies, collected via DI tag ``indexnow.url_submitter``
*   [REFACTOR] Introduce ``ApiKeyResolver`` as single source of truth for the site setting ``indexnow.apiKey``
*   [REFACTOR] ``SearchEngineNotifier::notify()`` expects ``list<Stack>`` instead of ``\SplObjectStorage``
*   [REFACTOR] Make value objects, events, listeners and internal services ``final``. The notifier
    classes stay extensible (``protected``, XCLASS or service alias) and URL submitters can be added or
    replaced via the priority of the DI tag ``indexnow.url_submitter``
*   [REFACTOR] ``ExtConf`` uses the Bing endpoint as constructor default, an emptied setting falls back to it
*   [REFACTOR] Add ``Stack::fromRaw()`` to trim raw values, ``Stack`` uses constructor property promotion
*   [BUGFIX] Do not HTML-encode preview URLs before storing them in the stack
*   [BUGFIX] Prevent TypeError when a record to merge does not exist anymore
*   [BUGFIX] Log missing API key in batch mode instead of silently skipping the host
*   [BUGFIX] Inform IndexNow about translations, if TYPO3 synchronized a changed ``l10n_mode=exclude``
    (or ``allowLanguageSynchronization``) field from the default language into them
    (`#28 <https://github.com/jweiland-net/indexnow/issues/28>`__)
*   [BUGFIX] Queue the new URL instead of the old one, if the slug of a page has been changed
*   [BUGFIX] Queue new pages. The final UID is resolved from the temporary ``NEW...`` ID via
    ``DataHandler::$substNEWwithIDs``
*   [BUGFIX] Skip hidden pages and records on hidden pages (``RespectHiddenPagesEventListener``),
    as their URLs are not reachable in frontend
*   [!!!][TASK] Only queue records whose content has actually been changed. Saving a record without
    changes does not inform IndexNow anymore. Changing only MM relations (f.e. ``categories``) does not
    inform IndexNow, as DataHandler does not report if they have really been changed. The DataHandler
    hook moved from ``processDatamap_beforeStart`` to ``processDatamap_afterDatabaseOperations``
