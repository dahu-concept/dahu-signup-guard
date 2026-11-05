<?php
/**
 * Uninstall routine.
 *
 * Everything this plugin stores is its own: two blocklists, three switches,
 * a log of refused attempts and the salt naming the honeypot field. None of
 * it is content the site would miss, so all of it goes.
 *
 * @package UbacSignup
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Options created by this plugin.
 */
const UBAC_SIGNUP_UNINSTALL_OPTIONS = array(
	'ubac_signup_settings',
	'ubac_signup_log',
	'ubac_signup_field_salt',
);

/**
 * Clean one site.
 *
 * @return void
 */
function ubac_signup_uninstall_site() {

	// The welcome screen's own three flags.
	//
	// They are interface state, not the shop's data, which is why they sit
	// BEFORE any “remove data on uninstall” guard: whatever a shop chose to
	// keep, a reinstall has to greet it again. `includes/ubac-welcome.php`
	// writes them with update_option(), so they outlive the plugin unless
	// they are deleted here — and the welcome screen and the guided tour
	// would then stay silent for good, with nothing on screen to explain why.
	delete_option( 'ubac_welcome_seen_ubac-signup' );
	delete_option( 'ubac_welcome_pending_ubac-signup' );
	delete_option( 'ubac_welcome_tour_ubac-signup' );
	foreach ( UBAC_SIGNUP_UNINSTALL_OPTIONS as $option ) {
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
		ubac_signup_uninstall_site();
		restore_current_blog();
	}
} else {
	ubac_signup_uninstall_site();
}
