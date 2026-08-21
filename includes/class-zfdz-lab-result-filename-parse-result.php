<?php
/**
 * Laboratory result filename parse result.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Represents either a parsed laboratory result document or an error code.
 */
final class ZFDZ_Lab_Result_Filename_Parse_Result {

	/**
	 * Whether parsing succeeded.
	 *
	 * @var bool
	 */
	private readonly bool $valid;

	/**
	 * Parsed document for a valid result.
	 *
	 * @var ZFDZ_Lab_Result_Document|null
	 */
	private readonly ?ZFDZ_Lab_Result_Document $document;

	/**
	 * Machine-readable code for an invalid result.
	 *
	 * @var string|null
	 */
	private readonly ?string $error_code;

	/**
	 * Creates a parse result.
	 *
	 * @param bool                          $valid      Whether parsing succeeded.
	 * @param ZFDZ_Lab_Result_Document|null $document   Parsed document.
	 * @param string|null                   $error_code Machine-readable error code.
	 */
	private function __construct( bool $valid, ?ZFDZ_Lab_Result_Document $document, ?string $error_code ) {
		$this->valid      = $valid;
		$this->document   = $document;
		$this->error_code = $error_code;
	}

	/**
	 * Creates a valid parse result.
	 *
	 * @param ZFDZ_Lab_Result_Document $document Parsed document.
	 * @return self
	 */
	public static function from_document( ZFDZ_Lab_Result_Document $document ): self {
		return new self( true, $document, null );
	}

	/**
	 * Creates an invalid parse result.
	 *
	 * @param string $error_code Machine-readable error code.
	 * @throws InvalidArgumentException When the error code is empty.
	 * @return self
	 */
	public static function from_error( string $error_code ): self {
		if ( '' === $error_code ) {
			throw new InvalidArgumentException( 'A parse error code cannot be empty.' );
		}

		return new self( false, null, $error_code );
	}

	/**
	 * Returns whether parsing succeeded.
	 *
	 * @return bool
	 */
	public function is_valid(): bool {
		return $this->valid;
	}

	/**
	 * Returns the parsed document for a valid result.
	 *
	 * @return ZFDZ_Lab_Result_Document|null
	 */
	public function get_document(): ?ZFDZ_Lab_Result_Document {
		return $this->document;
	}

	/**
	 * Returns the error code for an invalid result.
	 *
	 * @return string|null
	 */
	public function get_error_code(): ?string {
		return $this->error_code;
	}
}
