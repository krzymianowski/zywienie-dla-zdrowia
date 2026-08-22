<?php
/**
 * Tests the WordPress laboratory result public URL resolver.
 *
 * @package ZywienieDlaZdrowia
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests candidate-only URL generation without loading WordPress.
 */
final class ZFDZ_WordPress_Lab_Result_Public_Url_Resolver_Test extends TestCase {

	private const DIRECTORY_URL = 'https://example.test/media/zywienie-dla-zdrowia/badania';

	/**
	 * Returns no URL for an unavailable menu catalog.
	 *
	 * @return void
	 */
	public function test_returns_null_for_menu_catalog_unavailable(): void {
		$result = ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::from_unavailable(
			ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::UNAVAILABLE_REASON_MENU_CATALOG
		);

		$this->assertNull( $this->create_resolver()->resolve( $result, self::DIRECTORY_URL ) );
	}

	/**
	 * Returns no URL for an unavailable laboratory result catalog.
	 *
	 * @return void
	 */
	public function test_returns_null_for_lab_catalog_unavailable(): void {
		$result = ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::from_unavailable(
			ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::UNAVAILABLE_REASON_LAB_CATALOG
		);

		$this->assertNull( $this->create_resolver()->resolve( $result, self::DIRECTORY_URL ) );
	}

	/**
	 * Returns no URL when no validated result exists.
	 *
	 * @return void
	 */
	public function test_returns_null_for_no_result(): void {
		$result = ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::from_decision(
			ZFDZ_Lab_Result_Public_Presentation_Decision::from_no_result()
		);

		$this->assertNull( $this->create_resolver()->resolve( $result, self::DIRECTORY_URL ) );
	}

	/**
	 * Returns no URL when the latest result is unmatched.
	 *
	 * @return void
	 */
	public function test_returns_null_for_blocked_unmatched(): void {
		$result = ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::from_decision(
			ZFDZ_Lab_Result_Public_Presentation_Decision::from_blocked_unmatched()
		);

		$this->assertNull( $this->create_resolver()->resolve( $result, self::DIRECTORY_URL ) );
	}

	/**
	 * Builds the exact candidate URL from the original filename.
	 *
	 * @return void
	 */
	public function test_resolves_exact_candidate_url(): void {
		$filename = '2026-08-21_2026-08-31_2027-02-01_Badanie kontrolne.pdf';
		$result   = $this->create_candidate_result( $filename, 'Badanie kontrolne' );

		$this->assertSame(
			self::DIRECTORY_URL . '/' . rawurlencode( $filename ),
			$this->create_resolver()->resolve( $result, self::DIRECTORY_URL )
		);
	}

	/**
	 * Uses the original filename rather than the display name.
	 *
	 * @return void
	 */
	public function test_uses_original_filename_instead_of_display_name(): void {
		$filename     = '2026-08-21_2026-08-31_2027-02-01_Original source.pdf';
		$display_name = 'Different display name';
		$result       = $this->create_candidate_result( $filename, $display_name );
		$url          = $this->create_resolver()->resolve( $result, self::DIRECTORY_URL );

		$this->assertSame( self::DIRECTORY_URL . '/' . rawurlencode( $filename ), $url );
		$this->assertStringNotContainsString( rawurlencode( $display_name ) . '.pdf', $url );
	}

	/**
	 * Avoids a duplicate slash for a directory URL with a trailing slash.
	 *
	 * @return void
	 */
	public function test_accepts_directory_url_with_trailing_slash(): void {
		$filename = '2026-08-21_2026-08-31_2027-02-01_Test.pdf';
		$result   = $this->create_candidate_result( $filename, 'Test' );

		$this->assertSame(
			self::DIRECTORY_URL . '/' . rawurlencode( $filename ),
			$this->create_resolver()->resolve( $result, self::DIRECTORY_URL . '/' )
		);
	}

	/**
	 * Adds one separator for a directory URL without a trailing slash.
	 *
	 * @return void
	 */
	public function test_accepts_directory_url_without_trailing_slash(): void {
		$filename = '2026-08-21_2026-08-31_2027-02-01_Test.pdf';
		$result   = $this->create_candidate_result( $filename, 'Test' );

		$this->assertSame(
			self::DIRECTORY_URL . '/' . rawurlencode( $filename ),
			$this->create_resolver()->resolve( $result, self::DIRECTORY_URL )
		);
	}

	/**
	 * Percent-encodes spaces in the filename path segment.
	 *
	 * @return void
	 */
	public function test_percent_encodes_spaces(): void {
		$filename = '2026-08-21_2026-08-31_2027-02-01_Badanie kontrolne.pdf';
		$result   = $this->create_candidate_result( $filename, 'Badanie kontrolne' );
		$url      = $this->create_resolver()->resolve( $result, self::DIRECTORY_URL );

		$this->assertStringContainsString( 'Badanie%20kontrolne.pdf', $url );
		$this->assertStringNotContainsString( 'Badanie kontrolne.pdf', $url );
	}

