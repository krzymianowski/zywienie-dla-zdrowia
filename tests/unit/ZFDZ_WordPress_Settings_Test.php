<?php
/**
 * Tests the standalone parts of the WordPress settings model.
 *
 * @package ZywienieDlaZdrowia
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests defaults and strict checkbox sanitization without loading WordPress.
 */
final class ZFDZ_WordPress_Settings_Test extends TestCase {

	/**
	 * Returns exactly the four approved option keys.
	 *
	 * @return void
	 */
	public function test_defaults_contain_exactly_four_known_keys(): void {
		$this->assertSame(
			array(
				'overview_show_menus',
				'overview_show_lab_results',
				'menu_show_upcoming',
				'open_documents_new_tab',
			),
			array_keys( ZFDZ_WordPress_Settings::get_defaults() )
		);
	}

	/**
	 * Preserves the exact Stage 21 behavior when the option is absent.
	 *
	 * @return void
	 */
	public function test_defaults_preserve_existing_public_behavior(): void {
		$this->assertSame(
			array(
				'overview_show_menus'       => true,
				'overview_show_lab_results' => true,
				'menu_show_upcoming'        => true,
				'open_documents_new_tab'    => false,
			),
			ZFDZ_WordPress_Settings::get_defaults()
		);
	}

	/**
	 * Treats exact checkbox values as selected.
	 *
	 * @return void
	 */
	public function test_sanitize_accepts_all_exact_checked_values(): void {
		$input = array_fill_keys( array_keys( ZFDZ_WordPress_Settings::get_defaults() ), '1' );

		$this->assertSame(
			array_fill_keys( array_keys( ZFDZ_WordPress_Settings::get_defaults() ), true ),
			ZFDZ_WordPress_Settings::sanitize( $input )
		);
	}

	/**
	 * Preserves an already normalized selected checkbox.
	 *
	 * @return void
	 */
	public function test_sanitize_preserves_boolean_true(): void {
		$result = ZFDZ_WordPress_Settings::sanitize(
			array( 'overview_show_menus' => true )
		);

		$this->assertTrue( $result['overview_show_menus'] );
	}

	/**
	 * Preserves an already normalized unselected checkbox.
	 *
	 * @return void
	 */
	public function test_sanitize_preserves_boolean_false(): void {
		$result = ZFDZ_WordPress_Settings::sanitize(
			array( 'overview_show_menus' => false )
		);

		$this->assertFalse( $result['overview_show_menus'] );
	}

	/**
	 * Produces the same result when WordPress sanitizes a normalized value again.
	 *
	 * @return void
	 */
	public function test_sanitize_is_idempotent(): void {
		$input    = array(
			'overview_show_menus' => '1',
			'menu_show_upcoming'  => '1',
		);
		$expected = array(
			'overview_show_menus'       => true,
			'overview_show_lab_results' => false,
			'menu_show_upcoming'        => true,
			'open_documents_new_tab'    => false,
		);
		$once     = ZFDZ_WordPress_Settings::sanitize( $input );
		$twice    = ZFDZ_WordPress_Settings::sanitize( $once );

		$this->assertSame( $expected, $once );
		$this->assertSame( $once, $twice );
	}

	/**
	 * Treats all missing checkboxes as unselected during an explicit save.
	 *
	 * @return void
	 */
	public function test_sanitize_empty_input_returns_all_false(): void {
		$this->assertSame(
			array_fill_keys( array_keys( ZFDZ_WordPress_Settings::get_defaults() ), false ),
			ZFDZ_WordPress_Settings::sanitize( array() )
		);
	}

	/**
	 * Selects only the submitted checkbox in a partial form payload.
	 *
	 * @return void
	 */
	public function test_sanitize_partial_input_selects_only_exact_checked_key(): void {
		$expected                              = array_fill_keys( array_keys( ZFDZ_WordPress_Settings::get_defaults() ), false );
		$expected['overview_show_lab_results'] = true;

		$this->assertSame(
			$expected,
			ZFDZ_WordPress_Settings::sanitize(
				array( 'overview_show_lab_results' => '1' )
			)
		);
	}

	/**
	 * Removes keys outside the approved option schema.
	 *
	 * @return void
	 */
	public function test_sanitize_discards_unknown_keys(): void {
		$result = ZFDZ_WordPress_Settings::sanitize(
			array(
				'overview_show_menus' => '1',
				'unknown_setting'     => '1',
			)
		);

		$this->assertArrayNotHasKey( 'unknown_setting', $result );
		$this->assertCount( 4, $result );
	}

	/**
	 * Does not interpret the string zero as selected.
	 *
	 * @return void
	 */
	public function test_sanitize_does_not_treat_zero_as_true(): void {
		$result = ZFDZ_WordPress_Settings::sanitize(
			array( 'open_documents_new_tab' => '0' )
		);

		$this->assertFalse( $result['open_documents_new_tab'] );
	}

	/**
	 * Does not interpret arbitrary strings as selected.
	 *
	 * @return void
	 */
	public function test_sanitize_does_not_treat_random_string_as_true(): void {
		$result = ZFDZ_WordPress_Settings::sanitize(
			array( 'menu_show_upcoming' => 'yes' )
		);

		$this->assertFalse( $result['menu_show_upcoming'] );
	}

	/**
	 * Safely normalizes a malformed non-array submission.
	 *
	 * @return void
	 */
	public function test_sanitize_non_array_input_returns_all_false(): void {
		$this->assertSame(
			array_fill_keys( array_keys( ZFDZ_WordPress_Settings::get_defaults() ), false ),
			ZFDZ_WordPress_Settings::sanitize( 'invalid' )
		);
	}
}
