<?php

namespace Illuminatech\Balance\Test;

use Illuminate\Database\Capsule\Manager;

/**
 * Base class for the test cases.
 */
class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Illuminate\Database\Capsule\Manager test DB manager.
     */
    private $db;

    /**
     * Gets a database manager instance.
     *
     * @return \Illuminate\Database\Capsule\Manager
     */
    protected function getDb(): Manager
    {
        if ($this->db === null) {
            $db = new Manager;

            $db->addConnection([
                'driver'    => 'sqlite',
                'database'  => ':memory:',
            ]);

            $db->setAsGlobal();

            $this->db = $db;
        }

        return $this->db;
    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function getConnection()
    {
        return $this->getDb()->getConnection();
    }

    /**
     * Get a schema builder instance.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function getSchemaBuilder()
    {
        return $this->getConnection()->getSchemaBuilder();
    }
}
