<?php
/**
 * Tests the menu filename parser.
 *
 * @package ZywienieDlaZdrowia
 */

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests the standalone menu filename parser.
 */
final class ZFDZ_Menu_Filename_Parser_Test extends TestCase {

	/**
	 * Tests valid filenames and their parsed document values.
	 *
	 * @param string $filename Expected original filename.
	 * @param string $start    Expected start date.
	 * @param string $end      Expected end date.
	 * @param string $name     Expected extracted name.
	 * @return void
	 */
	#[DataProvider( 'provide_valid_filenames' )]
	public function test_parses_valid_filename( string $filename, string $start, string $end, string $name ): void {
		$parser = new ZFDZ_Menu_Filename_Parser();
		$result = $parser->parse( $filename );

		$this->assertTrue( $result->is_valid() );
		$this->assertNull( $result->get_error_code() );
		$this->assertInstanceOf( ZFDZ_Menu_Document::class, $result->get_document() );

		$document = $result->get_document();

		$this->assertSame( $filename, $document->get_original_filename() );
		$this->assertSame( $start, $document->get_start_date() );
		$this->assertSame( $end, $document->get_end_date() );
		$this->assertSame( $name, $document->get_name() );
	}

	/**
	 * Provides valid menu document filenames.
	 *
	 * @return array<string, array{string, string, string, string}>
	 */
	public static function provide_valid_filenames(): array {
		return array(
			'hyphenated name'        => array(
				'2026-09-01_2026-09-10_dieta-podstawowa.pdf',
				'2026-09-01',
				'2026-09-10',
				'dieta-podstawowa',
			),
			'underscored name'       => array(
				'2026-09-01_2026-09-10_dieta_podstawowa.pdf',
				'2026-09-01',
				'2026-09-10',
				'dieta_podstawowa',
			),
			'name containing spaces' => array(
				'2026-09-01_2026-09-10_Dieta podstawowa.pdf',
				'2026-09-01',
				'2026-09-10',
				'Dieta podstawowa',
			),
			'polish characters'      => array(
				'2026-09-01_2026-09-10_dieta-łatwostrawna.pdf',
				'2026-09-01',
				'2026-09-10',
				'dieta-łatwostrawna',
			),
			'leap day'               => array(
				'2024-02-29_2024-03-05_test.pdf',
				'2024-02-29',
				'2024-03-05',
				'test',
			),
			'single day period'      => array(
				'2026-09-01_2026-09-01_test.pdf',
				'2026-09-01',
				'2026-09-01',
				'test',
			),
			'uppercase extension'    => array(
				'2026-09-01_2026-09-10_test.PDF',
				'2026-09-01',
				'2026-09-10',
				'test',
			),
			'name containing digit'  => array(
				'2026-09-01_2026-09-10_dieta-bezglutenowa-2.pdf',
				'2026-09-01',
				'2026-09-10',
				'dieta-bezglutenowa-2',
			),
		);
	}

	/**
	 * Tests invalid filenames and their machine-readable error codes.
	 *
	 * @param string $filename   Invalid filename.
	 * @param string $error_code Expected error code.
	 * @return void
	 */
	#[DataProvider( 'provide_invalid_filenames' )]
	public function test_rejects_invalid_filename( string $filename, string $error_code ): void {
		$parser = new ZFDZ_Menu_Filename_Parser();
		$result = $parser->parse( $filename );

		$this->assertFalse( $result->is_valid() );
		$this->assertNull( $result->get_document() );
		$this->assertSame( $error_code, $result->get_error_code() );
	}

