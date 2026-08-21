<?php
/**
 * Validated menu catalog builder.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Orchestrates menu directory scanning and PDF candidate validation.
 */
final class ZFDZ_Menu_Catalog_Builder {

	/**
	 * Standalone menu directory scanner.
	 *
	 * @var ZFDZ_Menu_Directory_Scanner
	 */
	private readonly ZFDZ_Menu_Directory_Scanner $directory_scanner;

	/**
	 * Standalone PDF candidate validator.
	 *
	 * @var ZFDZ_PDF_File_Validator
	 */
	private readonly ZFDZ_PDF_File_Validator $pdf_validator;

	/**
	 * Creates a validated menu catalog builder.
	 *
	 * @param ZFDZ_Menu_Directory_Scanner $directory_scanner Menu directory scanner.
	 * @param ZFDZ_PDF_File_Validator     $pdf_validator     PDF candidate validator.
	 */
	public function __construct(
		ZFDZ_Menu_Directory_Scanner $directory_scanner,
		ZFDZ_PDF_File_Validator $pdf_validator
	) {
		$this->directory_scanner = $directory_scanner;
		$this->pdf_validator     = $pdf_validator;
	}

	/**
	 * Builds a validated catalog from a trusted menu directory path.
	 *
	 * @param string $directory Trusted directory path from application configuration.
	 * @return ZFDZ_Menu_Catalog_Result
	 * @throws LogicException When a dependency returns a contradictory result.
	 */
	public function build( string $directory ): ZFDZ_Menu_Catalog_Result {
		$scan_result = $this->directory_scanner->scan( $directory );

		if ( ! $scan_result->is_successful() ) {
			$directory_error_code = $scan_result->get_directory_error_code();

			if ( null === $directory_error_code || '' === $directory_error_code ) {
				throw new LogicException( 'A failed directory scan requires an error code.' );
			}

			return ZFDZ_Menu_Catalog_Result::from_directory_error( $directory_error_code );
		}

		$documents           = array();
		$validated_filenames = array();
		$issues              = $scan_result->get_issues();

		foreach ( $scan_result->get_documents() as $document ) {
			$filename          = $document->get_original_filename();
			$file_path         = $this->create_file_path( $directory, $filename );
			$validation_result = $this->pdf_validator->validate( $file_path );

			if ( $validation_result->is_valid_candidate() ) {
				$documents[]                      = $document;
				$validated_filenames[ $filename ] = true;
				continue;
			}

			$error_code = $validation_result->get_error_code();

			if ( null === $error_code || '' === $error_code ) {
				throw new LogicException( 'An invalid PDF candidate requires an error code.' );
			}

			$issues[] = new ZFDZ_Menu_Scan_Issue( $filename, $error_code );
		}

		$this->sort_issues( $issues );

		return ZFDZ_Menu_Catalog_Result::from_catalog(
			$documents,
			$this->filter_groups( $scan_result->get_groups(), $validated_filenames ),
			$issues
		);
	}

	/**
	 * Creates a platform-independent file path without normalizing trusted input.
	 *
	 * @param string $directory Trusted directory path.
	 * @param string $filename  Parser-approved filename.
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
	 * Filters scanner groups to documents accepted by the PDF validator.
	 *
	 * @param array $groups              Scanner period groups.
	 * @param array $validated_filenames Set of validated original filenames.
	 * @return list<ZFDZ_Menu_Period_Group>
	 */
	private function filter_groups( array $groups, array $validated_filenames ): array {
		$filtered_groups = array();

		foreach ( $groups as $group ) {
			$group_documents = array_values(
				array_filter(
					$group->get_documents(),
					static function ( ZFDZ_Menu_Document $document ) use ( $validated_filenames ): bool {
						return isset( $validated_filenames[ $document->get_original_filename() ] );
					}
				)
			);

			if ( array() === $group_documents ) {
				continue;
			}

			$filtered_groups[] = new ZFDZ_Menu_Period_Group(
				$group->get_start_date(),
				$group->get_end_date(),
				$group_documents
			);
		}

		return $filtered_groups;
	}

	/**
	 * Sorts all entry-level issues using binary string comparison.
	 *
	 * @param array $issues Issues to sort.
	 * @return void
	 */
	private function sort_issues( array &$issues ): void {
		usort(
			$issues,
			static function ( ZFDZ_Menu_Scan_Issue $left, ZFDZ_Menu_Scan_Issue $right ): int {
				$name_order = strcmp( $left->get_entry_name(), $right->get_entry_name() );

				if ( 0 !== $name_order ) {
					return $name_order;
				}

				return strcmp( $left->get_error_code(), $right->get_error_code() );
			}
		);
	}
}
