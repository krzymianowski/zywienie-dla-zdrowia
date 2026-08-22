<?php
/**
 * Public WordPress overview shortcode.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Registers and renders the aggregate public overview shortcode.
 */
final class ZFDZ_WordPress_Overview_Shortcode {

	public const SHORTCODE_TAG = 'zywienie_dla_zdrowia';

	/**
	 * Registers the WordPress hook that installs the shortcode callback.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'register_shortcode' ) );
	}

	/**
	 * Registers the shortcode without rendering either child view.
	 *
	 * @return void
	 */
	public static function register_shortcode(): void {
		add_shortcode( self::SHORTCODE_TAG, array( self::class, 'render' ) );
	}

	/**
	 * Returns the current menu view followed by the laboratory result view.
	 *
	 * @return string
	 */
	public static function render(): string {
		$menu_markup       = ZFDZ_WordPress_Menu_Shortcode::render();
		$lab_result_markup = ZFDZ_WordPress_Lab_Result_Shortcode::render();

		return '<div class="zfdz-overview">'
			. '<div class="zfdz-overview-menus">'
			. $menu_markup
			. '</div>'
			. '<div class="zfdz-overview-lab-result">'
			. $lab_result_markup
			. '</div>'
			. '</div>';
	}
}
