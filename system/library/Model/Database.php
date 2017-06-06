<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   数据库模型类Database
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Model;
use Ocara\Request;
use Ocara\Error;
use Ocara\Call;
use Ocara\Cache;
use Ocara\FormToken;
use Ocara\Database as DefaultDatabase;
use Ocara\DatabaseBase;
use Ocara\ModelBase;
use Ocara\Iterator\Database\ObjectRecords;

defined('OC_PATH') or exit('Forbidden!');

abstract class Database extends ModelBase
{
	
	/**
	 * @var @_primary 主键字段列表
	 * @var $_primaries 主键字段数组
	 */
	protected $_driver = null;

	protected $_primary;
	protected $_table;
	protected $_server;
	protected $_dataType;
	protected $_fields;

	private $_tag;
	private $_master;
	private $_slave;
	private $_database;
	private $_tableName;
	private $_oldTable;
	private $_isOrm;
	private $_selected;
	private $_insertId;

	private $_relations = array();
	private $_changes   = array();
	private $_sql       = array();
	private $_primaries = array();
	private $_joins 	= array();

	private static $_config = array();
	private static $_requirePrimary;

	/**
	 * Model constructor.
	 */
	public function __construct()
	{
		$this->initialize();
	}

	/**
	 * 初始化
	 */
	public function initialize()
	{
		if (self::$_requirePrimary === null) {
			$required = ocConfig('MODEL_REQUIRE_PRIMARY', true);
			self::$_requirePrimary = $required ? true : false;
		}

		if (self::$_requirePrimary && empty($this->_primary)) {
			Error::show('no_primaries');
		}

		$this->_tag = get_called_class();
		$this->_tableName = empty($this->_table) ? ucfirst($this->getClass()) : $this->_table;
		$this->_oldTable = $this->_tableName;

		$this->_join(false, $this->_tag, 'this');
		$this->setDataType($this->_dataType);
		self::loadConfig($this->_tag);

		if ($this->_primary) {
			$this->_primaries = explode(',', $this->_primary);
		}

		if (method_exists($this, '_model')) $this->_model();
		return $this;
	}

	/**
	 * 获取Model标记
	 * @return string
	 */
	public function getTag()
	{
		return $this->_tag;
	}

	/**
	 * 获取表名
	 * @return mixed
	 */
	public function getTableName()
	{
		return $this->_tableName;
	}

	/**
	 * 获取表的全名（包括前缀）
	 * @return mixed
	 */
	public function getTableFullname()
	{
		return $this->connect()->getTableFullname($this->_tableName);
	}

	/**
	 * 执行分库分表
	 * @param array $data
	 */
	public function sharding(array $data = array())
	{
		if (method_exists($this, '_sharding')) {
			$this->_sharding($data);
		}

		return $this;
	}

	/**
	 * 合并查询（去除重复值）
	 * @param object $model
	 * @return $this
	 */
	public function union(\Ocara\ModelBase $model)
	{
		$this->connect()->union($model, false);
		return $this;
	}

	/**
	 * 合并查询
	 * @param object $model
	 * @return $this
	 */
	public function unionAll(\Ocara\ModelBase $model)
	{
		$this->connect()->union($model, true);
		return $this;
	}

	/**
	 * 分库分表 - 修改表名后初始化设置
	 */
	private function _tableInit()
	{
		$tables   = $this->_sql['tables'];
		$oldTable = ocDel($tables, $this->_oldTable);
		$this->_sql['tables'] = array();
		$this->_join(false, $this->_tag, 'this');

		$newTables = $this->_sql['tables'];
		$newTables[$this->_tableName] = array_merge(
			$oldTable,
			$newTables[$this->_tableName]
		);
		$newTables = array_merge(
			$newTables,
			$tables
		);

		$this->_sql['tables'] = $newTables;
		$this->_oldTable = $this->_tableName;
	}

	/**
	 * 加载配置文件
	 */
	public static function loadConfig($class)
	{
		if (!empty(self::$_config[$class])) {
			return true;
		}

		$modelConfig['JOIN'] = array();
		$modelConfig['MAP']  = array();
		$modelConfig['VALIDATE']  = array();
		$modelConfig['LANG']  = array();

		$filePath = self::getConfigPath($class);
		$path = ocPath('conf', "model/{$filePath}");

		if (ocFileExists($path)) {
			include ($path);
			if (isset($CONF) && is_array($CONF)) {
				$modelConfig = array_merge(
					array_diff_key($modelConfig, $CONF),
					array_intersect_key($CONF, $modelConfig)
				);
			}
		}

		ksort($modelConfig);
		self::$_config[$class] = $modelConfig;
	}

	/**
	 * 获取配置数据
	 * @return array
	 * @param string $key
	 * @param string $field
	 */
	public static function getConfig($key = null, $field = null, $class = null)
	{
		$class = $class ? $class : get_called_class();

		if ($num = func_num_args()) {
			if ($key == 'LANG' && empty(self::$_config[$class]['LANG'])) {
				$filePath = self::getConfigPath($class);
				$path = ocPath('lang', 'model' . OC_DIR_SEP . $filePath);
				if (ocFileExists($path)) {
					include ($path);
					if (isset($LANG) && is_array($LANG)) {
						self::$_config[$class]['LANG'] = $LANG;
					}
				}
			}
			$config = ocGet($key, self::$_config[$class]);
			return isset($config[$field]) ? $config[$field] : $config;
		}

		return self::$_config[$class];
	}

