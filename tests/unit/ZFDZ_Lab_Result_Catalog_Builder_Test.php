<?php
/**
 * Tests the standalone validated laboratory result catalog pipeline.
 *
 * @package ZywienieDlaZdrowia
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests scanner, PDF validator, and exact-period matcher orchestration.
 */
final class ZFDZ_Lab_Result_Catalog_Builder_Test extends TestCase {

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
			. 'zfdz-lab-result-catalog-'
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
	 * Propagates directory failure and returns empty collections.
	 *
	 * @return void
	 */
	public function test_propagates_directory_failure(): void {
		$result = $this->create_builder()->build(
			$this->temporary_directory . DIRECTORY_SEPARATOR . 'missing',
			array()
		);

		$this->assertFalse( $result->is_successful() );
		$this->assertSame(
			ZFDZ_Lab_Result_Directory_Scanner::ERROR_DIRECTORY_NOT_FOUND,
			$result->get_directory_error_code()
		);
		$this->assertSame( array(), $result->get_documents() );
		$this->assertSame( array(), $result->get_associations() );
		$this->assertSame( array(), $result->get_issues() );
	}

	/**
	 * Builds a successful empty catalog for an empty directory.
	 *
	 * @return void
	 */
	public function test_builds_successful_empty_catalog(): void {
		$result = $this->create_builder()->build( $this->temporary_directory, array() );

		$this->assertTrue( $result->is_successful() );
		$this->assertNull( $result->get_directory_error_code() );
		$this->assertSame( array(), $result->get_documents() );
		$this->assertSame( array(), $result->get_associations() );
		$this->assertSame( array(), $result->get_issues() );
	}

	/**
	 * Matches one validated PDF candidate to an exact menu period.
	 *
	 * @return void
	 */
	public function test_builds_matched_association_for_exact_menu_period(): void {
		$filename = '2026-08-21_2026-08-31_2026-08-27_Badanie.pdf';
		$group    = $this->create_menu_group( '2026-08-21', '2026-08-31' );
		$this->create_file( $filename, self::VALID_PDF_CONTENT );

		$result      = $this->create_builder()->build( $this->temporary_directory, array( $group ) );
		$association = $result->get_associations()[0];

		$this->assertTrue( $result->is_successful() );
		$this->assertCount( 1, $result->get_documents() );
		$this->assertCount( 1, $result->get_associations() );
		$this->assertTrue( $association->is_matched() );
		$this->assertSame( $group, $association->get_menu_group() );
		$this->assertSame( $result->get_documents()[0], $association->get_document() );
		$this->assertSame( array(), $result->get_issues() );
	}

	/**
	 * Keeps a validated candidate unmatched when no exact menu group exists.
	 *
	 * @return void
	 */
	public function test_builds_unmatched_association_without_menu_group(): void {
		$filename = '2026-08-21_2026-08-31_2026-08-27_Badanie.pdf';
		$this->create_file( $filename, self::VALID_PDF_CONTENT );

		$result      = $this->create_builder()->build( $this->temporary_directory, array() );
		$association = $result->get_associations()[0];

		$this->assertCount( 1, $result->get_documents() );
		$this->assertFalse( $association->is_matched() );
		$this->assertNull( $association->get_menu_group() );
		$this->assertSame( array(), $result->get_issues() );
	}

	/**
	 * Allows several validated results to reference the same menu group.
	 *
	 * @return void
	 */
	public function test_matches_multiple_results_to_one_menu_group(): void {
		$older = '2026-08-21_2026-08-31_2026-08-24_Badanie starsze.pdf';
		$newer = '2026-08-21_2026-08-31_2026-08-28_Badanie nowsze.pdf';
		$group = $this->create_menu_group( '2026-08-21', '2026-08-31' );
		$this->create_file( $older, self::VALID_PDF_CONTENT );
		$this->create_file( $newer, self::VALID_PDF_CONTENT );

		$result = $this->create_builder()->build( $this->temporary_directory, array( $group ) );

		$this->assertSame( array( $newer, $older ), $this->get_document_filenames( $result->get_documents() ) );
		$this->assertSame( $group, $result->get_associations()[0]->get_menu_group() );
		$this->assertSame( $group, $result->get_associations()[1]->get_menu_group() );
	}

