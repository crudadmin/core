<?php

namespace Admin\Core\Tests\Concerns;

use PHPUnit\Framework\Assert as PHPUnit;

trait FeatureAssertions
{

    /**
     * Get doctrine scheme
     * @param  AdminModel $model
     * @return SchemaManager
     */
    private function getDoctrineScheme($model)
    {
        $model = $this->getModelClass($model);

        return $model->getConnection()
                     ->getDoctrineConnection()
                     ->getSchemaManager();
    }

    /**
     * Returns doctrine column
     * @param  AdminModel $model
     * @param  string     $column
     * @return Doctrine\DBAL\Schema\Column
     */
    public function getDoctrineColumn($model, string $column)
    {
        $model = $this->getModelClass($model);

        $columns = $this->getDoctrineScheme($model)->listTableColumns($model->getTable());

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
     * @param  AdminModel $model
     * @param  string     $column
     * @param  string     $type
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
     * @param  AdminModel $model
     * @param  string     $column
     * @return this
     */
    public function assertColumnExists($model, string $column)
    {
        PHPUnit::assertNotNull($this->getDoctrineColumn($model, $column), "Column $column does not exists");

        return $this;
    }

    /**
     * Check if column is NotNull
     * @param  AdminModel $model
     * @param  string     $column
     * @param  bool       $isNull
     * @return this
     */
    public function assertColumnNotNull($model, string $column, bool $isNull)
    {
        $dbNull = $this->getDoctrineColumn($model, $column)->getNotNull();

        PHPUnit::assertEquals($isNull, $dbNull, "Column $column does not match excepted null value [".($isNull ? 'true' : 'false')."] with db value [".($dbNull ? 'true' : 'false')."]");

        return $this;
    }

    /**
     * Check if column length
     * @param  AdminModel $model
     * @param  string     $column
     * @param  int        $length
     * @return this
     */
    public function assertColumnLength($model, string $column, int $length)
    {
        $dbLength = $this->getDoctrineColumn($model, $column)->getLength();

        PHPUnit::assertEquals($length, $dbLength, "Column $column does not match excepted length [$length] with db value [$dbLength]");

        return $this;
    }

    /**
     * Check if column is unsigned
     * @param  AdminModel $model
     * @param  string     $column
     * @param  int        $length
     * @return this
     */
    public function assertColumnUnsigned($model, string $column, bool $unsigned)
    {
        $dbUnsigned = $this->getDoctrineColumn($model, $column)->getUnsigned();

        PHPUnit::assertEquals($unsigned, $dbUnsigned, "Column $column does not match excepted unsigned [[".($unsigned ? 'true' : 'false')."]] with db value [[".($dbUnsigned ? 'true' : 'false')."]]");

        return $this;
    }
}