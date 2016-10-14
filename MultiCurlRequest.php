<?php
/**
 * @link https://github.com/paulzi/multicurl
 * @copyright Copyright (c) 2016 PaulZi (pavel.zimakoff@gmail.com)
 * @license MIT (https://github.com/paulzi/multicurl/blob/master/LICENSE)
 */

namespace paulzi\multicurl;

/**
 * Class MultiCurlRequest. Structure for transmission in the MultiCurl.
 * @author PaulZi (pavel.zimakoff@gmail.com)
 */
class MultiCurlRequest
{
    /**
     * @var resource curl_init resource @see: curl_init
     */
    public $curl;

    /**
     * @var callable on before callback
     */
    public $onBefore;

    /**
     * @var callable on success callback
     */
    public $onSuccess;

    /**
     * @var callable on error callback
     */
    public $onError;

    /**
     * @var callable on error callback
     */
    public $onAlways;

    /**
     * @var callable on retry callback
     */
    public $onRetry;

    /**
     * @var array additional parameters
     */
    public $params = [];
}