	/**
	 * 修改字段配置
	 * @param $key
	 * @param $field
	 * @param $value
	 */
	public static function setConfig($key, $field, $value, $class = null)
	{
		$class = $class ? $class : get_called_class();
		$config = self::getConfig($key);
		$config[$key][$field] = $value;

		self::$_config[$class][$key] = $config;
	}

	/**
	 * 获取配置文件路径
	 */
	public static function getConfigPath($class)
	{
		$path = trim(str_ireplace('Model' . OC_NS_SEP, OC_EMPTY, $class), OC_NS_SEP);
		$filePath = implode(OC_DIR_SEP, array_map('lcfirst', explode(OC_NS_SEP, $path)));

		return $filePath . '.php';
	}

	/**
	 * 获取当前数据库对象
	 * @param bool $slave
	 */
	public function db($slave = false)
	{
		$name = $slave ? '_slave' : '_driver';
		if (is_object($this->$name)) {
			return $this->$name;
		}

		Error::show('null_database');
	}

	/**
	 * 切换数据库
	 * @param string $name
	 */
	public function selectDatabase($name)
	{
		$this->_database = $name;
	}

	/**
	 * 切换数据表
	 * @param $name
	 */
	public function selectTable($name, $primary = null)
	{
		$this->_table = $name;
		$this->_tableName = $name;

		if ($primary) {
			$this->_primary = $primary;
			if ($this->_primary) {
				$this->_primaries = explode(',', $this->_primary);
			}
		}

		$this->_tableInit();
	}

	/**
	 * 新建ORM模型
	 * @param array $data
	 */
	public function data(array $data = array())
	{
		$data = $this->_getPostData($data);

		if ($data) {
			ocDel($data, FormToken::getTokenTag());
			$this->setProperty($data);
		}

		$this->_isOrm = true;
		return $this;
	}

	/**
	 * 从数据库获取数据表的字段
	 */
	public function loadFields()
	{
		$this->_fields = $this->connect()->getFields($this->_tableName);
		return $this;
	}

	/**
	 * 获取字段
	 */
	public function getFields()
	{
		if (empty($this->_fields)) {
			$this->loadFields();
		}

		return $this->_fields;
	}

	/**
	 * 获取ORM数据
	 */
	public function getData()
	{
		return $this->getProperty();
	}

	/**
	 * 字段别名映射
	 * @param array $data
	 */
	public function map(array $data)
	{
		$result = array();

		foreach ($data as $key => $value) {
			$key = strtr($key, self::$_config[$this->_tag]['MAP']);
			if ($this->_fields && !isset($this->_fields[$key])
				|| $key == FormToken::getTokenTag()
			) {
				continue;
			}
			$result[$key] = $value;
		}

		if ($this->_fields) {
			if (!is_object($this->_driver)) {
				$this->_driver = $this->connect();
			}
			$result = $this->_driver->formatFieldValues($this->_fields, $result);
		}

		return $result;
	}

	/**
	 * 设置结果集返回数据类型
	 * @param string $dataType
	 */
	public function setDataType($dataType)
	{
		$this->_dataType = strtolower($dataType);
		return $this;
	}

	/**
	 * 清理SQL
	 */
	public function clearSql()
	{
		$this->_sql = array();
		$this->_join(false, $this->_tableName, 'a');
		$this->_driver = $this->_master;
		return $this;
	}

	/**
	 * 清理ORM数据
	 */
	public function clearData()
	{
		$this->_isOrm = false;
		$this->clearProperty();
		return $this;
	}

	/**
	 * 清理Model的SQL和ORM数据
	 */
	public function clear()
	{
		$this->clearSql();
		$this->clearData();
		return $this;
	}

	/**
	 * 缓存查询的数据
	 * @param string $server
	 * @param bool $required
	 */
	public function cache($server = null, $required = false)
	{
		$server = $server ? $server : 'default';
		$this->_sql['cache'] = array($server, $required);
		return $this;
	}

	/**
	 * 规定使用主库查询
	 */
	public function master()
	{
		$this->_sql['option']['master'] = true;
		return $this;
	}

	/**
	 * 是否是ORM模型
	 */
	public function isOrm()
	{
		return $this->_isOrm;
	}

