<?php

use PHPUnit\Framework\TestCase;

/** Base class: fresh in-memory database per test. Never touches Db3.db. */
abstract class DbTestCase extends TestCase
{
    protected SQLite3 $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = getDb(':memory:');
        createSchema($this->db);
    }

    protected function tearDown(): void
    {
        $this->db->close();
        parent::tearDown();
    }
}
