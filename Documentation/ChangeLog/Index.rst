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
*   [REFACTOR] Split ``DataHandlerHook`` into single-responsibility services
    (``BackendContextValidator``, ``RecordResolver``, ``PageUidResolver``, ``LanguageResolver``,
    ``PagePreviewUrlBuilder``, ``PreviewUrlCollector``)
*   [REFACTOR] Split ``SearchEngineNotifier`` into ``SingleUrlSubmitter`` and ``BatchUrlSubmitter``
    strategies, collected via DI tag ``indexnow.url_submitter``
*   [REFACTOR] Introduce ``ApiKeyResolver`` as single source of truth for the site setting ``indexnow.apiKey``
*   [REFACTOR] ``SearchEngineNotifier::notify()`` expects ``list<Stack>`` instead of ``\SplObjectStorage``
*   [REFACTOR] Make value objects, events and listeners ``final``, services ``readonly``
*   [BUGFIX] Do not HTML-encode preview URLs before storing them in the stack
*   [BUGFIX] Prevent TypeError when a record to merge does not exist anymore
*   [BUGFIX] Log missing API key in batch mode instead of silently skipping the host
