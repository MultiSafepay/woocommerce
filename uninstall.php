<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * @see        https://developer.wordpress.org/plugins/plugin-basics/uninstall-methods/
 * @since      4.0.0
 * @todo       Define the actions needed on uninstall. Consider the following:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication. See: https://codex.wordpress.org/WordPress_Nonces
 * - Consider the user roles.
 * - Consider multisite configurations.
 * - Don`t delete settings or anything on database at least user select this options on plugin settings
 *
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die();
}
