<?php
/**
 * WordPress plugin settings page.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Registers and renders the native WordPress Settings API integration.
 */
final class ZFDZ_WordPress_Settings_Page {

	/**
	 * Administration page slug.
	 */
	public const PAGE_SLUG = 'zfdz-settings';

	/**
	 * Settings API group.
	 */
	private const SETTINGS_GROUP = 'zfdz-settings';

	/**
	 * Public presentation settings section.
	 */
	private const PUBLIC_VIEW_SECTION = 'zfdz-public-view';

	/**
	 * Registers administration-only hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_init', array( self::class, 'register_settings' ) );
		add_action( 'admin_menu', array( self::class, 'register_menu' ), 20 );
	}

	/**
	 * Registers the settings submenu below the existing plugin menu.
	 *
	 * @return void
	 */
	public static function register_menu(): void {
		add_submenu_page(
			ZFDZ_WordPress_Admin::PAGE_SLUG,
			__( 'Żywienie dla Zdrowia — Ustawienia', 'zywienie-dla-zdrowia' ),
			__( 'Ustawienia', 'zywienie-dla-zdrowia' ),
			ZFDZ_WordPress_Admin::CAPABILITY,
			self::PAGE_SLUG,
			array( self::class, 'render' )
		);
	}

	/**
	 * Registers the option, section, and four checkbox fields.
	 *
	 * @return void
	 */
	public static function register_settings(): void {
		register_setting(
			self::SETTINGS_GROUP,
			ZFDZ_WordPress_Settings::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( ZFDZ_WordPress_Settings::class, 'sanitize' ),
				'default'           => ZFDZ_WordPress_Settings::get_defaults(),
			)
		);

		add_settings_section(
			self::PUBLIC_VIEW_SECTION,
			esc_html__( 'Widok publiczny', 'zywienie-dla-zdrowia' ),
			array( self::class, 'render_public_view_section' ),
			self::PAGE_SLUG
		);

		self::add_checkbox_field(
			ZFDZ_WordPress_Settings::KEY_OVERVIEW_SHOW_MENUS,
			esc_html__( 'Pokazuj jadłospisy w widoku zbiorczym', 'zywienie-dla-zdrowia' ),
			__( 'Steruje wyłącznie widocznością modułu jadłospisów w [zywienie_dla_zdrowia]. Samodzielny shortcode [zfdz_jadlospisy] pozostaje dostępny.', 'zywienie-dla-zdrowia' )
		);

		self::add_checkbox_field(
			ZFDZ_WordPress_Settings::KEY_OVERVIEW_SHOW_LAB_RESULTS,
			esc_html__( 'Pokazuj wyniki badań w widoku zbiorczym', 'zywienie-dla-zdrowia' ),
			__( 'Steruje wyłącznie widocznością modułu wyników badań w [zywienie_dla_zdrowia]. Samodzielny shortcode [zfdz_badania] pozostaje dostępny.', 'zywienie-dla-zdrowia' )
		);

		self::add_checkbox_field(
			ZFDZ_WordPress_Settings::KEY_MENU_SHOW_UPCOMING,
			esc_html__( 'Pokazuj nadchodzące jadłospisy', 'zywienie-dla-zdrowia' ),
			__( 'Wpływa na [zfdz_jadlospisy], a tym samym na widok zbiorczy. Nie zmienia [zfdz_jadlospisy_archiwum], nie usuwa okresów z katalogu i nie wpływa na powiązanie wyników badań.', 'zywienie-dla-zdrowia' )
		);

		self::add_checkbox_field(
			ZFDZ_WordPress_Settings::KEY_OPEN_DOCUMENTS_NEW_TAB,
			esc_html__( 'Otwieraj dokumenty PDF w nowej karcie', 'zywienie-dla-zdrowia' ),
			__( 'Dotyczy publicznych linków PDF generowanych przez plugin.', 'zywienie-dla-zdrowia' )
		);
	}

	/**
	 * Renders the settings page.
	 *
	 * @return void
	 */
	public static function render(): void {
		if ( ! current_user_can( ZFDZ_WordPress_Admin::CAPABILITY ) ) {
			wp_die( esc_html__( 'Nie masz uprawnień do zarządzania ustawieniami.', 'zywienie-dla-zdrowia' ) );
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Żywienie dla Zdrowia — Ustawienia', 'zywienie-dla-zdrowia' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::SETTINGS_GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>

			<h2><?php echo esc_html__( 'Dostępne shortcode’y', 'zywienie-dla-zdrowia' ); ?></h2>
			<dl>
				<dt><code><?php echo esc_html( '[zywienie_dla_zdrowia]' ); ?></code></dt>
				<dd><?php echo esc_html__( 'Bieżący widok zbiorczy jadłospisów i wyniku badania.', 'zywienie-dla-zdrowia' ); ?></dd>
				<dt><code><?php echo esc_html( '[zfdz_jadlospisy]' ); ?></code></dt>
				<dd><?php echo esc_html__( 'Aktualne i, zależnie od ustawienia, nadchodzące jadłospisy.', 'zywienie-dla-zdrowia' ); ?></dd>
				<dt><code><?php echo esc_html( '[zfdz_jadlospisy_archiwum]' ); ?></code></dt>
				<dd><?php echo esc_html__( 'Archiwalne jadłospisy.', 'zywienie-dla-zdrowia' ); ?></dd>
				<dt><code><?php echo esc_html( '[zfdz_badania]' ); ?></code></dt>
				<dd><?php echo esc_html__( 'Najnowszy techniczny publiczny stan wyniku badania.', 'zywienie-dla-zdrowia' ); ?></dd>
			</dl>
			<p><?php echo esc_html__( 'Zmiana widoczności modułów w widoku zbiorczym nie wyłącza ich niezależnych shortcode’ów.', 'zywienie-dla-zdrowia' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Renders the public view section description.
	 *
	 * @return void
	 */
	public static function render_public_view_section(): void {
		// The field descriptions provide the required setting context.
	}

	/**
	 * Renders one controlled checkbox field.
	 *
	 * @param array<string, string> $args Field key and description.
	 * @return void
	 */
	public static function render_checkbox( array $args ): void {
		$key         = $args['key'];
		$description = $args['description'];
		$settings    = ZFDZ_WordPress_Settings::get_all();
		$field_name  = ZFDZ_WordPress_Settings::OPTION_NAME . '[' . $key . ']';
		?>
		<label for="<?php echo esc_attr( $key ); ?>">
			<input
				type="checkbox"
				id="<?php echo esc_attr( $key ); ?>"
				name="<?php echo esc_attr( $field_name ); ?>"
				value="1"
				<?php checked( $settings[ $key ] ); ?>
			>
			<?php echo esc_html( $description ); ?>
		</label>
		<?php
	}

	/**
	 * Registers one checkbox field with its controlled callback arguments.
	 *
	 * @param string $key         Settings key and field identifier.
	 * @param string $label       Field label.
	 * @param string $description Field description.
	 * @return void
	 */
	private static function add_checkbox_field( string $key, string $label, string $description ): void {
		add_settings_field(
			$key,
			$label,
			array( self::class, 'render_checkbox' ),
			self::PAGE_SLUG,
			self::PUBLIC_VIEW_SECTION,
			array(
				'key'         => $key,
				'description' => $description,
			)
		);
	}
}
