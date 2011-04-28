<?php
/*
Plugin Name: TLS-SRP Authentication
Plugin URI: http://trustedhttp.org/TLS-SRP_Authentication_in_WordPress
Description: Use TLS-SRP authentication with WordPress.
Version: 1.0
Author: Quinn Slack
License: GPLv2
*/

/*  Copyright 2011 Quinn Slack (email: sqs at cs dot stanford dot edu)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define('TLS_SRP_AUTH_ENV_VAR', 'SSL_SRP_USER');
define('TLS_SRP_SHOW_DEBUG_INFO', true);

define('TLS_SRP_PLUGIN_VERSION', '1.0');

global $srp_metakeys, $srp_groups, $default_srp_group;;
$srp_metakeys = array('srp_v', 'srp_s', 'srp_group');
$srp_groups = array(1024, 1536, 2048);
$default_srp_group = 1024;


function tls_srp_authenticate($user) {

    $username = $_SERVER[TLS_SRP_AUTH_ENV_VAR];

    if (!$username) {
        return new WP_Error('empty_tls_srp_username', 'No TLS-SRP username');
    }

    $user = new WP_User($username);    
    $user = wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID, false, true);

    return $user;
}

function tls_srp_logout() {
    header('Location: ' . get_site_option('home'));
    exit();
}

function tls_srp_wp_footer() {
    $tls_srp_user = $_SERVER[TLS_SRP_AUTH_ENV_VAR] ?
            $_SERVER[TLS_SRP_AUTH_ENV_VAR] : "none";
    $wp_user = (wp_get_current_user()->ID) ? wp_get_current_user()->user_login : "none";
    echo "<div style='position:absolute;text-align:right;top:0;right:0;width:220px;font-size:1.9em;font-weight:bold;line-height:150%;background-color:white;color:red;font-family:sans-serif;padding:7px;border:solid 3px red;'>@TLSUser(" . $tls_srp_user . ")<br>@WPUser(" . $wp_user . ")</div>";
}

if (!function_exists('auth_redirect')) {
    function auth_redirect() {
        tls_srp_authenticate(null);
    }
}

function tls_srp_get_user_srpinfo($user_id) {
    global $srp_metakeys;
    $srpinfo = array();
    foreach ($srp_metakeys as $metakey) {
        $srpinfo[$metakey] = get_user_meta($user_id, $metakey, true);
    }
    return $srpinfo;
}

function tls_srp_edit_user_profile($user) {
    $srpinfo = tls_srp_get_user_srpinfo($user->ID);
?>
<h3>SRP user credentials</h3>
<table class="form-table">
<tr>
    <th><label for="srp_v">SRP verifier</label></th>
    <td><textarea name="srp_v" id="srp_v"><?php echo esc_attr($srpinfo['srp_v']) ?></textarea><span class="description">Base64-encoded</span></td>
</tr>
<tr>
    <th><label for="srp_s">SRP salt</label></th>
    <td><input type="text" name="srp_s" id="srp_s" value="<?php echo esc_attr($srpinfo['srp_s']) ?>" class="regular-text" /> <span class="description">Base64-encoded</span></td>
</tr>
<tr>
    <th><label for="srp_group">SRP group size</label></th>
    <td><select name="srp_group" id="srp_group">
<?php
    global $srp_groups, $default_srp_group;
    foreach ($srp_groups as $group) {
        echo "        <option value=\"$group\"";
        if ($srpinfo['srp_group'] == $group ||
            (!$srpinfo['srp_group'] && $group == $default_srp_group))
            echo " selected=\"selected\"";
        echo ">$group</option>\n";
    }
?>
    </select></td>
</tr>
</table>
<?php
}

function tls_srp_update_user_srpinfo($user_id) {
    global $srp_metakeys, $srp_groups;
    $metavals = array();
    foreach ($srp_metakeys as $key) {
        $metavals[$key] = (isset($_POST[$key]) ? $_POST[$key] : '');
    }

    // If srp_s and srp_v are empty, then don't set srp_group.
    if (!$metavals['srp_v'] && !$metavals['srp_s'])
        $metavals['srp_group'] = '';

    foreach ($srp_metakeys as $key) {
        $val = $metavals[$key];
        if ($val) {
            update_user_meta($user_id, $key, $val);
        } else {
            delete_user_meta($user_id, $key);
        }
    }
}

add_action('wp_logout', 'tls_srp_logout');
add_action('init', 'tls_srp_authenticate');

add_action('show_user_profile', 'tls_srp_edit_user_profile');
add_action('edit_user_profile', 'tls_srp_edit_user_profile');
add_action('edit_user_profile_update', 'tls_srp_update_user_srpinfo');
add_action('personal_options_update', 'tls_srp_update_user_srpinfo');

/* Add debug info to footer */
if (TLS_SRP_SHOW_DEBUG_INFO)
    add_action('wp_footer', 'tls_srp_wp_footer');

?>
