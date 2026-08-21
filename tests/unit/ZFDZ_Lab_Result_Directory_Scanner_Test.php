<?php
/**
 * Tests the standalone laboratory result directory scanner.
 *
 * @package ZywienieDlaZdrowia
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests non-recursive and deterministic laboratory result scanning.
 */
final class ZFDZ_Lab_Result_Directory_Scanner_Test extends TestCase {

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
			. 'zfdz-lab-result-scanner-'
			. bin2hex( random_bytes( 8 ) );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- Standalone filesystem test fixture.
		$this->assertTrue( mkdir( $this->temporary_directory, 0700 ) );
	}

	/**
	 * Removes every temporary entry created by the test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		$this->remove_directory( $this->temporary_directory );
	}

	/**
	 * Reports a missing directory without entry-level results.
	 *
	 * @return void
	 */
	public function test_reports_missing_directory(): void {
		$result = $this->create_scanner()->scan(
			$this->temporary_directory . DIRECTORY_SEPARATOR . 'missing'
		);

		$this->assert_directory_failure(
			$result,
			ZFDZ_Lab_Result_Directory_Scanner::ERROR_DIRECTORY_NOT_FOUND
		);
	}

	/**
	 * Reports a regular file supplied as the directory path.
	 *
	 * @return void
	 */
	public function test_reports_regular_file_as_not_a_directory(): void {
		$file_path = $this->create_file( 'not-a-directory', 'content' );

		$result = $this->create_scanner()->scan( $file_path );

		$this->assert_directory_failure(
			$result,
			ZFDZ_Lab_Result_Directory_Scanner::ERROR_NOT_A_DIRECTORY
		);
	}

	/**
	 * Treats an empty directory as a successful empty scan.
	 *
	 * @return void
	 */
	public function test_scans_empty_directory_successfully(): void {
		$result = $this->create_scanner()->scan( $this->temporary_directory );

		$this->assertTrue( $result->is_successful() );
		$this->assertNull( $result->get_directory_error_code() );
		$this->assertSame( array(), $result->get_documents() );
		$this->assertSame( array(), $result->get_issues() );
	}

	/**
	 * Returns a document recognized solely from a regular file basename.
	 *
	 * @return void
	 */
	public function test_returns_recognized_filename_document(): void {
		$filename = '2026-08-21_2026-08-31_2026-08-27_Badanie mikrobiologiczne.pdf';
		$this->create_file( $filename, 'content is not read by the scanner' );

		$result = $this->create_scanner()->scan( $this->temporary_directory );

		$this->assertTrue( $result->is_successful() );
		$this->assertSame( array( $filename ), $this->get_document_filenames( $result->get_documents() ) );
		$this->assertSame( array(), $result->get_issues() );
	}

	/**
	 * Sorts multiple recognized filenames using binary comparison.
	 *
	 * @return void
	 */
	public function test_sorts_multiple_recognized_documents_by_filename(): void {
		$expected = array(
			'2026-08-01_2026-08-10_2026-08-15_Badanie B.pdf',
			'2026-08-21_2026-08-31_2026-08-25_Badanie A.pdf',
			'2026-08-21_2026-08-31_2026-08-27_Badanie C.pdf',
		);

		$this->create_file( $expected[2], 'third' );
		$this->create_file( $expected[0], 'first' );
		$this->create_file( $expected[1], 'second' );

		$result = $this->create_scanner()->scan( $this->temporary_directory );

		$this->assertSame( $expected, $this->get_document_filenames( $result->get_documents() ) );
	}

	/**
	 * Preserves the parser extension error for a regular non-PDF file.
	 *
	 * @return void
	 */
	public function test_reports_unsupported_extension(): void {
		$this->create_file( 'notes.txt', 'notes' );

		$result = $this->create_scanner()->scan( $this->temporary_directory );

		$this->assert_issue(
			$result,
			'notes.txt',
			ZFDZ_Lab_Result_Filename_Parser::ERROR_UNSUPPORTED_EXTENSION
		);
	}

	/**
	 * Preserves the parser format error for a malformed laboratory filename.
	 *
	 * @return void
	 */
	public function test_reports_malformed_laboratory_filename(): void {
		$this->create_file( 'badanie-final.pdf', 'content' );

		$result = $this->create_scanner()->scan( $this->temporary_directory );

		$this->assert_issue(
			$result,
			'badanie-final.pdf',
			ZFDZ_Lab_Result_Filename_Parser::ERROR_INVALID_FORMAT
		);
	}

	/**
	 * Preserves an invalid referenced menu date error.
	 *
	 * @return void
	 */
	public function test_reports_invalid_menu_date(): void {
		$filename = '2026-02-30_2026-03-10_2026-03-05_Badanie.pdf';
		$this->create_file( $filename, 'content' );

		$result = $this->create_scanner()->scan( $this->temporary_directory );

		$this->assert_issue(
			$result,
			$filename,
			ZFDZ_Lab_Result_Filename_Parser::ERROR_INVALID_MENU_START_DATE
		);
	}

	/**
	 * Preserves an invalid result date error.
	 *
	 * @return void
	 */
	public function test_reports_invalid_result_date(): void {
		$filename = '2026-02-01_2026-02-28_2026-02-30_Badanie.pdf';
		$this->create_file( $filename, 'content' );

		$result = $this->create_scanner()->scan( $this->temporary_directory );

		$this->assert_issue(
			$result,
			$filename,
			ZFDZ_Lab_Result_Filename_Parser::ERROR_INVALID_RESULT_DATE
		);
	}

	/**
	 * Preserves an invalid extracted name error.
	 *
	 * @return void
	 */
	public function test_reports_invalid_name(): void {
		$filename = '2026-08-21_2026-08-31_2026-08-27_...pdf';
		$this->create_file( $filename, 'content' );

		$result = $this->create_scanner()->scan( $this->temporary_directory );

		$this->assert_issue(
			$result,
			$filename,
			ZFDZ_Lab_Result_Filename_Parser::ERROR_INVALID_NAME
		);
	}

	/**
	 * Does not rescue an invalid filename based on valid-looking PDF content.
	 *
	 * @return void
	 */
	public function test_does_not_rescue_invalid_filename_with_pdf_content(): void {
		$this->create_file( 'badanie-final.pdf', "%PDF-1.7\n%%EOF\n" );

		$result = $this->create_scanner()->scan( $this->temporary_directory );

		$this->assertSame( array(), $result->get_documents() );
		$this->assert_issue(
			$result,
			'badanie-final.pdf',
			ZFDZ_Lab_Result_Filename_Parser::ERROR_INVALID_FORMAT
		);
	}

	/**
	 * Reports a directory entry and never scans its nested document.
	 *
	 * @return void
	 */
	public function test_reports_directory_without_recursive_scanning(): void {
		$this->create_directory( 'archive' );
		$this->create_file(
			'archive' . DIRECTORY_SEPARATOR . '2026-08-21_2026-08-31_2026-08-27_Nested.pdf',
			'content'
		);

		$result = $this->create_scanner()->scan( $this->temporary_directory );

		$this->assertSame( array(), $result->get_documents() );
		$this->assert_issue(
			$result,
			'archive',
			ZFDZ_Lab_Result_Scan_Issue::ERROR_UNSUPPORTED_ENTRY_TYPE
		);
	}

	/**
	 * Rejects a symlink before treating it as a regular file.
	 *
	 * @return void
	 */
	public function test_reports_symlink_without_following_target(): void {
		$scan_directory = $this->create_directory( 'results' );
		$target_path    = $this->create_file( 'target.pdf', 'target content' );
		$link_name      = '2026-08-21_2026-08-31_2026-08-27_Link.pdf';
		$link_path      = $scan_directory . DIRECTORY_SEPARATOR . $link_name;

		if ( ! $this->create_symlink( $target_path, $link_path ) ) {
			$this->markTestSkipped( 'The current environment does not permit creating symbolic links.' );
		}

		$result = $this->create_scanner()->scan( $scan_directory );

		$this->assertSame( array(), $result->get_documents() );
		$this->assert_issue(
			$result,
			$link_name,
			ZFDZ_Lab_Result_Scan_Issue::ERROR_UNSAFE_SYMLINK
		);
	}

	/**
	 * Recognizes a broken symlink as unsafe rather than a normal missing file.
	 *
	 * @return void
	 */
	public function test_reports_broken_symlink_as_unsafe(): void {
		$scan_directory = $this->create_directory( 'broken-results' );
		$link_name      = '2026-08-21_2026-08-31_2026-08-27_Broken.pdf';
		$link_path      = $scan_directory . DIRECTORY_SEPARATOR . $link_name;
		$missing_target = $this->temporary_directory . DIRECTORY_SEPARATOR . 'missing-target.pdf';

		if ( ! $this->create_symlink( $missing_target, $link_path ) ) {
			$this->markTestSkipped( 'The current environment does not permit creating symbolic links.' );
		}

		$result = $this->create_scanner()->scan( $scan_directory );

		$this->assertSame( array(), $result->get_documents() );
		$this->assert_issue(
			$result,
			$link_name,
			ZFDZ_Lab_Result_Scan_Issue::ERROR_UNSAFE_SYMLINK
		);
	}

	/**
	 * Sorts scanner issues independently of filesystem iteration order.
	 *
	 * @return void
	 */
	public function test_sorts_issues_deterministically(): void {
		$this->create_file( 'z-notes.txt', 'notes' );
		$this->create_directory( 'm-directory' );
		$this->create_file( 'a-badanie.pdf', 'content' );

		$result = $this->create_scanner()->scan( $this->temporary_directory );

		$this->assertSame(
			array( 'a-badanie.pdf', 'm-directory', 'z-notes.txt' ),
			$this->get_issue_names( $result->get_issues() )
		);
	}

	/**
	 * Does not expose the source directory through scan models.
	 *
	 * @return void
	 */
	public function test_scan_models_do_not_expose_source_paths(): void {
		$this->create_file( 'notes.txt', 'notes' );

		$result = $this->create_scanner()->scan( $this->temporary_directory );
		$issue  = $result->get_issues()[0];

		$this->assertFalse( method_exists( $result, 'get_directory' ) );
		$this->assertFalse( method_exists( $result, 'get_directory_path' ) );
		$this->assertFalse( method_exists( $result, 'get_absolute_path' ) );
		$this->assertFalse( method_exists( $issue, 'get_path' ) );
		$this->assertFalse( method_exists( $issue, 'get_file_path' ) );
		$this->assertFalse( method_exists( $issue, 'get_url' ) );
		$this->assertSame( 'notes.txt', $issue->get_entry_name() );
		$this->assertStringNotContainsString( $this->temporary_directory, $issue->get_entry_name() );
	}

	/**
	 * Creates a successful scan without a directory error and reindexes lists.
	 *
	 * @return void
	 */
	public function test_successful_scan_result_invariant_and_reindexing(): void {
		$document = $this->create_document_model();
		$issue    = new ZFDZ_Lab_Result_Scan_Issue( 'notes.txt', 'unsupported_extension' );

		$result = ZFDZ_Lab_Result_Scan_Result::from_scan(
			array( 4 => $document ),
			array( 8 => $issue )
		);

		$this->assertTrue( $result->is_successful() );
		$this->assertNull( $result->get_directory_error_code() );
		$this->assertSame( array( $document ), $result->get_documents() );
		$this->assertSame( array( $issue ), $result->get_issues() );
	}

	/**
	 * Rejects an invalid successful-scan document element.
	 *
	 * @return void
	 */
	public function test_successful_scan_result_rejects_invalid_document_element(): void {
		$this->expectException( InvalidArgumentException::class );

		ZFDZ_Lab_Result_Scan_Result::from_scan( array( 'invalid' ), array() );
	}

	/**
	 * Rejects an invalid successful-scan issue element.
	 *
	 * @return void
	 */
	public function test_successful_scan_result_rejects_invalid_issue_element(): void {
		$this->expectException( InvalidArgumentException::class );

		ZFDZ_Lab_Result_Scan_Result::from_scan( array(), array( 'invalid' ) );
	}

	/**
	 * Creates a failed scan with empty collections.
	 *
	 * @return void
	 */
	public function test_failed_scan_result_invariant(): void {
		$result = ZFDZ_Lab_Result_Scan_Result::from_directory_error( 'directory_not_found' );

		$this->assertFalse( $result->is_successful() );
		$this->assertSame( 'directory_not_found', $result->get_directory_error_code() );
		$this->assertSame( array(), $result->get_documents() );
		$this->assertSame( array(), $result->get_issues() );
	}

	/**
	 * Rejects an empty directory error code.
	 *
	 * @return void
	 */
	public function test_scan_result_rejects_empty_directory_error_code(): void {
		$this->expectException( InvalidArgumentException::class );

		ZFDZ_Lab_Result_Scan_Result::from_directory_error( '' );
	}

	/**
	 * Rejects an empty scan issue entry name.
	 *
	 * @return void
	 */
	public function test_scan_issue_rejects_empty_entry_name(): void {
		$this->expectException( InvalidArgumentException::class );

		new ZFDZ_Lab_Result_Scan_Issue( '', 'invalid_format' );
	}

	/**
	 * Rejects an empty scan issue error code.
	 *
	 * @return void
	 */
	public function test_scan_issue_rejects_empty_error_code(): void {
		$this->expectException( InvalidArgumentException::class );

		new ZFDZ_Lab_Result_Scan_Issue( 'file.pdf', '' );
	}

	/**
	 * Creates the standalone scanner.
	 *
	 * @return ZFDZ_Lab_Result_Directory_Scanner
	 */
	private function create_scanner(): ZFDZ_Lab_Result_Directory_Scanner {
		return new ZFDZ_Lab_Result_Directory_Scanner( new ZFDZ_Lab_Result_Filename_Parser() );
	}

	/**
	 * Creates one valid document model for result invariant tests.
	 *
	 * @return ZFDZ_Lab_Result_Document
	 */
	private function create_document_model(): ZFDZ_Lab_Result_Document {
		return new ZFDZ_Lab_Result_Document(
			'2026-08-21_2026-08-31_2026-08-27_Badanie.pdf',
			'2026-08-21',
			'2026-08-31',
			'2026-08-27',
			'Badanie'
		);
	}

	/**
	 * Creates a directory below the current test root.
	 *
	 * @param string $relative_path Relative directory path.
	 * @return string
	 */
	private function create_directory( string $relative_path ): string {
		$directory = $this->temporary_directory . DIRECTORY_SEPARATOR . $relative_path;

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- Standalone filesystem test fixture.
		$this->assertTrue( mkdir( $directory, 0700, true ) );

		return $directory;
	}

	/**
	 * Creates a file below the current test root.
	 *
	 * @param string $relative_path Relative file path.
	 * @param string $contents      Fixture contents.
	 * @return string
	 */
	private function create_file( string $relative_path, string $contents ): string {
		$file_path = $this->temporary_directory . DIRECTORY_SEPARATOR . $relative_path;
		$file      = new SplFileObject( $file_path, 'wb' );

		$file->fwrite( $contents );

		return $file_path;
	}

	/**
	 * Attempts to create a symlink with expected platform warnings contained.
	 *
	 * @param string $target_path Target path inside the temporary root or intentionally missing.
	 * @param string $link_path   Link path inside the temporary root.
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
	 * Removes a temporary directory tree without following symlinks.
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
				// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Standalone cleanup restricted to its unique temporary root.
				unlink( $entry->getPathname() );
				continue;
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Standalone cleanup restricted to its unique temporary root.
			rmdir( $entry->getPathname() );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Standalone cleanup restricted to its unique temporary root.
		rmdir( $directory );
	}

	/**
	 * Returns original filenames from document models.
	 *
	 * @param array $documents Documents.
	 * @return list<string>
	 */
	private function get_document_filenames( array $documents ): array {
		return array_map(
			static function ( ZFDZ_Lab_Result_Document $document ): string {
				return $document->get_original_filename();
			},
			$documents
		);
	}

	/**
	 * Returns issue entry names.
	 *
	 * @param array $issues Issues.
	 * @return list<string>
	 */
	private function get_issue_names( array $issues ): array {
		return array_map(
			static function ( ZFDZ_Lab_Result_Scan_Issue $issue ): string {
				return $issue->get_entry_name();
			},
			$issues
		);
	}

	/**
	 * Asserts one entry-level issue.
	 *
	 * @param ZFDZ_Lab_Result_Scan_Result $result     Scan result.
	 * @param string                      $entry_name Expected entry name.
	 * @param string                      $error_code Expected error code.
	 * @return void
	 */
	private function assert_issue(
		ZFDZ_Lab_Result_Scan_Result $result,
		string $entry_name,
		string $error_code
	): void {
		$this->assertTrue( $result->is_successful() );
		$this->assertCount( 1, $result->get_issues() );
		$this->assertSame( $entry_name, $result->get_issues()[0]->get_entry_name() );
		$this->assertSame( $error_code, $result->get_issues()[0]->get_error_code() );
	}

	/**
	 * Asserts a directory-level failure.
	 *
	 * @param ZFDZ_Lab_Result_Scan_Result $result     Scan result.
	 * @param string                      $error_code Expected directory error code.
	 * @return void
	 */
	private function assert_directory_failure(
		ZFDZ_Lab_Result_Scan_Result $result,
		string $error_code
	): void {
		$this->assertFalse( $result->is_successful() );
		$this->assertSame( $error_code, $result->get_directory_error_code() );
		$this->assertSame( array(), $result->get_documents() );
		$this->assertSame( array(), $result->get_issues() );
	}
}
