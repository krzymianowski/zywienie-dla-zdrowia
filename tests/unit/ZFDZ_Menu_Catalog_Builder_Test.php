<?php
/**
 * Tests the standalone validated menu catalog pipeline.
 *
 * @package ZywienieDlaZdrowia
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests scanner-to-validator orchestration without WordPress.
 */
final class ZFDZ_Menu_Catalog_Builder_Test extends TestCase {

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

	private const PDF_WITHOUT_EOF = "%PDF-1.7\n"
		. "1 0 obj\n"
		. "<<>>\n"
		. "endobj\n";

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
			. 'zfdz-menu-catalog-'
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
	 * Preserves a scanner directory failure and returns no entry-level results.
	 *
	 * @return void
	 */
	public function test_reports_missing_directory(): void {
		$result = $this->create_builder()->build(
			$this->temporary_directory . DIRECTORY_SEPARATOR . 'missing'
		);

		$this->assertFalse( $result->is_successful() );
		$this->assertSame(
			ZFDZ_Menu_Directory_Scanner::ERROR_DIRECTORY_NOT_FOUND,
			$result->get_directory_error_code()
		);
		$this->assertSame( array(), $result->get_documents() );
		$this->assertSame( array(), $result->get_groups() );
		$this->assertSame( array(), $result->get_issues() );
	}

	/**
	 * Treats an empty directory as a successful empty catalog.
	 *
	 * @return void
	 */
	public function test_builds_empty_catalog(): void {
		$result = $this->create_builder()->build( $this->temporary_directory );

		$this->assertTrue( $result->is_successful() );
		$this->assertNull( $result->get_directory_error_code() );
		$this->assertSame( array(), $result->get_documents() );
		$this->assertSame( array(), $result->get_groups() );
		$this->assertSame( array(), $result->get_issues() );
	}

	/**
	 * Includes a recognized file that passes limited PDF candidate validation.
	 *
	 * @return void
	 */
	public function test_includes_validated_pdf_candidate(): void {
		$filename = '2026-09-01_2026-09-10_dieta-podstawowa.pdf';
		$this->create_file( $filename, self::VALID_PDF_CONTENT );

		$result = $this->create_builder()->build( $this->temporary_directory );

		$this->assertTrue( $result->is_successful() );
		$this->assertSame( array( $filename ), $this->get_document_filenames( $result->get_documents() ) );
		$this->assertCount( 1, $result->get_groups() );
		$this->assertSame( array( $filename ), $this->get_document_filenames( $result->get_groups()[0]->get_documents() ) );
		$this->assertSame( array(), $result->get_issues() );
	}

	/**
	 * Removes a recognized filename whose contents fail PDF validation.
	 *
	 * @return void
	 */
	public function test_reports_invalid_pdf_content_as_validation_issue(): void {
		$filename = '2026-09-01_2026-09-10_dieta-podstawowa.pdf';
		$this->create_file( $filename, "This is not a PDF\n" );

		$result = $this->create_builder()->build( $this->temporary_directory );

		$this->assertTrue( $result->is_successful() );
		$this->assertSame( array(), $result->get_documents() );
		$this->assertSame( array(), $result->get_groups() );
		$this->assertCount( 1, $result->get_issues() );
		$this->assertSame( $filename, $result->get_issues()[0]->get_entry_name() );
		$this->assertContains(
			$result->get_issues()[0]->get_error_code(),
			array(
				ZFDZ_PDF_File_Validator::ERROR_UNSUPPORTED_MIME_TYPE,
				ZFDZ_PDF_File_Validator::ERROR_INVALID_PDF_HEADER,
			)
		);
	}

	/**
	 * Reports a recognized PDF candidate without an EOF marker.
	 *
	 * @return void
	 */
	public function test_reports_pdf_candidate_without_eof_marker(): void {
		$filename = '2026-09-01_2026-09-10_brak-eof.pdf';
		$this->create_file( $filename, self::PDF_WITHOUT_EOF );

		$result = $this->create_builder()->build( $this->temporary_directory );

		$this->assertSame( array(), $result->get_documents() );
		$this->assertSame( array(), $result->get_groups() );
		$this->assertSame( $filename, $result->get_issues()[0]->get_entry_name() );
		$this->assertSame(
			ZFDZ_PDF_File_Validator::ERROR_INVALID_PDF_EOF,
			$result->get_issues()[0]->get_error_code()
		);
	}

