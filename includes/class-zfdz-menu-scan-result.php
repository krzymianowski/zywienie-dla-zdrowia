<?php
/**
 * Menu directory scan result.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Represents either a successful scan or a directory-level failure.
 */
final class ZFDZ_Menu_Scan_Result {

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
	 * Recognized documents in deterministic order.
	 *
	 * @var list<ZFDZ_Menu_Document>
	 */
	private readonly array $documents;

	/**
	 * Period groups in deterministic order.
	 *
	 * @var list<ZFDZ_Menu_Period_Group>
	 */
	private readonly array $groups;

	/**
	 * Entry-level issues in deterministic order.
	 *
	 * @var list<ZFDZ_Menu_Scan_Issue>
	 */
	private readonly array $issues;

	/**
	 * Creates a scan result.
	 *
	 * @param bool        $successful          Whether scanning succeeded.
	 * @param string|null $directory_error_code Directory error code.
	 * @param array       $documents           Recognized documents.
	 * @param array       $groups              Period groups.
	 * @param array       $issues              Entry-level issues.
	 */
	private function __construct(
		bool $successful,
		?string $directory_error_code,
		array $documents,
		array $groups,
		array $issues
	) {
		$this->successful           = $successful;
		$this->directory_error_code = $directory_error_code;
		$this->documents            = array_values( $documents );
		$this->groups               = array_values( $groups );
		$this->issues               = array_values( $issues );
	}

	/**
	 * Creates a successful scan result.
	 *
	 * @param array $documents Recognized documents.
	 * @param array $groups    Period groups.
	 * @param array $issues    Entry-level issues.
	 * @return self
	 */
	public static function from_scan( array $documents, array $groups, array $issues ): self {
		return new self( true, null, $documents, $groups, $issues );
	}

	/**
	 * Creates a directory-level failure result with empty collections.
	 *
	 * @param string $error_code Machine-readable directory error code.
	 * @return self
	 */
	public static function from_directory_error( string $error_code ): self {
		return new self( false, $error_code, array(), array(), array() );
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
	 * @return list<ZFDZ_Menu_Document>
	 */
	public function get_documents(): array {
		return $this->documents;
	}

	/**
	 * Returns period groups in deterministic order.
	 *
	 * @return list<ZFDZ_Menu_Period_Group>
	 */
	public function get_groups(): array {
		return $this->groups;
	}

	/**
	 * Returns entry-level issues in deterministic order.
	 *
	 * @return list<ZFDZ_Menu_Scan_Issue>
	 */
	public function get_issues(): array {
		return $this->issues;
	}
}
