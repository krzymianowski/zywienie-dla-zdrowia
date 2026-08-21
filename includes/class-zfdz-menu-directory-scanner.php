<?php
/**
 * Menu directory scanner.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Scans the direct contents of a trusted menu directory path.
 */
final class ZFDZ_Menu_Directory_Scanner {

	public const ERROR_DIRECTORY_NOT_FOUND    = 'directory_not_found';
	public const ERROR_NOT_A_DIRECTORY        = 'not_a_directory';
	public const ERROR_DIRECTORY_NOT_READABLE = 'directory_not_readable';
	public const ERROR_DIRECTORY_SCAN_FAILED  = 'directory_scan_failed';

	/**
	 * Filename parser used for regular files.
	 *
	 * @var ZFDZ_Menu_Filename_Parser
	 */
	private readonly ZFDZ_Menu_Filename_Parser $filename_parser;

	/**
	 * Creates a menu directory scanner.
	 *
	 * @param ZFDZ_Menu_Filename_Parser $filename_parser Filename parser.
	 */
	public function __construct( ZFDZ_Menu_Filename_Parser $filename_parser ) {
		$this->filename_parser = $filename_parser;
	}

	/**
	 * Scans the direct contents of a trusted absolute directory path.
	 *
	 * @param string $directory Trusted absolute directory path from application configuration.
	 * @return ZFDZ_Menu_Scan_Result
	 */
	public function scan( string $directory ): ZFDZ_Menu_Scan_Result {
		if ( ! file_exists( $directory ) ) {
			return ZFDZ_Menu_Scan_Result::from_directory_error( self::ERROR_DIRECTORY_NOT_FOUND );
		}

		if ( ! is_dir( $directory ) ) {
			return ZFDZ_Menu_Scan_Result::from_directory_error( self::ERROR_NOT_A_DIRECTORY );
		}

		if ( ! is_readable( $directory ) ) {
			return ZFDZ_Menu_Scan_Result::from_directory_error( self::ERROR_DIRECTORY_NOT_READABLE );
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
					$issues[] = new ZFDZ_Menu_Scan_Issue(
						$entry_name,
						ZFDZ_Menu_Scan_Issue::ERROR_UNSAFE_SYMLINK
					);
					continue;
				}

				if ( ! $entry->isFile() ) {
					$issues[] = new ZFDZ_Menu_Scan_Issue(
						$entry_name,
						ZFDZ_Menu_Scan_Issue::ERROR_UNSUPPORTED_ENTRY_TYPE
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
					$issues[] = new ZFDZ_Menu_Scan_Issue( $entry_name, $error_code );
				}
			}
		} catch ( RuntimeException ) {
			return ZFDZ_Menu_Scan_Result::from_directory_error( self::ERROR_DIRECTORY_SCAN_FAILED );
		}

		$this->sort_documents( $documents );
		$this->sort_issues( $issues );

		return ZFDZ_Menu_Scan_Result::from_scan(
			$documents,
			$this->create_groups( $documents ),
			$issues
		);
	}

	/**
	 * Sorts documents by filename dates and then by original filename.
	 *
	 * @param array $documents Documents to sort.
	 * @return void
	 */
	private function sort_documents( array &$documents ): void {
		usort(
			$documents,
			static function ( ZFDZ_Menu_Document $left, ZFDZ_Menu_Document $right ): int {
				$start_date_order = strcmp( $right->get_start_date(), $left->get_start_date() );

				if ( 0 !== $start_date_order ) {
					return $start_date_order;
				}

				$end_date_order = strcmp( $right->get_end_date(), $left->get_end_date() );

				if ( 0 !== $end_date_order ) {
					return $end_date_order;
				}

				return strcmp( $left->get_original_filename(), $right->get_original_filename() );
			}
		);
	}

	/**
	 * Sorts issues by entry name and error code using binary string comparison.
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

	/**
	 * Creates and sorts exact-period groups from sorted documents.
	 *
	 * @param array $documents Sorted documents.
	 * @return list<ZFDZ_Menu_Period_Group>
	 */
	private function create_groups( array $documents ): array {
		$periods = array();

		foreach ( $documents as $document ) {
			$period_key = $document->get_start_date() . '_' . $document->get_end_date();

			if ( ! isset( $periods[ $period_key ] ) ) {
				$periods[ $period_key ] = array(
					'start_date' => $document->get_start_date(),
					'end_date'   => $document->get_end_date(),
					'documents'  => array(),
				);
			}

			$periods[ $period_key ]['documents'][] = $document;
		}

		$groups = array();

		foreach ( $periods as $period ) {
			$groups[] = new ZFDZ_Menu_Period_Group(
				$period['start_date'],
				$period['end_date'],
				$period['documents']
			);
		}

		usort(
			$groups,
			static function ( ZFDZ_Menu_Period_Group $left, ZFDZ_Menu_Period_Group $right ): int {
				$start_date_order = strcmp( $right->get_start_date(), $left->get_start_date() );

				if ( 0 !== $start_date_order ) {
					return $start_date_order;
				}

				return strcmp( $right->get_end_date(), $left->get_end_date() );
			}
		);

		return $groups;
	}
}
