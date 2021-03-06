<?php
/**
 * API处理类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

use Ocara\Exceptions\Exception;

class Api extends Base
{
    /**
     * 获取结果
     * @param mixed $data
     * @param array $message
     * @param $status
     * @return array
     */
    public function getResult($data, array $message, $status)
    {
        $result = array(
            'status' => $status,
            'code' => $message['code'],
            'message' => $message['message'],
            'body' => $data
        );

        return $result;
    }

    /**
     * 获取XML结果
     * @param array|string $result
     * @return mixed
     * @throws Exception
     */
    protected function getXmlResult($result)
    {
        $xmlObj = ocService()->xml;
        $xmlObj->loadArray(array('root', $result));
        $xml = $xmlObj->getResult();

        return $xml;
    }

    /**
     * 格式化响应内容
     * @param mixed $result
     * @param string $contentType
     * @return false|mixed|string
     * @throws Exception
     */
    public function format($result, $contentType)
    {
        switch ($contentType) {
            case 'json':
                if (defined('JSON_UNESCAPED_UNICODE')) {
                    $content = json_encode($result, JSON_UNESCAPED_UNICODE);
                } else {
                    $content = json_encode($result);
                }
                break;
            case 'xml':
                $content = $this->getXmlResult($result);
                break;
            default:
                $content = $result;
        }

        return $content;
    }
}