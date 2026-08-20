<?php
/**
 * Menu filename parser.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Parses menu document filenames without accessing the filesystem.
 */
final class ZFDZ_Menu_Filename_Parser {

	public const ERROR_INVALID_PATH          = 'invalid_path';
	public const ERROR_UNSUPPORTED_EXTENSION = 'unsupported_extension';
	public const ERROR_INVALID_FORMAT        = 'invalid_format';
	public const ERROR_INVALID_START_DATE    = 'invalid_start_date';
	public const ERROR_INVALID_END_DATE      = 'invalid_end_date';
	public const ERROR_INVALID_DATE_RANGE    = 'invalid_date_range';
	public const ERROR_INVALID_NAME          = 'invalid_name';

	/**
	 * Recognized menu filename structure.
	 *
	 * @var string
	 */
	private const FILENAME_PATTERN = '/^(\d{4}-\d{2}-\d{2})_(\d{4}-\d{2}-\d{2})_(.*)\.pdf\z/is';

	/**
	 * Parses a menu document filename.
	 *
	 * @param string $filename Untrusted filename.
	 * @return ZFDZ_Menu_Filename_Parse_Result
	 */
	public function parse( string $filename ): ZFDZ_Menu_Filename_Parse_Result {
		if (
			str_contains( $filename, "\0" )
			|| str_contains( $filename, '/' )
			|| str_contains( $filename, '\\' )
		) {
			return ZFDZ_Menu_Filename_Parse_Result::from_error( self::ERROR_INVALID_PATH );
		}

		if ( 1 !== preg_match( '/\.pdf\z/i', $filename ) ) {
			return ZFDZ_Menu_Filename_Parse_Result::from_error( self::ERROR_UNSUPPORTED_EXTENSION );
		}

		$matches = array();

		if ( 1 !== preg_match( self::FILENAME_PATTERN, $filename, $matches ) ) {
			return ZFDZ_Menu_Filename_Parse_Result::from_error( self::ERROR_INVALID_FORMAT );
		}

		$start_date = $matches[1];
		$end_date   = $matches[2];
		$name       = $matches[3];

		if ( ! $this->is_valid_name( $name ) ) {
			return ZFDZ_Menu_Filename_Parse_Result::from_error( self::ERROR_INVALID_NAME );
		}

		if ( ! $this->is_valid_date( $start_date ) ) {
			return ZFDZ_Menu_Filename_Parse_Result::from_error( self::ERROR_INVALID_START_DATE );
		}

		if ( ! $this->is_valid_date( $end_date ) ) {
			return ZFDZ_Menu_Filename_Parse_Result::from_error( self::ERROR_INVALID_END_DATE );
		}

		if ( $end_date < $start_date ) {
			return ZFDZ_Menu_Filename_Parse_Result::from_error( self::ERROR_INVALID_DATE_RANGE );
		}

		return ZFDZ_Menu_Filename_Parse_Result::from_document(
			new ZFDZ_Menu_Document( $filename, $start_date, $end_date, $name )
		);
	}

	/**
	 * Checks whether a date is a real calendar date.
	 *
	 * @param string $date Date in YYYY-MM-DD format.
	 * @return bool
	 */
	private function is_valid_date( string $date ): bool {
		$year  = (int) substr( $date, 0, 4 );
		$month = (int) substr( $date, 5, 2 );
		$day   = (int) substr( $date, 8, 2 );

		return checkdate( $month, $day, $year );
	}

	/**
	 * Checks whether the extracted name is usable and valid UTF-8.
	 *
	 * @param string $name Extracted name.
	 * @return bool
	 */
	private function is_valid_name( string $name ): bool {
		if ( 1 !== preg_match( '//u', $name ) ) {
			return false;
		}

		if ( '' === $name || '.' === $name || '..' === $name ) {
			return false;
		}

		if ( 1 === preg_match( '/[\x00-\x1F\x7F]/', $name ) ) {
			return false;
		}

		return 1 !== preg_match( '/\A\s|\s\z/u', $name );
	}
}