	/**
	 * 保存记录
	 * @param array $data
	 * @param string|array $condition
	 * @param bool $debug
	 */
	private function _save($data, $condition, $debug = false)
	{
		if ($condition) {
			call_user_func_array('ocDel', array(&$data, $this->_primaries));
			if (method_exists($this, '_beforeUpdate')) {
				$this->_beforeUpdate();
			}
		} else {
			if (method_exists($this, '_beforeCreate')) {
				$this->_beforeCreate();
			}
		}

		$data = $this->map(array_merge($data, $this->getProperty()));
		if (empty($data)) {
			Error::show('fault_save_data');
		}

		if ($this->_changes) {
			$this->db()->transBegin();
		}

		if ($condition) {
			call_user_func_array('ocDel', array(&$data, $this->_primaries));
			$result = $this->_driver->update($this->_tableName, $data, $condition, $debug);
			if (!$debug){
				$this->_relateSave();
				if(method_exists($this, '_afterUpdate')) {
					$this->_afterUpdate();
				}
			}
		} else {
			$result = $this->_driver->insert($this->_tableName, $data, $debug);
			if (!$debug) {
				$this->_insertId = $this->_driver->getInsertId();
				$this->_selectInsertRow($data);
				$this->_relateSave(true);
				if (method_exists($this, '_afterCreate')) {
					$this->_afterCreate();
				}
				$result = $this->_insertId;
			}
		}

		if ($debug === DatabaseBase::DEBUG_RETURN) return $result;

		$this->clearProperty();
		return $result;
	}

	/**
	 * 选择当前插入成功的记录
	 * @param $data
	 */
	public function _selectInsertRow($data)
	{
		$primaries = array();

		foreach ($this->_primaries as $field) {
			if (isset($data[$field])) {
				$primaries[] = $data[$field];
			} else {
				$primaries[] = $this->_insertId;
			}
		}

		$where = $this->_getPrimaryCondition($primaries);
		$this->selectFirst($where);
	}

	/**
	 * 获取最后插入的记录ID
	 * @return mixed
	 */
	public function getInsertId()
	{
		return $this->_insertId;
	}

	/**
	 * 预处理
	 * @param bool $prepare
	 */
	public function prepare($prepare = true)
	{
		$this->_driver->is_prepare($prepare);
	}

	/**
	 * 绑定参数
	 * @param string $type
	 * @param mixed $args
	 */
	public function bind($type, &$args)
	{
		call_user_func_array(array($this->_driver, 'bind'), func_get_args());
	}

	/**
	 * 保存数据（ORM模型）
	 * @param $debug
	 * @return mixed
	 */
	public function save($debug = false)
	{
		$condition = $this->_getCondition();
		$data = array();

		if ($condition) {
			$result = $this->_update('update', $debug, $condition, $data);
		} else {
			$result = $this->_save($data, false, $debug);
		}

		return $result;
	}

	/**
	 * 新建记录
	 * @param array $data
	 * @param bool $debug
	 */
	public function create(array $data = array(), $debug = false)
	{
		$this->connect();
		$data = $this->map($this->_getPostData($data));
		$result = $this->_save($data, false, $debug);
		return $result;
	}

	/**
	 * 获取数据
	 * @param $data
	 * @return array|null|string
	 */
	protected function _getPostData($data)
	{
		if (empty($data)) {
			$data = Request::getPost();
			if ($data) {
				$this->loadFields();
			}
		}

		return $data;
	}

	/**
	 * 更新记录
	 * @param string|array $condition
	 * @param bool $debug
	 */
	public function update(array $data, $debug = false)
	{
		$condition = $this->_getCondition();
		$result = $this->_update('update', $debug, $condition, $data);
		return $result;
	}

	/**
	 * 删除记录
	 * @param string|array $condition
	 * @param bool $debug
	 */
	public function delete($debug = false)
	{
		$condition = $this->_getCondition();
		$result = $this->_update('delete', $debug, $condition);
		return $result;
	}

	/**
	 * 获取操作条件
	 * @return array|null
	 */
	private function _getCondition()
	{
		$this->connect();
		$condition = $this->_genSql(false);

		return $condition;
	}

	/**
	 * 获取数据更新或删除的条件
	 * @param string $type
	 * @param bool $debug
	 * @param array $data
	 */
	private function _update($type, $debug, $condition, array $data = array())
	{
		if (empty($condition)) Error::show('need_condition');

		if ($type == 'update') {
			$result = $this->_save($data, $condition, $debug);
		} else {
			if (!$debug && method_exists($this, '_beforeDelete')) {
				$this->_beforeDelete();
			}
			$result = $this->_driver->delete($this->_tableName, $condition, $debug);
			if (!$debug && !$this->_driver->errorExists() && method_exists($this, '_afterDelete')) {
				$this->_afterDelete();
			}
		}

		if ($debug === DatabaseBase::DEBUG_RETURN) return $result;

		return $result;
	}

	/**
	 * 用SQL语句获取多条记录
	 * @param string $sql
	 * @param bool $debug
	 */
	public function query($sql, $debug = false)
	{
		return $sql ? $this->connect(false)->query($sql, $debug) : false;
	}

	/**
	 * 用SQL语句获取一条记录
	 * @param string $sql
	 * @param bool $debug
	 */
	public function queryRow($sql, $debug = false)
	{
		return $sql ? $this->connect(false)->queryRow($sql, $debug) : false;
	}

	/**
	 * 获取SQL
	 * @return array
	 */
	public function getSql()
	{
		return $this->_sql;
	}

	/**
	 * 设置SQL
	 * @param $sql
	 */
	public function setSql($sql)
	{
		$this->_sql = $sql;
	}

