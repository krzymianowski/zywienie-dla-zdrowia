<?php
/**
 * Validated laboratory result catalog builder.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Orchestrates scanning, PDF candidate validation, and menu matching.
 */
final class ZFDZ_Lab_Result_Catalog_Builder {

	/**
	 * Standalone laboratory result directory scanner.
	 *
	 * @var ZFDZ_Lab_Result_Directory_Scanner
	 */
	private readonly ZFDZ_Lab_Result_Directory_Scanner $directory_scanner;

	/**
	 * Standalone PDF candidate validator.
	 *
	 * @var ZFDZ_PDF_File_Validator
	 */
	private readonly ZFDZ_PDF_File_Validator $pdf_validator;

	/**
	 * Standalone exact-period menu matcher.
	 *
	 * @var ZFDZ_Lab_Result_Menu_Matcher
	 */
	private readonly ZFDZ_Lab_Result_Menu_Matcher $menu_matcher;

	/**
	 * Creates a laboratory result catalog builder.
	 *
	 * @param ZFDZ_Lab_Result_Directory_Scanner $directory_scanner Laboratory result scanner.
	 * @param ZFDZ_PDF_File_Validator           $pdf_validator     PDF candidate validator.
	 * @param ZFDZ_Lab_Result_Menu_Matcher      $menu_matcher      Exact-period matcher.
	 */
	public function __construct(
		ZFDZ_Lab_Result_Directory_Scanner $directory_scanner,
		ZFDZ_PDF_File_Validator $pdf_validator,
		ZFDZ_Lab_Result_Menu_Matcher $menu_matcher
	) {
		$this->directory_scanner = $directory_scanner;
		$this->pdf_validator     = $pdf_validator;
		$this->menu_matcher      = $menu_matcher;
	}

	/**
	 * Builds a validated catalog from a trusted laboratory result directory.
	 *
	 * @param string $directory   Trusted directory path from application configuration.
	 * @param array  $menu_groups Existing validated menu period groups.
	 * @return ZFDZ_Lab_Result_Catalog_Result
	 * @throws LogicException When a dependency returns a contradictory result.
	 */
	public function build( string $directory, array $menu_groups ): ZFDZ_Lab_Result_Catalog_Result {
		$scan_result = $this->directory_scanner->scan( $directory );

		if ( ! $scan_result->is_successful() ) {
			$directory_error_code = $scan_result->get_directory_error_code();

			if ( null === $directory_error_code || '' === $directory_error_code ) {
				throw new LogicException( 'A failed directory scan requires an error code.' );
			}

			return ZFDZ_Lab_Result_Catalog_Result::from_directory_error( $directory_error_code );
		}

		$validated_documents = array();
		$issues              = $scan_result->get_issues();

		foreach ( $scan_result->get_documents() as $document ) {
			$filename          = $document->get_original_filename();
			$file_path         = $this->create_file_path( $directory, $filename );
			$validation_result = $this->pdf_validator->validate( $file_path );

			if ( $validation_result->is_valid_candidate() ) {
				$validated_documents[] = $document;
				continue;
			}

			$error_code = $validation_result->get_error_code();

			if ( null === $error_code || '' === $error_code ) {
				throw new LogicException( 'An invalid PDF candidate requires an error code.' );
			}

			$issues[] = new ZFDZ_Lab_Result_Scan_Issue( $filename, $error_code );
		}

		$this->sort_issues( $issues );

		return ZFDZ_Lab_Result_Catalog_Result::from_catalog(
			$this->menu_matcher->match( $validated_documents, $menu_groups ),
			$issues
		);
	}

	/**
	 * Creates a platform-independent path without normalizing trusted input.
	 *
	 * @param string $directory Trusted directory path.
	 * @param string $filename  Parser-approved original filename.
	 * @return string
	 */
	private function create_file_path( string $directory, string $filename ): string {
		$last_character = substr( $directory, -1 );

		if ( '/' === $last_character || '\\' === $last_character ) {
			return $directory . $filename;
		}

		return $directory . DIRECTORY_SEPARATOR . $filename;
	}

	/**
	 * Sorts scanner and validation issues using binary comparison.
	 *
	 * @param array $issues Issues to sort.
	 * @return void
	 */
	private function sort_issues( array &$issues ): void {
		usort(
			$issues,
			static function ( ZFDZ_Lab_Result_Scan_Issue $left, ZFDZ_Lab_Result_Scan_Issue $right ): int {
				$name_order = strcmp( $left->get_entry_name(), $right->get_entry_name() );

				if ( 0 !== $name_order ) {
					return $name_order;
				}

				return strcmp( $left->get_error_code(), $right->get_error_code() );
			}
		);
	}
}
