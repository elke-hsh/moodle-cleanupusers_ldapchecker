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
 * Sub-plugin ldapchecker.
 *
 * @package   userstatus_ldapchecker
 * @copyright 2016/17 N. Herrmann
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace userstatus_ldapchecker;

use tool_cleanupusers\archiveduser;
use tool_cleanupusers\userstatusinterface;

defined('MOODLE_INTERNAL') || die;

/**
 * Class that checks the status of different users depending on the time they have not signed in for.
 *
 * @package    userstatus_ldapchecker
 * @copyright  2016/17 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class ldapchecker implements userstatusinterface {

    /** @var int seconds until a user should be deleted */
    private $timedelete;

    /** @var array lookuptable for ldap users */
    private $lookup = array();

    /**
     * This constructor sets timesuspend and timedelete from days to seconds.
     * @throws \dml_exception
     */
    public function __construct($testing = false) {

        $config = get_config('userstatus_ldapchecker');

        // Calculates days to seconds.
        $this->timedelete = $config->deletetime * 86400;

        // Only connect to LDAP if we are not in testing case
        if ($testing === false) {

            $ldap = ldap_connect($config->host_url) or die("Could not connect to $config->host_url");

            $bind = ldap_bind($ldap, $config->bind_dn, $config->bind_pw); // returns 1 bzw true falls korrekt

            if($bind) {
                $contexts = $config->contexts;

                $attributes = array('cn');
                $filter = '(cn=*)';
                $search = ldap_search($ldap, $contexts, $filter, $attributes) or die("Error in search Query: " . ldap_error($ldap));
                $result = ldap_get_entries($ldap, $search);

                foreach ($result as $user) {
                    if(isset($user['cn'])) {
                        foreach ($user['cn'] as $cn) {
                            $this->lookup[$cn] = true;
                        }
                    }
                }
            }
        }

    }

    /**
     * All users who are not suspended and not deleted are selected. If a user did not sign in for the hitherto
     * determined suspendtime he/she will be returned.
     * Users not signed in for the hitherto determined suspendtime, do not show up in the ldap lookuptable.
     * The array includes merely the necessary information which comprises the userid, lastaccess, suspended, deleted
     * and the username.
     *
     * @return array of users to suspend
     * @throws \dml_exception
     */
    public function get_to_suspend() {
        global $DB;

        $select = "auth='cas' AND deleted=0 AND suspended=0";
        $users = $DB->get_records_select('user', $select);
        $tosuspend = array();

        foreach ($users as $key => $user) {
            if (!is_siteadmin($user) && !array_key_exists($user->username, $this->lookup)) {
                $informationuser = new archiveduser($user->id, $user->suspended, $user->lastaccess, $user->username, $user->deleted);
                $tosuspend[$key] = $informationuser;
            }
        }

        return $tosuspend;
    }

    /**
     * All users who never logged in will be returned in the array.
     * The array includes merely the necessary information which comprises the userid, lastaccess, suspended, deleted
     * and the username.
     *
     * @return array of users who never logged in
     * @throws \dml_exception
     */
    public function get_never_logged_in() {
        global $DB;
        $select = "auth='cas' AND lastaccess=0 AND deleted=0 AND firstname!='Anonym'";
        $arrayofuser = $DB->get_records_select('user', $select);
        $neverloggedin = array();
        foreach ($arrayofuser as $key => $user) {
            if (empty($user->lastaccess) && $user->deleted == 0) {
                $informationuser = new archiveduser($user->id, $user->suspended, $user->lastaccess, $user->username,
                    $user->deleted);
                $neverloggedin[$key] = $informationuser;
            }
        }

        return $neverloggedin;
    }

    /**
     * All users who should be deleted will be returned in the array.
     * The array includes merely the necessary information which comprises the userid, lastaccess, suspended, deleted
     * and the username.
     * The function checks the user table and the tool_cleanupusers_archive table. Therefore, users who are suspended by
     * the tool_cleanupusers plugin and users who are suspended manually are screened.
     *
     * @return array of users who should be deleted.
     * @throws \dml_exception
     */
    public function get_to_delete() {
        global $DB;

        // Select clause for users who are suspended.
		$select = "auth='cas' AND deleted=0 AND suspended=1 AND (lastaccess!=0 OR firstname='Anonym')";
        $users = $DB->get_records_select('user', $select);
        $todeleteusers = array();

        // Users who are not suspended by the plugin but are marked as suspended in the main table.
        foreach ($users as $key => $user) {
            // Additional check for deletion, lastaccess and admin.
            if ($user->deleted == 0 && !is_siteadmin($user)) {
                $mytimestamp = time();

                // User was suspended by the plugin.
                if ($user->firstname == 'Anonym' && $user->lastaccess == 0) {
                    $select = 'id=' . $user->id;

                    $record = $DB->get_records_select('tool_cleanupusers', $select);
                    if (!empty($record) && $record[$user->id]->timestamp != 0) {
                        $suspendedbyplugin = true;
                        $timearchived = $DB->get_record('tool_cleanupusers', array('id' => $user->id), 'timestamp');
                        $timenotloggedin = $mytimestamp - $timearchived->timestamp;
                    } else {
                        // Users firstname is Anonym, although he is not in the plugin table. It can not be determined
                        // when the user was suspended therefore he/she can not be handled.
                        continue;
                    }
                } else if ($user->lastaccess != 0) {
                    // User was suspended manually.
                    $suspendedbyplugin = false;
                    $timenotloggedin = $mytimestamp - $user->lastaccess;
                } else {
                    // The user was not suspended by the plugin but does not have a last access, therefore he/she is
                    // not handled. This should not happen due to the select clause.
                    continue;
                }

                // When the user did not sign in for the timedeleted he/she should be deleted.
                if ($timenotloggedin > $this->timedelete && $user->suspended == 1) {
                    if ($suspendedbyplugin) {
                        // Users who are suspended by the plugin, therefore the plugin table is used.
                        $select = 'id=' . $user->id;
                        $pluginuser = $DB->get_record_select('tool_cleanupusers_archive', $select);
                        $informationuser = new archiveduser($pluginuser->id, $pluginuser->suspended,
                            $pluginuser->lastaccess, $pluginuser->username, $pluginuser->deleted);
                    } else {
                        $informationuser = new archiveduser($user->id, $user->suspended, $user->lastaccess,
                            $user->username, $user->deleted);
                    }
                    $todeleteusers[$key] = $informationuser;
                }
            }
        }

        return $todeleteusers;
    }

    /**
     * All users that should be reactivated will be returned.
     *
     * User should be reactivated when their lastaccess is smaller than the timesuspend variable. Although users are
     * not able to sign in when they are flagged as suspended, this is necessary to react when the timesuspended setting
     * is changed.
     *
     * @return array of objects
     * @throws \dml_exception
     * @throws \dml_exception
     */
    public function get_to_reactivate() {
        global $DB;
        // Only users who are currently suspended are relevant.
        $select = "auth='cas' AND deleted=0 AND suspended=1";
        $users = $DB->get_records_select('user', $select);
        $toactivate = array();

        foreach ($users as $key => $user) {

            if (!is_siteadmin($user) && !empty($user->username) && array_key_exists($user->username, $this->lookup)) {

                $shadowtableuser = $DB->get_record('tool_cleanupusers_archive', array('id' => $user->id));

                if($shadowtableuser) {
                    $user = $shadowtableuser;
                }

                $activateuser = new archiveduser($user->id, $user->suspended, $user->lastaccess,
                                                 $user->username, $user->deleted);

                $toactivate[$key] = $activateuser;

            }
        }

        return $toactivate;
    }

    public function fill_ldap_response_for_testing($dummy_ldap) {
        $this->lookup = $dummy_ldap;
    }

}
