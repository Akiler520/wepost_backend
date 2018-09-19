<?php
/**
 * process of http request
 * @author : martin
 * @since : 2018-04-13
 */

namespace App\Lib;

class MtHttpClient
{
    private static $instance = null;

    /**
     * timeout ms
     * @var int
     */
    private $_timeout = 15000;

    public $lastCode = null;

    public $httpContainer = [];

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }


    public function postRequest($url, $param = [])
    {
        $response = $this->httpBuilder([
            'path'   => $url,
            'method' => 'POST',
            'body'   => $param,
            'responseType'  => isset($param['responseType']) ? $param['responseType'] : "json"
        ]);

        return $response;
    }

    /**
     * @param $option
     * @return mixed
     * @throws \Exception
     */
    public function httpBuilder($option = [])
    {
        $method = isset($option['method']) ? $option['method'] : 'get';
        $this->url($option['path']);
        isset($option['headers']) and $this->headers($option['headers']);

        if ($method == 'POST'){
            $option['headers'] = ["content-type"=>"application/json"] and $this->headers($option['headers']);
        }

        isset($option['query']) and $this->queryString($option['query']);
        isset($option['body']) and $this->body($option['body']);
        isset($option['responseType']) and $this->responseType($option['responseType']);

        $ret = $this->$method();

        return $ret;
    }


    /**
     * @param $data array headers数组
     * @return $this
     */
    public function headers($data)
    {
        if (is_array($data)) {
            $headers = [];
            foreach ($data as $key => $value) {
                $headers[] = $key . ':' . $value;
            }
            $this->httpContainer['headers'] = $headers;
        }

        return $this;
    }

    public function addHeaders($data)
    {
        if (!isset($this->httpContainer['headers'])) {
            $this->httpContainer['headers'] = [];
        }
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $this->httpContainer['headers'][] = $key . ':' . $value;
            }
        }

        return $this;
    }

    public function queryString($data)
    {
        $this->httpContainer['queryString'] = http_build_query($data);

        return $this;
    }

    /**
     * @param $data mixed request body
     * @return $this
     */
    public function body($data)
    {
        if (in_array('content-type:application/json', isset($this->httpContainer['headers']) ?
            $this->httpContainer['headers'] : [])) {
            $data = json_encode($data);
        }
        $this->httpContainer['body'] = $data;

        return $this;
    }

    public function url($url)
    {
        $this->httpContainer['url'] = $url;


        return $this;
    }

    public function responseType($type)
    {
        $this->httpContainer['responseType'] = $type;

        return $this;
    }

    /**
     * @param $url
     * @return mixed
     */
    public function get()
    {
        $this->httpContainer['method'] = 'GET';

        return $this->run();
    }

    /**
     * @param $url
     * @return mixed
     */
    public function post()
    {
        $this->httpContainer['method'] = 'POST';

        return $this->run();
    }

    /**
     * @param $url
     * @return mixed
     */
    public function put()
    {
        $this->httpContainer['method'] = 'PUT';

        return $this->run();
    }

    public function patch()
    {
        $this->httpContainer['method'] = 'PATCH';

        return $this->run();
    }

    /**
     * @param $url
     * @return mixed
     */
    public function delete()
    {
        $this->httpContainer['method'] = 'DELETE';

        return $this->run();
    }

    /**
     * 统一返回结构为数组
     *
     * http返回的统一结构如下：
     * response 结构：
     * {
        "result": "true",
        "errMsg": "",
        "errCode": "",
        "data": []
     * }
     *
     * @return array|mixed|string
     * @throws \Exception
     */
    private function run()
    {
        /*$ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_URL, $this->httpContainer['url'] );
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_HEADER, 0 );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $this->httpContainer['body'] );
        $return = curl_exec ( $ch );
        curl_close ( $ch );
        print_r($return);

        exit;
        // test ok
        */

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        isset($this->httpContainer['headers']) && curl_setopt($ch, CURLOPT_HTTPHEADER, $this->httpContainer['headers']);
        isset($this->httpContainer['body']) && curl_setopt($ch, CURLOPT_POSTFIELDS, $this->httpContainer['body']);

        if (isset($this->httpContainer['url'])) {
            $url = (false !== strpos($this->httpContainer['url'], '?')) ?
                $this->httpContainer['url'] . '&' . (isset($this->httpContainer['queryString']) ?
                    $this->httpContainer['queryString'] : '')
                    : $this->httpContainer['url'] . (isset($this->httpContainer['queryString']) ?
                    '?' . $this->httpContainer['queryString'] : '');

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->httpContainer['method']);

            //注意，毫秒超时一定要设置这个
            curl_setopt($ch, CURLOPT_NOSIGNAL, true);

            // TODO: 超时时间-毫秒, 如果执行时间超过这个值，则curl直接报false
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, $this->_timeout);

            $response = curl_exec($ch);
            $this->lastCode = curl_getinfo($ch)['http_code'];
//var_dump($response);exit;
            $errNo = curl_errno($ch);
            curl_close($ch);

            if ($errNo != 0) {
                MTResponse::jsonResponse("402", RESPONSE_ERROR);
            } else {
                $responseType = "json";

                isset($this->httpContainer['responseType']) && $responseType = $this->httpContainer['responseType'];

                if ($response) {
                    ($responseType == "json") && $response = json_decode($response, true);
                } else {
                    MTResponse::jsonResponse("402", RESPONSE_ERROR);
                }
            }

            return $response;
        } else {
            curl_close($ch);

            MTResponse::jsonResponse("402", RESPONSE_ERROR);
        }
    }
}
/*
USAGE
$ret = HttpClient::getInstance()
    ->headers(['content-type'=>'application/json'])
    ->body(['a'=>'b'])
    ->queryString(['_t'=>123])
    ->url('http://127.0.0.1:3001')
    ->post();
*/
