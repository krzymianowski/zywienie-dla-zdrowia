<?php
/**
 * Standalone PDF candidate file validator.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Performs limited, bounded checks on a trusted PDF candidate file path.
 */
final class ZFDZ_PDF_File_Validator {

	public const ERROR_UNSAFE_SYMLINK        = 'unsafe_symlink';
	public const ERROR_FILE_NOT_FOUND        = 'file_not_found';
	public const ERROR_NOT_A_REGULAR_FILE    = 'not_a_regular_file';
	public const ERROR_FILE_NOT_READABLE     = 'file_not_readable';
	public const ERROR_FILE_OPEN_FAILED      = 'file_open_failed';
	public const ERROR_FILE_STAT_FAILED      = 'file_stat_failed';
	public const ERROR_EMPTY_FILE            = 'empty_file';
	public const ERROR_UNSUPPORTED_MIME_TYPE = 'unsupported_mime_type';
	public const ERROR_MIME_DETECTION_FAILED = 'mime_detection_failed';
	public const ERROR_INVALID_PDF_HEADER    = 'invalid_pdf_header';
	public const ERROR_INVALID_PDF_EOF       = 'invalid_pdf_eof';
	public const ERROR_FILE_READ_FAILED      = 'file_read_failed';

	private const HEADER_READ_BYTES = 8;
	private const EOF_READ_BYTES    = 4096;

	private const SUPPORTED_MIME_TYPES = array(
		'application/pdf',
		'application/x-pdf',
	);

	/**
	 * Validates a trusted absolute file path as a PDF candidate.
	 *
	 * The path is expected to come from a trusted application layer, not directly
	 * from an HTTP request. The path is never stored in the returned result.
	 *
	 * @param string $file_path Trusted absolute path to a candidate file.
	 * @return ZFDZ_PDF_Validation_Result
	 */
	public function validate( string $file_path ): ZFDZ_PDF_Validation_Result {
		if ( is_link( $file_path ) ) {
			return ZFDZ_PDF_Validation_Result::from_error( self::ERROR_UNSAFE_SYMLINK );
		}

		if ( ! file_exists( $file_path ) ) {
			return ZFDZ_PDF_Validation_Result::from_error( self::ERROR_FILE_NOT_FOUND );
		}

		if ( ! is_file( $file_path ) ) {
			return ZFDZ_PDF_Validation_Result::from_error( self::ERROR_NOT_A_REGULAR_FILE );
		}

		if ( ! is_readable( $file_path ) ) {
			return ZFDZ_PDF_Validation_Result::from_error( self::ERROR_FILE_NOT_READABLE );
		}

		try {
			$file = new SplFileObject( $file_path, 'rb' );
		} catch ( RuntimeException ) {
			return ZFDZ_PDF_Validation_Result::from_error( self::ERROR_FILE_OPEN_FAILED );
		}

		try {
			$file_statistics = $file->fstat();
		} catch ( RuntimeException ) {
			return ZFDZ_PDF_Validation_Result::from_error( self::ERROR_FILE_STAT_FAILED );
		}

		if (
			false === $file_statistics
			|| ! isset( $file_statistics['size'] )
			|| ! is_int( $file_statistics['size'] )
			|| 0 > $file_statistics['size']
		) {
			return ZFDZ_PDF_Validation_Result::from_error( self::ERROR_FILE_STAT_FAILED );
		}

		$file_size = $file_statistics['size'];

		if ( 0 === $file_size ) {
			return ZFDZ_PDF_Validation_Result::from_error( self::ERROR_EMPTY_FILE );
		}

		$mime_check_performed = class_exists( 'finfo' );
		$detected_mime_type   = null;

		if ( $mime_check_performed ) {
			$detected_mime_type = $this->detect_mime_type( $file_path );

			if ( null === $detected_mime_type ) {
				return ZFDZ_PDF_Validation_Result::from_error(
					self::ERROR_MIME_DETECTION_FAILED,
					true
				);
			}

			if ( ! in_array( $detected_mime_type, self::SUPPORTED_MIME_TYPES, true ) ) {
				return ZFDZ_PDF_Validation_Result::from_error(
					self::ERROR_UNSUPPORTED_MIME_TYPE,
					true,
					$detected_mime_type
				);
			}
		}

		try {
			$file->rewind();
			$header = $file->fread( self::HEADER_READ_BYTES );
		} catch ( RuntimeException ) {
			return ZFDZ_PDF_Validation_Result::from_error(
				self::ERROR_FILE_READ_FAILED,
				$mime_check_performed,
				$detected_mime_type
			);
		}

		if ( false === $header ) {
			return ZFDZ_PDF_Validation_Result::from_error(
				self::ERROR_FILE_READ_FAILED,
				$mime_check_performed,
				$detected_mime_type
			);
		}

		if ( 1 !== preg_match( '/\A%PDF-\d\.\d\z/D', $header ) ) {
			return ZFDZ_PDF_Validation_Result::from_error(
				self::ERROR_INVALID_PDF_HEADER,
				$mime_check_performed,
				$detected_mime_type
			);
		}

		$tail_length = min( self::EOF_READ_BYTES, $file_size );
		$tail_offset = $file_size - $tail_length;

		try {
			if ( 0 !== $file->fseek( $tail_offset, SEEK_SET ) ) {
				return ZFDZ_PDF_Validation_Result::from_error(
					self::ERROR_FILE_READ_FAILED,
					$mime_check_performed,
					$detected_mime_type
				);
			}

			$tail = $file->fread( $tail_length );
		} catch ( RuntimeException ) {
			return ZFDZ_PDF_Validation_Result::from_error(
				self::ERROR_FILE_READ_FAILED,
				$mime_check_performed,
				$detected_mime_type
			);
		}

		if ( false === $tail || strlen( $tail ) !== $tail_length ) {
			return ZFDZ_PDF_Validation_Result::from_error(
				self::ERROR_FILE_READ_FAILED,
				$mime_check_performed,
				$detected_mime_type
			);
		}

		if ( ! str_contains( $tail, '%%EOF' ) ) {
			return ZFDZ_PDF_Validation_Result::from_error(
				self::ERROR_INVALID_PDF_EOF,
				$mime_check_performed,
				$detected_mime_type
			);
		}

		return ZFDZ_PDF_Validation_Result::from_valid_candidate(
			$mime_check_performed,
			$detected_mime_type
		);
	}

	/**
	 * Detects and normalizes a MIME type while containing expected warnings.
	 *
	 * @param string $file_path Trusted absolute file path.
	 * @return string|null
	 */
	private function detect_mime_type( string $file_path ): ?string {
		$warning_detected = false;
		$mime_type        = false;

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Converts a local finfo warning into a fail-closed result.
		set_error_handler(
			static function () use ( &$warning_detected ): bool {
				$warning_detected = true;
				return true;
			}
		);

		try {
			$file_info = new finfo( FILEINFO_MIME_TYPE );
			$mime_type = $file_info->file( $file_path );
		} catch ( Throwable ) {
			$warning_detected = true;
		} finally {
			restore_error_handler();
		}

		if ( $warning_detected || ! is_string( $mime_type ) ) {
			return null;
		}

		$mime_type = strtolower( trim( $mime_type ) );

		return '' === $mime_type ? null : $mime_type;
	}
}
