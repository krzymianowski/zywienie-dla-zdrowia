<?php
/**
 * Menu document value object.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Represents metadata parsed from a recognized menu document filename.
 */
final class ZFDZ_Menu_Document {

	/**
	 * Original filename.
	 *
	 * @var string
	 */
	private readonly string $original_filename;

	/**
	 * Menu period start date in YYYY-MM-DD format.
	 *
	 * @var string
	 */
	private readonly string $start_date;

	/**
	 * Menu period end date in YYYY-MM-DD format.
	 *
	 * @var string
	 */
	private readonly string $end_date;

	/**
	 * Name extracted from the filename.
	 *
	 * @var string
	 */
	private readonly string $name;

	/**
	 * Creates a menu document value object.
	 *
	 * @param string $original_filename Original filename.
	 * @param string $start_date        Menu period start date.
	 * @param string $end_date          Menu period end date.
	 * @param string $name              Name extracted from the filename.
	 */
	public function __construct( string $original_filename, string $start_date, string $end_date, string $name ) {
		$this->original_filename = $original_filename;
		$this->start_date        = $start_date;
		$this->end_date          = $end_date;
		$this->name              = $name;
	}

	/**
	 * Returns the original filename.
	 *
	 * @return string
	 */
	public function get_original_filename(): string {
		return $this->original_filename;
	}

	/**
	 * Returns the menu period start date.
	 *
	 * @return string
	 */
	public function get_start_date(): string {
		return $this->start_date;
	}

	/**
	 * Returns the menu period end date.
	 *
	 * @return string
	 */
	public function get_end_date(): string {
		return $this->end_date;
	}

	/**
	 * Returns the name extracted from the filename.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}
}
