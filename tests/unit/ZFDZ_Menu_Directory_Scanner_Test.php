<?php
/**
 * Tests the standalone menu directory scanner.
 *
 * @package ZywienieDlaZdrowia
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests directory scanning without WordPress.
 */
final class ZFDZ_Menu_Directory_Scanner_Test extends TestCase {

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
			. 'zfdz-menu-scanner-'
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
	 * Reports a missing directory without exposing partial results.
	 *
	 * @return void
	 */
	public function test_reports_missing_directory(): void {
		$result = $this->create_scanner()->scan( $this->temporary_directory . DIRECTORY_SEPARATOR . 'missing' );

		$this->assert_directory_failure(
			$result,
			ZFDZ_Menu_Directory_Scanner::ERROR_DIRECTORY_NOT_FOUND
		);
	}

	/**
	 * Reports a regular file used as the source path.
	 *
	 * @return void
	 */
	public function test_reports_path_that_is_not_a_directory(): void {
		$file_path = $this->create_file( 'source.txt' );
		$result    = $this->create_scanner()->scan( $file_path );

		$this->assert_directory_failure(
			$result,
			ZFDZ_Menu_Directory_Scanner::ERROR_NOT_A_DIRECTORY
		);
	}

	/**
	 * Treats an empty directory as a successful scan.
	 *
	 * @return void
	 */
	public function test_scans_empty_directory(): void {
		$result = $this->create_scanner()->scan( $this->temporary_directory );

		$this->assertTrue( $result->is_successful() );
		$this->assertNull( $result->get_directory_error_code() );
		$this->assertSame( array(), $result->get_documents() );
		$this->assertSame( array(), $result->get_groups() );
		$this->assertSame( array(), $result->get_issues() );
	}

	/**
	 * Returns a recognized document and its period group.
	 *
	 * @return void
	 */
	public function test_scans_one_valid_document(): void {
		$filename = '2026-09-01_2026-09-10_dieta-podstawowa.pdf';
		$this->create_file( $filename );

		$result = $this->create_scanner()->scan( $this->temporary_directory );

		$this->assertTrue( $result->is_successful() );
		$this->assertNull( $result->get_directory_error_code() );
		$this->assertCount( 1, $result->get_documents() );
		$this->assertCount( 1, $result->get_groups() );
		$this->assertSame( array(), $result->get_issues() );

		$document = $result->get_documents()[0];

		$this->assertSame( $filename, $document->get_original_filename() );
		$this->assertSame( '2026-09-01', $document->get_start_date() );
		$this->assertSame( '2026-09-10', $document->get_end_date() );
		$this->assertSame( 'dieta-podstawowa', $document->get_name() );
	}

	/**
	 * Sorts multiple documents by filename dates and binary filename order.
	 *
	 * @return void
	 */
	public function test_sorts_multiple_documents_deterministically(): void {
		$filenames = array(
			'2026-09-01_2026-09-10_dieta-b.pdf',
			'2026-09-11_2026-09-20_dieta-c.pdf',
			'2026-09-01_2026-09-15_dieta-b.pdf',
			'2026-09-01_2026-09-10_dieta-a.pdf',
		);

		foreach ( $filenames as $filename ) {
			$this->create_file( $filename );
		}

		$result = $this->create_scanner()->scan( $this->temporary_directory );

		$this->assertTrue( $result->is_successful() );
		$this->assertSame(
			array(
				'2026-09-11_2026-09-20_dieta-c.pdf',
				'2026-09-01_2026-09-15_dieta-b.pdf',
				'2026-09-01_2026-09-10_dieta-a.pdf',
				'2026-09-01_2026-09-10_dieta-b.pdf',
			),
			$this->get_document_filenames( $result->get_documents() )
		);
	}

