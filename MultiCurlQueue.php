<?php
/**
 * @link https://github.com/paulzi/multicurl
 * @copyright Copyright (c) 2016 PaulZi (pavel.zimakoff@gmail.com)
 * @license MIT (https://github.com/paulzi/multicurl/blob/master/LICENSE)
 */

namespace paulzi\multicurl;

/**
 * Class MultiCurlQueue. A wrapper over curl_multi_init with queue and threads.
 * @author PaulZi (pavel.zimakoff@gmail.com)
 */
class MultiCurlQueue extends MultiCurl
{
    /**
     * @var int threads count
     */
    public $threads = 1;

    /**
     * @var int the number of retries
     */
    public $retry   = 0;

    /**
     * @var MultiCurlRequest[] internal array of request
     */
    private $_queue;

    /**
     * @var array array of threads status
     */
    private $_status;

    /**
     * @var array storage of retry numbers
     */
    private $_retries;


    /**
     * Execute requests
     * @param MultiCurlRequest[] $requests
     */
    public function run($requests)
    {
        $this->_queue   = $requests;
        $this->_status  = array_fill(0, $this->threads, false);
        $this->_retries = array_fill(0, $this->threads, 0);
        $this->processNext();
        parent::run();
    }

    /**
     * Execute free threads
     */
    protected function processNext()
    {
        foreach ($this->_status as $i => $status) {
            if ($status === false && ($request = array_shift($this->_queue)) !== null) {
                $this->_status[$i]  = $request;
                $this->_retries[$i] = 0;
                $this->add($request);
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function onSuccess($request, $response, $content)
    {
        parent::onSuccess($request, $response, $content);

        $thread = $this->findThread($request);
        $this->_status[$thread] = false;
        $this->processNext();
    }

    /**
     * @inheritdoc
     */
    protected function onError($request, $response, $content, $errCode, $errMsg)
    {
        $thread	= $this->findThread($request);
        if ($this->_retries[$thread] < $this->retry) {
            $this->onRetry($request, $response, $content, $errCode, $errMsg, $this->_retries[$thread], $this->retry);
            $this->_retries[$thread]++;
            $this->add($request);
        } else {
            parent::onError($request, $response, $content, $errCode, $errMsg);
            $this->_status[$thread] = false;
            $this->processNext();
        }
    }

    /**
     * @param MultiCurlRequest $request
     * @param array $response response parameters @see: curl_getinfo
     * @param string $content content body of request
     * @param int $errCode CURLE_* error code
     * @param string $errMsg error message
     * @param int $retryIndex current retry index
     * @param int $retryTotal total retry count
     */
    protected function onRetry($request, $response, $content, $errCode, $errMsg, $retryIndex, $retryTotal)
    {
        if ($request->onRetry) {
            call_user_func($request->onRetry, $request, $response, $content, $errCode, $errMsg, $retryIndex, $retryTotal);
        }
    }

    /**
     * @param MultiCurlRequest $request
     * @return bool|int
     */
    private function findThread($request)
    {
        foreach ($this->_status as $i => $status) {
            if ($status === $request) {
                return $i;
            }
        }
        return false;
    }
}