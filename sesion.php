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
 * Prints a particular instance of jitsi
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_jitsi
 * @copyright  2019 Sergio Comerón <sergiocomeron@icloud.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(dirname(dirname(__FILE__))).'/lib/moodlelib.php');
require_once(dirname(__FILE__).'/lib.php');
$PAGE->set_url($CFG->wwwroot.'/mod/jitsi/sesion.php');
$PAGE->set_context(context_system::Instance());

$courseid = required_param('courseid', PARAM_INT);
$cmid = required_param('cmid', PARAM_INT);
$nombre = required_param('nom', PARAM_TEXT);
$sesion = required_param('ses', PARAM_TEXT);
$sesionnorm = str_replace(' ', '', $sesion);
$avatar = required_param('avatar', PARAM_TEXT);
require_login($courseid);

$PAGE->set_title($sesion);
$PAGE->set_heading($sesion);
echo $OUTPUT->header();

$context = context_module::instance($cmid);
if (!has_capability('mod/jitsi:view', $context)) {
    notice(get_string('noviewpermission', 'jitsi'));
}

// CREATE JWT TOKEN for Jitsi

$header = json_encode([
  "kid" => "jitsi/custom_key_name",
  "typ"=> "JWT",
  "alg"=> "HS256"        // Hash HMAC
]);
$payload = $header = json_encode([
  "context"=>[
    "user"=> [
      "avatar"=> $avatar,
      "name"=> $nombre,
      "email"=> "",
      "id"=> "abcd:a1b2c3-d4e5f6-0abc1-23de-abcdef01fedcba" // only for internal usage
    ],
    "group"=> "a123-123-456-789"         // only for internal usage
  ],
  "aud"=> "jitsi",
  "iss"=> $CFG->jitsi_app_id,            // Required - as JWT_APP_ID env
  "sub"=> $CFG->jitsi_domain,            // Requied: as DOMAIN env
  "room"=> "*",                          // restricted room name or * for all room
  "exp"=> Date.now()+24*3600*1000,       // unix timestamp for expiration, for example 24 hours
  "moderator" => true         // true/false for room moderator role
]);
$secret = $CFG->jitsi_secret;

// Encode Header to Base64Url String
$base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));

// Encode Payload to Base64Url String
$base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

// Create Signature Hash
$signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, 'abC123!', true);

// Encode Signature to Base64Url String
$base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

// Create JWT
$jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

echo "<script src=\"https://meet.jit.si/external_api.js\"></script>\n";
echo "<script>\n";
echo "var domain = \"".$CFG->jitsi_domain."\";\n";
echo "";
echo "var options = {\n";
echo "roomName: \"".$sesionnorm."\",\n";
if ($CFG->branch < 36) {
    echo "parentNode: document.querySelector('#region-main .card-body'),\n";
} else {
    echo "parentNode: document.querySelector('#region-main'),\n";
}
echo "width: '100%',\n";
echo "height: 650,\n";
echo "jwt: '$jwt',}\n";
echo "var api = new JitsiMeetExternalAPI(domain, options);\n";
echo "api.executeCommand('displayName', '".$nombre."');\n";
echo "api.executeCommand('toggleVideo');\n";
echo "api.executeCommand('avatarUrl', '".$avatar."');\n";
echo "</script>\n";

echo $OUTPUT->footer();
