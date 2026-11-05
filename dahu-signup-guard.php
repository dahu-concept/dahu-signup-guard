<?php
/**
 * Plugin Name:       Dahu Signup Guard
 * Description:       Keeps spam registrations out, without a captcha: an invisible honeypot, blocklists for usernames and email addresses, and a log of what was turned away. Works on the WordPress and WooCommerce registration forms.
 * Version:           2.1.0
 * Author:            Hugo Vial-Jaime
 * Author URI:        https://dahu-concept.fr
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       dahu-signup-guard
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 *
 * @package DahuSignupGuard
 */

defined( 'ABSPATH' ) || exit;

define( 'DAHU_SIGNUP_GUARD_VERSION', '2.1.0' );
define( 'DAHU_SIGNUP_GUARD_FILE', __FILE__ );
define( 'DAHU_SIGNUP_GUARD_PATH', plugin_dir_path( __FILE__ ) );
define( 'DAHU_SIGNUP_GUARD_URL', plugin_dir_url( __FILE__ ) );
define( 'DAHU_SIGNUP_GUARD_SLUG', 'dahu-signup-guard' );

/* -------------------------------------------------------------------------
 * Translations
 *
 * There is deliberately no load_plugin_textdomain() call here. Since
 * WordPress 4.6 the translations of a plugin hosted on WordPress.org are
 * loaded on demand, and the directory's review asks that the call be removed
 * rather than kept: this plugin requires 6.0, so there is no older WordPress
 * to cover. Translations come from translate.wordpress.org.
 * ---------------------------------------------------------------------- */

/* -------------------------------------------------------------------------
 * Bootstrap
 *
 * No WooCommerce guard: the plugin protects the WordPress registration form
 * on its own, and simply also covers WooCommerce's when it is present.
 * ---------------------------------------------------------------------- */

require_once DAHU_SIGNUP_GUARD_PATH . 'includes/class-dahu-signup-guard-settings.php';
require_once DAHU_SIGNUP_GUARD_PATH . 'includes/class-dahu-signup-guard-log.php';
require_once DAHU_SIGNUP_GUARD_PATH . 'includes/class-dahu-signup-guard-honeypot.php';
require_once DAHU_SIGNUP_GUARD_PATH . 'includes/class-dahu-signup-guard-rules.php';

add_action(
	'plugins_loaded',
	static function () {
		Dahu_Signup_Guard_Settings::init();
		Dahu_Signup_Guard_Honeypot::init();
		Dahu_Signup_Guard_Rules::init();
	}
);

/* -------------------------------------------------------------------------
 * Plugins screen
 * ---------------------------------------------------------------------- */

add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	static function ( $links ) {
		$settings = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'options-general.php?page=' . DAHU_SIGNUP_GUARD_SLUG ) ),
			esc_html__( 'Settings', 'dahu-signup-guard' )
		);
		array_unshift( $links, $settings );
		return $links;
	}
);

add_filter(
	'plugin_row_meta',
	static function ( $links, $file ) {

		if ( plugin_basename( __FILE__ ) !== $file ) {
			return $links;
		}

		$header = get_file_data( __FILE__, array( 'Version' => 'Version' ) );

		return array(
			sprintf(
				/* translators: %s: plugin version number. */
				esc_html__( 'Version %s', 'dahu-signup-guard' ),
				esc_html( $header['Version'] )
			),
			sprintf(
				'<a href="%s" target="_blank" rel="noopener">%s</a>',
				esc_url( 'https://dahu-concept.fr' ),
				esc_html__( 'By Hugo Vial-Jaime — Dahu-Concept', 'dahu-signup-guard' )
			),
			sprintf(
				'<a href="%s" target="_blank" rel="noopener">%s</a>',
				esc_url( 'https://dahu-concept.fr' ),
				esc_html__( 'Plugin website', 'dahu-signup-guard' )
			),
		);
	},
	10,
	2
);

/*
 * The welcome screen, shown once just after activation.
 *
 * Registered here rather than inside the admin include, because the
 * activation flag has to be set whatever else is loaded.
 */
