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

class Common extends BootstrapBase implements BootstrapInterface
{
    public function init()
    {
        parent::init(); // TODO: Change the autogenerated stub

        if (!ocFileExists(OC_WEB_ROOT . '.htaccess')) {
            self::createHtaccess();
        }
    }

    /**
     * 运行访问控制器
     * @param array|string $route
     * @param array $params
     * @param null $moduleNamespace
     * @return mixed
     */
    public function start($route, $params = array(), $moduleNamespace = null)
    {
        $service = ocService();
        $moduleNamespace = $moduleNamespace ? : OC_MODULE_NAMESPACE;
        $this->fire(self::EVENT_BEFORE_DISPATCH);

        $service->dispatcher->dispatch($route, $moduleNamespace, $params);
        $service->response->sendHeaders();

        return $service->response->send();
    }

    /**
     * 生成伪静态文件
     * @param string $moreContent
     * @throws \Ocara\Exceptions\Exception
     */
    public static function createHtaccess($moreContent = OC_EMPTY)
    {
        $file = OC_WEB_ROOT . '.htaccess';
        $htaccess = ocImport(OC_SYS . 'data/rewrite/apache.php');

        if (empty($htaccess)) {
            ocService()->error->show('no_rewrite_default_file');
        }

        if (is_writeable(OC_WEB_ROOT)) {
            $htaccess = sprintf($htaccess, $moreContent);
            ocWrite($file, $htaccess);
        } else {
            ocService()->error->show('not_writeable_htaccess');
        }
    }
}