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


add_action('wp_logout', 'tls_srp_logout');
add_action('init', 'tls_srp_authenticate');

/* Add debug info to footer */
if (TLS_SRP_SHOW_DEBUG_INFO)
    add_action('wp_footer', 'tls_srp_wp_footer');

?>
