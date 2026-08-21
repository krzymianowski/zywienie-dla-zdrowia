<?php
/**
 * Tests the laboratory result filename parser.
 *
 * @package ZywienieDlaZdrowia
 */

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests standalone laboratory result filename parsing.
 */
final class ZFDZ_Lab_Result_Filename_Parser_Test extends TestCase {

	/**
	 * Tests valid filenames and their parsed document values.
	 *
	 * @param string $filename   Expected original filename.
	 * @param string $menu_start Expected menu start date.
	 * @param string $menu_end   Expected menu end date.
	 * @param string $result_date Expected laboratory result date.
	 * @param string $name       Expected extracted name.
	 * @return void
	 */
	#[DataProvider( 'provide_valid_filenames' )]
	public function test_parses_valid_filename(
		string $filename,
		string $menu_start,
		string $menu_end,
		string $result_date,
		string $name
	): void {
		$result = ( new ZFDZ_Lab_Result_Filename_Parser() )->parse( $filename );

		$this->assertTrue( $result->is_valid() );
		$this->assertNull( $result->get_error_code() );
		$this->assertInstanceOf( ZFDZ_Lab_Result_Document::class, $result->get_document() );

		$document = $result->get_document();

		$this->assertSame( $filename, $document->get_original_filename() );
		$this->assertSame( $menu_start, $document->get_menu_start_date() );
		$this->assertSame( $menu_end, $document->get_menu_end_date() );
		$this->assertSame( $result_date, $document->get_result_date() );
		$this->assertSame( $name, $document->get_name() );
	}

