<?php
/**
 * 公用函数
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

use Ocara\Exceptions\Exception;

/**
 * 替换空白字符
 * @param string $string
 * @param string $replace
 * @return mixed
 */
function ocReplaceSpace($string, $replace = '')
{
    return preg_replace('/[\s\v]+/', $replace, $string);
}

/**
 * 检查扩展
 * @param string $extension
 * @param bool $required
 * @return bool
 * @throws Exception
 */
function ocCheckExtension($extension, $required = true)
{
    if (!extension_loaded($extension)) {
        if ($required) {
            ocService()->error->show('failed_load_extension', array($extension));
        }
        return false;
    }

    return true;
}

/**
 * 设置或获取全局变量
 * @param string $name
 * @param mixed $value
 * @return mixed
 * @throws Exception
 */
function ocGlobal($name, $value = null)
{
    $globals = ocService()->globals;

    if (func_num_args() >= 2) {
        $globals->set($name, $value);
    } else {
        return $globals->get($name);
    }
}

/**
 * 对象递归转换成关联数组
 * @param object $data
 * @return array
 */
function ocArray($data)
{
    if (is_object($data)) {
        $data = get_object_vars($data);
    }

    if (is_array($data)) {
        return array_map(__FUNCTION__, $data);
    } else {
        return $data;
    }
}

/**
 * 检测是否关联数组
 * @param array $data
 * @return bool
 */
function ocAssoc(array $data)
{
    return array_keys($data) !== range(0, count($data) - 1);
}

/**
 * 数组递归转换成对象
 * @param mixed $data
 * @return object
 */
function ocObject($data)
{
    if (is_array($data)) {
        return (object)array_map(__FUNCTION__, $data);
    } else {
        return $data;
    }
}

/**
 * 检查路径是否存在，如果不存在则新建
 * @param string $path
 * @param integer $perm
 * @param bool $required
 * @return bool
 * @throws Exception
 */
function ocCheckPath($path, $perm = null, $required = false)
{
    if (empty($path)) return false;

    if (!is_dir($path)) {
        $perm = $perm ?: 0755;
        if (!@mkdir($path, $perm, true)) {
            if ($required) {
                ocService()->error->show('failed_make_dir');
            } else {
                return false;
            }
        } else {
            chmod($path, $perm);
        }
    }

    return is_dir($path);
}

/**
 * 新建类实例
 * @param string $name
 * @param array $params
 * @return object
 * @throws Exception
 */
function ocClass($name, array $params = array())
{
    if ($params) {
        try {
            $reflection = new ReflectionClass($name);
            $object = $reflection->newInstanceArgs($params);
        } catch (\Exception $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }
    } else {
        $object = new $name();
    }

    return $object;
}

/**
 * 检测类是否存在
 * @param string $class
 * @return bool
 */
function ocClassExists($class)
{
    try {
        $result = class_exists($class);
    } catch (\Exception $exception) {
        return false;
    }

    return $result;
}

/**
 * 加载函数库文件
 * @param string $filePath
 * @throws Exception
 */
function ocFunc($filePath)
{
    $filePath = ocCommPath($filePath);
    $filePath = $filePath . '.php';

    if (ocFileExists($file = ocPath('functions', $filePath)) ||
        ocFileExists($file = OC_SYS . 'service/functions/' . $filePath) ||
        ocFileExists($file = OC_SYS . '../extension/service/functions/' . $filePath)
    ) {
        ocImport($filePath);
    }

    ocService()->error->show('not_exists_function_file');
}

/**
 * 使用原生的SQL语句，防止框架进行SQL安全过滤和转义
 * @param string $sql
 * @return mixed|string
 * @throws Exception
 */
function ocSql($sql)
{
    if (is_string($sql) || is_numeric($sql)) {
        $sql = ocService()->request->stripSqlTag($sql);
        return OC_SQL_TAG . $sql;
    }

    return $sql;
}

/**
 * 是否是标准名称
 * @param $name
 * @return false|int
 */
function ocIsStandardName($name)
{
    return preg_match('/^[^\d]\w*$/', $name);
}


/**
 * 内容处理函数
 */

/**
 * 写入文件
 * @param string $filePath
 * @param string $content
 * @param bool $append
 * @param int $perm
 * @return bool|false|int
 * @throws Exception
 */
