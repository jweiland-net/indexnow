# TYPO3 Extension `indexnow`

[![Packagist][packagist-logo-stable]][extension-packagist-url]
[![Latest Stable Version][extension-build-shield]][extension-ter-url]
[![License][LICENSE_BADGE]][extension-packagist-url]
[![Total Downloads][extension-downloads-badge]][extension-packagist-url]
[![Monthly Downloads][extension-monthly-downloads]][extension-packagist-url]
[![TYPO3 12.4][TYPO3-shield]][TYPO3-12-url]

> The extension is in a heavy development phase. Breaking changes can be expected
> between releases.

## What is IndexNow?

[IndexNow](https://www.indexnow.org/) is an open protocol that allows website
owners to notify search engines when their content is updated, deleted or
modified, allowing for faster indexing. By using IndexNow, websites can ensure
that their content is discovered and processed more quickly by search engines,
potentially increasing visibility and traffic.

Without IndexNow, search engines may take longer to detect changes, which can
delay how quickly updated content is reflected in search results.

## What does this extension do?

This TYPO3 extension listens for changes to various types of records
(DataHandler), tries to retrieve the page UID and builds a preview URL for that
page. This preview URL is then stored until the scheduler task sends this URL
to IndexNow to inform search engines about new content.

## Installation

### Installation using Composer

Run the following command within your Composer based TYPO3 project:

```
composer require jweiland/indexnow
```

### Installation using Extension Manager

Login into TYPO3 Backend of your project and click on `Extensions` in the left
menu. Press the `Retrieve/Update` button and search for the extension key
`indexnow`. Import the extension from TER (TYPO3 Extension Repository).

## Configuration

### Get API Key

You can define your own API key. You only have to adopt following rules:

* Minimum of 8 hexadecimal characters
* Maximum of 128 hexadecimal characters
* Allowed characters: lowercase (a-z), uppercase (A-Z), numbers (0-9), dash (-)

As an alternative you can build a valid API key
at [Bing](https://www.bing.com/indexnow/getstarted)

### Extension Settings

Log-In to TYPO3 backend and visit module `Settings`.

Chose `Configure extensions` and open `indexnow`.

API Key
: Enter the just built API key from above.

Search Engine Endpoint
: Enter the API endpoint URL with IndexNow protocol.
See [Search Engine endpoint list](https://www.indexnow.org/faq). We added
IndexNow endpoint of Bing as default for you. All endpoints will inform
search engines in the same way. It doesn't matter which endpoint you chose. The
result is always the same. Just to clarify that: Using the Bing endpoint will
not only inform the Bing search engine! It will inform all registered search
engines. Use `###URL###` placeholder to replace that with the URL of the
modified page. Use `###APIKEY###` placeholder to replace that with the API key
from above.

Enable Debug Mode
: While saving records in TYPO3 backend a flash message with the preview URL
of modified page will be shown.

### Host API Key file

Create a file named `[API key].txt` with your API key as content and move it
into your document root directory of your website server.

#### Example

If you chose `abc-ABC-123` as your API key you have to create a file named
`abc-ABC-123.txt` and set `abc-ABC-123` as content of that file. Upload file
`abc-ABC-123.txt` into the `/var/www/my-typo3-page/public` folder. Open
`https://example.com/abc-ABC-123.txt` to make sure the file is public
available and its content is `abc-ABC-123`.

### Task

You need TYPO3 system extension `scheduler`.

Create a new task of type `Execute console commands`.

Chose command `indexnow:notify`.

Set timings and recurring to your needs and save the task.

### Command

Without `scheduler` extension you need to execute the following command to
inform IndexNow endpoint:

```bash
vendor/bin/typo3 indexnow:notify
```

## Usage

Just change a value on a page or content element and store the record.
Internally the page UID will be extracted and will be used to build a preview
URL. That URL will be bind with the IndexNow API endpoint URL and stored in
table `tx_indexnow_stack`.

Now you have to wait until next scheduler task run where all inserted stack
entries will be processed.

## FAQ

### Scheduler Task fails

Please have a look into log file. You will find log file at:

```bash
var/log/typo3_indexnow_[hash].log
```

### IndexNow was informed, but search results are not updated

The IndexNow provider will use your API key and request the file:

```text
[API key].txt
```

with API key as content from your server. If it does not exist, validation
fails and search engines will provide updated information much later.

### I have changed content, but there is no record in `tx_indexnow_stack`

Only records stored by TYPO3 DataHandler are processed.

Please check field `no_index` in page properties of your stored record. Make
sure page is allowed to be indexed through search engines.

Check logs for any problems.

Foreign extensions can hook into extension `indexnow` and prevent informing
IndexNow under various circumstances.

### How can I test, if IndexNow works as expected?

In documentation of [Bing IndexNow getting started](https://www.bing.com/indexnow/getstarted) I found following information:

Use [Bing Webmaster Tools](https://www.bing.com/webmasters) to verify if
your URLs are received by search engines.

## For developers

To prevent pages to be sent to IndexNow you can use event `ModifyPageUidEvent`.

Register your own EventListener:

```yaml
MyVendor\MyExtension\EventListener\DoSomethingEventListener:
  tags:
    - name: event.listener
      event: JWeiland\IndexNow\Event\ModifyPageUidEvent
```

Set page UID to `0` in event class:

```php
$modifyPageUidEvent->setPageUid(0);
```

This will prevent IndexNow to be informed.

## Missing version tags

I had to remove the tags `0.0.2` and `0.0.3` as these versions contain
a different version in `composer.json` which related to error
notifications at Packagist. You can still download these
versions from TYPO3 TER or use version `0.0.4` which solves
that issue.

## ToDo

There is a possibility to send ~10.000 update links in just one request. That
would be much better than sending each URL one by one.

<!-- MARKDOWN LINKS & IMAGES -->

[extension-build-shield]: https://poser.pugx.org/jweiland/indexnow/v/stable.svg?style=for-the-badge

[extension-downloads-badge]: https://poser.pugx.org/jweiland/indexnow/d/total.svg?style=for-the-badge

[extension-monthly-downloads]: https://poser.pugx.org/jweiland/indexnow/d/monthly?style=for-the-badge

[extension-ter-url]: https://extensions.typo3.org/extension/sync_crop_areas/

[extension-packagist-url]: https://packagist.org/packages/jweiland/indexnow/

[packagist-logo-stable]: https://img.shields.io/badge/--grey.svg?style=for-the-badge&logo=packagist&logoColor=white

[TYPO3-12-url]: https://get.typo3.org/version/12

[TYPO3-shield]: https://img.shields.io/badge/TYPO3-12.4-green.svg?style=for-the-badge&logo=typo3

[LICENSE_BADGE]: https://img.shields.io/github/license/jweiland-net/indexnow?label=license&style=for-the-badge