require_once DAHU_SIGNUP_GUARD_PATH . 'includes/dahu-welcome.php';

/*
 * The flag, not a redirect: an activation hook that redirects breaks bulk
 * activation and WP-CLI, which activate several plugins in one request. The
 * welcome screen reads the flag on the next page load, where a redirect is
 * safe, and clears it when several plugins were activated together.
 */
register_activation_hook(
	__FILE__,
	static function () {
		Dahu_Welcome::mark_activated( 'dahu-signup-guard' );
	}
);

add_action(
	'init',
	static function () {
		Dahu_Welcome::register(
			array(
				'slug'       => 'dahu-signup-guard',
				'name'       => __( 'Signup guard', 'dahu-signup-guard' ),
				'tagline'    => __( 'Keeps robots out of the registration forms, and logs what it turned away.', 'dahu-signup-guard' ),
				'file'       => DAHU_SIGNUP_GUARD_FILE,
				'version'    => DAHU_SIGNUP_GUARD_VERSION,
				'textdomain' => 'dahu-signup-guard',
				'mark'       => array(
					'view'  => '0 0 112 112',
					'paths' => array(
						'M56 6 L102 22 L102 60 Q102 92 56 108 Q10 92 10 60 L10 22 Z',
						'M36 58 L50 72 L78 42',
					),
				),
				/*
				 * The welcome screen's words come from here: the shared file is
				 * copied byte for byte across the whole family, so it can carry
				 * no text domain of its own.
				 */
				'strings'    => array(
					'next'   => __( 'Next', 'dahu-signup-guard' ),
					'done'   => __( 'Done', 'dahu-signup-guard' ),
					'skip'   => __( 'Skip', 'dahu-signup-guard' ),
					'of'     => __( 'of', 'dahu-signup-guard' ),
					'denied' => __( 'You are not allowed to view this page.', 'dahu-signup-guard' ),
				),
				'points'     => array(
				array(
					'title' => __( 'An invisible trap', 'dahu-signup-guard' ),
					'text'  => __( 'A hidden field real visitors never see.', 'dahu-signup-guard' ),
				),
				array(
					'title' => __( 'Your own patterns', 'dahu-signup-guard' ),
					'text'  => __( 'Refuse usernames and addresses you recognise.', 'dahu-signup-guard' ),
				),
				array(
					'title' => __( 'A log that is read', 'dahu-signup-guard' ),
					'text'  => __( 'What was blocked, when, and why.', 'dahu-signup-guard' ),
				),
				),

				/*
				 * The one-time tour.
				 *
				 * Every selector below points at something this screen always
				 * renders. A step whose target is missing is skipped without a
				 * sound, so a state-dependent target produces a shorter tour --
				 * or, if none survive, no tour at all and no error. The log
				 * table and its "Clear the log" button are exactly that trap:
				 * both are only rendered once something has been blocked, and
				 * a shop seeing this tour has just installed the plugin, so
				 * its log is empty. The log card's heading is used instead,
				 * which is rendered either way.
				 */
				'tour_screen' => 'settings_page_dahu-signup-guard',
				'tour'        => array(
					array(
						'selector' => 'input[name="dahu_signup_guard_settings[honeypot]"]',
						'title'    => __( 'Already protecting you', 'dahu-signup-guard' ),
						'text'     => __( 'The invisible trap is on by default, and stops most bots on its own.', 'dahu-signup-guard' ),
					),
					array(
						'selector' => '#dahu-signup-guard-username-patterns',
						'title'    => __( 'Add what you recognise', 'dahu-signup-guard' ),
						'text'     => __( 'One entry per line, plain text. A username containing any of them is refused.', 'dahu-signup-guard' ),
					),
					array(
						'selector' => '.dahu-ui-card__title',
						'title'    => __( 'See what was turned away', 'dahu-signup-guard' ),
						'text'     => __( 'Each refused registration is listed here, with the reason it was refused.', 'dahu-signup-guard' ),
					),
				),
			)
		);
	}
);
