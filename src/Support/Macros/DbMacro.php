<?php


namespace Hnllyrp\LaravelSupport\Support\Macros;

use Illuminate\Database\Query\Builder;

class DbMacro
{
    /**
     * @var Builder
     */
    protected $builder;

    /**
     * The active PDO connection.
     *
     * @var \PDO|\Closure
     */
    protected $pdo;

    /**
     * Get the Doctrine DBAL schema manager for the connection.
     *
     * @var \Doctrine\DBAL\Schema\AbstractSchemaManager
     */
    protected $schema;

    protected $prefix;

    /**
     * DbMacro constructor.
     * @param Builder $builder
     */
    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
        $this->pdo = $this->builder->getConnection()->getPdo();
        $this->schema = $this->builder->getConnection()->getDoctrineSchemaManager();

        // 表前缀
        $this->prefix = $this->builder->getConnection()->getTablePrefix();
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
     *
     * @param string $value
     * @return string
     */
    protected function escape(string $value)
    {
        return $value ? $this->pdo->quote($value) : '';
    }

    /**
     * 添加表注释
     *
     * @return bool
     */
    public function comment($content = '')
    {
        if (empty($content)) {
            return false;
        }

        $value = $this->escape(trim($content));

        $sql = "ALTER TABLE {$this->tableName()} COMMENT {$value};";

        return $this->builder->getConnection()->statement($sql);
    }

    /**
     * 判断表是否存在索引
     * @return bool
     */
    public function hasIndex($index_name)
    {
        $index_name = trim($index_name);
        if (empty($index_name)) {
            return false;
        }

        $indexesFound = $this->schema->listTableIndexes($this->tableName());

        if (array_key_exists($index_name, $indexesFound)) {
            return true;
        }

        return false;
    }
}
