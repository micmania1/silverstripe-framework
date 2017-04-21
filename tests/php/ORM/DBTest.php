<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Control\Director;
use SilverStripe\ORM\DB;
use SilverStripe\Dev\SapphireTest;

class DBTest extends SapphireTest
{

    public function testValidAlternativeDatabaseName()
    {

        $prefix = getenv('SS_DATABASE_PREFIX') ?: 'ss_';

        Director::set_environment_type('dev');
        $this->assertTrue(DB::valid_alternative_database_name($prefix.'tmpdb1234567'));
        $this->assertFalse(DB::valid_alternative_database_name($prefix.'tmpdb12345678'));
        $this->assertFalse(DB::valid_alternative_database_name('tmpdb1234567'));
        $this->assertFalse(DB::valid_alternative_database_name('random'));
        $this->assertFalse(DB::valid_alternative_database_name(''));

        Director::set_environment_type('live');
        $this->assertFalse(DB::valid_alternative_database_name($prefix.'tmpdb1234567'));

        Director::set_environment_type('dev');
    }
}