	/**
	 * Ignores modification times when sorting documents.
	 *
	 * @return void
	 */
	public function test_sorts_documents_independently_of_filemtime(): void {
		$newer_period_path = $this->create_file( '2026-10-01_2026-10-10_nowszy-okres.pdf' );
		$older_period_path = $this->create_file( '2026-01-01_2026-01-10_starszy-okres.pdf' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_touch -- Deliberately sets fixture metadata to verify it is ignored.
		$this->assertTrue( touch( $newer_period_path, 1577836800 ) );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_touch -- Deliberately sets fixture metadata to verify it is ignored.
		$this->assertTrue( touch( $older_period_path, 1893456000 ) );

		$result = $this->create_scanner()->scan( $this->temporary_directory );

		$this->assertSame(
			array(
				'2026-10-01_2026-10-10_nowszy-okres.pdf',
				'2026-01-01_2026-01-10_starszy-okres.pdf',
			),
			$this->get_document_filenames( $result->get_documents() )
		);
	}

	/**
	 * Groups documents with exactly the same start and end dates.
	 *
	 * @return void
	 */
	public function test_groups_documents_by_exact_period(): void {
		$this->create_file( '2026-09-11_2026-09-20_dieta-podstawowa.pdf' );
		$this->create_file( '2026-09-01_2026-09-10_dieta-podstawowa.pdf' );
		$this->create_file( '2026-09-01_2026-09-10_dieta-lekkostrawna.pdf' );

		$result = $this->create_scanner()->scan( $this->temporary_directory );
		$groups = $result->get_groups();

		$this->assertCount( 2, $groups );
		$this->assertSame( '2026-09-11', $groups[0]->get_start_date() );
		$this->assertSame( '2026-09-20', $groups[0]->get_end_date() );
		$this->assertCount( 1, $groups[0]->get_documents() );
		$this->assertSame( '2026-09-01', $groups[1]->get_start_date() );
		$this->assertSame( '2026-09-10', $groups[1]->get_end_date() );
		$this->assertSame(
			array(
				'2026-09-01_2026-09-10_dieta-lekkostrawna.pdf',
				'2026-09-01_2026-09-10_dieta-podstawowa.pdf',
			),
			$this->get_document_filenames( $groups[1]->get_documents() )
		);
	}

	/**
	 * Reports an unrecognized PDF through the parser error code.
	 *
	 * @return void
	 */
	public function test_reports_invalid_pdf_filename(): void {
		$this->create_file( 'jadlospis-final.pdf' );

		$result = $this->create_scanner()->scan( $this->temporary_directory );
		$issue  = $result->get_issues()[0];

		$this->assertTrue( $result->is_successful() );
		$this->assertSame( array(), $result->get_documents() );
		$this->assertSame( 'jadlospis-final.pdf', $issue->get_entry_name() );
		$this->assertSame( ZFDZ_Menu_Filename_Parser::ERROR_INVALID_FORMAT, $issue->get_error_code() );
	}

	/**
	 * Reports non-PDF files without executing PHP content.
	 *
	 * @return void
	 */
	public function test_reports_non_pdf_and_does_not_execute_php_file(): void {
		$this->create_file( 'notes.txt' );
		$this->create_file( 'index.php', "<?php throw new RuntimeException( 'This file must not execute.' );" );

		$result = $this->create_scanner()->scan( $this->temporary_directory );
		$issues = $result->get_issues();

		$this->assertTrue( $result->is_successful() );
		$this->assertCount( 2, $issues );
		$this->assertSame( 'index.php', $issues[0]->get_entry_name() );
		$this->assertSame( ZFDZ_Menu_Filename_Parser::ERROR_UNSUPPORTED_EXTENSION, $issues[0]->get_error_code() );
		$this->assertSame( 'notes.txt', $issues[1]->get_entry_name() );
		$this->assertSame( ZFDZ_Menu_Filename_Parser::ERROR_UNSUPPORTED_EXTENSION, $issues[1]->get_error_code() );
	}

	/**
	 * Reports a subdirectory and never scans its contents.
	 *
	 * @return void
	 */
	public function test_reports_subdirectory_without_scanning_it(): void {
		$this->create_directory( 'archive' );
		$this->create_file( 'archive' . DIRECTORY_SEPARATOR . '2025-01-01_2025-01-10_dieta-a.pdf' );

		$result = $this->create_scanner()->scan( $this->temporary_directory );
		$issues = $result->get_issues();

		$this->assertTrue( $result->is_successful() );
		$this->assertSame( array(), $result->get_documents() );
		$this->assertCount( 1, $issues );
		$this->assertSame( 'archive', $issues[0]->get_entry_name() );
		$this->assertSame( ZFDZ_Menu_Scan_Issue::ERROR_UNSUPPORTED_ENTRY_TYPE, $issues[0]->get_error_code() );
	}

	/**
	 * Reports a symlink before considering whether its target is a file.
	 *
	 * @return void
	 */
	public function test_reports_symlink_without_following_target(): void {
		$scan_directory = $this->create_directory( 'menus' );
		$target_path    = $this->create_file( 'target.pdf' );
		$link_name      = '2026-09-01_2026-09-10_linked.pdf';
		$link_path      = $scan_directory . DIRECTORY_SEPARATOR . $link_name;
		$created        = false;

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Captures an expected platform permission error from the symlink test.
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

		if ( ! $created ) {
			$this->markTestSkipped( 'The current environment does not permit creating symbolic links.' );
		}

		$result = $this->create_scanner()->scan( $scan_directory );
		$issues = $result->get_issues();

		$this->assertTrue( $result->is_successful() );
		$this->assertSame( array(), $result->get_documents() );
		$this->assertCount( 1, $issues );
		$this->assertSame( $link_name, $issues[0]->get_entry_name() );
		$this->assertSame( ZFDZ_Menu_Scan_Issue::ERROR_UNSAFE_SYMLINK, $issues[0]->get_error_code() );
	}

	/**
	 * Keeps valid documents when other entries are problematic and sorts issues.
	 *
	 * @return void
	 */
	public function test_scans_mixed_directory_contents(): void {
		$this->create_file( '2026-09-11_2026-09-20_dieta-b.pdf' );
		$this->create_file( '2026-09-01_2026-09-10_dieta-a.pdf' );
		$this->create_file( 'z-jadlospis.pdf' );
		$this->create_file( 'notes.txt' );
		$this->create_directory( 'archive' );

		$result = $this->create_scanner()->scan( $this->temporary_directory );

		$this->assertTrue( $result->is_successful() );
		$this->assertCount( 2, $result->get_documents() );
		$this->assertCount( 2, $result->get_groups() );
		$this->assertSame(
			array( 'archive', 'notes.txt', 'z-jadlospis.pdf' ),
			$this->get_issue_names( $result->get_issues() )
		);
		$this->assertSame(
			array(
				ZFDZ_Menu_Scan_Issue::ERROR_UNSUPPORTED_ENTRY_TYPE,
				ZFDZ_Menu_Filename_Parser::ERROR_UNSUPPORTED_EXTENSION,
				ZFDZ_Menu_Filename_Parser::ERROR_INVALID_FORMAT,
			),
			$this->get_issue_codes( $result->get_issues() )
		);
	}

	/**
	 * Does not expose the source directory path through result models.
	 *
	 * @return void
	 */
	public function test_result_models_do_not_expose_absolute_source_path(): void {
		$this->create_file( 'notes.txt' );

		$result = $this->create_scanner()->scan( $this->temporary_directory );
		$issue  = $result->get_issues()[0];

		$this->assertFalse( method_exists( $result, 'get_directory' ) );
		$this->assertFalse( method_exists( $result, 'get_directory_path' ) );
		$this->assertFalse( method_exists( $issue, 'get_path' ) );
		$this->assertFalse( method_exists( $issue, 'get_absolute_path' ) );
		$this->assertSame( 'notes.txt', $issue->get_entry_name() );
		$this->assertStringNotContainsString( $this->temporary_directory, $issue->get_entry_name() );
	}

	/**
	 * Creates a scanner with the accepted concrete parser dependency.
	 *
	 * @return ZFDZ_Menu_Directory_Scanner
	 */
	private function create_scanner(): ZFDZ_Menu_Directory_Scanner {
		return new ZFDZ_Menu_Directory_Scanner( new ZFDZ_Menu_Filename_Parser() );
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
	 * @param string $contents      Optional fixture content.
	 * @return string
	 */
	private function create_file( string $relative_path, string $contents = '' ): string {
		$file_path = $this->temporary_directory . DIRECTORY_SEPARATOR . $relative_path;
		$file      = new SplFileObject( $file_path, 'w' );

		if ( '' !== $contents ) {
			$file->fwrite( $contents );
		}

		return $file_path;
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
	 * Returns original filenames from document models.
	 *
	 * @param array $documents Documents.
	 * @return list<string>
	 */
	private function get_document_filenames( array $documents ): array {
		return array_map(
			static function ( ZFDZ_Menu_Document $document ): string {
				return $document->get_original_filename();
			},
			$documents
		);
	}

	/**
	 * Returns entry names from scan issues.
	 *
	 * @param array $issues Issues.
	 * @return list<string>
	 */
	private function get_issue_names( array $issues ): array {
		return array_map(
			static function ( ZFDZ_Menu_Scan_Issue $issue ): string {
				return $issue->get_entry_name();
			},
			$issues
		);
	}

	/**
	 * Returns error codes from scan issues.
	 *
	 * @param array $issues Issues.
	 * @return list<string>
	 */
	private function get_issue_codes( array $issues ): array {
		return array_map(
			static function ( ZFDZ_Menu_Scan_Issue $issue ): string {
				return $issue->get_error_code();
			},
			$issues
		);
	}

	/**
	 * Asserts the invariant for a directory-level failure result.
	 *
	 * @param ZFDZ_Menu_Scan_Result $result     Scan result.
	 * @param string                $error_code Expected directory error code.
	 * @return void
	 */
	private function assert_directory_failure( ZFDZ_Menu_Scan_Result $result, string $error_code ): void {
		$this->assertFalse( $result->is_successful() );
		$this->assertSame( $error_code, $result->get_directory_error_code() );
		$this->assertSame( array(), $result->get_documents() );
		$this->assertSame( array(), $result->get_groups() );
		$this->assertSame( array(), $result->get_issues() );
	}
}
