<?php
/**
 * WordPress plugin settings.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Provides normalized access to the plugin's public presentation settings.
 */
final class ZFDZ_WordPress_Settings {

	/**
	 * Single WordPress option containing all plugin settings.
	 */
	public const OPTION_NAME = 'zfdz_settings_v1';

	/**
	 * Controls menu visibility in the aggregate shortcode.
	 */
	public const KEY_OVERVIEW_SHOW_MENUS = 'overview_show_menus';

	/**
	 * Controls laboratory-result visibility in the aggregate shortcode.
	 */
	public const KEY_OVERVIEW_SHOW_LAB_RESULTS = 'overview_show_lab_results';

	/**
	 * Controls the upcoming menu section in the public menu shortcode.
	 */
	public const KEY_MENU_SHOW_UPCOMING = 'menu_show_upcoming';

	/**
	 * Controls whether public PDF links open in a new browser tab.
	 */
	public const KEY_OPEN_DOCUMENTS_NEW_TAB = 'open_documents_new_tab';

	/**
	 * Returns backward-compatible settings defaults without writing the option.
	 *
	 * @return array<string, bool>
	 */
	public static function get_defaults(): array {
		return array(
			self::KEY_OVERVIEW_SHOW_MENUS       => true,
			self::KEY_OVERVIEW_SHOW_LAB_RESULTS => true,
			self::KEY_MENU_SHOW_UPCOMING        => true,
			self::KEY_OPEN_DOCUMENTS_NEW_TAB    => false,
		);
	}

	/**
	 * Sanitizes one Settings API form submission to the exact known schema.
	 *
	 * @param mixed $input Submitted option value.
	 * @return array<string, bool>
	 */
	public static function sanitize( mixed $input ): array {
		$submitted = is_array( $input ) ? $input : array();
		$sanitized = array();

		foreach ( array_keys( self::get_defaults() ) as $key ) {
			$value = $submitted[ $key ] ?? null;

			$sanitized[ $key ] = true === $value || '1' === $value;
		}

		return $sanitized;
	}

	/**
	 * Returns the stored settings normalized over the current defaults.
	 *
	 * Missing or malformed values do not cause an option write.
	 *
	 * @return array<string, bool>
	 */
	public static function get_all(): array {
		$defaults = self::get_defaults();
		$stored   = get_option( self::OPTION_NAME, null );

		if ( ! is_array( $stored ) ) {
			return $defaults;
		}

		$settings = array();

		foreach ( $defaults as $key => $default ) {
			$settings[ $key ] = isset( $stored[ $key ] ) && is_bool( $stored[ $key ] )
				? $stored[ $key ]
				: $default;
		}

		return $settings;
	}

	/**
	 * Whether the aggregate shortcode should render the menu module.
	 *
	 * @return bool
	 */
	public static function should_show_menus_in_overview(): bool {
		return self::get_all()[ self::KEY_OVERVIEW_SHOW_MENUS ];
	}

	/**
	 * Whether the aggregate shortcode should render the laboratory-result module.
	 *
	 * @return bool
	 */
	public static function should_show_lab_results_in_overview(): bool {
		return self::get_all()[ self::KEY_OVERVIEW_SHOW_LAB_RESULTS ];
	}

	/**
	 * Whether the current menu shortcode should render upcoming periods.
	 *
	 * @return bool
	 */
	public static function should_show_upcoming_menus(): bool {
		return self::get_all()[ self::KEY_MENU_SHOW_UPCOMING ];
	}

	/**
	 * Whether public PDF links should open in a new browser tab.
	 *
	 * @return bool
	 */
	public static function should_open_documents_in_new_tab(): bool {
		return self::get_all()[ self::KEY_OPEN_DOCUMENTS_NEW_TAB ];
	}
}