	/**
	 * Percent-encodes ampersands in the filename path segment.
	 *
	 * @return void
	 */
	public function test_percent_encodes_ampersand(): void {
		$filename = '2026-08-21_2026-08-31_2027-02-01_Badanie A & B.pdf';
		$result   = $this->create_candidate_result( $filename, 'Badanie A & B' );
		$url      = $this->create_resolver()->resolve( $result, self::DIRECTORY_URL );

		$this->assertStringContainsString( 'Badanie%20A%20%26%20B.pdf', $url );
		$this->assertStringNotContainsString( '&', $url );
	}

	/**
	 * Percent-encodes a Unicode filename as one UTF-8 URL path segment.
	 *
	 * @return void
	 */
	public function test_percent_encodes_unicode_filename(): void {
		$filename = '2026-08-21_2026-08-31_2027-02-01_Badanie żółć.pdf';
		$result   = $this->create_candidate_result( $filename, 'Badanie żółć' );

		$this->assertSame(
			self::DIRECTORY_URL . '/' . rawurlencode( $filename ),
			$this->create_resolver()->resolve( $result, self::DIRECTORY_URL )
		);
	}

	/**
	 * Resolves the exact document exposed by the candidate result.
	 *
	 * @return void
	 */
	public function test_resolves_the_exact_candidate_document(): void {
		$selected_filename = '2026-08-21_2026-08-31_2027-03-02_Selected.pdf';
		$result            = $this->create_candidate_result( $selected_filename, 'Selected' );
		$url               = $this->create_resolver()->resolve( $result, self::DIRECTORY_URL );

		$this->assertSame( $selected_filename, $result->get_document()->get_original_filename() );
		$this->assertSame( self::DIRECTORY_URL . '/' . rawurlencode( $selected_filename ), $url );
	}

	/**
	 * Rejects an impossible candidate filename containing a forward slash.
	 *
	 * @return void
	 */
	public function test_rejects_forward_slash_in_candidate_filename(): void {
		$result = $this->create_candidate_result(
			'2026-08-21_2026-08-31_2027-02-01_subdirectory/result.pdf',
			'Result'
		);

		$this->expectException( LogicException::class );

		$this->create_resolver()->resolve( $result, self::DIRECTORY_URL );
	}

	/**
	 * Rejects an impossible candidate filename containing a backslash.
	 *
	 * @return void
	 */
	public function test_rejects_backslash_in_candidate_filename(): void {
		$result = $this->create_candidate_result(
			'2026-08-21_2026-08-31_2027-02-01_subdirectory\\result.pdf',
			'Result'
		);

		$this->expectException( LogicException::class );

		$this->create_resolver()->resolve( $result, self::DIRECTORY_URL );
	}

	/**
	 * Rejects an impossible candidate filename containing NUL.
	 *
	 * @return void
	 */
	public function test_rejects_nul_in_candidate_filename(): void {
		$result = $this->create_candidate_result(
			"2026-08-21_2026-08-31_2027-02-01_Result\0.pdf",
			'Result'
		);

		$this->expectException( LogicException::class );

		$this->create_resolver()->resolve( $result, self::DIRECTORY_URL );
	}

	/**
	 * Creates the URL resolver under test.
	 *
	 * @return ZFDZ_WordPress_Lab_Result_Public_Url_Resolver
	 */
	private function create_resolver(): ZFDZ_WordPress_Lab_Result_Public_Url_Resolver {
		return new ZFDZ_WordPress_Lab_Result_Public_Url_Resolver();
	}

	/**
	 * Creates a public presentation candidate result fixture.
	 *
	 * @param string $original_filename Original laboratory result filename.
	 * @param string $display_name      Display name extracted from the filename.
	 * @return ZFDZ_WordPress_Lab_Result_Public_Presentation_Result
	 */
	private function create_candidate_result(
		string $original_filename,
		string $display_name
	): ZFDZ_WordPress_Lab_Result_Public_Presentation_Result {
		$menu_start_date = '2026-08-21';
		$menu_end_date   = '2026-08-31';
		$document        = new ZFDZ_Lab_Result_Document(
			$original_filename,
			$menu_start_date,
			$menu_end_date,
			'2027-02-01',
			$display_name
		);
		$association     = ZFDZ_Lab_Result_Menu_Association::from_match(
			$document,
			new ZFDZ_Menu_Period_Group( $menu_start_date, $menu_end_date, array() )
		);
		$decision        = ZFDZ_Lab_Result_Public_Presentation_Decision::from_candidate( $association );

		return ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::from_decision( $decision );
	}
}
