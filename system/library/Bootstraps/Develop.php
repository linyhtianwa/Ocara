<?php
/**
 * Created by PhpStorm.
 * User: BORUI-DIY
 * Date: 2017/6/25 0025
 * Time: 下午 1:50
 */
namespace Ocara\Bootstraps;

use Ocara\Interfaces\Bootstrap as BootstrapInterface;
use Ocara\Core\BootstrapBase;
use Ocara\Core\Develop as DevelopBootstrap;
use Ocara\Dispatchers\Develop as DevelopDispatcher;

class Develop extends BootstrapBase implements BootstrapInterface
{
    public static $config;

    /**
     * 运行访问控制器
     * @param array|string $route
     * @return mixed
     * @throws \Ocara\Exceptions\Exception
     */
    public function start($route)
    {
        if (OC_SYS_MODEL != 'develop') {
            ocService()->error->show('unallowed_develop');
        }

        session_start();

        $this->event(self::EVENT_BEFORE_RUN)
             ->fire(array($route));

        define('OC_DEV_DIR', OC_SYS . 'resource/develop/');
        $developConfig = ocImport(OC_DEV_DIR . 'config.php', true, false);
        self::$config = $developConfig;

        $dispatcher = new DevelopDispatcher();
        ocService()->setService('dispatcher', $dispatcher);
        $dispatcher->dispatch();

        $response = ocService()->response;
        $response->sendHeaders();

        return $response->send();
    }

    /**
     * 输出模板
     * @param $filename
     * @param $tpl
     * @param array $vars
     * @throws \Ocara\Exceptions\Exception
     */
    public function tpl($filename, $tpl, array $vars = array())
    {
        (is_array($vars) && $vars) && extract($vars);

        if($tpl == 'global'){
            $path = OC_DEV_DIR.'global.php';
        } else {
            $path = OC_DEV_DIR . ($filename ? 'tpl/' . $filename : 'index') . '.php';
        }

        if (!ocFileExists($path)) {
            self::error($filename . '模板文件不存在.');
        }

        if($tpl == 'global'){
            $contentFile = $filename;
            include($path);
        } else {
            ocImport(OC_DEV_DIR . 'header.php');
            include($path);
            ocImport(OC_DEV_DIR . 'footer.php');
        }

        exit();
    }

    /**
     * 打印错误
     * @param $msg
     * @param string $tpl
     * @throws \Ocara\Exceptions\Exception
     */
    public function error($msg, $tpl = 'module')
    {
        self::tpl('error', $tpl, get_defined_vars());
    }

    /**
     * 错误返回
     * @param $msg
     * @return string
     * @throws \Ocara\Exceptions\Exception
     */
    public function back($msg)
    {
        $back = ocService()->html->createElement('a', array(
            'href' => 'javascript:;',
            'onclick' => 'setTimeout(function(){history.back();},0)',
        ), '返回');

        return  $msg . $back;
    }

    /**
     * 检测登录
     */
    public function checkLogin()
    {
        return !empty($_SESSION['OC_DEV_LOGIN']);
    }
}