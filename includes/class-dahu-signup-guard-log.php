<?php
/**
 * Log of blocked registration attempts.
 *
 * @package DahuSignupGuard
 */

defined( 'ABSPATH' ) || exit;

/**
 * Records what was turned away, and how the visitor's address is handled.
 */
class Dahu_Signup_Guard_Log {

	/**
	 * Option holding the log. Never autoloaded.
	 *
	 * @var string
	 */
	const OPTION = 'dahu_signup_guard_log';

	/**
	 * Entries kept. Older ones are dropped.
	 *
	 * @var int
	 */
	const MAX = 200;

	/**
	 * Read the log.
	 *
	 * @return array<int, array<string, string>>
	 */
	public static function get() {
		$log = get_option( self::OPTION, array() );
		return is_array( $log ) ? $log : array();
	}

	/**
	 * Empty the log.
	 *
	 * @return void
	 */
	public static function clear() {
		update_option( self::OPTION, array(), false );
	}

	/**
	 * Record one blocked attempt.
	 *
	 * @param string $reason   Why it was blocked.
	 * @param string $username Submitted username.
	 * @param string $email    Submitted email address.
	 * @return void
	 */
	public static function add( $reason, $username, $email ) {

		$log = self::get();

		array_unshift(
			$log,
			array(
				'date'     => current_time( 'mysql' ),
				'reason'   => sanitize_text_field( (string) $reason ),
				'username' => sanitize_text_field( (string) $username ),
				'email'    => sanitize_text_field( (string) $email ),
				'ip'       => self::visitor_ip(),
			)
		);

		if ( count( $log ) > self::MAX ) {
			$log = array_slice( $log, 0, self::MAX );
		}

		update_option( self::OPTION, $log, false );
	}

	/**
	 * The visitor's address, as it should be stored.
	 *
	 * Two deliberate choices here.
	 *
	 * Proxy headers are NOT trusted by default. `X-Forwarded-For` and friends
	 * are set by the client unless a proxy in front of the site overwrites
	 * them, so trusting them out of the box means anyone can write whatever
	 * address they like into this log — which is worse than having no address
	 * at all. A site genuinely behind a proxy opts in through the filter.
	 *
	 * The address is anonymised by default, because an IP address is personal
	 * data and a log of registration attempts is not a reason to keep one in
	 * full. The last octet of an IPv4 address, and the last 80 bits of an
	 * IPv6 one, are zeroed — enough to recognise a repeat offender's network,
	 * not enough to single out a person.
	 *
	 * @return string
	 */
	public static function visitor_ip() {

		$raw = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		/**
		 * Filters the HTTP headers trusted to carry the visitor's address.
		 *
		 * Empty by default. Only add a header when a proxy you control
		 * overwrites it on every request; otherwise a visitor can forge it.
		 *
		 *     add_filter( 'dahu_signup_guard_trusted_ip_headers', static function () {
		 *         return array( 'HTTP_CF_CONNECTING_IP' );
		 *     } );
		 *
		 * @since 2.0.0
		 *
		 * @param string[] $headers $_SERVER keys, in order of preference.
		 */
		$headers = (array) apply_filters( 'dahu_signup_guard_trusted_ip_headers', array() );

		foreach ( $headers as $header ) {
			if ( empty( $_SERVER[ $header ] ) ) {
				continue;
			}
			$value = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
			$first = trim( explode( ',', $value )[0] );
			if ( '' !== $first ) {
				$raw = $first;
				break;
			}
		}

		if ( '' === $raw || ! filter_var( $raw, FILTER_VALIDATE_IP ) ) {
			return '—';
		}

		if ( Dahu_Signup_Guard_Settings::get( 'store_full_ip' ) ) {
			return $raw;
		}

		return self::anonymise( $raw );
	}

	/**
	 * Drop the host part of an address.
	 *
	 * @param string $ip Valid IP address.
	 * @return string
	 */
	public static function anonymise( $ip ) {

		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			$parts = explode( '.', $ip );
			$parts[3] = '0';
			return implode( '.', $parts );
		}

		$packed = @inet_pton( $ip ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- already validated, guards against a platform without IPv6.

		if ( false === $packed || 16 !== strlen( $packed ) ) {
			return '—';
		}

		// Keep the first 48 bits, zero the rest.
		$masked = substr( $packed, 0, 6 ) . str_repeat( "\0", 10 );

		$out = @inet_ntop( $masked ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- see above.

		return $out ? $out : '—';
	}
}
