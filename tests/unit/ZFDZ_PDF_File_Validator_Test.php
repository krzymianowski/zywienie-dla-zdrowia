<?php
/**
 * Tests the standalone PDF candidate file validator.
 *
 * @package ZywienieDlaZdrowia
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests bounded PDF candidate checks without WordPress.
 */
final class ZFDZ_PDF_File_Validator_Test extends TestCase {

	private const VALID_PDF_CONTENT = "%PDF-1.7\n"
		. "1 0 obj\n"
		. "<< /Type /Catalog >>\n"
		. "endobj\n"
		. "xref\n"
		. "0 1\n"
		. "0000000000 65535 f \n"
		. "trailer\n"
		. "<< /Root 1 0 R /Size 1 >>\n"
		. "startxref\n"
		. "9\n"
		. "%%EOF\n";

	/**
	 * Temporary directory owned by the current test.
	 *
	 * @var string
	 */
	private string $temporary_directory;

	/**
	 * Creates a unique temporary directory.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->temporary_directory = sys_get_temp_dir()
			. DIRECTORY_SEPARATOR
			. 'zfdz-pdf-validator-'
			. bin2hex( random_bytes( 8 ) );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- Standalone filesystem test fixture.
		$this->assertTrue( mkdir( $this->temporary_directory, 0700 ) );
	}

	/**
	 * Removes every temporary entry created by the current test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		$this->remove_directory( $this->temporary_directory );
	}

	/**
	 * Reports a missing file before attempting MIME or content checks.
	 *
	 * @return void
	 */
	public function test_reports_missing_file(): void {
		$result = $this->create_validator()->validate(
			$this->temporary_directory . DIRECTORY_SEPARATOR . 'missing.pdf'
		);

		$this->assert_invalid_result( $result, ZFDZ_PDF_File_Validator::ERROR_FILE_NOT_FOUND );
		$this->assertFalse( $result->was_mime_check_performed() );
		$this->assertNull( $result->get_detected_mime_type() );
	}

	/**
	 * Reports a directory passed in place of a regular file.
	 *
	 * @return void
	 */
	public function test_reports_directory_path(): void {
		$result = $this->create_validator()->validate( $this->temporary_directory );

		$this->assert_invalid_result( $result, ZFDZ_PDF_File_Validator::ERROR_NOT_A_REGULAR_FILE );
	}

	/**
	 * Reports an empty regular file.
	 *
	 * @return void
	 */
	public function test_reports_empty_file(): void {
		$file_path = $this->create_file( 'empty.pdf' );
		$result    = $this->create_validator()->validate( $file_path );

		$this->assert_invalid_result( $result, ZFDZ_PDF_File_Validator::ERROR_EMPTY_FILE );
		$this->assertFalse( $result->was_mime_check_performed() );
	}

	/**
	 * Accepts a synthetic PDF candidate independently of its extension.
	 *
	 * @return void
	 */
	public function test_accepts_valid_pdf_candidate_with_non_pdf_extension(): void {
		$file_path = $this->create_file( 'candidate.bin', self::VALID_PDF_CONTENT );
		$result    = $this->create_validator()->validate( $file_path );

		$this->assertTrue( $result->is_valid_candidate() );
		$this->assertNull( $result->get_error_code() );

		if ( class_exists( 'finfo' ) ) {
			$this->assertTrue( $result->was_mime_check_performed() );
			$this->assertContains(
				$result->get_detected_mime_type(),
				array( 'application/pdf', 'application/x-pdf' )
			);
		} else {
			$this->assertFalse( $result->was_mime_check_performed() );
			$this->assertNull( $result->get_detected_mime_type() );
		}
	}

	/**
	 * Rejects content without a PDF header.
	 *
	 * @return void
	 */
	public function test_rejects_invalid_pdf_header(): void {
		$file_path = $this->create_file( 'invalid-header.pdf', "This is not a PDF\n%%EOF\n" );
		$result    = $this->create_validator()->validate( $file_path );

		$this->assertFalse( $result->is_valid_candidate() );
		$this->assertContains(
			$result->get_error_code(),
			array(
				ZFDZ_PDF_File_Validator::ERROR_UNSUPPORTED_MIME_TYPE,
				ZFDZ_PDF_File_Validator::ERROR_INVALID_PDF_HEADER,
			)
		);
	}

