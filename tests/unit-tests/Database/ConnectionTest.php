<?php

/*
 * This file is part of the hyn/multi-tenant package.
 *
 * (c) Daniël Klabbers <daniel@klabbers.email>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @see https://laravel-tenancy.com
 * @see https://github.com/hyn/multi-tenant
 */

namespace Hyn\Tenancy\Tests\Database;

use Hyn\Tenancy\Tests\Test;
use Illuminate\Database\Connection as DatabaseConnection;

class ConnectionTest extends Test
{
    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function without_identification_no_tenant_connection_is_active()
    {
        $this->setUpHostnames(true);

        $this->connection->get();
    }

    /**
     * @test
     * @depends without_identification_no_tenant_connection_is_active
     */
    public function hostname_identification_switches_connection()
    {
        $this->setUpHostnames(true);
        $this->activateTenant();

        $failsWithoutWebsite = false;

        try {
            $this->connection->get();
        } catch (\InvalidArgumentException $e) {
            $failsWithoutWebsite = true;
        }

        $this->assertTrue($failsWithoutWebsite, 'Tenant connection should not work, when the hostname has no website.');
        $this->assertTrue($this->connection->system() instanceof DatabaseConnection, 'System connection is not working.');
    }

    /**
     * @test
     * @depends hostname_identification_switches_connection
     */
    public function both_connections_work()
    {
        $this->setUpHostnames(true);
        $this->setUpWebsites(true, true);
        $this->activateTenant();

        $this->assertTrue($this->connection->get() instanceof DatabaseConnection, 'Tenant connection is not set up properly.');
        $this->assertTrue($this->connection->system() instanceof DatabaseConnection, 'System connection fails once tenant connection is set up.');
    }

    /**
     * @test
     * @depends both_connections_work
     */
    public function can_migrate_the_tenant()
    {
        config(['tenancy.db.tenant-migrations-path' => __DIR__ . '/../../migrations']);

        $this->assertNotNull(config('tenancy.db.tenant-migrations-path'));

        $this->setUpHostnames(true);
        $this->setUpWebsites(true, true);
        $this->activateTenant();

        $this->assertTrue($this->connection->get()->getSchemaBuilder()->hasTable('samples'));
    }
}