	/**
	 * 按条件选择首行
	 * @param string|numric|array $condition
	 * @param string|array $option
	 * @param bool $debug
	 */
	public function selectFirst($condition = false, $option = null, $debug = false)
	{
		$this->_selected = true;
		$data = $this->findFirst($condition, $option, $debug);

		if ($debug === DatabaseBase::DEBUG_RETURN) return $data;
		if ($data) {
			$this->data($data);
			return $this;
		}

		return null;
	}

	/**
	 * 按主键选择一行记录
	 * @param string|numric|array $primaryValues
	 * @param string|array $option
	 * @param bool $debug
	 */
	public static function select($primaryValues, $option = null, $debug = false)
	{
		$model = new static();
		$condition = $model->_getPrimaryCondition($primaryValues);
		$model->selectFirst($condition, $option, $debug);

		return $model;
	}

	/**
	 * 选择多条记录
	 * @param $condition
	 * @param null $options
	 * @param bool $debug
	 * @return array|ObjectRecords
	 */
	public static function selectAll($condition, $options = null, $debug = false)
	{
		$records = new ObjectRecords(get_called_class(), array($condition), $options, $debug);

		$times = isset($options['times']) ? $options['times'] : 0;
		$start = isset($options['start']) ? $options['start'] : 0;
		$rows = isset($options['rows']) ? $options['rows'] : 1;

		$records->setLimit($times, $start, $rows);

		return $records;
	}

	/**
	 * 获取主键条件
	 * @param $condition
	 * @return array
	 * @throws \Ocara\Exception
	 */
	private function _getPrimaryCondition($condition)
	{
		if (empty($this->_primaries)) {
			Error::show('no_primary');
		}

		if (ocEmpty($condition)) {
			Error::show('need_primary_value');
		}

		if (is_string($condition) || is_numeric($condition)) {
			$values = explode(',', trim($condition));
		} elseif (is_array($condition)) {
			$values = $condition;
		} else {
			Error::show('fault_primary_value_format');
		}

		if (count($this->_primaries) == count($values)) {
			$where = $this->map(array_combine($this->_primaries, $values));
		} else {
			Error::show('fault_primary_num');
		}

		return $where;
	}

	/**
	 * 查询多条记录
	 * @param string|array $condition
	 * @param string|array $option
	 * @param bool $debug
	 */
	public function find($condition = false, $option = false, $debug = false)
	{
		return $this->_find($condition, $option, $debug, false);
	}

	/**
	 * 查询一条记录
	 * @param string|array $condition
	 * @param string|array $option
	 * @param bool $debug
	 */
	public function findFirst($condition = false, $option = false, $debug = false)
	{
		return $this->_find($condition, $option, $debug, true);
	}

	/**
	 * 获取某个字段值
	 * @param string $field
	 * @param string|array $condition
	 * @param bool $debug
	 */
	public function findValue($field, $condition = false, $debug = false)
	{
		$row = $this->findFirst($condition, $field, $debug);

		if ($debug === DatabaseBase::DEBUG_RETURN) return $row;

		if (is_object($row)) {
			return property_exists($row, $field) ? $row->$field : null;
		}

		$row = (array)$row;
		return isset($row[$field]) ? $row[$field] : OC_EMPTY;
	}

	/**
	 * 查询总数
	 * @param boolean $debug
	 */
	public function getTotal($debug = false)
	{
		if (ocGet('option.group', $this->_sql)) {
			$result = $this->_find(false, false, $debug, false, true);
			return $debug === DatabaseBase::DEBUG_RETURN ? $result : count($result);
		} else {
			$result = $this->_find(false, false, $debug, true, true);
			if ($debug === DatabaseBase::DEBUG_RETURN) return $result;
			return $result ? $result['total'] : 0;
		}
	}

	/**
	 * 查询数据
	 * @param string|array $condition
	 * @param string|array $option
	 * @param bool $debug
	 * @param bool $queryRow
	 * @param bool $count
	 */
	private function _find($condition, $option, $debug, $queryRow, $count = false)
	{
		if ($condition) $this->where($condition);
		if ($queryRow) {
			if (!empty($this->_sql['option']['limit'])) {
				$this->_sql['option']['limit'][1] = 1;
			} else {
				$this->limit(1);
			}
		}

		if ($option) {
			if (ocScalar($option)) {
				$option = array('fields' => $option);
			}
			foreach ($option as $key => $value) {
				$this->_sql['option'][$key] = $value;
			}
		}

		$this->connect(false);
		$fields = $count ? $this->connect(false)->getCountSql('1', 'total') : false;
		$sql = $this->_genSql(true, $fields, $count);

		$cacheInfo = null;
		if (isset($this->_sql['cache']) && is_array($this->_sql['cache'])) {
			$cacheInfo = $this->_sql['cache'];
		}

		list($cacheConnect, $cacheRequired) = $cacheInfo;
		$ifCache = empty($debug) && $cacheConnect;

		if ($ifCache) {
			$sqlEncode = md5($sql);
			$cacheObj  = Cache::connect($cacheConnect, $cacheRequired);
			$cacheData = $this->_getCacheData($sql, $sqlEncode, $cacheObj, $cacheRequired);
			if ($cacheData) return $cacheData;
		}

		if ($queryRow) {
			$result = $this->_driver->queryRow($sql, $debug, $count);
		} else {
			$result = $this->_driver->query($sql, $debug, true, true, false, $count);
		}

		if ($debug === DatabaseBase::DEBUG_RETURN) {
			return $result;
		}

		if (!$count && ocGet('option.page', $this->_sql)) {
			$result = array('total' => $this->getTotal($debug), 'data'	=> $result);
		}

		if ($ifCache && is_object($cacheObj)) {
			$this->_saveCacheData($cacheObj, $sql, $sqlEncode, $cacheRequired, $result);
		}

		$this->_selected = false;
		return $result;
	}