	/**
	 * Provides invalid filenames with their expected error codes.
	 *
	 * @return array<string, array{string, string}>
	 */
	public static function provide_invalid_filenames(): array {
		return array(
			'invalid start day'       => array(
				'2026-02-30_2026-03-10_test.pdf',
				ZFDZ_Menu_Filename_Parser::ERROR_INVALID_START_DATE,
			),
			'invalid end day'         => array(
				'2026-09-01_2026-02-30_test.pdf',
				ZFDZ_Menu_Filename_Parser::ERROR_INVALID_END_DATE,
			),
			'invalid start month'     => array(
				'2026-13-01_2026-09-10_test.pdf',
				ZFDZ_Menu_Filename_Parser::ERROR_INVALID_START_DATE,
			),
			'zero start month'        => array(
				'2026-00-10_2026-09-10_test.pdf',
				ZFDZ_Menu_Filename_Parser::ERROR_INVALID_START_DATE,
			),
			'non-leap year'           => array(
				'2023-02-29_2023-03-05_test.pdf',
				ZFDZ_Menu_Filename_Parser::ERROR_INVALID_START_DATE,
			),
			'end before start'        => array(
				'2026-09-10_2026-09-01_test.pdf',
				ZFDZ_Menu_Filename_Parser::ERROR_INVALID_DATE_RANGE,
			),
			'filename without dates'  => array(
				'jadlospis.pdf',
				ZFDZ_Menu_Filename_Parser::ERROR_INVALID_FORMAT,
			),
			'filename with one date'  => array(
				'2026-09-01_test.pdf',
				ZFDZ_Menu_Filename_Parser::ERROR_INVALID_FORMAT,
			),
			'missing name separator'  => array(
				'2026-09-01_2026-09-10.pdf',
				ZFDZ_Menu_Filename_Parser::ERROR_INVALID_FORMAT,
			),
			'invalid date separators' => array(
				'2026_09_01_2026_09_10_test.pdf',
				ZFDZ_Menu_Filename_Parser::ERROR_INVALID_FORMAT,
			),
			'unsupported extension'   => array(
				'2026-09-01_2026-09-10_test.txt',
				ZFDZ_Menu_Filename_Parser::ERROR_UNSUPPORTED_EXTENSION,
			),
			'double extension'        => array(
				'2026-09-01_2026-09-10_test.pdf.exe',
				ZFDZ_Menu_Filename_Parser::ERROR_UNSUPPORTED_EXTENSION,
			),
			'parent Unix path'        => array(
				'../2026-09-01_2026-09-10_test.pdf',
				ZFDZ_Menu_Filename_Parser::ERROR_INVALID_PATH,
			),
			'parent Windows path'     => array(
				'..\\2026-09-01_2026-09-10_test.pdf',
				ZFDZ_Menu_Filename_Parser::ERROR_INVALID_PATH,
			),
			'Unix folder path'        => array(
				'folder/2026-09-01_2026-09-10_test.pdf',
				ZFDZ_Menu_Filename_Parser::ERROR_INVALID_PATH,
			),
			'Windows folder path'     => array(
				'folder\\2026-09-01_2026-09-10_test.pdf',
				ZFDZ_Menu_Filename_Parser::ERROR_INVALID_PATH,
			),
			'NUL byte'                => array(
				"2026-09-01_2026-09-10_test\0.pdf",
				ZFDZ_Menu_Filename_Parser::ERROR_INVALID_PATH,
			),
			'empty name'              => array(
				'2026-09-01_2026-09-10_.pdf',
				ZFDZ_Menu_Filename_Parser::ERROR_INVALID_NAME,
			),
			'single dot name'         => array(
				'2026-09-01_2026-09-10_..pdf',
				ZFDZ_Menu_Filename_Parser::ERROR_INVALID_NAME,
			),
			'double dot name'         => array(
				'2026-09-01_2026-09-10_...pdf',
				ZFDZ_Menu_Filename_Parser::ERROR_INVALID_NAME,
			),
			'whitespace-only name'    => array(
				'2026-09-01_2026-09-10_   .pdf',
				ZFDZ_Menu_Filename_Parser::ERROR_INVALID_NAME,
			),
			'leading space in name'   => array(
				'2026-09-01_2026-09-10_ dieta-podstawowa.pdf',
				ZFDZ_Menu_Filename_Parser::ERROR_INVALID_NAME,
			),
			'trailing space in name'  => array(
				'2026-09-01_2026-09-10_dieta-podstawowa .pdf',
				ZFDZ_Menu_Filename_Parser::ERROR_INVALID_NAME,
			),
			'tab in name'             => array(
				"2026-09-01_2026-09-10_dieta\tpodstawowa.pdf",
				ZFDZ_Menu_Filename_Parser::ERROR_INVALID_NAME,
			),
			'line feed in name'       => array(
				"2026-09-01_2026-09-10_dieta\npodstawowa.pdf",
				ZFDZ_Menu_Filename_Parser::ERROR_INVALID_NAME,
			),
			'carriage return in name' => array(
				"2026-09-01_2026-09-10_dieta\rpodstawowa.pdf",
				ZFDZ_Menu_Filename_Parser::ERROR_INVALID_NAME,
			),
			'invalid UTF-8 name'      => array(
				"2026-09-01_2026-09-10_\xC3\x28.pdf",
				ZFDZ_Menu_Filename_Parser::ERROR_INVALID_NAME,
			),
		);
	}
}
