<?php

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace CodeIgniter\Database\Live;

use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;
use Tests\Support\Database\Seeds\CITestSeeder;

/**
 * @group DatabaseLive
 *
 * @internal
 */
final class TransactionTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $seed    = CITestSeeder::class;

    protected function setUp(): void
    {
        // Reset connection instance.
        $this->db = Database::connect($this->DBGroup, false);

        parent::setUp();
    }

    /**
     * Sets $DBDebug to false.
     *
     * WARNING: this value will persist! take care to roll it back.
     */
    protected function disableDBDebug(): void
    {
        $this->setPrivateProperty($this->db, 'DBDebug', false);
    }

    /**
     * Sets $DBDebug to true.
     */
    protected function enableDBDebug(): void
    {
        $this->setPrivateProperty($this->db, 'DBDebug', true);
    }

    public function testTransStartDBDebugTrue()
    {
        $builder = $this->db->table('job');
        $e       = null;

        try {
            $this->db->transStart();

            $jobData = [
                'name'        => 'Grocery Sales',
                'description' => 'Discount!',
            ];
            $builder->insert($jobData);

            // Duplicate entry '1' for key 'PRIMARY'
            $jobData = [
                'id'          => 1,
                'name'        => 'Comedian',
                'description' => 'Theres something in your teeth',
            ];
            $builder->insert($jobData);

            $this->db->transComplete();
        } catch (DatabaseException $e) {
            // Do nothing.
        }

        $this->assertInstanceOf(DatabaseException::class, $e);
        $this->dontSeeInDatabase('job', ['name' => 'Grocery Sales']);
    }

    public function testTransStartDBDebugFalse()
    {
        $this->disableDBDebug();

        $builder = $this->db->table('job');

        $this->db->transStart();

        $jobData = [
            'name'        => 'Grocery Sales',
            'description' => 'Discount!',
        ];
        $builder->insert($jobData);

        $this->assertTrue($this->db->transStatus());

        // Duplicate entry '1' for key 'PRIMARY'
        $jobData = [
            'id'          => 1,
            'name'        => 'Comedian',
            'description' => 'Theres something in your teeth',
        ];
        $builder->insert($jobData);

        $this->assertFalse($this->db->transStatus());

        $this->db->transComplete();

        $this->dontSeeInDatabase('job', ['name' => 'Grocery Sales']);

        $this->enableDBDebug();
    }

    public function testTransStrictTrueAndDBDebugFalse()
    {
        $this->disableDBDebug();

        $builder = $this->db->table('job');

        // The first transaction group
        $this->db->transStart();

        $jobData = [
            'name'        => 'Grocery Sales',
            'description' => 'Discount!',
        ];
        $builder->insert($jobData);

        $this->assertTrue($this->db->transStatus());

        // Duplicate entry '1' for key 'PRIMARY'
        $jobData = [
            'id'          => 1,
            'name'        => 'Comedian',
            'description' => 'Theres something in your teeth',
        ];
        $builder->insert($jobData);

        $this->assertFalse($this->db->transStatus());

        $this->db->transComplete();

        $this->dontSeeInDatabase('job', ['name' => 'Grocery Sales']);

        // The second transaction group
        $this->db->transStart();

        $jobData = [
            'name'        => 'Comedian',
            'description' => 'Theres something in your teeth',
        ];
        $builder->insert($jobData);

        $this->assertFalse($this->db->transStatus());

        $this->db->transComplete();

        $this->dontSeeInDatabase('job', ['name' => 'Comedian']);

        $this->enableDBDebug();
    }

    public function testTransStrictFalseAndDBDebugFalse()
    {
        $this->disableDBDebug();

        $builder = $this->db->table('job');

        $this->db->transStrict(false);

        // The first transaction group
        $this->db->transStart();

        $jobData = [
            'name'        => 'Grocery Sales',
            'description' => 'Discount!',
        ];
        $builder->insert($jobData);

        $this->assertTrue($this->db->transStatus());

        // Duplicate entry '1' for key 'PRIMARY'
        $jobData = [
            'id'          => 1,
            'name'        => 'Comedian',
            'description' => 'Theres something in your teeth',
        ];
        $builder->insert($jobData);

        $this->assertFalse($this->db->transStatus());

        $this->db->transComplete();

        $this->dontSeeInDatabase('job', ['name' => 'Grocery Sales']);

        // The second transaction group
        $this->db->transStart();

        $jobData = [
            'name'        => 'Comedian',
            'description' => 'Theres something in your teeth',
        ];
        $builder->insert($jobData);

        $this->assertTrue($this->db->transStatus());

        $this->db->transComplete();

        $this->seeInDatabase('job', ['name' => 'Comedian']);

        $this->enableDBDebug();
    }
}