	/**
	 * 连接数据库
	 * @param bool $master
	 */
	public function connect($master = true)
	{
		$this->_driver = null;

		if (!($master || ocGet('option.master', $this->_sql))) {
			if (!is_object($this->_slave)) {
				$this->_slave = DefaultDatabase::factory($this->_server, false, false);
			}
			$this->_driver = $this->_slave;
		}

		if (!is_object($this->_driver)) {
			if (!is_object($this->_master)) {
				$this->_master = DefaultDatabase::factory($this->_server);
			}
			$this->_driver = $this->_master;
		}

		if ($this->_database) {
			$this->_driver->selectDatabase($this->_database);
		}

		$this->_driver->setDataType($this->_dataType);

		return $this->_driver;
	}

	/**
	 * 获取缓存数据
	 * @param object $cacheObj
	 * @param string $sql
	 * @param string $sqlEncode
	 * @param bool $cacheRequired
	 */
	public function _getCacheData($cacheObj, $sql, $sqlEncode, $cacheRequired)
	{
		if (is_object($cacheObj)) {
			if ($callback = ocConfig('CALLBACK.model.query.get_cache_data', null)) {
				$params = array($cacheObj, $sql, $cacheRequired);
				if ($result = Call::run($callback, $params)) {
					return $result;
				}
			} else {
				if ($cacheData = $cacheObj->getVar($sqlEncode)) {
					return json_decode($cacheData);
				}
			}
		}

		return null;
	}

	/**
	 * 保存缓存数据
	 * @param object $cacheObj
	 * @param string $sql
	 * @param string $sqlEncode
	 * @param bool $cacheRequired
	 * @param array $result
	 */
	public function _saveCacheData($cacheObj, $sql, $sqlEncode, $cacheRequired, $result)
	{
		if ($callback = ocConfig('CALLBACK.model.query.save_cache_data', null)) {
			$params = array($cacheObj, $sql, $result, $cacheRequired);
			Call::run($callback, $params);
		} else {
			$cacheObj->setVar($sqlEncode, json_encode($result));
		}
	}

	/**
	 * 左联接
	 * @param string $class
	 * @param string $alias
	 * @param string $on
	 */
	public function leftJoin($class, $alias = null, $on = null)
	{
		return $this->_join('left', $class, $alias, $on);
	}

	/**
	 * 右联接
	 * @param string $class
	 * @param string $alias
	 * @param string $on
	 */
	public function rightJoin($class, $alias = null, $on = null)
	{
		return $this->_join('right', $class, $alias, $on);
	}

	/**
	 * 全联接
	 * @param string $class
	 * @param string $alias
	 * @param string $on
	 */
	public function innerJoin($class, $alias = null, $on = null)
	{
		return $this->_join('inner', $class, $alias, $on);
	}

	/**
	 * 解析on参数
	 * @param string $alias
	 * @param string $on
	 */
	public function parseOn($alias, $on)
	{
		if (is_array($on)) {
			$on = $this->_driver->parseCondition($on, 'AND', '=', $alias);
		}

		return $on;
	}

	/**
	 * 解析fields参数
	 * @param string $alias
	 * @param string $fields
	 */
	public function parseField($alias, $fields)
	{
		$_field = explode(',', $fields);

		foreach ($_field as $key => $value) {
			$value = explode('.', ltrim($value));
			$field = trim($value[count($value) - 1]);
			$_field[$key] = $this->_driver->getFieldNameSql($field, $alias);
		}

		return implode(',', $_field);
	}

	/**
	 * 附加字段
	 * @param string|array $fields
	 * @param string $table
	 */
	public function fields($fields, $table = false)
	{
		if ($fields) {
			if ($table) $table = $this->_getTable($table);
			$fields = array($table, $fields);
			$this->_sql['option']['fields'][] = $fields;
		}

		return $this;
	}

	/**
	 * 附加联接关系
	 * @param string $on
	 * @param string $table
	 */
	private function _addOn($on, $alias = false)
	{
		$this->_sql['tables'][$alias]['on'] = $on;
		return $this;
	}

	/**
	 * 生成Between条件
	 * @param string $field
	 * @param string|integer $value1
	 * @param string|integer $value2
	 * @param string $table
	 */
	public function between($field, $value1, $value2, $table = false)
	{
		$where = array($table, 'between', array($field, $value1, $value2));
		$this->_sql['option']['where'][] = $where;

		return $this;
	}

