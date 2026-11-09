<?php
/**
 * Settings screen and stored options.
 *
 * @package UbacSignup
 */

defined( 'ABSPATH' ) || exit;

/**
 * Holds the options and renders the settings page.
 */
class Ubac_Signup_Settings {

	/**
	 * Option name.
	 *
	 * @var string
	 */
	const OPTION = 'ubac_signup_settings';

	/**
	 * Settings group.
	 *
	 * @var string
	 */
	const GROUP = 'ubac_signup_group';

	/**
	 * Capability required.
	 *
	 * @var string
	 */
	const CAPABILITY = 'manage_options';

	/**
	 * Hook everything up.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_style' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_clear_log' ) );
	}

	/**
	 * Default values, which also whitelist the accepted keys.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return array(
			'honeypot'           => 1,
			'block_plus_aliases' => 0,
			'username_patterns'  => '',
			'email_patterns'     => '',
			'store_full_ip'      => 0,
		);
	}

	/**
	 * All options, merged over the defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function all() {
		$stored = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $stored ) ? $stored : array(), self::defaults() );
	}

	/**
	 * One option.
	 *
	 * @param string $key Option key.
	 * @return mixed
	 */
	public static function get( $key ) {
		$all = self::all();
		return array_key_exists( $key, $all ) ? $all[ $key ] : null;
	}

