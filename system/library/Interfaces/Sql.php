<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   数据库SQL生成类接口Sql
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Interfaces;

defined('OC_PATH') or exit('Forbidden!');

/**
 * 数据库对象接口
 * @author Administrator
 */
interface Sql
{
	/**
	 * 生成SELECT语句
	 * @param string $fields
	 * @param string $tables
	 * @param array $options
	 */
	public function getSelectSql($fields, $tables, $options);

	/**
	 * 生成INSERT语句
	 * @param string $table
	 * @param array $data
	 */
	public function getInsertSql($table, $data);

	/**
	 * 生成INSERT基本语句
	 * @param string $type
	 * @param string $table
	 * @param array $data
	 */
	public function getInsertSqlBase($type, $table, $data);
	
	/**
	 * 生成UPDATE语句
	 * @param string $table
	 * @param string|array $data
	 * @param string $where
	 */
	public function getUpdateSql($table, $data, $where);

	/**
	 * 生成REPLACE语句
	 * @param string $table
	 * @param array $data
	 */
	public function getReplaceSql($table, $data);

	/**
	 * 生成DELETE语句
	 * @param string $table
	 * @param string $where
	 * @param string $delete
	 */
	public function getDeleteSql($table, $where, $delete = false);

	/**
	 * 获取表的字段信息
	 * @param string $table
	 */
	public function getShowFieldsSql($table);

	/**
	 * 生成联接语句
	 * @param string $type
	 * @param string $table
	 * @param string $alias
	 * @param string $on
	 */
	public function getJoinSql($type, $table, $alias, $on);

	/**
	 * 获取最后一次插入的ID
	 */
	public function getLastIdSql();

	/**
	 * 生成limit字符串
	 * @param string|array $limit
	 */
	public function getLimitSql($limit);

	/**
	 * 生成In语句
	 * @param string $field
	 * @param string|array $list
	 * @param string $alias
	 * @param string $sign
	 */
	public function getInSql($field, $list, $alias = false, $sign = 'IN');
	
	/**
	 * 获取Between语句
	 * @param string $field
	 * @param string|integer $value1
	 * @param string|integer $value2
	 */
	public function getBetweenSql($field, $value1, $value2);
	
	/**
	 * 生成统计字段SQL
	 * @param string $countFiled
	 * @param string $fieldName
	 */
	public function getCountSql($countFiled, $fieldName);

	/**
	 * 格式化键值数组
	 * @param array $data
	 * @param string $link
	 * @param string $sign
	 * @param string $alias
	 */
	public function getFieldCondition($data, $link = 'AND', $sign = '=', $alias = false);

	/**
	 * 生成选项语句
	 * @param string $type
	 * @param array $options
	 */
	public function getOptionSql($type, $options);

	/**
	 * 检查是否是字符串或数字条件
	 * @param string $condition
	 */
	public function checkStringCondition($condition);

	/**
	 * 解析查询条件
	 * @param string|array $condition
	 * @param string $link
	 * @param string $sign
	 * @param string $alias
	 */
	public function parseCondition($condition, $link = 'AND', $sign = '=', $alias = false);

	/**
	 * 获取别名SQL
	 * @param string $alias
	 */
	public function getAliasSql($alias);

	/**
	 * 获取字段列表SQL
	 * @param array $fields
	 * @param bool $alias
	 * @return string
	 */
	public function getFieldsSql(array $fields, $alias = false);

	/**
	 * 字段组合
	 * @param array $fields
	 * @return bool|string
	 */
	public function getMultiFieldsSql(array $fields);

	/**
	 * 获取字段名称SQL
	 * @param $field
	 * @param bool $alias
	 * @return string
	 */
	public function getFieldNameSql($field, $alias = false);

	/**
	 * 转换字段为别名
	 * @param $fields
	 * @param $aliasFields
	 */
	public function getAliasFieldsSql($fields, $aliasFields);

	/**
	 * [别名.]字段解析
	 * @param string $field
	 * @param string $alias
	 */
	public function parseField($field, $alias = false);

	/**
	 * 值格式解析
	 * @param string $value
	 * @param string $paramType
	 * @param bool $ifQuote
	 */
	public function parseValue($value, $paramType = 'where', $ifQuote = true);
	
	/**
	 * 获取字段值SQL
	 * @param string $val
	 * @param bool $ifQuote
	 */
	public function getValueSql($val, $ifQuote = true);

	/**
	 * 给符号加空格
	 * @param string|array $sign
	 */
	public function wrapSign($sign);

	/**
	 * 转义字符
	 * @param string $name
	 * @param bool $addSlashes
	 */
	public function filterName($name, $addSlashes = true);
	
	/**
	 * SQL安全过滤
	 * @param string $content
	 * @param bool $addSlashes
	 */
	public function filterValue($content, $addSlashes = true);

	/**
	 * 给逗号分隔的列表加引号
	 * @param string $string
	 * @param string $quote
	 */
	public function quoteList($list, $quote = OC_QUOTE);
	
	/**
	 * 获取表名
	 * @param string $dbName
	 * @param string $tableName
	 */
	public function getTableNameSql($dbName, $tableName);
	
	/**
	 * 将数组改成条件字符串
	 * @param array $data
	 */
	public function getWhereSql($data);

	/**
	 * 获取合并查询语句
	 * @param string $sql
	 * @param bool $unionAll
	 * @return string
	 */
	public function getUnionSql($sql, $unionAll = false);

	/**
	 * 将条件数组连接成条件字符串
	 * @param array $data
	 * @param string $link
	 */
	public function linkWhere(array $data, $link = 'AND');

	/**
	 * 魔术方法__call
	 * @param string $name
	 * @param array $params
	 */
	public function __call($name, $params);
}