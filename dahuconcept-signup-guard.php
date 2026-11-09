<?php
/**
 * Plugin Name:       DahuConcept Signup Guard
 * Description:       Keeps spam registrations out, without a captcha: an invisible honeypot, blocklists for usernames and email addresses, and a log of what was turned away. Works on the WordPress and WooCommerce registration forms.
 * Version:           2.1.2
 * Author:            Hugo Vial-Jaime
 * Author URI:        https://dahu-concept.fr
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       dahuconcept-signup-guard
 * Domain Path:       /languages
 * Requires at least: 6.2
 * Requires PHP:      7.4
 *
 * @package UbacSignup
 */

defined( 'ABSPATH' ) || exit;

define( 'UBAC_SIGNUP_VERSION', '2.1.2' );
define( 'UBAC_SIGNUP_FILE', __FILE__ );
define( 'UBAC_SIGNUP_PATH', plugin_dir_path( __FILE__ ) );
define( 'UBAC_SIGNUP_URL', plugin_dir_url( __FILE__ ) );
define( 'UBAC_SIGNUP_SLUG', 'ubac-signup' );

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

require_once UBAC_SIGNUP_PATH . 'includes/class-ubac-signup-settings.php';
require_once UBAC_SIGNUP_PATH . 'includes/class-ubac-signup-log.php';
require_once UBAC_SIGNUP_PATH . 'includes/class-ubac-signup-honeypot.php';
require_once UBAC_SIGNUP_PATH . 'includes/class-ubac-signup-rules.php';

add_action(
	'plugins_loaded',
	static function () {
		Ubac_Signup_Settings::init();
		Ubac_Signup_Honeypot::init();
		Ubac_Signup_Rules::init();
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
			esc_url( admin_url( 'options-general.php?page=' . UBAC_SIGNUP_SLUG ) ),
			esc_html__( 'Settings', 'dahuconcept-signup-guard' )
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
				esc_html__( 'Version %s', 'dahuconcept-signup-guard' ),
				esc_html( $header['Version'] )
			),
			sprintf(
				'<a href="%s" target="_blank" rel="noopener">%s</a>',
				esc_url( 'https://dahu-concept.fr' ),
				esc_html__( 'By Hugo Vial-Jaime — Dahu-Concept', 'dahuconcept-signup-guard' )
			),
			sprintf(
				'<a href="%s" target="_blank" rel="noopener">%s</a>',
				esc_url( 'https://dahu-concept.fr' ),
				esc_html__( 'Plugin website', 'dahuconcept-signup-guard' )
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
require_once UBAC_SIGNUP_PATH . 'includes/ubac-welcome.php';

/*
 * The flag, not a redirect: an activation hook that redirects breaks bulk
 * activation and WP-CLI, which activate several plugins in one request. The
 * welcome screen reads the flag on the next page load, where a redirect is
 * safe, and clears it when several plugins were activated together.
 */
register_activation_hook(
	__FILE__,
	static function () {
		Ubac_Welcome::mark_activated( 'dahuconcept-signup-guard' );
	}
);

add_action(
	'init',
	static function () {
		Ubac_Welcome::register(
			array(
				'slug'       => 'dahuconcept-signup-guard',
				'name'       => __( 'Signup guard', 'dahuconcept-signup-guard' ),
				'tagline'    => __( 'Keeps robots out of the registration forms, and logs what it turned away.', 'dahuconcept-signup-guard' ),
				'file'       => UBAC_SIGNUP_FILE,
				'version'    => UBAC_SIGNUP_VERSION,
				'textdomain' => 'dahuconcept-signup-guard',
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
					'next'   => __( 'Next', 'dahuconcept-signup-guard' ),
					'done'   => __( 'Done', 'dahuconcept-signup-guard' ),
					'skip'   => __( 'Skip', 'dahuconcept-signup-guard' ),
					'of'     => __( 'of', 'dahuconcept-signup-guard' ),
					'denied' => __( 'You are not allowed to view this page.', 'dahuconcept-signup-guard' ),
				),
				'points'     => array(
				array(
					'title' => __( 'An invisible trap', 'dahuconcept-signup-guard' ),
					'text'  => __( 'A hidden field real visitors never see.', 'dahuconcept-signup-guard' ),
				),
				array(
					'title' => __( 'Your own patterns', 'dahuconcept-signup-guard' ),
					'text'  => __( 'Refuse usernames and addresses you recognise.', 'dahuconcept-signup-guard' ),
				),
				array(
					'title' => __( 'A log that is read', 'dahuconcept-signup-guard' ),
					'text'  => __( 'What was blocked, when, and why.', 'dahuconcept-signup-guard' ),
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
				'tour_screen' => 'settings_page_ubac-signup',
				'tour'        => array(
					array(
						'selector' => 'input[name="ubac_signup_settings[honeypot]"]',
						'title'    => __( 'Already protecting you', 'dahuconcept-signup-guard' ),
						'text'     => __( 'The invisible trap is on by default, and stops most bots on its own.', 'dahuconcept-signup-guard' ),
					),
					array(
						'selector' => '#ubac-signup-username-patterns',
						'title'    => __( 'Add what you recognise', 'dahuconcept-signup-guard' ),
						'text'     => __( 'One entry per line, plain text. A username containing any of them is refused.', 'dahuconcept-signup-guard' ),
					),
					array(
						'selector' => '.ubac-ui-card__title',
						'title'    => __( 'See what was turned away', 'dahuconcept-signup-guard' ),
						'text'     => __( 'Each refused registration is listed here, with the reason it was refused.', 'dahuconcept-signup-guard' ),
					),
				),
			)
		);
	}
);
