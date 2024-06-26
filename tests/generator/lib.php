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
    public function test_create_preparation() {
        global $DB;
        $generator = advanced_testcase::getDataGenerator();

        $yearago = time() - 366 * 86400;
        $dayago = time() - 86400;

        // Create users which are in the LDAP-response.
        $generator->create_user(['username' => 'tu_id_1', 'auth' => 'shibboleth', 'lastaccess' => $yearago]);
        $generator->create_user(['username' => 'tu_id_2', 'auth' => 'shibboleth', 'lastaccess' => $yearago]);
        $generator->create_user(['username' => 'tu_id_3', 'auth' => 'shibboleth', 'lastaccess' => $yearago]);
        $generator->create_user(['username' => 'tu_id_4', 'auth' => 'shibboleth', 'lastaccess' => $yearago]);

        // Create user which should be suspended (not in lookup-table).
        $generator->create_user(['username' => 'to_suspend', 'auth' => 'shibboleth', 'lastaccess' => $yearago]);

        // Create users which should NOT be suspended.
        $generator->create_user(['username' => 'manually_suspended', 'auth' => 'shibboleth', 'suspended' => 1,
            'lastaccess' => $yearago]);
        $generator->create_user(['username' => 'manually_deleted', 'auth' => 'shibboleth', 'deleted' => 1]);

        // Create users which never logged in.
        $generator->create_user(['username' => 'never_logged_in_1', 'auth' => 'shibboleth', 'lastaccess' => 0]);
        $generator->create_user(['username' => 'never_logged_in_2', 'auth' => 'shibboleth']);

        // Create user which should be reactivated (are suspended but in lookup-table).
        $reactivate = $generator->create_user(['username' => 'anonym1', 'firstname' => 'Anonym',
        'auth' => 'shibboleth', 'suspended' => 1, 'lastaccess' => 0]);
        $DB->insert_record_raw('tool_cleanupusers', ['id' => $reactivate->id, 'archived' => true,
        'timestamp' => $yearago], true, false, true);
        $DB->insert_record_raw('tool_cleanupusers_archive', ['id' => $reactivate->id, 'auth' => 'shibboleth',
        'username' => 'to_reactivate',
            'suspended' => 1, 'lastaccess' => $yearago], true, false, true);

        // Create users which should NOT be reactivated.
        $notreactivate1 = $generator->create_user(['username' => 'to_not_reactivate', 'auth' => 'shibboleth',
        'suspended' => 1, 'lastaccess' => $yearago]);
        $generator->create_user(['username' => 'to_not_reactivate_username_taken', 'auth' => 'shibboleth',
        'lastaccess' => $dayago]);
        $notreactivate2 = $generator->create_user(['username' => 'anonym2', 'firstname' => 'Anonym',
        'auth' => 'shibboleth', 'suspended' => 1, 'lastaccess' => 0]);
        $DB->insert_record_raw('tool_cleanupusers', ['id' => $notreactivate2->id, 'archived' => true,
        'timestamp' => $yearago], true, false, true);
        $DB->insert_record_raw('tool_cleanupusers_archive', ['id' => $notreactivate2->id, 'auth' => 'shibboleth',
        'username' => 'to_not_reactivate_username_taken',
            'suspended' => 1, 'lastaccess' => $yearago], true, false, true);
        $notreactivate3 = $generator->create_user(['username' => 'anonym3', 'firstname' => 'Anonym',
        'auth' => 'shibboleth', 'suspended' => 1, 'lastaccess' => 0]);
        $DB->insert_record_raw('tool_cleanupusers', ['id' => $notreactivate3->id, 'archived' => true,
        'timestamp' => $yearago], true, false, true);
        $notreactivate4 = $generator->create_user(['username' => 'anonym4', 'firstname' => 'Anonym',
        'auth' => 'shibboleth', 'suspended' => 1, 'lastaccess' => 0]);
        $DB->insert_record_raw('tool_cleanupusers_archive', ['id' => $notreactivate4->id, 'auth' => 'shibboleth',
        'username' => 'to_not_reactivate_entry_missing',
            'suspended' => 1, 'lastaccess' => $yearago], true, false, true);
        $notreactivate5 = $generator->create_user(['username' => 'anonym5', 'firstname' => 'Anonym',
        'auth' => 'shibboleth', 'suspended' => 1, 'lastaccess' => 0]);

        // Create user which was suspended with the plugin and should be deleted (was suspended one year ago or earlier).
        $delete = $generator->create_user(['username' => 'anonym6', 'firstname' => 'Anonym', 'auth' => 'shibboleth',
        'suspended' => 1, 'lastaccess' => 0]);
        $DB->insert_record_raw(
            'tool_cleanupusers',
            ['id' => $delete->id, 'archived' => true, 'timestamp' => $yearago],
            true,
            false,
            true
        );
        $DB->insert_record_raw('tool_cleanupusers_archive', ['id' => $delete->id, 'auth' => 'shibboleth',
        'username' => 'to_delete', 'suspended' => 1, 'lastaccess' => $yearago], true, false, true);

        // Create users which were suspended with the plugin and should NOT be deleted.
        $notdelete1 = $generator->create_user(['username' => 'anonym7', 'firstname' => 'Anonym', 'auth' => 'shibboleth',
        'suspended' => 1, 'lastaccess' => 0]);
        $DB->insert_record_raw(
            'tool_cleanupusers',
            ['id' => $notdelete1->id, 'archived' => true, 'timestamp' => $dayago],
            true,
            false,
            true
        );
        $DB->insert_record_raw('tool_cleanupusers_archive', ['id' => $notdelete1->id, 'auth' => 'shibboleth',
        'username' => 'to_not_delete_one_day', 'suspended' => 1, 'lastaccess' => $yearago], true, false, true);
        $notdelete2 = $generator->create_user(['username' => 'anonym8', 'firstname' => 'Anonym', 'auth' => 'shibboleth',
        'suspended' => 1, 'lastaccess' => 0]);
        $DB->insert_record_raw(
            'tool_cleanupusers',
            ['id' => $notdelete2->id, 'archived' => true, 'timestamp' => $yearago],
            true,
            false,
            true
        );
        $notdelete3 = $generator->create_user(['username' => 'anonym9', 'firstname' => 'Anonym', 'auth' => 'shibboleth',
        'suspended' => 1, 'lastaccess' => 0]);
        $DB->insert_record_raw('tool_cleanupusers_archive', ['id' => $notdelete3->id, 'auth' => 'shibboleth',
        'username' => 'to_not_delete_entry_missing', 'suspended' => 1, 'lastaccess' => $yearago], true, false, true);
        $notdelete4 = $generator->create_user(['username' => 'anonym10', 'firstname' => 'Anonym', 'auth' => 'shibboleth',
        'suspended' => 1, 'lastaccess' => 0]);
    }
}
