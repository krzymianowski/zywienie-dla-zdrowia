<?php
/**
 * Laboratory result scan issue value object.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Represents a problem with one entry in a laboratory result directory.
 */
final class ZFDZ_Lab_Result_Scan_Issue {

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
	 * @throws InvalidArgumentException When either value is empty.
	 */
	public function __construct( string $entry_name, string $error_code ) {
		if ( '' === $entry_name ) {
			throw new InvalidArgumentException( 'A scan issue entry name cannot be empty.' );
		}

		if ( '' === $error_code ) {
			throw new InvalidArgumentException( 'A scan issue error code cannot be empty.' );
		}

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
