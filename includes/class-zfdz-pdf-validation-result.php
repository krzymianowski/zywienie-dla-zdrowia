<?php
/**
 * PDF file validation result.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Represents a valid PDF candidate or a machine-readable validation error.
 */
final class ZFDZ_PDF_Validation_Result {

	/**
	 * Whether the file passed the limited PDF candidate checks.
	 *
	 * @var bool
	 */
	private readonly bool $valid_candidate;

	/**
	 * Machine-readable error code for an invalid candidate.
	 *
	 * @var string|null
	 */
	private readonly ?string $error_code;

	/**
	 * Whether MIME detection was attempted.
	 *
	 * @var bool
	 */
	private readonly bool $mime_check_performed;

	/**
	 * Detected MIME type when detection succeeded.
	 *
	 * @var string|null
	 */
	private readonly ?string $detected_mime_type;

	/**
	 * Creates a PDF validation result while enforcing its invariants.
	 *
	 * @param bool        $valid_candidate      Whether the candidate passed validation.
	 * @param string|null $error_code           Machine-readable error code.
	 * @param bool        $mime_check_performed Whether MIME detection was attempted.
	 * @param string|null $detected_mime_type   Detected MIME type.
	 * @throws InvalidArgumentException When the supplied state is contradictory.
	 */
	private function __construct(
		bool $valid_candidate,
		?string $error_code,
		bool $mime_check_performed,
		?string $detected_mime_type
	) {
		if ( $valid_candidate && null !== $error_code ) {
			throw new InvalidArgumentException( 'A valid PDF candidate cannot contain an error code.' );
		}

		if ( ! $valid_candidate && ( null === $error_code || '' === $error_code ) ) {
			throw new InvalidArgumentException( 'An invalid PDF candidate requires an error code.' );
		}

		if ( ! $mime_check_performed && null !== $detected_mime_type ) {
			throw new InvalidArgumentException( 'A MIME type cannot be present when MIME detection was not performed.' );
		}

		if ( '' === $detected_mime_type ) {
			throw new InvalidArgumentException( 'A detected MIME type cannot be empty.' );
		}

		if ( $valid_candidate && $mime_check_performed && null === $detected_mime_type ) {
			throw new InvalidArgumentException( 'A valid candidate requires the detected MIME type after a MIME check.' );
		}

		$this->valid_candidate      = $valid_candidate;
		$this->error_code           = $error_code;
		$this->mime_check_performed = $mime_check_performed;
		$this->detected_mime_type   = $detected_mime_type;
	}

	/**
	 * Creates a valid PDF candidate result.
	 *
	 * @param bool        $mime_check_performed Whether MIME detection was performed.
	 * @param string|null $detected_mime_type   Detected MIME type.
	 * @return self
	 */
	public static function from_valid_candidate(
		bool $mime_check_performed,
		?string $detected_mime_type
	): self {
		return new self( true, null, $mime_check_performed, $detected_mime_type );
	}

	/**
	 * Creates an invalid PDF candidate result.
	 *
	 * @param string      $error_code           Machine-readable error code.
	 * @param bool        $mime_check_performed Whether MIME detection was attempted.
	 * @param string|null $detected_mime_type   Detected MIME type.
	 * @return self
	 */
	public static function from_error(
		string $error_code,
		bool $mime_check_performed = false,
		?string $detected_mime_type = null
	): self {
		return new self( false, $error_code, $mime_check_performed, $detected_mime_type );
	}

	/**
	 * Returns whether the file passed the limited PDF candidate checks.
	 *
	 * @return bool
	 */
	public function is_valid_candidate(): bool {
		return $this->valid_candidate;
	}

	/**
	 * Returns the machine-readable error code for an invalid candidate.
	 *
	 * @return string|null
	 */
	public function get_error_code(): ?string {
		return $this->error_code;
	}

	/**
	 * Returns whether MIME detection was attempted.
	 *
	 * @return bool
	 */
	public function was_mime_check_performed(): bool {
		return $this->mime_check_performed;
	}

	/**
	 * Returns the detected MIME type when available.
	 *
	 * @return string|null
	 */
	public function get_detected_mime_type(): ?string {
		return $this->detected_mime_type;
	}
}
