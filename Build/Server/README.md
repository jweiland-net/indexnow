# Endpoint for development purpose

For developing and debugging it is better to not send requests
to an official endpoint. For these cases a local PHP webserver
can be used.


## Start PHP webserver

### Start on the local system

From the project directory:

    php -S localhost:1234 -t Build/Server

From this directory:

    php -S localhost:1234

### Start in ddev

Connect into the ddev web container and start the PHP webserver:

    ddev ssh
    php -S localhost:1234 -t public/typo3conf/ext/indexnow/Build/Server

### Available endpoints

For now, there are two endpoints available:

| Endpoint URL                    |  Description                                 |
|---------------------------------|----------------------------------------------|
| `http://localhost:1234/200.php` | Successful response (`200 OK`)               |
| `http://localhost:1234/429.php` | Erroneous response (`429 Too Many Requests`) |

## Use local webserver as endpoint

Point the search engine endpoint in the extension configuration
(`basic.searchEngineEndpoint`) to the desired endpoint, for example:

    http://localhost:1234/200.php?url=###URL###&key=###APIKEY###

The request is displayed on the console where you started the local PHP webserver:

    [Mon Nov 15 17:43:08 2021] 127.0.0.1:34500 [200]: GET /200.php?url=https://typo3v10.ddev.site/en&key=8b06f2e17bee4a6f9843aa95f4be3f86
