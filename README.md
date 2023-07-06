Copyright: developed and maintained by TU Darmstadt

License: http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

# userstatus_ldapchecker
A subplugin for https://github.com/learnweb/moodle-tool_cleanupusers that uses data from an external server connected with LDAP.

Settings located at /admin/settings.php?section=cleanupusers_userstatusldapchecker:

cleanupusers_csv folder contains a reworked version of archive_user_task.php from tool_cleanupusers that saves users archived by the cron in a CSV file.

## Installation
* Copy/Clone to `https://YOURSITE/admin/tool/cleanupusers/userstatus/` directory
  * Alternatively use `git clone https://github.com/eLearning-TUDarmstadt/moodle-cleanupusers_ldapchecker admin/tool/cleanupusers/userstatus/ldapchecker` in your moodle root
* Enable database upgrade
* Go to `https://YOURSITE/admin/settings.php?section=cleanupusers_userstatusldapchecker` or `Site Administration->Users->Clean up users->LDAP Checker`
