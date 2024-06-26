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

namespace userstatus_ldapchecker;
use advanced_testcase;

/**
 * The class contains a test script for the moodle userstatus_ldapchecker
 *
 * @package    userstatus_ldapchecker
 * @category   phpunit
 * @copyright  2016/17 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \userstatus_ldapchecker\ldapchecker::get_to_suspend()
 * @covers     \userstatus_ldapchecker\ldapchecker::get_never_logged_in()
 * @covers     \userstatus_ldapchecker\ldapchecker::get_to_delete()
 * @covers     \userstatus_ldapchecker\ldapchecker::get_to_reactivate()
 */
class userstatus_ldapchecker_test extends advanced_testcase {
    protected function set_up() {
        $config = get_config('userstatus_ldapchecker');
        $config->deletetime = 365; // Set time for deleting users in days.

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
        $this->set_up();
        // Testing is set to true which means that it does not try to connect to LDAP.
        $checker = new ldapchecker(true);

        $checker->fill_ldap_response_for_testing([ "tu_id_1" => 1,
                                                                    "tu_id_2" => 1,
                                                                    "tu_id_3" => 1,
                                                                    "tu_id_4" => 1,
                                                                    "to_reactivate" => 1,
                                                                    "to_not_reactivate_username_taken" => 1,
                                                                    "to_not_reactivate_entry_missing" => 1,
                                                                ]);

        // Never logged in.
        // Suspended users without archive table entry are included.
        $never = ["anonym4", "anonym5", "anonym9", "anonym10", "never_logged_in_1", "never_logged_in_2"];
        $returnnever = $checker->get_never_logged_in();
        $this->assertEqualsCanonicalizing(array_map(fn($user) => $user->username, $returnnever), $never);

        // To suspend.
        $suspend = ["never_logged_in_1", "never_logged_in_2", "to_suspend"];
        $returnsuspend = $checker->get_to_suspend();
        $this->assertEqualsCanonicalizing(array_map(fn($user) => $user->username, $returnsuspend), $suspend);

        // To reactivate.
        $reactivate = ["to_reactivate"];
        $returnreactivate = $checker->get_to_reactivate();
        $this->assertEqualsCanonicalizing(array_map(fn($user) => $user->username, $returnreactivate), $reactivate);

        // To delete.
        $delete = ["to_delete"];
        $returndelete = $checker->get_to_delete();
        $this->assertEqualsCanonicalizing(array_map(fn($user) => $user->username, $returndelete), $delete);

        $this->resetAfterTest(true);
    }
    /**
     * Methods recommended by moodle to assure database and dataroot is reset.
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
     * Methods recommended by moodle to assure database is reset.
     */
    public function test_user_table_was_reset() {
        global $DB;
        $this->assertEquals(2, $DB->count_records('user', []));
        $this->assertEquals(0, $DB->count_records('tool_cleanupusers', []));
    }
}