	/**
	 * 添加条件
	 * @param string|array $where
	 * @param string $table
	 */
	public function where($where, $alias = false)
	{
		if (!ocEmpty($where)) {
			$where = array($alias, 'where', $where);
			$this->_sql['option']['where'][] = $where;
		}

		return $this;
	}

	/**
	 * 生成复杂条件
	 * @param string $sign
	 * @param array $where
	 * @param string $alias
	 */
	public function cWhere($sign, $where, $alias = false)
	{
		if (is_string($where)) {
			//$link = $where [AND|OR] , $where = $alias [field=>value]
			$where = array($where => $alias);
			$alias = ($last = func_get_arg(3)) ? $last : false;
		}

		if (!ocEmpty($where)) {
			$where = array($alias, 'cWhere', array($sign, $where));
			$this->_sql['option']['where'][] = $where;
		}

		return $this;
	}

	/**
	 * 更多条件
	 * @param string $where
	 * @param string $link
	 */
	public function mWhere($where, $link = false)
	{
		$link = $link ? $link : 'AND';
		$this->_sql['option']['mWhere'][] = compact('where', 'link');
		return $this;
	}

	/**
	 * 尾部更多SQL语句
	 * @param string $sql
	 */
	public function more($sql)
	{
		$sql = (array)$sql;
		foreach ($sql as $value) {
			$this->_sql['option']['more'][] = $value;
		}
		return $this;
	}

	/**
	 * 分组
	 * @param string $groupBy
	 */
	public function groupBy($groupBy)
	{
		if ($groupBy) {
			$this->_sql['option']['group'] = $groupBy;
		}
		return $this;
	}

	/**
	 * 分组条件
	 * @param string $having
	 */
	public function having($having, $table = false)
	{
		if (!ocEmpty($having)) {
			$having = array($table, 'where', $having);
			$this->_sql['option']['having'][] = $having;
		}

		return $this;
	}

	/**
	 * 附加排序
	 * @param string $orderBy
	 */
	public function orderBy($orderBy)
	{
		if ($orderBy) {
			$this->_sql['option']['order'] = $orderBy;
		}
		return $this;
	}

	/**
	 * 附加Limit
	 * @param string $limit
	 */
	public function limit($offset, $rows = 1)
	{
		if (func_num_args() < 2) {
			$rows = $offset;
			$offset = 0;
		}

		$this->_sql['option']['limit'] = array($offset, $rows);
		return $this;
	}

	/**
	 * 分页处理
	 * @param array $limitInfo
	 */
	public function page(array $limitInfo)
	{
		$this->_sql['option']['page'] = true;
		list($offset, $rows) = $limitInfo;
		return $this->limit($offset, $rows);
	}

	/**
	 * 设置字段别名转换映射
	 * @param $tables
	 */
	private function _getAliasFields($tables)
	{
		$unJoined = count($tables) <= 1;
		$transforms = array();

		if ($unJoined) {
			$map = self::getConfig('MAP');
			if ($map) {
				$transforms['this'] = $map;
			}
		} else {
			$transforms = array();
			foreach ($tables as $alias => $row) {
				if ($alias == 'this') {
					if ($map = self::getConfig('MAP')) {
						$transforms['this'] = $map;
					}
				} elseif (isset($this->_joins[$alias])) {
					$config = $this->_getRelateConfig($alias);
					if ($map = self::getConfig('MAP', null, $config['class'])) {
						$transforms[$alias] = $map;
					}
				}
			}
		}

		return $transforms;
	}

	/**
	 * 生成Sql
	 * @param boolean $select
	 * @param string $fields
	 * @param bool $count
	 */
	private function _genSql($select, $fields = false, $count = false)
	{
		$this->_driver->clearParams();

		$where  = array();
		$option = ocGet('option', $this->_sql, array());
		$tables = ocGet('tables', $this->_sql, array());
		$from   = $this->_getFromSql($select, $tables);

		if (empty($fields)) {
			if (isset($option['fields']) && $option['fields']) {
				$fields = $this->_getFieldsSql($option['fields']);
			} else {
				$fields = $this->_driver->getDefaultFieldsSql();
			}
		}

		if (isset($option['where']) && $option['where']) {
			$option['where'] = $this->_getWhereSql($option['where']);
			$where[] = array('where' => $option['where'], 'link' => 'AND');
		}

		if (isset($option['mWhere']) && $option['mWhere']) {
			foreach ($option['mWhere'] as $row) {
				$row['where'] = $this->_driver->parseCondition($row['where']);
				$where[] = $row;
			}
		}

		$option['where'] = $this->_driver->getWhereSql($where);
		if (isset($option['limit'])) {
			if ($count) {
				ocDel($option, 'limit');
			} else {
				$option['limit'] = $this->_driver->getLimitSql($option['limit']);
			}
		}

		if (isset($option['having'])) {
			$option['having'] = $this->_getWhereSql($option['having']);
		}

		if ($select) {
			$aliasFields = $this->_getAliasFields($tables);
			$fields = $this->_driver->getAliasFieldsSql($fields, $aliasFields);
			return $this->_driver->getSelectSql($fields, $from, $option);
		} else {
			return $option['where'];
		}
	}

