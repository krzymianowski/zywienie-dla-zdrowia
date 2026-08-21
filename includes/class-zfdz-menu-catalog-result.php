<?php
/**
 * Validated menu catalog result.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Represents a validated menu catalog or a directory-level failure.
 */
final class ZFDZ_Menu_Catalog_Result {

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
	 * Validated PDF candidates in deterministic order.
	 *
	 * @var list<ZFDZ_Menu_Document>
	 */
	private readonly array $documents;

	/**
	 * Period groups containing only validated PDF candidates.
	 *
	 * @var list<ZFDZ_Menu_Period_Group>
	 */
	private readonly array $groups;

	/**
	 * Scanner and PDF validation issues in deterministic order.
	 *
	 * @var list<ZFDZ_Menu_Scan_Issue>
	 */
	private readonly array $issues;

	/**
	 * Creates a catalog result while enforcing directory failure invariants.
	 *
	 * @param bool        $successful          Whether catalog building succeeded.
	 * @param string|null $directory_error_code Directory error code.
	 * @param array       $documents           Validated documents.
	 * @param array       $groups              Filtered period groups.
	 * @param array       $issues              Entry-level issues.
	 * @throws InvalidArgumentException When the supplied state is contradictory.
	 */
	private function __construct(
		bool $successful,
		?string $directory_error_code,
		array $documents,
		array $groups,
		array $issues
	) {
		if ( $successful && null !== $directory_error_code ) {
			throw new InvalidArgumentException( 'A successful catalog cannot contain a directory error code.' );
		}

		if ( ! $successful && ( null === $directory_error_code || '' === $directory_error_code ) ) {
			throw new InvalidArgumentException( 'A failed catalog requires a directory error code.' );
		}

		if ( ! $successful && ( ! empty( $documents ) || ! empty( $groups ) || ! empty( $issues ) ) ) {
			throw new InvalidArgumentException( 'A failed catalog cannot contain entry-level results.' );
		}

		$this->successful           = $successful;
		$this->directory_error_code = $directory_error_code;
		$this->documents            = array_values( $documents );
		$this->groups               = array_values( $groups );
		$this->issues               = array_values( $issues );
	}

	/**
	 * Creates a successful validated catalog.
	 *
	 * @param array $documents Validated documents.
	 * @param array $groups    Filtered period groups.
	 * @param array $issues    Scanner and PDF validation issues.
	 * @return self
	 */
	public static function from_catalog( array $documents, array $groups, array $issues ): self {
		return new self( true, null, $documents, $groups, $issues );
	}

	/**
	 * Creates a directory-level failure with empty collections.
	 *
	 * @param string $error_code Machine-readable directory error code.
	 * @return self
	 */
	public static function from_directory_error( string $error_code ): self {
		return new self( false, $error_code, array(), array(), array() );
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
	 * Returns validated PDF candidates in deterministic order.
	 *
	 * @return list<ZFDZ_Menu_Document>
	 */
	public function get_documents(): array {
		return $this->documents;
	}

	/**
	 * Returns period groups containing only validated candidates.
	 *
	 * @return list<ZFDZ_Menu_Period_Group>
	 */
	public function get_groups(): array {
		return $this->groups;
	}

	/**
	 * Returns scanner and PDF validation issues in deterministic order.
	 *
	 * @return list<ZFDZ_Menu_Scan_Issue>
	 */
	public function get_issues(): array {
		return $this->issues;
	}
}