function ocWrite($filePath, $content, $append = false, $perm = null)
{
    if (is_dir($filePath)) {
        ocService()->error->show('invalid_dir_file_path');
    }

    $perm = $perm ?: 0755;
    $dirPath = dirname($filePath);
    $result = false;

    if (ocCheckPath($dirPath, $perm)) {

        if (!is_writable($dirPath)) {
            ocService()->error->show('no_dir_write_perm');
        }

        if ($fo = @fopen($filePath, $append ? 'ab' : 'wb')) {
            if (false === flock($fo, LOCK_EX | LOCK_NB)) {
                ocService()->error->show('failed_file_lock');
            }
            if (is_array($content)) {
                foreach ($content as $row) {
                    $result = @fwrite($fo, $row . PHP_EOL);
                    if (!$result) break;
                }
            } else {
                $result = @fwrite($fo, $content);
            }
            flock($fo, LOCK_UN);
            @fclose($fo);
        } else {
            if (ocFileExists($filePath)) {
                if (function_exists('file_put_contents')) {
                    $writeMode = $append ? FILE_APPEND : LOCK_EX;
                    if (is_array($content)) {
                        $content = implode(PHP_EOL, $content);
                    }
                    $result = @file_put_contents($filePath, $content, $writeMode);
                }
            }
        }
    }

    return $result;
}

/**
 * 获取本地文件内容
 * @param string $filePath
 * @param bool $checkPath
 * @return false|string
 * @throws Exception
 */
function ocRead($filePath, $checkPath = true)
{
    if ($checkPath && !preg_match('/^(.+)?\.\w+$/', $filePath)) {
        ocService()->error->show('invalid_path', array($filePath));
    }

    $content = OC_EMPTY;

    if (ocFileExists($filePath)) {
        if (!is_readable($filePath)) {
            ocService()->error->show('no_file_read_perm');
        }
        if ($fo = @fopen($filePath, 'rb')) {
            if (false === flock($fo, LOCK_SH | LOCK_NB)) {
                ocService()->error->show('failed_file_lock');
            }
            @fseek($fo, 0);
            while (!@feof($fo)) {
                $content = $content . @fgets($fo);
            }
            flock($fo, LOCK_UN);
            @fclose($fo);
        } else {
            if (function_exists('file_get_contents')) {
                $content = @file_get_contents($filePath);
            }
        }
    }

    return $content;
}

/**
 * 读取内容
 * @param string $target
 * @return false|int|string
 */
function ocGetContents($target)
{
    if (function_exists('file_get_contents')) {
        return file_get_contents($target);
    }

    return implode('', file($target));
}

/**
 * 获取远程内容
 * @param string $url
 * @param true|string|array $data
 * @param array $headers
 * @return bool|false|string|null
 * @throws Exception
 */
function ocRemote($url, $data = null, array $headers = array())
{
    if (!@ini_get('allow_url_fopen')) return null;

    if (!function_exists('file_get_contents')) return ocCurl($url, $data, $headers);

    if (!$data) return @file_get_contents($url);

    if ($data === true) {
        $data = '';
    } else {
        if (is_array($data)) {
            $data = http_build_query($data, OC_EMPTY, '&');
        }
    }

    $header = "Content-type: application/x-www-form-urlencoded\r\n";
    $header = $header . "Content-length:" . strlen($data) . "\r\n";

    if ($headers) {
        foreach ($headers as $value) {
            $header = $header . $value . "\r\n";
        }
    }

    $context['http'] = array(
        'method' => 'POST',
        'header' => $header,
        'content' => $data
    );

    $result = @file_get_contents($url, false, stream_context_create($context));
    return $result;
}

/**
 * 使用CURL扩展获取远程内容
 * @param string $url
 * @param mixed $data
 * @param array $headers
 * @param bool $showError
 * @param string $method
 * @return bool|string|null
 * @throws Exception
 */
function ocCurl($url, $data = null, array $headers = array(), $showError = false, $method = null)
{
    if (!function_exists('curl_init')) return null;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    if ($headers) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    if ($data) {
        curl_setopt($ch, CURLOPT_POST, 1);
        if ($data === true) {
            $data = '';
        } elseif (is_array($data)) {
            $data = http_build_query($data);
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }

    if ($method) {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    }

    $urlInfo = parse_url($url);

    if (empty($urlInfo['scheme']) || strtolower($urlInfo['scheme']) != 'https') {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }

    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
    $content = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        ocService()->error->writeLog('curl params :' . json_encode(func_get_args(), JSON_UNESCAPED_UNICODE));
        $error = json_encode($error, JSON_UNESCAPED_UNICODE);
        ocService()->error->writeLog('curl request error :' . $error);
        if ($showError) {
            ocService()->error->show('failed_curl_return', array($error));
        }
    }

    return $content;
}

/**
 * 获取二维数组字段值（保留原来的KEY）
 * @param array $array
 * @param string $field
 * @return array
 */
function ocColumn(array $array, $field)
{
    $data = array();
    foreach ($array as $key => $value) {
        $data[$key] = $value[$field];
    }
    return $data;
}

/**
 * 兼容函数
 */
if (!function_exists('array_column')) {
    /**
     * array_column
     * @param $array
     * @param $field
     * @return array
     */
    function array_column(array $array, $field)
    {
        $data = array();
        foreach ($array as $key => $value) {
            $data[] = $value[$field];
        }
        return $data;
    }
}