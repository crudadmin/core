<?php

namespace Admin\Core\Tests\Concerns;

use PHPUnit\Framework\Assert as PHPUnit;

trait MigrationAssertions
{
    /*
     * Model schema builder
     */
    private $schema;

    /**
     * Set schema
     * @param Schema $schema
     */
    public function setSchema($schema)
    {
        $this->schema = $schema;
    }

    /**
     * Set schema
     * @return Schema
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * Get doctrine schema
     * @return SchemaManager
     */
    public function getDoctrineSchema()
    {
        return $this->getSchema()
                    ->getConnection()
                    ->getDoctrineConnection()
                    ->getSchemaManager();
    }

    /**
     * Returns doctrine column
     * @param  string $table
     * @param  string $column
     * @return Doctrine\DBAL\Schema\Column
     */
    public function getDoctrineColumn($table, string $column)
    {
        $columns = $this->getDoctrineSchema()->listTableColumns($table);

        //Fix doctrine columns keys format
        $array = [];
        foreach ($columns as $key => $dbColumn) {
            $key = str_replace('`', '', $key);

            $array[$key] = $dbColumn;
        }

        return isset($array[$column]) ? $array[$column] : null;
    }

    /**
     * Check if column is correct db type
     * @param  string $table
     * @param  string $column
     * @param  string $type
     * @return this
     */
    public function assertColumnType($model, string $column, string $type)
    {
        $dbType = $this->getDoctrineColumn($model, $column)->getType();

        PHPUnit::assertEquals($dbType->getName(), $type, "Column $column does not match excepted db type [$type] with actual type [$dbType]");

        return $this;
    }

    /**
     * Check if column in database does exists
     * @param  string $table
     * @param  string $column
     * @return this
     */
    public function assertColumnExists($table, string $column)
    {
        PHPUnit::assertNotNull($this->getDoctrineColumn($table, $column), "Column $column does not exists");

        return $this;
    }

    /**
     * Check if column is NotNull
     * @param  string $table
     * @param  string $column
     * @param  bool   $isNull
     * @return this
     */
    public function assertColumnNotNull($table, string $column, bool $isNull)
    {
        $dbNull = $this->getDoctrineColumn($table, $column)->getNotNull();

        PHPUnit::assertEquals($isNull, $dbNull, "Column $column does not match excepted NULL value [".($isNull ? 'true' : 'false')."] with db value [".($dbNull ? 'true' : 'false')."]");

        return $this;
    }

    /**
     * Check if column length
     * @param  string $table
     * @param  string $column
     * @param  int    $length
     * @return this
     */
    public function assertColumnLength($table, string $column, int $length)
    {
        $dbLength = $this->getDoctrineColumn($table, $column)->getLength();

        PHPUnit::assertEquals($length, $dbLength, "Column $column does not match excepted length [$length] with db value [$dbLength]");

        return $this;
    }

    /**
     * Check if column is unsigned
     * @param  string $table
     * @param  string $column
     * @param  int    $length
     * @return this
     */
    public function assertColumnUnsigned($table, string $column, bool $unsigned)
    {
        $dbUnsigned = $this->getDoctrineColumn($table, $column)->getUnsigned();

        PHPUnit::assertEquals($unsigned, $dbUnsigned, "Column $column does not match excepted unsigned [[".($unsigned ? 'true' : 'false')."]] with db value [[".($dbUnsigned ? 'true' : 'false')."]]");

        return $this;
    }

    /**
     * Check if table has foreign key
     * @param  string $table
     * @param  string $key
     * @return this
     */
    public function assertTableExists($table)
    {
        $tableExists = $this->getSchema()->hasTable($table);

        PHPUnit::assertTrue($tableExists, "Table [$table] does not exists");

        return $this;
    }

    /**
     * Check if table has foreign key
     * @param  string $table
     * @param  string $key
     * @return this
     */
    public function assertHasForeignKey($table, string $key)
    {
        $keys = array_map(function($key){
            return $key->getName();
        }, $this->getDoctrineSchema()->listTableForeignKeys($table));

        PHPUnit::assertContains($key, $keys, "Table [$table] does not have foreign key [$key]");

        return $this;
    }
}