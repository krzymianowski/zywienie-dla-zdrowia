<?php
/**
 * Laboratory result directory scanner.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Scans direct entries in a trusted laboratory result directory.
 */
final class ZFDZ_Lab_Result_Directory_Scanner {

	public const ERROR_DIRECTORY_NOT_FOUND    = 'directory_not_found';
	public const ERROR_NOT_A_DIRECTORY        = 'not_a_directory';
	public const ERROR_DIRECTORY_NOT_READABLE = 'directory_not_readable';
	public const ERROR_DIRECTORY_SCAN_FAILED  = 'directory_scan_failed';

	/**
	 * Filename parser used for regular files.
	 *
	 * @var ZFDZ_Lab_Result_Filename_Parser
	 */
	private readonly ZFDZ_Lab_Result_Filename_Parser $filename_parser;

	/**
	 * Creates a laboratory result directory scanner.
	 *
	 * @param ZFDZ_Lab_Result_Filename_Parser $filename_parser Filename parser.
	 */
	public function __construct( ZFDZ_Lab_Result_Filename_Parser $filename_parser ) {
		$this->filename_parser = $filename_parser;
	}

	/**
	 * Scans direct contents of a trusted absolute directory path.
	 *
	 * @param string $directory Trusted directory path from application configuration.
	 * @return ZFDZ_Lab_Result_Scan_Result
	 */
	public function scan( string $directory ): ZFDZ_Lab_Result_Scan_Result {
		if ( ! file_exists( $directory ) ) {
			return ZFDZ_Lab_Result_Scan_Result::from_directory_error( self::ERROR_DIRECTORY_NOT_FOUND );
		}

		if ( ! is_dir( $directory ) ) {
			return ZFDZ_Lab_Result_Scan_Result::from_directory_error( self::ERROR_NOT_A_DIRECTORY );
		}

		if ( ! is_readable( $directory ) ) {
			return ZFDZ_Lab_Result_Scan_Result::from_directory_error( self::ERROR_DIRECTORY_NOT_READABLE );
		}

		try {
			$iterator  = new DirectoryIterator( $directory );
			$documents = array();
			$issues    = array();

			foreach ( $iterator as $entry ) {
				if ( $entry->isDot() ) {
					continue;
				}

				$entry_name = $entry->getFilename();

				if ( $entry->isLink() ) {
					$issues[] = new ZFDZ_Lab_Result_Scan_Issue(
						$entry_name,
						ZFDZ_Lab_Result_Scan_Issue::ERROR_UNSAFE_SYMLINK
					);
					continue;
				}

				if ( ! $entry->isFile() ) {
					$issues[] = new ZFDZ_Lab_Result_Scan_Issue(
						$entry_name,
						ZFDZ_Lab_Result_Scan_Issue::ERROR_UNSUPPORTED_ENTRY_TYPE
					);
					continue;
				}

				$parse_result = $this->filename_parser->parse( $entry_name );

				if ( $parse_result->is_valid() ) {
					$document = $parse_result->get_document();

					if ( null !== $document ) {
						$documents[] = $document;
					}
					continue;
				}

				$error_code = $parse_result->get_error_code();

				if ( null !== $error_code ) {
					$issues[] = new ZFDZ_Lab_Result_Scan_Issue( $entry_name, $error_code );
				}
			}
		} catch ( RuntimeException ) {
			return ZFDZ_Lab_Result_Scan_Result::from_directory_error( self::ERROR_DIRECTORY_SCAN_FAILED );
		}

		$this->sort_documents( $documents );
		$this->sort_issues( $issues );

		return ZFDZ_Lab_Result_Scan_Result::from_scan( $documents, $issues );
	}

	/**
	 * Sorts documents by original filename using binary string comparison.
	 *
	 * @param array $documents Documents to sort.
	 * @return void
	 */
	private function sort_documents( array &$documents ): void {
		usort(
			$documents,
			static function ( ZFDZ_Lab_Result_Document $left, ZFDZ_Lab_Result_Document $right ): int {
				return strcmp( $left->get_original_filename(), $right->get_original_filename() );
			}
		);
	}

	/**
	 * Sorts issues by entry name and error code using binary comparison.
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