	/**
	 * Rejects a PDF signature that appears after the beginning of the file.
	 *
	 * @return void
	 */
	public function test_rejects_pdf_header_later_in_file(): void {
		$file_path = $this->create_file(
			'late-header.pdf',
			"junk\n" . self::VALID_PDF_CONTENT
		);
		$result    = $this->create_validator()->validate( $file_path );

		$this->assertFalse( $result->is_valid_candidate() );
		$this->assertContains(
			$result->get_error_code(),
			array(
				ZFDZ_PDF_File_Validator::ERROR_UNSUPPORTED_MIME_TYPE,
				ZFDZ_PDF_File_Validator::ERROR_INVALID_PDF_HEADER,
			)
		);
	}

	/**
	 * Rejects a PDF candidate without an EOF marker.
	 *
	 * @return void
	 */
	public function test_rejects_missing_pdf_eof_marker(): void {
		$file_path = $this->create_file(
			'missing-eof.pdf',
			"%PDF-1.7\n1 0 obj\n<<>>\nendobj\n"
		);
		$result    = $this->create_validator()->validate( $file_path );

		$this->assert_invalid_result( $result, ZFDZ_PDF_File_Validator::ERROR_INVALID_PDF_EOF );
	}

	/**
	 * Allows trailing whitespace and small trailing data after the EOF marker.
	 *
	 * @return void
	 */
	public function test_accepts_eof_marker_with_small_trailing_data(): void {
		$file_path = $this->create_file(
			'trailing-data.pdf',
			self::VALID_PDF_CONTENT . str_repeat( " \r\n", 100 ) . 'trailing-data'
		);
		$result    = $this->create_validator()->validate( $file_path );

		$this->assertTrue( $result->is_valid_candidate() );
		$this->assertNull( $result->get_error_code() );
	}

	/**
	 * Searches for EOF only in the bounded final 4096-byte fragment.
	 *
	 * @return void
	 */
	public function test_does_not_accept_eof_outside_bounded_tail(): void {
		$file_path = $this->create_file(
			'eof-outside-tail.pdf',
			self::VALID_PDF_CONTENT . str_repeat( 'A', 4097 )
		);
		$result    = $this->create_validator()->validate( $file_path );

		$this->assert_invalid_result( $result, ZFDZ_PDF_File_Validator::ERROR_INVALID_PDF_EOF );
	}

	/**
	 * Rejects a MIME type that is clearly not a PDF when fileinfo is available.
	 *
	 * @return void
	 */
	public function test_rejects_mime_type_mismatch(): void {
		if ( ! class_exists( 'finfo' ) ) {
			$this->markTestSkipped( 'The fileinfo extension is not available.' );
		}

		$file_path = $this->create_file( 'plain-text.dat', "This is plain text and not a PDF.\n" );
		$result    = $this->create_validator()->validate( $file_path );

		$this->assert_invalid_result( $result, ZFDZ_PDF_File_Validator::ERROR_UNSUPPORTED_MIME_TYPE );
		$this->assertTrue( $result->was_mime_check_performed() );
		$this->assertNotNull( $result->get_detected_mime_type() );
		$this->assertNotContains(
			$result->get_detected_mime_type(),
			array( 'application/pdf', 'application/x-pdf' )
		);
	}

	/**
	 * Rejects a symlink to a regular PDF candidate.
	 *
	 * @return void
	 */
	public function test_rejects_symlink_before_file_checks(): void {
		$target_path = $this->create_file( 'target.pdf', self::VALID_PDF_CONTENT );
		$link_path   = $this->temporary_directory . DIRECTORY_SEPARATOR . 'linked.pdf';

		if ( ! $this->create_symlink( $target_path, $link_path ) ) {
			$this->markTestSkipped( 'The current environment does not permit creating symbolic links.' );
		}

		$result = $this->create_validator()->validate( $link_path );

		$this->assert_invalid_result( $result, ZFDZ_PDF_File_Validator::ERROR_UNSAFE_SYMLINK );
	}

	/**
	 * Reports a broken symlink as unsafe rather than missing.
	 *
	 * @return void
	 */
	public function test_rejects_broken_symlink_before_existence_check(): void {
		$missing_target = $this->temporary_directory . DIRECTORY_SEPARATOR . 'missing-target.pdf';
		$link_path      = $this->temporary_directory . DIRECTORY_SEPARATOR . 'broken-link.pdf';

		if ( ! $this->create_symlink( $missing_target, $link_path ) ) {
			$this->markTestSkipped( 'The current environment does not permit creating symbolic links.' );
		}

		$result = $this->create_validator()->validate( $link_path );

		$this->assert_invalid_result( $result, ZFDZ_PDF_File_Validator::ERROR_UNSAFE_SYMLINK );
	}

