<?php
/**
 * @link https://github.com/paulzi/multicurl
 * @copyright Copyright (c) 2016 PaulZi (pavel.zimakoff@gmail.com)
 * @license MIT (https://github.com/paulzi/multicurl/blob/master/LICENSE)
 */

namespace paulzi\multicurl;

/**
 * Class MultiCurl. A wrapper over curl_multi_init
 * @author PaulZi (pavel.zimakoff@gmail.com)
 */
class MultiCurl
{
    /**
     * @var resource curl_multi_init handler
     */
    private $_mh;

    /**
     * @var MultiCurlRequest[] array of request
     */
    private $_requests;

    /**
     * @var bool list updated flag
     */
    private $_updated;


    public function __construct()
    {
        $this->_mh       = curl_multi_init();
        $this->_requests = [];
    }

    public function __destruct()
    {
        curl_multi_close($this->_mh);
    }

    /**
     * Add request to execute
     * @param MultiCurlRequest $request
     */
    public function add($request)
    {
        $this->onBefore($request);
        array_push($this->_requests, $request);
        curl_multi_add_handle($this->_mh, $request->curl);
        $this->_updated = true;
    }

    /**
     * Execute requests
     */
    public function run()
    {
        $running = null;
        do {
            curl_multi_exec($this->_mh, $running);
            $this->_updated = false;
            while ($info = curl_multi_info_read($this->_mh)) {
                $index = false;
                foreach ($this->_requests as $i => $item) {
                    if ($item->curl === $info['handle']) {
                        $index = $i;
                        break;
                    }
                }
                if ($index === false) {
                    throw new \Exception('Curl request not founded in list');
                }
                $request = $this->_requests[$index];
                unset($this->_requests[$index]);

                $content  = curl_multi_getcontent($request->curl);
                $response = curl_getinfo($request->curl);

                curl_multi_remove_handle($this->_mh, $info['handle']);

                if ($info['result'] === CURLE_OK) {
                    $this->onSuccess($request, $response, $content);
                } else {
                    $errCode = $info['result'];
                    $errMsg  = function_exists('curl_strerror') ? curl_strerror($errCode) : "cURL error {$errCode}";
                    $this->onError($request, $response, $content, $errCode, $errMsg);
                }
                $this->onAlways($request, $response, $content);
            }
            curl_multi_select($this->_mh, 1);
        } while ($running>0 || $this->_updated);
    }

    /**
     * @param MultiCurlRequest $request
     */
    protected function onBefore($request)
    {
        if ($request->onBefore) {
            call_user_func($request->onBefore, $request);
        }
    }

    /**
     * @param MultiCurlRequest $request
     * @param array $response response parameters @see: curl_getinfo
     * @param string $content content body of response
     */
    protected function onSuccess($request, $response, $content)
    {
        if ($request->onSuccess) {
            call_user_func($request->onSuccess, $request, $response, $content);
        }
    }

    /**
     * @param MultiCurlRequest $request
     * @param array $response response parameters @see: curl_getinfo
     * @param string $content content body of response
     * @param int $errCode CURLE_* error code
     * @param string $errMsg error message
     */
    protected function onError($request, $response, $content, $errCode, $errMsg)
    {
        if ($request->onError) {
            call_user_func($request->onError, $request, $response, $content, $errCode, $errMsg);
        }
    }

    /**
     * @param MultiCurlRequest $request
     * @param array $response response parameters @see: curl_getinfo
     * @param string $content content body of response
     */
    protected function onAlways($request, $response, $content)
    {
        if ($request->onAlways) {
            call_user_func($request->onAlways, $request, $response, $content);
        }
    }
}