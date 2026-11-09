<?php
/**
 * Invisible honeypot field.
 *
 * @package UbacSignup
 */

defined( 'ABSPATH' ) || exit;

/**
 * Prints a field a human never sees and a careless bot always fills.
 */
class Ubac_Signup_Honeypot {

	/**
	 * Option holding the per-site salt used to name the field.
	 *
	 * @var string
	 */
	const SALT_OPTION = 'ubac_signup_field_salt';

	/**
	 * Hook the field onto both registration forms.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'register_form', array( __CLASS__, 'render' ) );
		add_action( 'woocommerce_register_form', array( __CLASS__, 'render' ) );
	}

	/**
	 * Name of the trap field.
	 *
	 * Derived from a salt generated once per site rather than hardcoded. A
	 * fixed name is a signature: a bot written against this plugin would
	 * recognise it everywhere and simply leave it alone. A name that differs
	 * from site to site has to be worked out again each time, which is the
	 * whole point of a honeypot.
	 *
	 * @return string
	 */
	public static function field_name() {

		$salt = get_option( self::SALT_OPTION, '' );

		if ( ! is_string( $salt ) || '' === $salt ) {
			$salt = wp_generate_password( 20, false, false );
			update_option( self::SALT_OPTION, $salt, false );
		}

		// A plausible-looking field name, so it does not stand out in the DOM.
		return 'contact_' . substr( md5( 'ubac-signup' . $salt ), 0, 10 );
	}

	/**
	 * Print the field.
	 *
	 * Hidden off-canvas rather than with `display:none`, which some bots
	 * check for, and marked aria-hidden with tabindex -1 so that a screen
	 * reader and the keyboard both skip it.
	 *
	 * @return void
	 */
	public static function render() {

		if ( ! Ubac_Signup_Settings::get( 'honeypot' ) ) {
			return;
		}

		$name = self::field_name();
		?>
		<p style="position:absolute !important;left:-9999px !important;top:-9999px !important;height:0;width:0;overflow:hidden;" aria-hidden="true">
			<label for="<?php echo esc_attr( $name ); ?>">
				<?php esc_html_e( 'Leave this field empty', 'dahuconcept-signup-guard' ); ?>
			</label>
			<input type="text"
				id="<?php echo esc_attr( $name ); ?>"
				name="<?php echo esc_attr( $name ); ?>"
				value=""
				tabindex="-1"
				autocomplete="off" />
		</p>
		<?php
	}

	/**
	 * Was the trap field filled in?
	 *
	 * @return bool
	 */
	public static function was_tripped() {

		if ( ! Ubac_Signup_Settings::get( 'honeypot' ) ) {
			return false;
		}

		$name = self::field_name();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- reading the registration form WordPress itself is validating; the value is only tested for emptiness.
		if ( ! isset( $_POST[ $name ] ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- see above.
		return '' !== trim( sanitize_text_field( wp_unslash( $_POST[ $name ] ) ) );
	}
}
