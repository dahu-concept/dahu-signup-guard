<?php
/**
 * Uninstall routine.
 *
 * Everything this plugin stores is its own: two blocklists, three switches,
 * a log of refused attempts and the salt naming the honeypot field. None of
 * it is content the site would miss, so all of it goes.
 *
 * @package DahuSignupGuard
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Options created by this plugin.
 */
const DAHU_SIGNUP_GUARD_UNINSTALL_OPTIONS = array(
	'dahu_signup_guard_settings',
	'dahu_signup_guard_log',
	'dahu_signup_guard_field_salt',
);

/**
 * Clean one site.
 *
 * @return void
 */
function dahu_signup_guard_uninstall_site() {

	// The welcome screen's own three flags.
	//
	// They are interface state, not the shop's data, which is why they sit
	// BEFORE any “remove data on uninstall” guard: whatever a shop chose to
	// keep, a reinstall has to greet it again. `includes/dahu-welcome.php`
	// writes them with update_option(), so they outlive the plugin unless
	// they are deleted here — and the welcome screen and the guided tour
	// would then stay silent for good, with nothing on screen to explain why.
	delete_option( 'dahu_welcome_seen_dahu-signup-guard' );
	delete_option( 'dahu_welcome_pending_dahu-signup-guard' );
	delete_option( 'dahu_welcome_tour_dahu-signup-guard' );
	foreach ( DAHU_SIGNUP_GUARD_UNINSTALL_OPTIONS as $option ) {
		delete_option( $option );
	}
}

if ( is_multisite() ) {
	$site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $site_ids as $site_id ) {
		switch_to_blog( $site_id );
		dahu_signup_guard_uninstall_site();
		restore_current_blog();
	}
} else {
	dahu_signup_guard_uninstall_site();
}