	/**
	 * Keeps a parser issue even when the unrecognized file contains PDF data.
	 *
	 * @return void
	 */
	public function test_does_not_rescue_invalid_filename_with_valid_pdf_content(): void {
		$this->create_file( 'jadlospis-final.pdf', self::VALID_PDF_CONTENT );

		$result = $this->create_builder()->build( $this->temporary_directory );

		$this->assertSame( array(), $result->get_documents() );
		$this->assertSame( array(), $result->get_groups() );
		$this->assertSame( 'jadlospis-final.pdf', $result->get_issues()[0]->get_entry_name() );
		$this->assertSame(
			ZFDZ_Menu_Filename_Parser::ERROR_INVALID_FORMAT,
			$result->get_issues()[0]->get_error_code()
		);
	}

	/**
	 * Preserves a scanner extension issue without passing it into the catalog.
	 *
	 * @return void
	 */
	public function test_preserves_non_pdf_scanner_issue(): void {
		$this->create_file( 'notes.txt', self::VALID_PDF_CONTENT );

		$result = $this->create_builder()->build( $this->temporary_directory );

		$this->assertSame( array(), $result->get_documents() );
		$this->assertSame( 'notes.txt', $result->get_issues()[0]->get_entry_name() );
		$this->assertSame(
			ZFDZ_Menu_Filename_Parser::ERROR_UNSUPPORTED_EXTENSION,
			$result->get_issues()[0]->get_error_code()
		);
	}

	/**
	 * Combines scanner and validator issues in deterministic order.
	 *
	 * @return void
	 */
	public function test_builds_mixed_catalog_and_sorts_all_issues(): void {
		$valid_filename   = '2026-10-01_2026-10-10_dieta-valid.pdf';
		$invalid_filename = '2026-09-01_2026-09-10_dieta-invalid.pdf';
		$no_eof_filename  = '2026-08-01_2026-08-10_dieta-no-eof.pdf';

		$this->create_file( $valid_filename, self::VALID_PDF_CONTENT );
		$this->create_file( $invalid_filename, "This is not a PDF\n" );
		$this->create_file( $no_eof_filename, self::PDF_WITHOUT_EOF );
		$this->create_file( 'jadlospis-final.pdf', self::VALID_PDF_CONTENT );
		$this->create_file( 'notes.txt', 'notes' );
		$this->create_directory( 'archive' );

		$result = $this->create_builder()->build( $this->temporary_directory );

		$this->assertTrue( $result->is_successful() );
		$this->assertSame( array( $valid_filename ), $this->get_document_filenames( $result->get_documents() ) );
		$this->assertCount( 1, $result->get_groups() );
		$this->assertSame(
			array(
				$no_eof_filename,
				$invalid_filename,
				'archive',
				'jadlospis-final.pdf',
				'notes.txt',
			),
			$this->get_issue_names( $result->get_issues() )
		);
		$this->assertSame( ZFDZ_PDF_File_Validator::ERROR_INVALID_PDF_EOF, $result->get_issues()[0]->get_error_code() );
		$this->assertContains(
			$result->get_issues()[1]->get_error_code(),
			array(
				ZFDZ_PDF_File_Validator::ERROR_UNSUPPORTED_MIME_TYPE,
				ZFDZ_PDF_File_Validator::ERROR_INVALID_PDF_HEADER,
			)
		);
		$this->assertSame( ZFDZ_Menu_Scan_Issue::ERROR_UNSUPPORTED_ENTRY_TYPE, $result->get_issues()[2]->get_error_code() );
		$this->assertSame( ZFDZ_Menu_Filename_Parser::ERROR_INVALID_FORMAT, $result->get_issues()[3]->get_error_code() );
		$this->assertSame( ZFDZ_Menu_Filename_Parser::ERROR_UNSUPPORTED_EXTENSION, $result->get_issues()[4]->get_error_code() );
	}

