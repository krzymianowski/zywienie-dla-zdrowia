<?php
/**
 * WordPress administration integration.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Registers the plugin administration page and refresh handler.
 */
final class ZFDZ_WordPress_Admin {

	/**
	 * Required capability for the plugin administration page.
	 */
	public const CAPABILITY = 'manage_options';

	/**
	 * Administration page slug.
	 */
	public const PAGE_SLUG = 'zywienie-dla-zdrowia';

	/**
	 * Admin-post action used to refresh the menu catalog.
	 */
	public const REFRESH_ACTION = 'zfdz_refresh_menu_catalog';

	/**
	 * Nonce action used to refresh the menu catalog.
	 */
	public const REFRESH_NONCE_ACTION = 'zfdz_refresh_menu_catalog';

	/**
	 * Nonce field used to refresh the menu catalog.
	 */
	public const REFRESH_NONCE_FIELD = 'zfdz_refresh_menu_catalog_nonce';

	/**
	 * Registers WordPress administration hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_menu', array( self::class, 'register_menu' ) );
		add_action( 'admin_post_' . self::REFRESH_ACTION, array( self::class, 'handle_refresh' ) );
	}

	/**
	 * Registers the top-level administration menu page.
	 *
	 * @return void
	 */
	public static function register_menu(): void {
		add_menu_page(
			__( 'Żywienie dla Zdrowia — Status publikacji', 'zywienie-dla-zdrowia' ),
			__( 'Żywienie dla Zdrowia', 'zywienie-dla-zdrowia' ),
			self::CAPABILITY,
			self::PAGE_SLUG,
			array( ZFDZ_WordPress_Admin_Status_Page::class, 'render' ),
			'dashicons-food'
		);
	}

	/**
	 * Refreshes the menu catalog after capability and nonce verification.
	 *
	 * @return void
	 */
	public static function handle_refresh(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Nie masz uprawnień do odświeżania katalogu jadłospisów.', 'zywienie-dla-zdrowia' ) );
		}

		if ( ! self::is_post_request() ) {
			wp_die( esc_html__( 'Odświeżanie katalogu jadłospisów wymaga żądania POST.', 'zywienie-dla-zdrowia' ) );
		}

		check_admin_referer( self::REFRESH_NONCE_ACTION, self::REFRESH_NONCE_FIELD );

		$catalog       = ZFDZ_WordPress_Menu_Catalog_Service::create_default()->refresh_catalog();
		$refresh_state = $catalog->is_successful() ? 'success' : 'error';
		$redirect_url  = add_query_arg(
			'zfdz_refresh',
			$refresh_state,
			admin_url( 'admin.php?page=' . self::PAGE_SLUG )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Checks whether the current request uses the POST method.
	 *
	 * @return bool
	 */
	private static function is_post_request(): bool {
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || ! is_string( $_SERVER['REQUEST_METHOD'] ) ) {
			return false;
		}

		return 'post' === sanitize_key( wp_unslash( $_SERVER['REQUEST_METHOD'] ) );
	}
}
