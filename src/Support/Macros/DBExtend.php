<?php

namespace Hnllyrp\LaravelSupport\Support\Macros;

use Illuminate\Database\Query\Builder;

class DBExtend
{
    protected $builder, $pdo, $schema, $prefix;

    protected $content;

    private $valuesString, $sql;

    /**
     * 表注释
     * Comments constructor.
     * @param \Illuminate\Database\Query\Builder $builder
     * @param string $content
     */
    public function __construct(Builder $builder, string $content)
    {
        $this->builder = $builder;
        $this->pdo = $this->builder->getConnection()->getPdo();
        $this->schema = $this->builder->getConnection()->getDoctrineSchemaManager();

        // 表前缀
        $this->prefix = $this->builder->getConnection()->getTablePrefix();

        $this->content = $content;

        $this->prepareValues();
        $this->assembleStatement();
    }

    /**
     * Prepare the values.
     * @return void
     */
    private function prepareValues()
    {
        $this->valuesString = $this->escape($this->content);
    }

    /**
     * 表名含前缀
     * @return string
     */
    protected function tableName()
    {
        return $this->prefix . $this->builder->from;
    }

    /**
     * 转义字符串，为SQL语句中的字符串添加引号
     * @param string $value
     * @return array
     */
    private function escape(string $value)
    {
        return $value ? $this->pdo->quote($value) : 'NULL';
    }

    /**
     * 组合 SQL 语句
     * @return void
     */
    private function assembleStatement()
    {
        $this->sql = "ALTER TABLE {$this->tableName()} comment {$this->valuesString}";
    }

    /**
     * 执行语句 添加表注释
     * @return int
     */
    public function comments()
    {
        return $this->builder->getConnection()->statement($this->sql);
    }

    /**
     * 获取索引名
     * @return mixed
     */
    private function key_name()
    {
        return trim($this->content);
    }

    /**
     * 判断表是否存在索引
     * @return bool
     */
    public function hasIndex()
    {
        $indexesFound = $this->schema->listTableIndexes($this->tableName());

        if (array_key_exists($this->key_name(), $indexesFound)) {
            return true;
        }

        return false;
    }
}
