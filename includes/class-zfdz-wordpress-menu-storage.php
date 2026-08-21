<?php
/**
 * WordPress menu document storage.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Resolves and prepares the managed menu directory below WordPress uploads.
 */
final class ZFDZ_WordPress_Menu_Storage {

	public const ERROR_UPLOADS_UNAVAILABLE    = 'uploads_unavailable';
	public const ERROR_STORAGE_UNSAFE_SYMLINK = 'storage_unsafe_symlink';
	public const ERROR_STORAGE_PATH_CONFLICT  = 'storage_path_conflict';
	public const ERROR_STORAGE_CREATE_FAILED  = 'storage_create_failed';
	public const ERROR_STORAGE_NOT_READABLE   = 'storage_not_readable';

	private const ROOT_DIRECTORY_NAME = 'zywienie-dla-zdrowia';
	private const MENU_DIRECTORY_NAME = 'jadlospisy';

	/**
	 * Returns the managed menu directory path without creating it.
	 *
	 * @return string|WP_Error
	 */
	public function get_menu_directory_path(): string|WP_Error {
		$upload_base_directory = $this->get_upload_base_directory();

		if ( is_wp_error( $upload_base_directory ) ) {
			return $upload_base_directory;
		}

		$managed_root   = trailingslashit( $upload_base_directory ) . self::ROOT_DIRECTORY_NAME;
		$menu_directory = trailingslashit( $managed_root ) . self::MENU_DIRECTORY_NAME;

		$root_error = $this->validate_managed_path( $managed_root );

		if ( is_wp_error( $root_error ) ) {
			return $root_error;
		}

		$menu_error = $this->validate_managed_path( $menu_directory );

		if ( is_wp_error( $menu_error ) ) {
			return $menu_error;
		}

		return $menu_directory;
	}

	/**
	 * Returns the public URL of the managed menu directory without creating it.
	 *
	 * @return string|WP_Error
	 */
	public function get_menu_directory_url(): string|WP_Error {
		$upload_base_url = $this->get_upload_base_url();

		if ( is_wp_error( $upload_base_url ) ) {
			return $upload_base_url;
		}

		$managed_root_url = trailingslashit( $upload_base_url ) . self::ROOT_DIRECTORY_NAME;

		return trailingslashit( $managed_root_url ) . self::MENU_DIRECTORY_NAME;
	}

	/**
	 * Creates the managed menu directory when necessary and verifies it.
	 *
	 * @return true|WP_Error
	 */
	public function ensure_menu_directory(): true|WP_Error {
		$menu_directory = $this->get_menu_directory_path();

		if ( is_wp_error( $menu_directory ) ) {
			return $menu_directory;
		}

		if ( ! is_dir( $menu_directory ) && ! wp_mkdir_p( $menu_directory ) ) {
			$verification = $this->get_menu_directory_path();

			if ( is_wp_error( $verification ) ) {
				return $verification;
			}

			if ( ! is_dir( $verification ) ) {
				return $this->create_error( self::ERROR_STORAGE_CREATE_FAILED );
			}
		}

		$verification = $this->get_menu_directory_path();

		if ( is_wp_error( $verification ) ) {
			return $verification;
		}

		if ( ! is_dir( $verification ) ) {
			return $this->create_error( self::ERROR_STORAGE_CREATE_FAILED );
		}

		return true;
	}

	/**
	 * Returns the WordPress uploads base directory after defensive validation.
	 *
	 * @return string|WP_Error
	 */
	private function get_upload_base_directory(): string|WP_Error {
		$upload_directory = wp_get_upload_dir();

		if (
			! is_array( $upload_directory )
			|| ! isset( $upload_directory['basedir'] )
			|| ! is_string( $upload_directory['basedir'] )
			|| '' === trim( $upload_directory['basedir'] )
		) {
			return $this->create_error( self::ERROR_UPLOADS_UNAVAILABLE );
		}

		if (
			isset( $upload_directory['error'] )
			&& false !== $upload_directory['error']
			&& '' !== $upload_directory['error']
		) {
			return $this->create_error( self::ERROR_UPLOADS_UNAVAILABLE );
		}

		return $upload_directory['basedir'];
	}

	/**
	 * Returns the WordPress uploads base URL after defensive validation.
	 *
	 * @return string|WP_Error
	 */
	private function get_upload_base_url(): string|WP_Error {
		$upload_directory = wp_get_upload_dir();

		if (
			! is_array( $upload_directory )
			|| ! isset( $upload_directory['baseurl'] )
			|| ! is_string( $upload_directory['baseurl'] )
			|| '' === trim( $upload_directory['baseurl'] )
		) {
			return $this->create_error( self::ERROR_UPLOADS_UNAVAILABLE );
		}

		if (
			isset( $upload_directory['error'] )
			&& false !== $upload_directory['error']
			&& '' !== $upload_directory['error']
		) {
			return $this->create_error( self::ERROR_UPLOADS_UNAVAILABLE );
		}

		$upload_base_url = esc_url_raw( $upload_directory['baseurl'], array( 'http', 'https' ) );

		if ( '' === $upload_base_url ) {
			return $this->create_error( self::ERROR_UPLOADS_UNAVAILABLE );
		}

		return $upload_base_url;
	}

	/**
	 * Validates an existing plugin-managed filesystem entry.
	 *
	 * @param string $path Plugin-managed path.
	 * @return WP_Error|null
	 */
	private function validate_managed_path( string $path ): ?WP_Error {
		if ( is_link( $path ) ) {
			return $this->create_error( self::ERROR_STORAGE_UNSAFE_SYMLINK );
		}

		if ( file_exists( $path ) && ! is_dir( $path ) ) {
			return $this->create_error( self::ERROR_STORAGE_PATH_CONFLICT );
		}

		if ( is_dir( $path ) && ! is_readable( $path ) ) {
			return $this->create_error( self::ERROR_STORAGE_NOT_READABLE );
		}

		return null;
	}

	/**
	 * Creates a translated storage error without exposing filesystem paths.
	 *
	 * @param string $error_code Machine-readable storage error code.
	 * @return WP_Error
	 */
	private function create_error( string $error_code ): WP_Error {
		$messages = array(
			self::ERROR_UPLOADS_UNAVAILABLE    => __( 'Nie można ustalić katalogu przesyłanych plików WordPress.', 'zywienie-dla-zdrowia' ),
			self::ERROR_STORAGE_UNSAFE_SYMLINK => __( 'Zarządzany katalog dokumentów nie może być dowiązaniem symbolicznym.', 'zywienie-dla-zdrowia' ),
			self::ERROR_STORAGE_PATH_CONFLICT  => __( 'Nie można przygotować katalogu dokumentów z powodu konfliktu w systemie plików.', 'zywienie-dla-zdrowia' ),
			self::ERROR_STORAGE_CREATE_FAILED  => __( 'Nie udało się utworzyć katalogu dokumentów.', 'zywienie-dla-zdrowia' ),
			self::ERROR_STORAGE_NOT_READABLE   => __( 'Katalog dokumentów nie jest dostępny do odczytu.', 'zywienie-dla-zdrowia' ),
		);

		return new WP_Error( $error_code, $messages[ $error_code ] );
	}
}