	/**
	 * 获取条件SQL语句
	 * @param array $data
	 * @return array
	 */
	private function _getWhereSql(array $data)
	{
		$where = array();

		foreach ($data as $key => $value) {
			list($alias, $whereType, $whereData) = $value;
			if ($whereType == 'where') {
				if (is_array($whereData)) {
					$whereData = $this->map($whereData);
				}
				$where[] = $this->_driver->parseCondition(
					$whereData,  'AND', '=', $alias
				);
			} elseif ($whereType == 'between') {
				$where[] = call_user_func_array(array($this->_driver, 'getBetweenSql'), $whereData);
			} else {
				$where[] = $this->_getComplexWhere($whereData, $alias);
			}
		}

		$where = $this->_driver->linkWhere($where);
		$where = $this->_driver->wrapWhere($where);

		return $where;
	}

	/**
	 * 获取字段列表
	 * @param array $fields
	 * @param string $alias
	 */
	private function _getFieldsSql($data, $alias = false)
	{
		if (is_string($data)) {
			return $data;
		}

		$fields = array();
		foreach ($data as $key => $value) {
			list($table, $fieldData) = $value;
			$alias = false;
			if ($table) {
				$alias = ocGet(array('tables', $table, 'alias'), $this->_sql);
				if (is_string($fieldData)) {
					$fieldData = array_map('trim', (explode(',', $fieldData)));
				}
			}
			if (is_array($fieldData)) {
				$fields[] = $this->_driver->getFieldsSql($fieldData, $alias);
			} else {
				$fields[] = $fieldData;
			}
		}

		return $this->_driver->getMultiFieldsSql($fields);
	}

	/**
	 * 生成数据表SQL
	 * @param boolean $select
	 */
	private function _getFromSql($select, $tables)
	{
		$unJoined = count($tables) <= 1;
		$from = null;
		
		foreach ($tables as $alias => $param) {
			list($type, $fullname, $on) = array_fill(0, 3, null);
			extract($param);

			if (empty($fullname)) continue;
			if ($unJoined) $alias = false;

			$on = $this->parseOn($alias, $on);
			$fullname = $this->_driver->getTableFullname($fullname);

			if ($select) {
				$from = $from . $this->_driver->getJoinSql($type, $fullname, $alias, $on);
			}
		}

		return $from;
	}

	/**
	 * 详细的复杂条件
	 * @param array $where
	 * @param string $alias
	 */
	private function _getComplexWhereDetail($where, $alias)
	{
		$data = array_shift($where);
		$cond = null;

		if (is_string($data) && $where) {
			$data = array_map('trim', explode(OC_DIR_SEP, $data));
			$count = count($data);
			if ($count == 0) {
				Error::show('fault_cond_sign');
			} elseif ($count == 1) {
				$sign = $data[0];
				$link = 'AND';
			} else {
				list($link, $sign) = $data;
			}

			$cond = is_array($where) ? $this->map($where) : $where;
			$cond = $this->_driver->parseCondition($cond, $link, $sign, $alias);
		}

		return $this->_driver->wrapWhere($cond);
	}
	
	/**
	 * 复杂条件
	 * @param array $data
	 * @param string $alias
	 */
	private function _getComplexWhere($data, $alias)
	{
		$cond = null;

		if ($data[1]) {
			if (ocAssoc($data[1])) {
				array_unshift($data[1], $data[0]);
				$cond = $this->_getComplexWhereDetail($data[1], $alias);
			} else {
				$cond = array();
				foreach ($data[1] as $val) {
					$cond[] = $this->_getComplexWhereDetail($val, $alias);
				}
				$cond = $this->_driver->linkWhere($cond, $data[0]);
				$cond = $this->_driver->wrapWhere($cond);
			}
		}

		return $cond;
	}

	/**
	 * 获取表名
	 * @return mixed
	 */
	protected function getTable()
	{
		return $this->_table;
	}

	/**
	 * 获取全表名
	 * @param string $table
	 */
	protected function _getTable($table)
	{
		return empty($table) ? $this->_tableName : $table;
	}
	
	/**
	 * 配置联接
	 * @param string $alias
	 * @param string $class
	 */
	private function _configJoin($alias, $class)
	{
		$on = false;

		if (!empty(self::$_config[$this->_tag]['JOIN'][$class])) {
			$config = $this->_getRelateConfig($class);
			$foreignField = $this->_driver->getFieldNameSql($config['foreignKey'], $class);
			$primaryField = $this->_driver->getFieldNameSql($config['primaryKey'], 'this');
			$where = array($foreignField => $primaryField);
			$condition[] = $this->_driver->parseCondition($where, 'AND', null, $alias, false);
			if (is_array($config['condition'])) {
				foreach ($config['condition'] as $key => $value) {
					if (is_array($value)) {
						list($sign, $value) = $value;
						$key = $this->_driver->getFieldNameSql($key, $class);
						$value = array($key => $value);
						$condition[] = $this->_driver->parseCondition(
							$value, 'AND', $sign, $alias
						);
					}
				}
			}
			$on = $this->_driver->linkWhere($condition, 'AND');;
		}

		$this->_addOn($on, $alias);
	}
	
