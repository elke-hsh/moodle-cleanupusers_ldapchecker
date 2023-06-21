<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Data Generator for the userstatus_ldapchecker sub-plugin
 *
 * @package    userstatus_ldapchecker
 * @category   test
 * @copyright  2016/17 Nina Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Class Data Generator for the userstatus_ldapchecker sub-plugin
 *
 * @package    userstatus_ldapchecker
 * @category   test
 * @copyright  2016/17 Nina Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class userstatus_ldapchecker_generator extends testing_data_generator {
    /**
     * Creates users in Database to test the sub-plugin.
     */
    public function test_create_preparation () {
        global $DB;
        $generator = advanced_testcase::getDataGenerator();

        // Create users which are in the LDAP-response
        $generator->create_user(array('username' => 'TU_ID_1', 'auth' => 'cas'));
        $generator->create_user(array('username' => 'TU_ID_2', 'auth' => 'cas'));
        $generator->create_user(array('username' => 'TU_ID_3', 'auth' => 'cas'));
        $generator->create_user(array('username' => 'TU_ID_4', 'auth' => 'cas'));

        // Create user which should be suspended (not in lookup-table)
        $generator->create_user(array('username' => 'TO_SUSPEND', 'auth' => 'cas'));

        // Create user which should be reactivated (are suspended but in lookup-table)
        $generator->create_user(array('username' => 'TO_REACTIVATE', 'auth' => 'cas', 'suspended' => 1));

        // Create user which was suspended manually and should be deleted (not logged in since one year)
        $timestamponeyearago = time() - 366*86400;
        $generator->create_user(array('username' => 'TO_DELETE_MANUALLY', 'auth' => 'cas', 'suspended' => 1, 'lastaccess' => $timestamponeyearago));

        // Create user which was suspended with the plugin and should be deleted (was suspended one year ago or earlier)
        $delete = $generator->create_user(array('username' => 'Anonym', 'auth'=>'cas', 'suspended' => 1, 'lastaccess' => $timestamponeyearago));
        $DB->insert_record_raw('tool_cleanupusers', array('id' => $delete->id, 'archived' => true, 'timestamp' => $timestamponeyearago), true, false, true);
        $DB->insert_record_raw('tool_cleanupusers_archive', array('id' => $delete->id, 'auth'=>'cas', 'username' => 'TO_DELETE_PLUGIN',
            'suspended' => 1, 'lastaccess' => $timestamponeyearago), true, false, true);
        $deleteduser = $delete;

        return $deleteduser;

        /*
        $user = $generator->create_user(array('username' => 'neutraluser', 'lastaccess' => $mytimestamp));
        $data['user'] = $user;

        $timestamponeyearago = $mytimestamp - 31536000;
        $userlongnotloggedin = $generator->create_user(array('username' => 'userlongnotloggedin',
            'lastaccess' => $timestamponeyearago));
        $data['useroneyearnotlogedin'] = $userlongnotloggedin;

        $timestampfifteendays = $mytimestamp - 1296000;
        $userfifteendays = $generator->create_user(array('username' => 'userfifteendays', 'lastaccess' => $timestampfifteendays));
        $data['userfifteendays'] = $userfifteendays;

        // User manually suspended.
        $oneyearnintydays = $mytimestamp - 39313000;
        $userarchived = $generator->create_user(array('username' => 'userarchived', 'lastaccess' => $oneyearnintydays,
            'suspended' => 1));
        $data['userarchivedoneyearnintydays'] = $userarchived;

        $neverloggedin = $generator->create_user(array('username' => 'neverloggedin'));
        $data['neverloggedin'] = $neverloggedin;

        // User suspended by the plugin.
        $tendaysago = $mytimestamp - 864000;
        $reactivate = $generator->create_user(array('username' => 'Anonym', 'suspended' => 1));
        $DB->insert_record_raw('tool_cleanupusers', array('id' => $reactivate->id, 'archived' => true), true, false, true);
        $DB->insert_record_raw('tool_cleanupusers_archive', array('id' => $reactivate->id, 'username' => 'reactivate',
            'suspended' => 1, 'lastaccess' => $tendaysago), true, false, true);
        $data['reactivate'] = $reactivate;
        */

    }
}