	/**
	 * Add the settings page.
	 *
	 * @return void
	 */
	public static function register_menu() {
		add_options_page(
			__( 'DahuConcept Signup Guard', 'dahuconcept-signup-guard' ),
			__( 'Signup Guard', 'dahuconcept-signup-guard' ),
			self::CAPABILITY,
			UBAC_SIGNUP_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Register the option and its sanitising callback.
	 *
	 * @return void
	 */
	/**
	 * Loads this screen's stylesheet, on this screen only.
	 *
	 * @param string $hook Current admin page hook suffix.
	 * @return void
	 */
	public static function enqueue_style( $hook ) {

		if ( 'settings_page_' . UBAC_SIGNUP_SLUG !== $hook ) {
			return;
		}

		// The family sheet first, and its own second: the plugin's own rules
		// are written to sit on top of the shared components, so the order is
		// a dependency and not a hope. One handle across the family -- the
		// file is byte-identical everywhere, so whichever plugin loads it
		// first serves all of them, and a shop with six of these installed
		// downloads one copy.
		wp_enqueue_style(
			'ubac-ui',
			UBAC_SIGNUP_URL . 'assets/css/ubac-ui.css',
			array(),
			UBAC_SIGNUP_VERSION
		);

		wp_enqueue_style(
			'ubac-signup-admin',
			UBAC_SIGNUP_URL . 'assets/css/admin.css',
			array( 'ubac-ui' ),
			UBAC_SIGNUP_VERSION
		);
	}

	public static function register_settings() {
		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * Sanitise submitted settings.
	 *
	 * @param mixed $input Raw submitted value.
	 * @return array<string, mixed>
	 */
	public static function sanitize( $input ) {

		$clean = self::defaults();

		if ( ! is_array( $input ) ) {
			return $clean;
		}

		foreach ( array( 'honeypot', 'block_plus_aliases', 'store_full_ip' ) as $flag ) {
			$clean[ $flag ] = empty( $input[ $flag ] ) ? 0 : 1;
		}

		foreach ( array( 'username_patterns', 'email_patterns' ) as $list ) {
			if ( isset( $input[ $list ] ) ) {
				// NO wp_unslash() HERE. `wp-admin/options.php` unslashes what it
				// hands to a sanitize_callback, so what arrives already holds
				// real backslashes — and these patterns are matched as LITERAL
				// text by Ubac_Signup_Rules::matching_pattern(), with
				// strpos(). A pattern that lost a backslash would simply stop
				// matching, so the guard would quietly stop guarding against
				// it. WordPress also sanitises an option twice on a first save
				// (`update_option()` then `add_option()`), which made the loss
				// cumulative.
				$clean[ $list ] = sanitize_textarea_field( $input[ $list ] );
			}
		}

		return $clean;
	}

	/**
	 * Handle the "clear log" button.
	 *
	 * @return void
	 */
	public static function maybe_clear_log() {

		if ( ! isset( $_POST['ubac_signup_clear_log'] ) ) {
			return;
		}

		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		check_admin_referer( 'ubac_signup_clear_log' );

		Ubac_Signup_Log::clear();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => UBAC_SIGNUP_SLUG,
					'cleared' => '1',
				),
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public static function render_page() {

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You are not allowed to access this page.', 'dahuconcept-signup-guard' ) );
		}

		$o   = self::all();
		$log = Ubac_Signup_Log::get();
		?>
		<div class="wrap ubac-ui">
			<div class="ubac-ui-head">
				<h1 class="ubac-ui-head__title"><?php esc_html_e( 'Signup guard', 'dahuconcept-signup-guard' ); ?></h1>
				<p class="ubac-ui-head__sub"><?php esc_html_e( 'Keeps robots out of the registration forms, and logs what it turned away.', 'dahuconcept-signup-guard' ); ?></p>
			</div>
			<hr class="wp-header-end">

			<?php if ( isset( $_GET['cleared'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only. ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Log cleared.', 'dahuconcept-signup-guard' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php settings_fields( self::GROUP ); ?>

				<div class="ubac-ui-card">
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><?php esc_html_e( 'Honeypot', 'dahuconcept-signup-guard' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[honeypot]" value="1" <?php checked( $o['honeypot'], 1 ); ?> />
									<?php esc_html_e( 'Add an invisible trap field to the registration form', 'dahuconcept-signup-guard' ); ?>
								</label>
								<p class="description">
									<?php esc_html_e( 'Recommended. Stops most bots, and a real visitor never sees it. The field is named differently on every site, so it cannot be recognised and skipped.', 'dahuconcept-signup-guard' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Plus aliases', 'dahuconcept-signup-guard' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[block_plus_aliases]" value="1" <?php checked( $o['block_plus_aliases'], 1 ); ?> />
									<?php esc_html_e( 'Refuse addresses such as name+anything@gmail.com', 'dahuconcept-signup-guard' ); ?>
								</label>
								<p class="description">
									<?php esc_html_e( 'Only on providers that collapse these to one mailbox, which lets a single address register over and over. Elsewhere a plus address is often a legitimate, distinct one.', 'dahuconcept-signup-guard' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="ubac-signup-username-patterns"><?php esc_html_e( 'Blocked in the username', 'dahuconcept-signup-guard' ); ?></label>
							</th>
							<td>
								<textarea id="ubac-signup-username-patterns" class="large-text code" rows="6"
									name="<?php echo esc_attr( self::OPTION ); ?>[username_patterns]"><?php echo esc_textarea( $o['username_patterns'] ); ?></textarea>
								<p class="description">
									<?php esc_html_e( 'One entry per line. A registration is refused when its username contains any of them, ignoring case. Plain text, not regular expressions.', 'dahuconcept-signup-guard' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="ubac-signup-email-patterns"><?php esc_html_e( 'Blocked in the email address', 'dahuconcept-signup-guard' ); ?></label>
							</th>
							<td>
								<textarea id="ubac-signup-email-patterns" class="large-text code" rows="4"
									name="<?php echo esc_attr( self::OPTION ); ?>[email_patterns]"><?php echo esc_textarea( $o['email_patterns'] ); ?></textarea>
								<p class="description">
									<?php esc_html_e( 'One entry per line — a domain such as .example blocks every address ending in it. Plain text, not regular expressions.', 'dahuconcept-signup-guard' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Addresses in the log', 'dahuconcept-signup-guard' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[store_full_ip]" value="1" <?php checked( $o['store_full_ip'], 1 ); ?> />
									<?php esc_html_e( 'Store the full IP address', 'dahuconcept-signup-guard' ); ?>
								</label>
								<p class="description">
									<?php esc_html_e( 'Off by default: only the network part is kept, which is enough to recognise a repeat offender. An IP address is personal data, so turning this on may require a mention in your privacy policy.', 'dahuconcept-signup-guard' ); ?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>
				</div>

				<?php submit_button( null, 'ubac-ui-btn ubac-ui-btn--go' ); ?>
			</form>

			<div class="ubac-ui-card">
				<div class="ubac-ui-card__head">
					<h2 class="ubac-ui-card__title">
						<?php
						printf(
							/* translators: %d: number of blocked attempts recorded. */
							esc_html__( 'Blocked attempts (%d)', 'dahuconcept-signup-guard' ),
							count( $log )
						);
						?>
					</h2>
				</div>

			<?php if ( ! $log ) : ?>
				<p class="ubac-ui-hint"><?php esc_html_e( 'Nothing blocked so far.', 'dahuconcept-signup-guard' ); ?></p>
			<?php else : ?>
				<table class="ubac-ui-table ubac-sg-log">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Date', 'dahuconcept-signup-guard' ); ?></th>
							<th><?php esc_html_e( 'Reason', 'dahuconcept-signup-guard' ); ?></th>
							<th><?php esc_html_e( 'Username', 'dahuconcept-signup-guard' ); ?></th>
							<th><?php esc_html_e( 'Email', 'dahuconcept-signup-guard' ); ?></th>
							<th><?php esc_html_e( 'Address', 'dahuconcept-signup-guard' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $log as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row['date'] ?? '' ); ?></td>
								<td><?php echo esc_html( $row['reason'] ?? '' ); ?></td>
								<td><?php echo esc_html( $row['username'] ?? '' ); ?></td>
								<td><?php echo esc_html( $row['email'] ?? '' ); ?></td>
								<td><code><?php echo esc_html( $row['ip'] ?? '' ); ?></code></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<form method="post" class="ubac-sg-form-spaced">
					<?php wp_nonce_field( 'ubac_signup_clear_log' ); ?>
					<button type="submit" name="ubac_signup_clear_log" value="1" class="ubac-ui-btn">
						<?php esc_html_e( 'Clear the log', 'dahuconcept-signup-guard' ); ?>
					</button>
				</form>
			<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