	/**
	 * Removes rejected documents and groups that become empty.
	 *
	 * @return void
	 */
	public function test_filters_documents_from_groups_and_removes_empty_groups(): void {
		$valid_filenames = array(
			'2026-09-01_2026-09-10_dieta-a.pdf',
			'2026-09-01_2026-09-10_dieta-c.pdf',
		);
		$this->create_file( $valid_filenames[0], self::VALID_PDF_CONTENT );
		$this->create_file( '2026-09-01_2026-09-10_dieta-b.pdf', "This is not a PDF\n" );
		$this->create_file( $valid_filenames[1], self::VALID_PDF_CONTENT );
		$this->create_file( '2026-09-11_2026-09-20_dieta-d.pdf', "This is not a PDF\n" );

		$result = $this->create_builder()->build( $this->temporary_directory );
		$groups = $result->get_groups();

		$this->assertSame( $valid_filenames, $this->get_document_filenames( $result->get_documents() ) );
		$this->assertCount( 1, $groups );
		$this->assertSame( '2026-09-01', $groups[0]->get_start_date() );
		$this->assertSame( '2026-09-10', $groups[0]->get_end_date() );
		$this->assertSame( $valid_filenames, $this->get_document_filenames( $groups[0]->get_documents() ) );
	}

	/**
	 * Preserves scanner document and group order after filtering.
	 *
	 * @return void
	 */
	public function test_preserves_document_and_group_order_after_filtering(): void {
		$expected_filenames = array(
			'2026-12-01_2026-12-10_dieta-a.pdf',
			'2026-10-01_2026-10-10_dieta-c.pdf',
		);

		$this->create_file( $expected_filenames[0], self::VALID_PDF_CONTENT );
		$this->create_file( '2026-11-01_2026-11-10_dieta-b.pdf', "This is not a PDF\n" );
		$this->create_file( $expected_filenames[1], self::VALID_PDF_CONTENT );
		$this->create_file( '2026-09-01_2026-09-10_dieta-d.pdf', "This is not a PDF\n" );

		$result = $this->create_builder()->build( $this->temporary_directory );

		$this->assertSame( $expected_filenames, $this->get_document_filenames( $result->get_documents() ) );
		$this->assertSame(
			array( '2026-12-01', '2026-10-01' ),
			array_map(
				static function ( ZFDZ_Menu_Period_Group $group ): string {
					return $group->get_start_date();
				},
				$result->get_groups()
			)
		);
	}

	/**
	 * Joins filenames correctly with directory paths with or without a separator.
	 *
	 * @return void
	 */
	public function test_accepts_directory_paths_with_or_without_trailing_separator(): void {
		$filename                   = '2026-09-01_2026-09-10_dieta.pdf';
		$without_trailing_separator = $this->create_directory( 'without-trailing-separator' );
		$with_trailing_separator    = $this->create_directory( 'with-trailing-separator' );

		$this->create_file( 'without-trailing-separator' . DIRECTORY_SEPARATOR . $filename, self::VALID_PDF_CONTENT );
		$this->create_file( 'with-trailing-separator' . DIRECTORY_SEPARATOR . $filename, self::VALID_PDF_CONTENT );

		$builder        = $this->create_builder();
		$without_result = $builder->build( $without_trailing_separator );
		$with_result    = $builder->build( $with_trailing_separator . DIRECTORY_SEPARATOR );

		$this->assertSame( array( $filename ), $this->get_document_filenames( $without_result->get_documents() ) );
		$this->assertSame( array( $filename ), $this->get_document_filenames( $with_result->get_documents() ) );
	}

	/**
	 * Does not execute candidate contents during the pipeline.
	 *
	 * @return void
	 */
	public function test_does_not_execute_candidate_contents(): void {
		$filename = '2026-09-01_2026-09-10_php-content.pdf';
		$this->create_file(
			$filename,
			"<?php throw new RuntimeException( 'Must not execute.' );"
		);

		$result = $this->create_builder()->build( $this->temporary_directory );

		$this->assertTrue( $result->is_successful() );
		$this->assertSame( array(), $result->get_documents() );
		$this->assertSame( $filename, $result->get_issues()[0]->get_entry_name() );
	}

