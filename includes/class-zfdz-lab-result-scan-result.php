<?php
/**
 * Laboratory result directory scan result.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Represents a successful scan or a directory-level failure.
 */
final class ZFDZ_Lab_Result_Scan_Result {

	/**
	 * Whether scanning succeeded.
	 *
	 * @var bool
	 */
	private readonly bool $successful;

	/**
	 * Machine-readable directory error code.
	 *
	 * @var string|null
	 */
	private readonly ?string $directory_error_code;

	/**
	 * Recognized filename documents in deterministic order.
	 *
	 * @var list<ZFDZ_Lab_Result_Document>
	 */
	private readonly array $documents;

	/**
	 * Entry-level issues in deterministic order.
	 *
	 * @var list<ZFDZ_Lab_Result_Scan_Issue>
	 */
	private readonly array $issues;

	/**
	 * Creates a scan result while enforcing directory failure invariants.
	 *
	 * @param bool        $successful          Whether scanning succeeded.
	 * @param string|null $directory_error_code Directory error code.
	 * @param array       $documents           Recognized documents.
	 * @param array       $issues              Entry-level issues.
	 * @throws InvalidArgumentException When the supplied state is contradictory.
	 */
	private function __construct(
		bool $successful,
		?string $directory_error_code,
		array $documents,
		array $issues
	) {
		if ( $successful && null !== $directory_error_code ) {
			throw new InvalidArgumentException( 'A successful scan cannot contain a directory error code.' );
		}

		if ( ! $successful && ( null === $directory_error_code || '' === $directory_error_code ) ) {
			throw new InvalidArgumentException( 'A failed scan requires a directory error code.' );
		}

		if ( ! $successful && ( ! empty( $documents ) || ! empty( $issues ) ) ) {
			throw new InvalidArgumentException( 'A failed scan cannot contain entry-level results.' );
		}

		foreach ( $documents as $document ) {
			if ( ! $document instanceof ZFDZ_Lab_Result_Document ) {
				throw new InvalidArgumentException( 'Scan documents must contain only laboratory result documents.' );
			}
		}

		foreach ( $issues as $issue ) {
			if ( ! $issue instanceof ZFDZ_Lab_Result_Scan_Issue ) {
				throw new InvalidArgumentException( 'Scan issues must contain only laboratory result scan issues.' );
			}
		}

		$this->successful           = $successful;
		$this->directory_error_code = $directory_error_code;
		$this->documents            = array_values( $documents );
		$this->issues               = array_values( $issues );
	}

	/**
	 * Creates a successful directory scan.
	 *
	 * @param array $documents Recognized documents.
	 * @param array $issues    Entry-level issues.
	 * @return self
	 */
	public static function from_scan( array $documents, array $issues ): self {
		return new self( true, null, $documents, $issues );
	}

	/**
	 * Creates a directory-level failure with empty collections.
	 *
	 * @param string $error_code Machine-readable directory error code.
	 * @return self
	 */
	public static function from_directory_error( string $error_code ): self {
		return new self( false, $error_code, array(), array() );
	}

	/**
	 * Returns whether scanning succeeded.
	 *
	 * @return bool
	 */
	public function is_successful(): bool {
		return $this->successful;
	}

	/**
	 * Returns the directory error code for a failed scan.
	 *
	 * @return string|null
	 */
	public function get_directory_error_code(): ?string {
		return $this->directory_error_code;
	}

	/**
	 * Returns recognized documents in deterministic order.
	 *
	 * @return list<ZFDZ_Lab_Result_Document>
	 */
	public function get_documents(): array {
		return $this->documents;
	}

	/**
	 * Returns entry-level issues in deterministic order.
	 *
	 * @return list<ZFDZ_Lab_Result_Scan_Issue>
	 */
	public function get_issues(): array {
		return $this->issues;
	}
}
