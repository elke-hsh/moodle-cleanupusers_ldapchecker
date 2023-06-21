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
 * The class contains a test script for the moodle userstatus_ldapchecker
 *
 * @package    userstatus_ldapchecker
 * @category   phpunit
 * @copyright  2016/17 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use userstatus_ldapchecker\ldapchecker;

defined('MOODLE_INTERNAL') || die();

/**
 * The class contains a test script for the moodle userstatus_ldapchecker
 *
 * @package    userstatus_ldapchecker
 * @category   phpunit
 * @copyright  2016/17 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class userstatus_ldapchecker_testcase extends advanced_testcase {

    protected function set_up() {
        $config = get_config('userstatus_ldapchecker');
        $config->deletetime = 356; // Set time for deleting users in days

        $generator = $this->getDataGenerator()->get_plugin_generator('userstatus_ldapchecker');
        $data = $generator->test_create_preparation();
        $this->resetAfterTest(true);
        return $data;
    }
    /**
     * Function to test the class ldapchecker.
     *
     * @see ldapchecker
     */
    public function test_locallib() {
        $deleteduser_by_plugin = $this->set_up();

        // Testing is set to true which means that it does not try to connect to LDAP.
        $myuserstatuschecker = new ldapchecker(true);

        $myuserstatuschecker->fill_ldap_response_for_testing(array( "TU_ID_1" => 1,
                                                                    "TU_ID_2" => 1,
                                                                    "TU_ID_3" => 1,
                                                                    "TU_ID_4" => 1,
                                                                ));

        // User to susupend
        $returnsuspend = $myuserstatuschecker->get_to_suspend();
        $this->assertEquals("TO_SUSPEND", reset($returnsuspend)->username);

        // Add user which should be reactivated
        $myuserstatuschecker->fill_ldap_response_for_testing(array( "TU_ID_1" => 1,
            "TU_ID_2" => 1,
            "TU_ID_3" => 1,
            "TU_ID_4" => 1,
            "TO_REACTIVATE" => 1,
        ));
        $returntoreactivate = $myuserstatuschecker->get_to_reactivate();
        $this->assertEquals("TO_REACTIVATE", reset($returntoreactivate)->username);

        $returndelete = $myuserstatuschecker->get_to_delete();
        $this->assertEquals("TO_DELETE_MANUALLY", reset($returndelete)->username);
        $this->assertEquals($deleteduser_by_plugin->id, end($returndelete)->id);

    /*
        $this->assertEquals($data['userarchivedoneyearnintydays']->id,
            $returndelete[$data['userarchivedoneyearnintydays']->id]->id);
        //$this->assertEquals($data['neverloggedin']->id, $returnneverloggedin[$data['neverloggedin']->id]->id);
        // Merely id is compared since plugin only saves necessary data not complete user.
        $this->assertEquals($data['reactivate']->id, $returntoreactivate[$data['reactivate']->id]->id);
        $this->assertNotContains($data['user']->username, $returnsuspend);
        $this->assertNotContains($data['user']->username, $returndelete);
        $this->assertNotContains($data['user']->username, $returnneverloggedin);
        $this->assertNotContains($data['userfifteendays']->username, $returnsuspend);
        $this->assertNotContains($data['userfifteendays']->username, $returndelete);
        $this->assertNotContains($data['userfifteendays']->username, $returnneverloggedin);

        // Userarchived is not in array since time is not right.
        set_config('suspendtime', 400 , 'userstatus_ldapchecker');
        set_config('deletetime', 730 , 'userstatus_ldapchecker');
        $newstatuschecker = new ldapchecker();
        $returnsuspend = $newstatuschecker->get_to_suspend();
        $returndelete = $newstatuschecker->get_to_delete();
        $returnneverloggedin = $newstatuschecker->get_never_logged_in();

        $this->assertNotContains($data['user']->username, $returnsuspend);
        $this->assertNotContains($data['user']->username, $returndelete);
        $this->assertNotContains($data['user']->username, $returnneverloggedin);
        $this->assertNotContains($data['useroneyearnotlogedin']->username, $returnsuspend);
        $this->assertNotContains($data['useroneyearnotlogedin']->username, $returndelete);
        $this->assertNotContains($data['useroneyearnotlogedin']->username, $returnneverloggedin);
        $this->assertNotContains($data['userarchivedoneyearnintydays']->username, $returnsuspend);
        $this->assertNotContains($data['userarchivedoneyearnintydays']->username, $returndelete);
        $this->assertNotContains($data['userarchivedoneyearnintydays']->username, $returnneverloggedin);
        $this->assertEquals($data['neverloggedin']->id, $returnneverloggedin[$data['neverloggedin']->id]->id);

        set_config('suspendtime', 10 , 'userstatus_ldapchecker');
        set_config('deletetime', 20 , 'userstatus_ldapchecker');
        $newstatuschecker = new ldapchecker();
        $returnsuspend = $newstatuschecker->get_to_suspend();
        $returndelete = $newstatuschecker->get_to_delete();
        $returnneverloggedin = $newstatuschecker->get_never_logged_in();

        $this->assertEquals($data['useroneyearnotlogedin']->id, $returnsuspend[$data['useroneyearnotlogedin']->id]->id);
        $this->assertEquals($data['userfifteendays']->id, $returnsuspend[$data['userfifteendays']->id]->id);
        $this->assertEquals($data['userarchivedoneyearnintydays']->id,
            $returndelete[$data['userarchivedoneyearnintydays']->id]->id);
        $this->assertNotContains($data['user']->username, $returnsuspend);
        $this->assertNotContains($data['user']->username, $returndelete);
        $this->assertNotContains($data['user']->username, $returnneverloggedin);
        $this->assertEquals($data['neverloggedin']->id, $returnneverloggedin[$data['neverloggedin']->id]->id);
    */
        $this->resetAfterTest(true);

    }
    /**
     * Methodes recommended by moodle to assure database and dataroot is reset.
     */
    public function test_deleting() {
        global $DB;
        $this->resetAfterTest(true);
        $DB->delete_records('user');
        $DB->delete_records('tool_cleanupusers');
        $this->assertEmpty($DB->get_records('user'));
        $this->assertEmpty($DB->get_records('tool_cleanupusers'));
    }
    /**
     * Methodes recommended by moodle to assure database is reset.
     */
    public function test_user_table_was_reset() {
        global $DB;
        $this->assertEquals(2, $DB->count_records('user', array()));
        $this->assertEquals(0, $DB->count_records('tool_cleanupusers', array()));
    }
}