# MultiCurl

A wrappers over curl_multi_init.

[![Packagist Version](https://img.shields.io/packagist/v/paulzi/multicurl.svg)](https://packagist.org/packages/paulzi/multicurl)
[![Total Downloads](https://img.shields.io/packagist/dt/paulzi/multicurl.svg)](https://packagist.org/packages/paulzi/multicurl)

This provide two class:
- **MultiCurl** - simple wrapper over curl_multi_init with events
- **MultiCurlQueue** - extended version of MultiCurl, with queue of requests, retry failed requests and multithreading.  

## Install

Install via Composer:

```bash
composer require paulzi/multicurl:~0.1.0
```

or add

```bash
"paulzi/multicurl" : "~0.1.0"
```

to the `require` section of your `composer.json` file.


## Usage 
```php
function generateCurl($url)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL,            $url);
    curl_setopt($curl, CURLOPT_HEADER,         false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_ENCODING,       '');
    return $curl;
}


// usage MultiCurl
$request = new MultiCurlRequest();
$request->curl = generateCurl('https://google.com');
$request->onSuccess = function($request, $response, $content) {
    var_dump('Success', $content);
};
$request->onError = function($request, $response, $content, $errCode, $errMsg) {
    var_dump('Error', $errMsg);
};

$loader = new MultiCurl();
$loader->add($request);
$loader->run();


// usage MultiCurlQueue
$request1 = new MultiCurlRequest();
$request1->curl = generateCurl('https://google.com');
$request1->onSuccess = function($request, $response, $content) {
    var_dump('Request1 Success', $content);
};
$request1->onRetry = function($request, $response, $content, $errCode, $errMsg) {
    var_dump('Request1 Retry', $errMsg);
};
$request1->onError = function($request, $response, $content, $errCode, $errMsg) {
    var_dump('Request1 Error', $errMsg);
};

$request2 = new MultiCurlRequest();
$request2->curl = generateCurl('http://none.noexist');
$request2->onSuccess = function($request, $response, $content) {
    var_dump('Request2 Success', $content);
};
$request2->onRetry = function($request, $response, $content, $errCode, $errMsg) {
    var_dump('Request2 Retry', $errMsg);
};
$request2->onError = function($request, $response, $content, $errCode, $errMsg) {
    var_dump('Request2 Error', $errMsg);
};

$loader = new MultiCurlQueue();
$loader->threads = 2;
$loader->retry   = 3;
$loader->run([$request1, $request2]);
```

## Documentation

### MultiCurl
- `add($request)` - add request to job
- `run()` - execute requests
- `onBefore($request)` - on before execute request event
- `onSuccess($request, $response, $content)` - on success request event
- `onError($request, $response, $content, $errCode, $errMsg)` - on error request event
- `onAlways($request, $response, $content)` - on always request event

### MultiCurlQueue
- `run($requests)` - execute requests
- `onBefore($request)` - on before execute request event
- `onSuccess($request, $response, $content)` - on success request event
- `onError($request, $response, $content, $errCode, $errMsg)` - on error request event
- `onAlways($request, $response, $content)` - on always request event
- `onRetry($request, $response, $content, $errCode, $errMsg, $retryIndex, $retryTotal)` - on retry request event

### MultiCurlRequest
- `$curl` - curl resource
- `$onBefore` - on before execute request callback
- `$onSuccess` - on success request callback
- `$onError` - on error request callback
- `$onAlways` - on always request callback
- `$onRetry` - on retry request callback