	/**
	 * Does not execute PHP content while inspecting a candidate.
	 *
	 * @return void
	 */
	public function test_does_not_execute_php_content(): void {
		$file_path = $this->create_file(
			'content.php',
			"<?php throw new RuntimeException( 'Must not execute.' );"
		);
		$result    = $this->create_validator()->validate( $file_path );

		$this->assertFalse( $result->is_valid_candidate() );
		$this->assertContains(
			$result->get_error_code(),
			array(
				ZFDZ_PDF_File_Validator::ERROR_UNSUPPORTED_MIME_TYPE,
				ZFDZ_PDF_File_Validator::ERROR_INVALID_PDF_HEADER,
			)
		);
	}

	/**
	 * Does not expose or store a source path in the public result contract.
	 *
	 * @return void
	 */
	public function test_validation_result_does_not_expose_file_path(): void {
		$file_path      = $this->temporary_directory . DIRECTORY_SEPARATOR . 'missing.pdf';
		$result         = $this->create_validator()->validate( $file_path );
		$property_names = array_map(
			static function ( ReflectionProperty $property ): string {
				return $property->getName();
			},
			( new ReflectionObject( $result ) )->getProperties()
		);

		$this->assertFalse( method_exists( $result, 'get_path' ) );
		$this->assertFalse( method_exists( $result, 'get_file_path' ) );
		$this->assertFalse( method_exists( $result, 'get_absolute_path' ) );
		$this->assertNotContains( 'path', $property_names );
		$this->assertNotContains( 'file_path', $property_names );
		$this->assertNotContains( 'absolute_path', $property_names );
	}

	/**
	 * Rejects an empty error code in an invalid result factory.
	 *
	 * @return void
	 */
	public function test_validation_result_rejects_empty_error_code(): void {
		$this->expectException( InvalidArgumentException::class );

		ZFDZ_PDF_Validation_Result::from_error( '' );
	}

	/**
	 * Rejects MIME metadata when no MIME check was performed.
	 *
	 * @return void
	 */
	public function test_validation_result_rejects_contradictory_mime_state(): void {
		$this->expectException( InvalidArgumentException::class );

		ZFDZ_PDF_Validation_Result::from_valid_candidate( false, 'application/pdf' );
	}

	/**
	 * Creates the standalone validator.
	 *
	 * @return ZFDZ_PDF_File_Validator
	 */
	private function create_validator(): ZFDZ_PDF_File_Validator {
		return new ZFDZ_PDF_File_Validator();
	}

	/**
	 * Creates a file below the current test root.
	 *
	 * @param string $filename File name.
	 * @param string $contents Optional fixture content.
	 * @return string
	 */
	private function create_file( string $filename, string $contents = '' ): string {
		$file_path = $this->temporary_directory . DIRECTORY_SEPARATOR . $filename;
		$file      = new SplFileObject( $file_path, 'wb' );

		if ( '' !== $contents ) {
			$file->fwrite( $contents );
		}

		return $file_path;
	}

	/**
	 * Attempts to create a symlink while containing an expected permission warning.
	 *
	 * @param string $target_path Target path inside the current test root.
	 * @param string $link_path   Link path inside the current test root.
	 * @return bool
	 */
	private function create_symlink( string $target_path, string $link_path ): bool {
		$created = false;

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Captures an expected platform permission error from a symlink test.
		set_error_handler(
			static function (): bool {
				return true;
			}
		);

		try {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_symlink -- Required security behavior under test.
			$created = symlink( $target_path, $link_path );
		} finally {
			restore_error_handler();
		}

		return $created;
	}

	/**
	 * Removes the current temporary directory without following symlinks.
	 *
	 * @param string $directory Directory owned by the current test.
	 * @return void
	 */
	private function remove_directory( string $directory ): void {
		if ( ! is_dir( $directory ) ) {
			return;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $directory, FilesystemIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ( $iterator as $entry ) {
			if ( $entry->isLink() || $entry->isFile() ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Standalone test cleanup restricted to its unique temporary root.
				unlink( $entry->getPathname() );
				continue;
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Standalone test cleanup restricted to its unique temporary root.
			rmdir( $entry->getPathname() );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Standalone test cleanup restricted to its unique temporary root.
		rmdir( $directory );
	}

	/**
	 * Asserts the common invalid-result contract.
	 *
	 * @param ZFDZ_PDF_Validation_Result $result     Validation result.
	 * @param string                     $error_code Expected error code.
	 * @return void
	 */
	private function assert_invalid_result( ZFDZ_PDF_Validation_Result $result, string $error_code ): void {
		$this->assertFalse( $result->is_valid_candidate() );
		$this->assertSame( $error_code, $result->get_error_code() );
	}
}