	/**
	 * Provides valid laboratory result document filenames.
	 *
	 * @return array<string, array{string, string, string, string, string}>
	 */
	public static function provide_valid_filenames(): array {
		return array(
			'ordinary name'             => array(
				'2026-08-21_2026-08-31_2026-08-27_Badanie-mikrobiologiczne.pdf',
				'2026-08-21',
				'2026-08-31',
				'2026-08-27',
				'Badanie-mikrobiologiczne',
			),
			'name containing spaces'    => array(
				'2026-08-21_2026-08-31_2026-08-27_Badanie mikrobiologiczne.pdf',
				'2026-08-21',
				'2026-08-31',
				'2026-08-27',
				'Badanie mikrobiologiczne',
			),
			'polish characters'         => array(
				'2026-08-21_2026-08-31_2026-08-27_Badanie jakości żywności.pdf',
				'2026-08-21',
				'2026-08-31',
				'2026-08-27',
				'Badanie jakości żywności',
			),
			'ampersand'                 => array(
				'2026-08-21_2026-08-31_2026-08-27_Badanie A & B.pdf',
				'2026-08-21',
				'2026-08-31',
				'2026-08-27',
				'Badanie A & B',
			),
			'underscore'                => array(
				'2026-08-21_2026-08-31_2026-08-27_Badanie_kontrolne.pdf',
				'2026-08-21',
				'2026-08-31',
				'2026-08-27',
				'Badanie_kontrolne',
			),
			'digit'                     => array(
				'2026-08-21_2026-08-31_2026-08-27_Badanie 2.pdf',
				'2026-08-21',
				'2026-08-31',
				'2026-08-27',
				'Badanie 2',
			),
			'uppercase extension'       => array(
				'2026-08-21_2026-08-31_2026-08-27_Badanie.PDF',
				'2026-08-21',
				'2026-08-31',
				'2026-08-27',
				'Badanie',
			),
			'leap day'                  => array(
				'2024-02-29_2024-03-05_2024-02-29_Badanie.pdf',
				'2024-02-29',
				'2024-03-05',
				'2024-02-29',
				'Badanie',
			),
			'single-day menu period'    => array(
				'2026-08-21_2026-08-21_2026-08-25_Badanie.pdf',
				'2026-08-21',
				'2026-08-21',
				'2026-08-25',
				'Badanie',
			),
			'result date inside period' => array(
				'2026-08-21_2026-08-31_2026-08-25_Badanie.pdf',
				'2026-08-21',
				'2026-08-31',
				'2026-08-25',
				'Badanie',
			),
			'result date after period'  => array(
				'2026-08-01_2026-08-10_2026-08-15_Badanie.pdf',
				'2026-08-01',
				'2026-08-10',
				'2026-08-15',
				'Badanie',
			),
			'result date before period' => array(
				'2026-08-21_2026-08-31_2026-08-15_Badanie.pdf',
				'2026-08-21',
				'2026-08-31',
				'2026-08-15',
				'Badanie',
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
		$result = ( new ZFDZ_Lab_Result_Filename_Parser() )->parse( $filename );

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
			'filename without dates'      => array(
				'Badanie.pdf',
				ZFDZ_Lab_Result_Filename_Parser::ERROR_INVALID_FORMAT,
			),
			'filename with one date'      => array(
				'2026-08-21_Badanie.pdf',
				ZFDZ_Lab_Result_Filename_Parser::ERROR_INVALID_FORMAT,
			),
			'filename with two dates'     => array(
				'2026-08-21_2026-08-31_Badanie.pdf',
				ZFDZ_Lab_Result_Filename_Parser::ERROR_INVALID_FORMAT,
			),
			'missing name separator'      => array(
				'2026-08-21_2026-08-31_2026-08-27.pdf',
				ZFDZ_Lab_Result_Filename_Parser::ERROR_INVALID_FORMAT,
			),
			'invalid date separators'     => array(
				'2026_08_21_2026_08_31_2026_08_27_Badanie.pdf',
				ZFDZ_Lab_Result_Filename_Parser::ERROR_INVALID_FORMAT,
			),
			'invalid menu start day'      => array(
				'2026-02-30_2026-03-10_2026-03-05_Badanie.pdf',
				ZFDZ_Lab_Result_Filename_Parser::ERROR_INVALID_MENU_START_DATE,
			),
			'invalid menu start month'    => array(
				'2026-13-01_2026-09-10_2026-09-05_Badanie.pdf',
				ZFDZ_Lab_Result_Filename_Parser::ERROR_INVALID_MENU_START_DATE,
			),
			'invalid menu end day'        => array(
				'2026-02-01_2026-02-30_2026-02-15_Badanie.pdf',
				ZFDZ_Lab_Result_Filename_Parser::ERROR_INVALID_MENU_END_DATE,
			),
			'zero menu end month'         => array(
				'2026-01-01_2026-00-10_2026-01-05_Badanie.pdf',
				ZFDZ_Lab_Result_Filename_Parser::ERROR_INVALID_MENU_END_DATE,
			),
			'invalid result day'          => array(
				'2026-02-01_2026-02-28_2026-02-30_Badanie.pdf',
				ZFDZ_Lab_Result_Filename_Parser::ERROR_INVALID_RESULT_DATE,
			),
			'invalid result month'        => array(
				'2026-01-01_2026-01-10_2026-13-01_Badanie.pdf',
				ZFDZ_Lab_Result_Filename_Parser::ERROR_INVALID_RESULT_DATE,
			),
			'non-leap result date'        => array(
				'2023-02-01_2023-03-05_2023-02-29_Badanie.pdf',
				ZFDZ_Lab_Result_Filename_Parser::ERROR_INVALID_RESULT_DATE,
			),
			'menu end before start'       => array(
				'2026-08-31_2026-08-21_2026-08-25_Badanie.pdf',
				ZFDZ_Lab_Result_Filename_Parser::ERROR_INVALID_MENU_DATE_RANGE,
			),
			'unsupported extension'       => array(
				'2026-08-21_2026-08-31_2026-08-27_Badanie.txt',
				ZFDZ_Lab_Result_Filename_Parser::ERROR_UNSUPPORTED_EXTENSION,
			),
			'double extension'            => array(
				'2026-08-21_2026-08-31_2026-08-27_Badanie.pdf.exe',
				ZFDZ_Lab_Result_Filename_Parser::ERROR_UNSUPPORTED_EXTENSION,
			),
			'parent Unix path'            => array(
				'../2026-08-21_2026-08-31_2026-08-27_Badanie.pdf',
				ZFDZ_Lab_Result_Filename_Parser::ERROR_INVALID_PATH,
			),
			'parent Windows path'         => array(
				'..\\2026-08-21_2026-08-31_2026-08-27_Badanie.pdf',
				ZFDZ_Lab_Result_Filename_Parser::ERROR_INVALID_PATH,
			),
			'Unix folder path'            => array(
				'folder/2026-08-21_2026-08-31_2026-08-27_Badanie.pdf',
				ZFDZ_Lab_Result_Filename_Parser::ERROR_INVALID_PATH,
			),
			'Windows folder path'         => array(
				'folder\\2026-08-21_2026-08-31_2026-08-27_Badanie.pdf',
				ZFDZ_Lab_Result_Filename_Parser::ERROR_INVALID_PATH,
			),
			'NUL byte'                    => array(
				"2026-08-21_2026-08-31_2026-08-27_Badanie\0.pdf",
				ZFDZ_Lab_Result_Filename_Parser::ERROR_INVALID_PATH,
			),
			'empty name'                  => array(
				'2026-08-21_2026-08-31_2026-08-27_.pdf',
				ZFDZ_Lab_Result_Filename_Parser::ERROR_INVALID_NAME,
			),
			'single dot name'             => array(
				'2026-08-21_2026-08-31_2026-08-27_..pdf',
				ZFDZ_Lab_Result_Filename_Parser::ERROR_INVALID_NAME,
			),
			'double dot name'             => array(
				'2026-08-21_2026-08-31_2026-08-27_...pdf',
				ZFDZ_Lab_Result_Filename_Parser::ERROR_INVALID_NAME,
			),
			'whitespace-only name'        => array(
				'2026-08-21_2026-08-31_2026-08-27_   .pdf',
				ZFDZ_Lab_Result_Filename_Parser::ERROR_INVALID_NAME,
			),
			'leading whitespace in name'  => array(
				'2026-08-21_2026-08-31_2026-08-27_ Badanie.pdf',
				ZFDZ_Lab_Result_Filename_Parser::ERROR_INVALID_NAME,
			),
			'trailing whitespace in name' => array(
				'2026-08-21_2026-08-31_2026-08-27_Badanie .pdf',
				ZFDZ_Lab_Result_Filename_Parser::ERROR_INVALID_NAME,
			),
			'tab in name'                 => array(
				"2026-08-21_2026-08-31_2026-08-27_Badanie\tkontrolne.pdf",
				ZFDZ_Lab_Result_Filename_Parser::ERROR_INVALID_NAME,
			),
			'line feed in name'           => array(
				"2026-08-21_2026-08-31_2026-08-27_Badanie\nkontrolne.pdf",
				ZFDZ_Lab_Result_Filename_Parser::ERROR_INVALID_NAME,
			),
			'carriage return in name'     => array(
				"2026-08-21_2026-08-31_2026-08-27_Badanie\rkontrolne.pdf",
				ZFDZ_Lab_Result_Filename_Parser::ERROR_INVALID_NAME,
			),
			'Unicode control in name'     => array(
				"2026-08-21_2026-08-31_2026-08-27_Badanie\u{0085}kontrolne.pdf",
				ZFDZ_Lab_Result_Filename_Parser::ERROR_INVALID_NAME,
			),
			'invalid UTF-8 name'          => array(
				"2026-08-21_2026-08-31_2026-08-27_\xC3\x28.pdf",
				ZFDZ_Lab_Result_Filename_Parser::ERROR_INVALID_NAME,
			),
		);
	}

	/**
	 * Rejects an empty machine-readable error code.
	 *
	 * @return void
	 */
	public function test_parse_result_rejects_empty_error_code(): void {
		$this->expectException( InvalidArgumentException::class );

		ZFDZ_Lab_Result_Filename_Parse_Result::from_error( '' );
	}

	/**
	 * Confirms that the document model exposes no path or URL accessors.
	 *
	 * @return void
	 */
	public function test_document_model_does_not_expose_paths_or_urls(): void {
		$this->assertFalse( method_exists( ZFDZ_Lab_Result_Document::class, 'get_path' ) );
		$this->assertFalse( method_exists( ZFDZ_Lab_Result_Document::class, 'get_file_path' ) );
		$this->assertFalse( method_exists( ZFDZ_Lab_Result_Document::class, 'get_absolute_path' ) );
		$this->assertFalse( method_exists( ZFDZ_Lab_Result_Document::class, 'get_url' ) );
	}
}
