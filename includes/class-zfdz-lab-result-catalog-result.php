<?php
/**
 * Validated laboratory result catalog result.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Represents a validated laboratory result catalog or directory failure.
 */
final class ZFDZ_Lab_Result_Catalog_Result {

	/**
	 * Whether catalog building succeeded.
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
	 * Validated PDF candidate documents in matcher order.
	 *
	 * @var list<ZFDZ_Lab_Result_Document>
	 */
	private readonly array $documents;

	/**
	 * Exact-period matched and unmatched associations.
	 *
	 * @var list<ZFDZ_Lab_Result_Menu_Association>
	 */
	private readonly array $associations;

	/**
	 * Scanner and PDF validation issues in deterministic order.
	 *
	 * @var list<ZFDZ_Lab_Result_Scan_Issue>
	 */
	private readonly array $issues;

	/**
	 * Creates a catalog result while enforcing result invariants.
	 *
	 * @param bool        $successful          Whether catalog building succeeded.
	 * @param string|null $directory_error_code Directory error code.
	 * @param array       $associations        Laboratory result associations.
	 * @param array       $issues              Scanner and PDF validation issues.
	 * @throws InvalidArgumentException When the supplied state is contradictory.
	 */
	private function __construct(
		bool $successful,
		?string $directory_error_code,
		array $associations,
		array $issues
	) {
		if ( $successful && null !== $directory_error_code ) {
			throw new InvalidArgumentException( 'A successful catalog cannot contain a directory error code.' );
		}

		if ( ! $successful && ( null === $directory_error_code || '' === $directory_error_code ) ) {
			throw new InvalidArgumentException( 'A failed catalog requires a directory error code.' );
		}

		if ( ! $successful && ( ! empty( $associations ) || ! empty( $issues ) ) ) {
			throw new InvalidArgumentException( 'A failed catalog cannot contain entry-level results.' );
		}

		$documents    = array();
		$document_ids = array();

		foreach ( $associations as $association ) {
			if ( ! $association instanceof ZFDZ_Lab_Result_Menu_Association ) {
				throw new InvalidArgumentException( 'Catalog associations must contain only laboratory result associations.' );
			}

			$document    = $association->get_document();
			$document_id = spl_object_id( $document );

			if ( isset( $document_ids[ $document_id ] ) ) {
				throw new InvalidArgumentException( 'A laboratory result document cannot occur in more than one catalog association.' );
			}

			$document_ids[ $document_id ] = true;
			$documents[]                  = $document;
		}

		foreach ( $issues as $issue ) {
			if ( ! $issue instanceof ZFDZ_Lab_Result_Scan_Issue ) {
				throw new InvalidArgumentException( 'Catalog issues must contain only laboratory result scan issues.' );
			}
		}

		$this->successful           = $successful;
		$this->directory_error_code = $directory_error_code;
		$this->documents            = array_values( $documents );
		$this->associations         = array_values( $associations );
		$this->issues               = array_values( $issues );
	}

	/**
	 * Creates a successful validated catalog.
	 *
	 * @param array $associations Matched and unmatched associations.
	 * @param array $issues       Scanner and PDF validation issues.
	 * @return self
	 */
	public static function from_catalog( array $associations, array $issues ): self {
		return new self( true, null, $associations, $issues );
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
	 * Returns whether catalog building succeeded.
	 *
	 * @return bool
	 */
	public function is_successful(): bool {
		return $this->successful;
	}

	/**
	 * Returns the directory error code for a failed catalog.
	 *
	 * @return string|null
	 */
	public function get_directory_error_code(): ?string {
		return $this->directory_error_code;
	}

	/**
	 * Returns validated documents in association order.
	 *
	 * @return list<ZFDZ_Lab_Result_Document>
	 */
	public function get_documents(): array {
		return $this->documents;
	}

	/**
	 * Returns matched and unmatched associations in matcher order.
	 *
	 * @return list<ZFDZ_Lab_Result_Menu_Association>
	 */
	public function get_associations(): array {
		return $this->associations;
	}

	/**
	 * Returns scanner and PDF validation issues.
	 *
	 * @return list<ZFDZ_Lab_Result_Scan_Issue>
	 */
	public function get_issues(): array {
		return $this->issues;
	}
}
