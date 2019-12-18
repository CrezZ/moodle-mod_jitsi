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
$displayname=$USER->firstname.' '.$USER->lastname;
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

$rolestr = array();
$context = context_course::instance($courseid);
$roles = get_user_roles($context, $USER->id);
$is_teacher=true;
foreach ($roles as $role) {
$rolestr[] = $role->shortname;
$is_teacher=$is_teacher && ($role->shortname!='student') && ($role->shortname!='guest');
}
$rolestr = implode(', ', $rolestr);
echo " {$rolestr} in course {$courseid}";


// CREATE JWT TOKEN for Jitsi

$roomname='moodle'.$courseid;

$header = json_encode([
  "kid" => "jitsi/custom_key_name",
  "typ"=> "JWT",
  "alg"=> "HS256"        // Hash HMAC
],JSON_UNESCAPED_SLASHES);
$payload  = json_encode([
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
  "exp"=> time()+24*3600,       // unix timestamp for expiration, for example 24 hours
  "moderator" => $is_teacher         // true/false for room moderator role
],JSON_UNESCAPED_SLASHES);
$secret = $CFG->jitsi_secret;

// Encode Header to Base64Url String
$base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));

// Encode Payload to Base64Url String
$base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

// Create Signature Hash
$signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);

// Encode Signature to Base64Url String
$base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

// Create JWT
$jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;


$add1=
 "<script src=\"https://meet.jit.si/external_api.js\"></script>\n"
. "<script>\n"
. "var domain = \"".$CFG->jitsi_domain."\";\n"
. "var options = {\n"
. "roomName: \"moodle".$courseid."\",\n";
$add3='';
if ($CFG->branch < 36) {
    $add3= "parentNode: document.querySelector('#region-main .card-body'),\n";
} else {
    $add3= "parentNode: document.querySelector('#region-main'),\n";
}
$add2 = "width: '100%',\n"
. "height: 650,\n"
. "interfaceConfigOverwrite: {
	DEFAULT_BACKGROUND: 'white',
	TOOLBAR_ALWAYS_VISIBLE: true,
	DEFAULT_REMOTE_DISPLAY_NAME: 'USER',
	SHOW_JITSI_WATERMARK: false,
	SHOW_BRAND_WATERMARK: true,
	BRAND_WATERMARK_LINK: 'http://osu.ru/img/skins/55years/head-logo.jpg',
	DISPLAY_WELCOME_PAGE_CONTENT: false,
	INVITATION_POWERED_BY: false,
	TOOLBAR_BUTTONS: [
        'microphone', 'camera', 'closedcaptions', 'desktop', 'fullscreen',
        'fodeviceselection', 'hangup', 'profile', 'info', 'chat', 'recording',
        'livestreaming', 'etherpad', 'sharedvideo', 'settings', 'raisehand',
        'videoquality', 'filmstrip', 'invite', 'feedback', 'stats', 'shortcuts',
        'tileview', 'videobackgroundblur', 'download'
    ],
	 SETTINGS_SECTIONS: [ 'devices', 'language', 'moderator'],
	LIVE_STREAMING_HELP_LINK: 'http://www.osu.ru',
	SUPPORT_URL: 'https://www.osu.ru'
	
	},"
. "jwt: '$jwt'}\n"
. "var api = new JitsiMeetExternalAPI(domain, options);
	Window.api=api;\n"
. "api.executeCommand('displayName', '".$displayname."');\n"
//echo "api.executeCommand('toggleVideo');\n"; // TODO - student cannot see others
. "api.executeCommand('avatarUrl', '".$avatar."');\n"
. "</script>\n";

$base64add=base64_encode($add1."parentNode:undefined,\n".$add2);

echo "<script>
function OpenJitsiNewWindow(){
w=window.open('','Moodle Video','resizeable=1');
w.document.open();
w.document.write('<html><head></head><body>'+atob('$base64add'));
w.document.close();
}
</script>
<a href='#' onclick='OpenJitsiNewWindow();return false;'>В новом окне</a>
";

echo $add1.$add3.$add2;

echo $OUTPUT->footer();
