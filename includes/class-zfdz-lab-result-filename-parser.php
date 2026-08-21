<?php
/**
 * Laboratory result filename parser.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Parses laboratory result filenames without accessing the filesystem.
 */
final class ZFDZ_Lab_Result_Filename_Parser {

	public const ERROR_INVALID_PATH            = 'invalid_path';
	public const ERROR_UNSUPPORTED_EXTENSION   = 'unsupported_extension';
	public const ERROR_INVALID_FORMAT          = 'invalid_format';
	public const ERROR_INVALID_MENU_START_DATE = 'invalid_menu_start_date';
	public const ERROR_INVALID_MENU_END_DATE   = 'invalid_menu_end_date';
	public const ERROR_INVALID_RESULT_DATE     = 'invalid_result_date';
	public const ERROR_INVALID_MENU_DATE_RANGE = 'invalid_menu_date_range';
	public const ERROR_INVALID_NAME            = 'invalid_name';

	/**
	 * Recognized laboratory result filename structure.
	 *
	 * @var string
	 */
	private const FILENAME_PATTERN = '/^(\d{4}-\d{2}-\d{2})_(\d{4}-\d{2}-\d{2})_(\d{4}-\d{2}-\d{2})_(.*)\.pdf\z/is';

	/**
	 * Parses a laboratory result document filename.
	 *
	 * @param string $filename Untrusted filename.
	 * @return ZFDZ_Lab_Result_Filename_Parse_Result
	 */
	public function parse( string $filename ): ZFDZ_Lab_Result_Filename_Parse_Result {
		if (
			str_contains( $filename, "\0" )
			|| str_contains( $filename, '/' )
			|| str_contains( $filename, '\\' )
		) {
			return ZFDZ_Lab_Result_Filename_Parse_Result::from_error( self::ERROR_INVALID_PATH );
		}

		if ( 1 !== preg_match( '/\.pdf\z/i', $filename ) ) {
			return ZFDZ_Lab_Result_Filename_Parse_Result::from_error( self::ERROR_UNSUPPORTED_EXTENSION );
		}

		$matches = array();

		if ( 1 !== preg_match( self::FILENAME_PATTERN, $filename, $matches ) ) {
			return ZFDZ_Lab_Result_Filename_Parse_Result::from_error( self::ERROR_INVALID_FORMAT );
		}

		$menu_start_date = $matches[1];
		$menu_end_date   = $matches[2];
		$result_date     = $matches[3];
		$name            = $matches[4];

		if ( ! $this->is_valid_name( $name ) ) {
			return ZFDZ_Lab_Result_Filename_Parse_Result::from_error( self::ERROR_INVALID_NAME );
		}

		if ( ! $this->is_valid_date( $menu_start_date ) ) {
			return ZFDZ_Lab_Result_Filename_Parse_Result::from_error( self::ERROR_INVALID_MENU_START_DATE );
		}

		if ( ! $this->is_valid_date( $menu_end_date ) ) {
			return ZFDZ_Lab_Result_Filename_Parse_Result::from_error( self::ERROR_INVALID_MENU_END_DATE );
		}

		if ( ! $this->is_valid_date( $result_date ) ) {
			return ZFDZ_Lab_Result_Filename_Parse_Result::from_error( self::ERROR_INVALID_RESULT_DATE );
		}

		if ( $menu_end_date < $menu_start_date ) {
			return ZFDZ_Lab_Result_Filename_Parse_Result::from_error( self::ERROR_INVALID_MENU_DATE_RANGE );
		}

		return ZFDZ_Lab_Result_Filename_Parse_Result::from_document(
			new ZFDZ_Lab_Result_Document(
				$filename,
				$menu_start_date,
				$menu_end_date,
				$result_date,
				$name
			)
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

		if ( 1 === preg_match( '/\p{Cc}/u', $name ) ) {
			return false;
		}

		return 1 !== preg_match( '/\A\s|\s\z/u', $name );
	}
}
