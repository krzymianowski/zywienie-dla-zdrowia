<?php
/**
 * Menu scan issue value object.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Represents a problem with a single entry in the scanned directory.
 */
final class ZFDZ_Menu_Scan_Issue {

	public const ERROR_UNSAFE_SYMLINK         = 'unsafe_symlink';
	public const ERROR_UNSUPPORTED_ENTRY_TYPE = 'unsupported_entry_type';

	/**
	 * Entry name without its directory path.
	 *
	 * @var string
	 */
	private readonly string $entry_name;

	/**
	 * Machine-readable error code.
	 *
	 * @var string
	 */
	private readonly string $error_code;

	/**
	 * Creates a scan issue.
	 *
	 * @param string $entry_name Entry name without its directory path.
	 * @param string $error_code Machine-readable error code.
	 */
	public function __construct( string $entry_name, string $error_code ) {
		$this->entry_name = $entry_name;
		$this->error_code = $error_code;
	}

	/**
	 * Returns the entry name.
	 *
	 * @return string
	 */
	public function get_entry_name(): string {
		return $this->entry_name;
	}

	/**
	 * Returns the machine-readable error code.
	 *
	 * @return string
	 */
	public function get_error_code(): string {
		return $this->error_code;
	}
}
