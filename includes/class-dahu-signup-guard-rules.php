<?php
/**
 * Registration rules.
 *
 * @package DahuSignupGuard
 */

defined( 'ABSPATH' ) || exit;

/**
 * Decides whether a registration goes through.
 */
class Dahu_Signup_Guard_Rules {

	/**
	 * Hook into both registration forms.
	 *
	 * WordPress passes ( WP_Error, string, string, string ) and WooCommerce
	 * ( WP_Error, string, string ), so the two are wired separately rather
	 * than through one callback with optional arguments.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'registration_errors', array( __CLASS__, 'check_wp' ), 10, 3 );
		add_filter( 'woocommerce_registration_errors', array( __CLASS__, 'check_wc' ), 10, 3 );
	}

	/**
	 * WordPress registration form.
	 *
	 * @param WP_Error $errors   Errors so far.
	 * @param string   $username Submitted username.
	 * @param string   $email    Submitted email address.
	 * @return WP_Error
	 */
	public static function check_wp( $errors, $username, $email ) {
		return self::check( $errors, (string) $username, (string) $email );
	}

	/**
	 * WooCommerce registration form.
	 *
	 * @param WP_Error $errors   Errors so far.
	 * @param string   $username Submitted username.
	 * @param string   $email    Submitted email address.
	 * @return WP_Error
	 */
	public static function check_wc( $errors, $username, $email ) {
		return self::check( $errors, (string) $username, (string) $email );
	}

	/**
	 * Apply every rule, stopping at the first that refuses.
	 *
	 * @param WP_Error $errors   Errors so far.
	 * @param string   $username Submitted username.
	 * @param string   $email    Submitted email address.
	 * @return WP_Error
	 */
	public static function check( $errors, $username, $email ) {

		if ( ! is_wp_error( $errors ) ) {
			return $errors;
		}

		if ( Dahu_Signup_Guard_Honeypot::was_tripped() ) {
			$errors->add( 'dahu-signup-guard-honeypot', __( 'Could not validate the form. Please try again.', 'ubac-signup' ) );
			Dahu_Signup_Guard_Log::add( 'honeypot', $username, $email );
			return $errors;
		}

		$hit = self::matching_pattern( Dahu_Signup_Guard_Settings::get( 'username_patterns' ), $username );
		if ( null !== $hit ) {
			$errors->add( 'dahu-signup-guard-username', __( 'This username is not allowed.', 'ubac-signup' ) );
			/* translators: %s: the blocklist entry that matched. */
			Dahu_Signup_Guard_Log::add( sprintf( __( 'username (%s)', 'ubac-signup' ), $hit ), $username, $email );
			return $errors;
		}

		$hit = self::matching_pattern( Dahu_Signup_Guard_Settings::get( 'email_patterns' ), $email );
		if ( null !== $hit ) {
			$errors->add( 'dahu-signup-guard-email', __( 'This email address cannot be used to register.', 'ubac-signup' ) );
			/* translators: %s: the blocklist entry that matched. */
			Dahu_Signup_Guard_Log::add( sprintf( __( 'email (%s)', 'ubac-signup' ), $hit ), $username, $email );
			return $errors;
		}

		if ( Dahu_Signup_Guard_Settings::get( 'block_plus_aliases' ) && self::is_plus_alias( $email ) ) {
			$errors->add( 'dahu-signup-guard-alias', __( 'Please use your main email address, without a “+” alias.', 'ubac-signup' ) );
			Dahu_Signup_Guard_Log::add( 'plus alias', $username, $email );
			return $errors;
		}

		return $errors;
	}

	/**
	 * First blocklist entry contained in the value, if any.
	 *
	 * Entries are plain text, matched case-insensitively anywhere in the
	 * value — never regular expressions, so a typo in the settings screen
	 * cannot break registration for everyone.
	 *
	 * @param string $list  Blocklist, one entry per line.
	 * @param string $value Value to test.
	 * @return string|null
	 */
	private static function matching_pattern( $list, $value ) {

		$value = strtolower( trim( (string) $value ) );

		if ( '' === $value || '' === trim( (string) $list ) ) {
			return null;
		}

		$entries = preg_split( '/\r\n|\r|\n/', (string) $list );

		foreach ( (array) $entries as $entry ) {
			$entry = trim( (string) $entry );
			if ( '' !== $entry && false !== strpos( $value, strtolower( $entry ) ) ) {
				return $entry;
			}
		}

		return null;
	}

	/**
	 * Is this a plus-alias on a provider that collapses them?
	 *
	 * Gmail and the Google-hosted domains treat `name+anything@` as `name@`,
	 * which lets one mailbox register endlessly. Other providers are left
	 * alone: elsewhere a plus address is often a legitimate, distinct one.
	 *
	 * @param string $email Email address.
	 * @return bool
	 */
	private static function is_plus_alias( $email ) {

		/**
		 * Filters the domains on which a “+” alias is refused.
		 *
		 * @since 2.0.0
		 *
		 * @param string[] $domains Lower-case domain names.
		 */
		$domains = (array) apply_filters(
			'dahu_signup_guard_plus_alias_domains',
			array( 'gmail.com', 'googlemail.com' )
		);

		$email = strtolower( trim( (string) $email ) );

		if ( false === strpos( $email, '+' ) || ! is_email( $email ) ) {
			return false;
		}

		$parts  = explode( '@', $email );
		$domain = end( $parts );
		$local  = $parts[0];

		return in_array( $domain, array_map( 'strtolower', $domains ), true )
			&& false !== strpos( $local, '+' );
	}
}
