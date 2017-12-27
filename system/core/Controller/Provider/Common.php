<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   普通控制器提供器类Common
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Controller\Provider;

use Ocara\Request;
use Ocara\Error;
use Ocara\Model\Database as DatabaseModel;

defined('OC_PATH') or exit('Forbidden!');

class Common extends Base
{
    /**
     * @var $_isSubmit 是否POST提交
     * @var $_checkForm 是否检测表单
     */
    private $_isSubmit = null;
    private $_submitMethod = 'post';
    private $_checkForm = true;

    /**
     * 初始化设置
     */
    public function init()
    {
        $this->session->init();
        $this->setAjaxResponseErrorCode(false);
        $this->event('afterCreateForm')->append(array($this, 'afterCreateForm'));
    }

    /**
     * 获取动作执行方式
     */
    public function getDoWay()
    {
        return 'common';
    }

    /**
     * 设置和获取表单提交方式
     * @param null $method
     * @return string
     */
    public function submitMethod($method = null)
    {
        if (func_num_args()) {
            $method = $method == 'get' ? 'get' : 'post';
            $this->_submitMethod = $method;
        }
        return $this->_submitMethod;
    }

    /**
     * 设置和获取是否表单提交
     * @param bool $isSubmit
     * @return bool
     */
    public function isSubmit($isSubmit = null)
    {
        if (func_num_args()) {
            $this->_isSubmit = $isSubmit ? true : false;
        } else {
            return $this->_isSubmit;
        }
    }

    /**
     * 获取表单提交的数据
     * @param null $key
     * @param null $default
     * @return array|null|string
     */
    public function getSubmit($key = null, $default = null)
    {
        $data = $this->_submitMethod == 'post' ? $_POST : $_GET;
        $data = Request::getRequestValue($data, $key, $default);
        return $data;
    }

    /**
     * 打印模板
     * @param bool $file
     * @param array $vars
     */
    public function display($file = false, array $vars = array())
    {
        $content = $this->render($file, $vars);
        $this->view->output(array('content' => $content));
        $this->event('_after')->fire();

        die();
    }

    /**
     * 渲染模板
     * @param bool $file
     * @param array $vars
     * @return mixed
     */
    public function render($file = false, array $vars = array())
    {
        $this->formManager->setToken();

        if (empty($file)) {
            $tpl = $this->view->getTpl();
            if (empty($tpl)) {
                $this->view->setTpl($this->getRoute('action'));
            }
        }

        return $this->view->render($file, $vars, false);
    }

    /**
     * 获取表单并自动验证
     * @param null $name
     * @return $this|Form
     * @throws \Ocara\Exception
     */
    public function form($name = null)
    {
        $model = null;
        if (!$name) {
            $name = $this->getRoute('controller');
            $model = $this->model();
        }

        $form = $this->formManager->getForm($name);
        if (!$form) {
            $form = $this->formManager->create($name);
            if ($model) {
                $form->model($model, false);
            }
            $form->setRoute($this->getRoute());
            $this->event('afterCreateForm')->fire(array($name, $form));
        }

        return $form;
    }

    /**
     * 新建表单后处理
     * @param $name
     * @param $form
     */
    public function afterCreateForm($event, $name, $form)
    {
        $this->view->assign($name, $form);
    }

    /**
     * 开启/关闭/检测表单验证功能
     * @param null $check
     * @return bool
     */
    public function isCheckForm($check = null)
    {
        if ($check === null) {
            return $this->_checkForm;
        }
        $this->_checkForm = $check ? true : false;
    }

    /**
     * 设置AJAX返回格式（回调函数）
     * @param $result
     */
    public function formatAjaxResult($result)
    {
        if ($result['status'] == 'success') {
            $this->response->setStatusCode(Response::STATUS_OK);
            return $result;
        } else {
            if (!$this->response->getOption('statusCode')) {
                $this->response->setStatusCode(Response::STATUS_SERVER_ERROR);
            }
            return $result;
        }
    }

    /**
     * 数据模型字段验证
     * @param array $data
     * @param string|object $model
     * @param Validator|null $validator
     * @return mixed
     */
    public function validate($data, $model, Validator &$validator = null)
    {
        $validator = $validator ? : $this->validator;

        if (is_object($model)) {
            if ($model instanceof DatabaseModel) {
                $class = $model->getClass();
            } else {
                Error::show('fault_model_object');
            }
        } else {
            $class = $model;
        }

        $data = DatabaseModel::mapFields($data, $class);
        $rules = DatabaseModel::getConfig('VALIDATE', null, $class);
        $lang = DatabaseModel::getConfig('LANG', null, $class);
        $result = $validator->setRules($rules)->setLang($lang)->validate($data);

        return $result;
    }

    /**
     * 表单检测
     */
    public function checkForm()
    {
        $this->isSubmit();
        if (!($this->_isSubmit && $this->_checkForm && $this->formManager->getForm()))
            return true;

        $tokenTag  = $this->formToken->getTokenTag();
        $postToken = $this->getSubmit($tokenTag);
        $postForm = $this->formManager->getSubmitForm($postToken);

        if ($postForm) {
            $data = $this->getSubmit();
            $this->formManager->validate($postForm, $data);
        }

        return true;
    }
}