	/**
	 * 参数联接
	 * @param string $alias
	 * @param string $on
	 */
	private function _sqlJoin($alias, $on)
	{
		if ($on) $this->_addOn($on, $alias);
	}
	
	/**
	 * 联接查询
	 * @param string $type
	 * @param string $model
	 * @param string $alias
	 * @param string $on
	 */
	private function _join($type, $class, $alias, $on = false)
	{
		$isConfig = false;
		$this->connect();

		if ($type == false) {
			$alias = 'this';
			$fullname = $this->getTableName();
		} else {
			$config = $this->_getRelateConfig($class);
			if (!empty(self::$_config[$this->_tag]['JOIN'][$class])) {
				$model = $config['class']::build();
				$alias = $class;
				$isConfig = true;
			} else {
				$class = ltrim($class, OC_NS_SEP);
				$model = $class::build();
			}
			$table = $model->getTableName();
			$alias = $alias ? $alias : $table;
			$this->_joins[$alias] = $model;
			$fullname = $model->getTableName();
		}

		$this->_sql['tables'][$alias]['type'] = $type;
		$this->_sql['tables'][$alias]['fullname'] = $fullname;

		if ($isConfig) {
			$this->_configJoin($alias, $class);
		} else {
			$this->_sqlJoin($alias, $on);
		}

		return $this;
	}

	/**
	 * 获取关联模型
	 * @param string $key
	 */
	public function &__get($key)
	{
		if (isset(self::$_config[$this->_tag]['JOIN'][$key])) {
			if (!isset($this->_relations[$key])) {
				$this->_relations[$key] = $this->_relateFind($key);
			}
			return $this->_relations[$key];
		} else {
			return parent::__get($key);
		}
	}

	/**
	 * 获取关联模型
	 * @param string $key
	 */
	public function __set($key, $value)
	{
		if (isset(self::$_config[$this->_tag]['JOIN'][$key])) {
			$this->_relations[$key] = $value;
			$this->_changes[$key] = &$this->_relations[$key];
		} else {
			return parent::__set($key, $value);
		}
	}

	/**
	 * 关联模型查询
	 * @param string $alias
	 */
	private function _relateFind($alias)
	{
		$config = $this->_getRelateConfig($alias);
		$result = null;

		if ($config) {
			$where = array($config['foreignKey'] => $this->$config['primaryKey']);
			if (in_array($config['joinType'], array('one','manyOne'))) {
				$result = $config['class']::build()
					->where($where)
					->where($config['condition'])
					->selectFirst();
			} elseif (in_array($config['joinType'], array('oneMany','many'))) {
				$result = new ObjectRecords($config['class'], array($where, $config['condition']));
				$result->setLimit(0, 0, 1);
			}
		}

		return $result;
	}

	/**
	 * 关联模型数据保存
	 * @param bool $isCreate
	 */
	private function _relateSave($isCreate = false)
	{
		$changes = array();

		foreach ($this->_changes as $key => $object) {
			$config = $this->_getRelateConfig($key);
			if ($config && $this->hasProperty($config['primaryKey'])) {
				$data = array();
				if (in_array($config['joinType'], array('one','manyOne')) && is_object($object)) {
					$data = array($object);
				} elseif (in_array($config['joinType'], array('oneMany','many'))) {
					if (is_object($object)) {
						$data = array($object);
					} elseif (is_array($object)) {
						$data = $object;
					}
				}
				foreach ($data as &$model) {
					if (is_object($model) && $model instanceof \Ocara\ModelBase) {
						$where = array($config['foreignKey'] => $this->$config['primaryKey']);
						$model->$config['foreignKey'] = $this->$config['primaryKey'];
						if (!$isCreate) {
							$model->where($where)->where($config['condition']);
						}
						$model->save();
					}
				}
				$changes[$key] = $data;
			}
		}

		foreach ($changes as $key => $value) {
			foreach ($data as $model) {
				if (is_object($model) && $model instanceof \Ocara\ModelBase) {
					$model->db()->transCommit();
				}
			}
		}

		$this->db()->transCommit();
		return true;
	}

	/**
	 * 获取关联配置
	 * @param string $key
	 */
	private function _getRelateConfig($key)
	{
		$config = self::$_config[$this->_tag]['JOIN'][$key];

		if (count($config) < 3) {
			Error::show('fault_relate_config');
		}

		list($joinType, $primaryKey, $relation) = $config;
		$condition = isset($config[3]) ? $config[3]: null;

		if (is_array($relation)) {
			list($class, $foreignKey) = $relation;
		} else {
			$class = $relation;
			$foreignKey = $primaryKey;
		}

		$class = ltrim($class, OC_NS_SEP);
		$config = compact(
			'joinType', 'primaryKey', 'condition',
			'class', 'foreignKey'
		);

		return $config;
	}
}