	/**
	 * Preserves scanner symlink rejection in the final catalog.
	 *
	 * @return void
	 */
	public function test_preserves_symlink_issue_without_cataloging_target(): void {
		$menu_directory = $this->create_directory( 'menus' );
		$target_path    = $this->create_file( 'target.pdf', self::VALID_PDF_CONTENT );
		$link_name      = '2026-09-01_2026-09-10_linked.pdf';
		$link_path      = $menu_directory . DIRECTORY_SEPARATOR . $link_name;

		if ( ! $this->create_symlink( $target_path, $link_path ) ) {
			$this->markTestSkipped( 'The current environment does not permit creating symbolic links.' );
		}

		$result = $this->create_builder()->build( $menu_directory );

		$this->assertTrue( $result->is_successful() );
		$this->assertSame( array(), $result->get_documents() );
		$this->assertSame( array(), $result->get_groups() );
		$this->assertSame( $link_name, $result->get_issues()[0]->get_entry_name() );
		$this->assertSame( ZFDZ_Menu_Scan_Issue::ERROR_UNSAFE_SYMLINK, $result->get_issues()[0]->get_error_code() );
	}

	/**
	 * Does not expose or store source paths through the catalog result.
	 *
	 * @return void
	 */
	public function test_catalog_result_does_not_expose_source_paths(): void {
		$this->create_file( '2026-09-01_2026-09-10_invalid.pdf', "This is not a PDF\n" );

		$result         = $this->create_builder()->build( $this->temporary_directory );
		$issue          = $result->get_issues()[0];
		$property_names = array_map(
			static function ( ReflectionProperty $property ): string {
				return $property->getName();
			},
			( new ReflectionObject( $result ) )->getProperties()
		);

		$this->assertFalse( method_exists( $result, 'get_directory' ) );
		$this->assertFalse( method_exists( $result, 'get_directory_path' ) );
		$this->assertFalse( method_exists( $result, 'get_file_path' ) );
		$this->assertFalse( method_exists( $result, 'get_absolute_path' ) );
		$this->assertNotContains( 'directory', $property_names );
		$this->assertNotContains( 'directory_path', $property_names );
		$this->assertNotContains( 'file_path', $property_names );
		$this->assertNotContains( 'absolute_path', $property_names );
		$this->assertSame( '2026-09-01_2026-09-10_invalid.pdf', $issue->get_entry_name() );
		$this->assertStringNotContainsString( $this->temporary_directory, $issue->get_entry_name() );
	}

	/**
	 * Creates a successful result with no directory error code.
	 *
	 * @return void
	 */
	public function test_catalog_result_success_invariant(): void {
		$result = ZFDZ_Menu_Catalog_Result::from_catalog( array(), array(), array() );

		$this->assertTrue( $result->is_successful() );
		$this->assertNull( $result->get_directory_error_code() );
	}

	/**
	 * Creates a failed result with empty collections.
	 *
	 * @return void
	 */
	public function test_catalog_result_directory_failure_invariant(): void {
		$result = ZFDZ_Menu_Catalog_Result::from_directory_error( 'directory_not_found' );

		$this->assertFalse( $result->is_successful() );
		$this->assertSame( 'directory_not_found', $result->get_directory_error_code() );
		$this->assertSame( array(), $result->get_documents() );
		$this->assertSame( array(), $result->get_groups() );
		$this->assertSame( array(), $result->get_issues() );
	}

	/**
	 * Rejects an empty directory error code.
	 *
	 * @return void
	 */
	public function test_catalog_result_rejects_empty_directory_error_code(): void {
		$this->expectException( InvalidArgumentException::class );

		ZFDZ_Menu_Catalog_Result::from_directory_error( '' );
	}

	/**
	 * Creates the concrete standalone pipeline.
	 *
	 * @return ZFDZ_Menu_Catalog_Builder
	 */
	private function create_builder(): ZFDZ_Menu_Catalog_Builder {
		return new ZFDZ_Menu_Catalog_Builder(
			new ZFDZ_Menu_Directory_Scanner( new ZFDZ_Menu_Filename_Parser() ),
			new ZFDZ_PDF_File_Validator()
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
	 * @param string $contents      Fixture content.
	 * @return string
	 */
	private function create_file( string $relative_path, string $contents ): string {
		$file_path = $this->temporary_directory . DIRECTORY_SEPARATOR . $relative_path;
		$file      = new SplFileObject( $file_path, 'wb' );

		$file->fwrite( $contents );

		return $file_path;
	}

	/**
	 * Attempts to create a symlink while containing an expected permission warning.
	 *
	 * @param string $target_path Target inside the current test root.
	 * @param string $link_path   Link inside the current test root.
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
	 * Returns entry names from catalog issues.
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
}