	/**
	 * Rejects invalid PDF content and records one validator issue.
	 *
	 * @return void
	 */
	public function test_reports_invalid_pdf_content(): void {
		$filename = '2026-08-21_2026-08-31_2026-08-27_Badanie.pdf';
		$this->create_file( $filename, "This is not a PDF\n" );

		$result = $this->create_builder()->build( $this->temporary_directory, array() );

		$this->assertSame( array(), $result->get_documents() );
		$this->assertSame( array(), $result->get_associations() );
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
	 * Preserves the validator EOF error and removes the candidate.
	 *
	 * @return void
	 */
	public function test_reports_pdf_without_eof_marker(): void {
		$filename = '2026-08-21_2026-08-31_2026-08-27_Brak EOF.pdf';
		$this->create_file( $filename, self::PDF_WITHOUT_EOF );

		$result = $this->create_builder()->build( $this->temporary_directory, array() );

		$this->assertSame( array(), $result->get_documents() );
		$this->assertSame( array(), $result->get_associations() );
		$this->assertSame( $filename, $result->get_issues()[0]->get_entry_name() );
		$this->assertSame(
			ZFDZ_PDF_File_Validator::ERROR_INVALID_PDF_EOF,
			$result->get_issues()[0]->get_error_code()
		);
	}

	/**
	 * Does not rescue an invalid filename containing valid PDF data.
	 *
	 * @return void
	 */
	public function test_preserves_invalid_filename_scanner_issue(): void {
		$this->create_file( 'badanie-final.pdf', self::VALID_PDF_CONTENT );

		$result = $this->create_builder()->build( $this->temporary_directory, array() );

		$this->assertSame( array(), $result->get_documents() );
		$this->assertSame( array(), $result->get_associations() );
		$this->assertSame( 'badanie-final.pdf', $result->get_issues()[0]->get_entry_name() );
		$this->assertSame(
			ZFDZ_Lab_Result_Filename_Parser::ERROR_INVALID_FORMAT,
			$result->get_issues()[0]->get_error_code()
		);
	}

	/**
	 * Preserves a scanner extension issue without PDF validation.
	 *
	 * @return void
	 */
	public function test_preserves_non_pdf_extension_issue(): void {
		$this->create_file( 'notes.txt', self::VALID_PDF_CONTENT );

		$result = $this->create_builder()->build( $this->temporary_directory, array() );

		$this->assertSame( array(), $result->get_documents() );
		$this->assertSame( array(), $result->get_associations() );
		$this->assertSame( ZFDZ_Lab_Result_Filename_Parser::ERROR_UNSUPPORTED_EXTENSION, $result->get_issues()[0]->get_error_code() );
	}

	/**
	 * Combines and sorts scanner and validator issues deterministically.
	 *
	 * @return void
	 */
	public function test_combines_and_sorts_scanner_and_validator_issues(): void {
		$no_eof  = '2026-08-01_2026-08-10_2026-08-15_A-brak-eof.pdf';
		$invalid = '2026-08-21_2026-08-31_2026-08-27_B-invalid.pdf';
		$this->create_file( $invalid, "This is not a PDF\n" );
		$this->create_file( 'z-notes.txt', 'notes' );
		$this->create_file( $no_eof, self::PDF_WITHOUT_EOF );
		$this->create_directory( 'm-directory' );

		$result = $this->create_builder()->build( $this->temporary_directory, array() );

		$this->assertSame(
			array( $no_eof, $invalid, 'm-directory', 'z-notes.txt' ),
			$this->get_issue_names( $result->get_issues() )
		);
		$this->assertSame( ZFDZ_PDF_File_Validator::ERROR_INVALID_PDF_EOF, $result->get_issues()[0]->get_error_code() );
		$this->assertSame( ZFDZ_Lab_Result_Scan_Issue::ERROR_UNSUPPORTED_ENTRY_TYPE, $result->get_issues()[2]->get_error_code() );
		$this->assertSame( ZFDZ_Lab_Result_Filename_Parser::ERROR_UNSUPPORTED_EXTENSION, $result->get_issues()[3]->get_error_code() );
	}

	/**
	 * Uses matcher ordering and aligns catalog documents with associations.
	 *
	 * @return void
	 */
	public function test_preserves_matcher_order_for_associations_and_documents(): void {
		$expected = array(
			'2026-08-01_2026-08-10_2026-09-10_Newest.pdf',
			'2026-08-21_2026-08-31_2026-09-09_A.pdf',
			'2026-08-21_2026-08-31_2026-09-09_B.pdf',
			'2026-08-21_2026-08-30_2026-09-09_Earlier end.pdf',
			'2026-08-20_2026-08-31_2026-09-09_Earlier start.pdf',
			'2026-09-01_2026-09-10_2026-09-08_Oldest.pdf',
		);

		foreach ( array_reverse( $expected ) as $filename ) {
			$this->create_file( $filename, self::VALID_PDF_CONTENT );
		}

		$result = $this->create_builder()->build( $this->temporary_directory, array() );

		$this->assertSame( $expected, $this->get_document_filenames( $result->get_documents() ) );
		$this->assertSame(
			$expected,
			array_map(
				static function ( ZFDZ_Lab_Result_Menu_Association $association ): string {
					return $association->get_document()->get_original_filename();
				},
				$result->get_associations()
			)
		);

		foreach ( $result->get_associations() as $index => $association ) {
			$this->assertSame( $result->get_documents()[ $index ], $association->get_document() );
		}
	}

	/**
	 * Does not convert an unmatched association into an issue.
	 *
	 * @return void
	 */
	public function test_unmatched_association_is_not_an_issue(): void {
		$filename = '2026-08-21_2026-08-31_2026-08-27_Badanie.pdf';
		$group    = $this->create_menu_group( '2026-09-01', '2026-09-10' );
		$this->create_file( $filename, self::VALID_PDF_CONTENT );

		$result = $this->create_builder()->build( $this->temporary_directory, array( $group ) );

		$this->assertFalse( $result->get_associations()[0]->is_matched() );
		$this->assertSame( array(), $result->get_issues() );
	}

	/**
	 * Preserves a scanner symlink issue without cataloging its target.
	 *
	 * @return void
	 */
	public function test_preserves_symlink_issue_without_cataloging_target(): void {
		$result_directory = $this->create_directory( 'results' );
		$target_path      = $this->create_file( 'target.pdf', self::VALID_PDF_CONTENT );
		$link_name        = '2026-08-21_2026-08-31_2026-08-27_Link.pdf';
		$link_path        = $result_directory . DIRECTORY_SEPARATOR . $link_name;

		if ( ! $this->create_symlink( $target_path, $link_path ) ) {
			$this->markTestSkipped( 'The current environment does not permit creating symbolic links.' );
		}

		$result = $this->create_builder()->build( $result_directory, array() );

		$this->assertSame( array(), $result->get_documents() );
		$this->assertSame( array(), $result->get_associations() );
		$this->assertSame( $link_name, $result->get_issues()[0]->get_entry_name() );
		$this->assertSame( ZFDZ_Lab_Result_Scan_Issue::ERROR_UNSAFE_SYMLINK, $result->get_issues()[0]->get_error_code() );
	}

	/**
	 * Does not execute PHP-like candidate content.
	 *
	 * @return void
	 */
	public function test_does_not_execute_php_like_content(): void {
		$filename = '2026-08-21_2026-08-31_2026-08-27_PHP content.pdf';
		$this->create_file( $filename, "<?php throw new RuntimeException( 'Must not execute.' );" );

		$result = $this->create_builder()->build( $this->temporary_directory, array() );

		$this->assertTrue( $result->is_successful() );
		$this->assertSame( array(), $result->get_documents() );
		$this->assertSame( array(), $result->get_associations() );
		$this->assertSame( $filename, $result->get_issues()[0]->get_entry_name() );
	}

	/**
	 * Does not expose source paths through the final result or issues.
	 *
	 * @return void
	 */
	public function test_catalog_result_does_not_expose_source_paths(): void {
		$this->create_file( 'notes.txt', 'notes' );

		$result = $this->create_builder()->build( $this->temporary_directory, array() );
		$issue  = $result->get_issues()[0];

		$this->assertFalse( method_exists( $result, 'get_directory' ) );
		$this->assertFalse( method_exists( $result, 'get_directory_path' ) );
		$this->assertFalse( method_exists( $result, 'get_file_path' ) );
		$this->assertFalse( method_exists( $result, 'get_absolute_path' ) );
		$this->assertFalse( method_exists( $issue, 'get_path' ) );
		$this->assertFalse( method_exists( $issue, 'get_url' ) );
		$this->assertStringNotContainsString( $this->temporary_directory, $issue->get_entry_name() );
	}

	/**
	 * Preserves the matcher duplicate-period LogicException contract.
	 *
	 * @return void
	 */
	public function test_duplicate_menu_period_preserves_logic_exception(): void {
		$first  = $this->create_menu_group( '2026-08-21', '2026-08-31' );
		$second = $this->create_menu_group( '2026-08-21', '2026-08-31' );

		$this->expectException( LogicException::class );

		$this->create_builder()->build( $this->temporary_directory, array( $first, $second ) );
	}

	/**
	 * Preserves the matcher invalid menu group element contract.
	 *
	 * @return void
	 */
	public function test_invalid_menu_group_element_preserves_invalid_argument_exception(): void {
		$this->expectException( InvalidArgumentException::class );

		$this->create_builder()->build( $this->temporary_directory, array( 'invalid' ) );
	}

	/**
	 * Accepts trusted directory paths with and without a trailing separator.
	 *
	 * @return void
	 */
	public function test_accepts_directory_with_or_without_trailing_separator(): void {
		$filename         = '2026-08-21_2026-08-31_2026-08-27_Badanie.pdf';
		$without_trailing = $this->create_directory( 'without-trailing' );
		$with_trailing    = $this->create_directory( 'with-trailing' );
		$this->create_file( 'without-trailing' . DIRECTORY_SEPARATOR . $filename, self::VALID_PDF_CONTENT );
		$this->create_file( 'with-trailing' . DIRECTORY_SEPARATOR . $filename, self::VALID_PDF_CONTENT );

		$builder = $this->create_builder();
		$first   = $builder->build( $without_trailing, array() );
		$second  = $builder->build( $with_trailing . DIRECTORY_SEPARATOR, array() );

		$this->assertSame( array( $filename ), $this->get_document_filenames( $first->get_documents() ) );
		$this->assertSame( array( $filename ), $this->get_document_filenames( $second->get_documents() ) );
	}

	/**
	 * Creates a successful result with derived, reindexed documents.
	 *
	 * @return void
	 */
	public function test_catalog_result_success_invariant_and_reindexing(): void {
		$document    = $this->create_lab_document_model();
		$association = ZFDZ_Lab_Result_Menu_Association::from_unmatched( $document );
		$issue       = new ZFDZ_Lab_Result_Scan_Issue( 'notes.txt', 'unsupported_extension' );

		$result = ZFDZ_Lab_Result_Catalog_Result::from_catalog(
			array( 4 => $association ),
			array( 9 => $issue )
		);

		$this->assertTrue( $result->is_successful() );
		$this->assertNull( $result->get_directory_error_code() );
		$this->assertSame( array( $document ), $result->get_documents() );
		$this->assertSame( array( $association ), $result->get_associations() );
		$this->assertSame( array( $issue ), $result->get_issues() );
	}

	/**
	 * Rejects an invalid catalog issue element.
	 *
	 * @return void
	 */
	public function test_catalog_result_rejects_invalid_issue_element(): void {
		$this->expectException( InvalidArgumentException::class );

		ZFDZ_Lab_Result_Catalog_Result::from_catalog( array(), array( 'invalid' ) );
	}

	/**
	 * Rejects duplicate associations for the same document instance.
	 *
	 * @return void
	 */
	public function test_catalog_result_rejects_duplicate_document_instance(): void {
		$document = $this->create_lab_document_model();

		$this->expectException( InvalidArgumentException::class );

		ZFDZ_Lab_Result_Catalog_Result::from_catalog(
			array(
				ZFDZ_Lab_Result_Menu_Association::from_unmatched( $document ),
				ZFDZ_Lab_Result_Menu_Association::from_unmatched( $document ),
			),
			array()
		);
	}

	/**
	 * Creates a failed result with all collections empty.
	 *
	 * @return void
	 */
	public function test_catalog_result_directory_failure_invariant(): void {
		$result = ZFDZ_Lab_Result_Catalog_Result::from_directory_error( 'directory_not_found' );

		$this->assertFalse( $result->is_successful() );
		$this->assertSame( 'directory_not_found', $result->get_directory_error_code() );
		$this->assertSame( array(), $result->get_documents() );
		$this->assertSame( array(), $result->get_associations() );
		$this->assertSame( array(), $result->get_issues() );
	}

	/**
	 * Rejects an empty catalog directory error code.
	 *
	 * @return void
	 */
	public function test_catalog_result_rejects_empty_directory_error_code(): void {
		$this->expectException( InvalidArgumentException::class );

		ZFDZ_Lab_Result_Catalog_Result::from_directory_error( '' );
	}

	/**
	 * Creates the concrete standalone pipeline.
	 *
	 * @return ZFDZ_Lab_Result_Catalog_Builder
	 */
	private function create_builder(): ZFDZ_Lab_Result_Catalog_Builder {
		return new ZFDZ_Lab_Result_Catalog_Builder(
			new ZFDZ_Lab_Result_Directory_Scanner( new ZFDZ_Lab_Result_Filename_Parser() ),
			new ZFDZ_PDF_File_Validator(),
			new ZFDZ_Lab_Result_Menu_Matcher()
		);
	}

	/**
	 * Creates a menu period group fixture.
	 *
	 * @param string $start_date Period start date.
	 * @param string $end_date   Period end date.
	 * @return ZFDZ_Menu_Period_Group
	 */
	private function create_menu_group( string $start_date, string $end_date ): ZFDZ_Menu_Period_Group {
		return new ZFDZ_Menu_Period_Group( $start_date, $end_date, array() );
	}

	/**
	 * Creates one valid laboratory result document model.
	 *
	 * @return ZFDZ_Lab_Result_Document
	 */
	private function create_lab_document_model(): ZFDZ_Lab_Result_Document {
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
	 * @param string $target_path Target path inside the temporary root.
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
}
