<?php

namespace SilverStripe\Dev\Tasks;

use SilverStripe\Control\Director;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;

/**
 * Cleans up leftover databases from aborted test executions (starting with ss_tmpdb)
 * Task is restricted to users with administrator rights or running through CLI.
 */
class CleanupTestDatabasesTask extends BuildTask
{

    private static $segment = 'CleanupTestDatabasesTask';

    protected $title = 'Deletes all temporary test databases';

    protected $description = 'Cleans up leftover databases from aborted test executions (starting with ss_tmpdb)';

    public function run($request)
    {
        if (!Permission::check('ADMIN') && !Director::is_cli()) {
            $response = Security::permissionFailure();
            if ($response) {
                $response->output();
            }
            die;
        }

        SapphireTest::delete_all_temp_dbs();
    }
}
