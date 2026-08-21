<?php
/**
 * Laboratory result document value object.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Represents metadata parsed from a recognized laboratory result filename.
 */
final class ZFDZ_Lab_Result_Document {

	/**
	 * Original filename.
	 *
	 * @var string
	 */
	private readonly string $original_filename;

	/**
	 * Referenced menu period start date in YYYY-MM-DD format.
	 *
	 * @var string
	 */
	private readonly string $menu_start_date;

	/**
	 * Referenced menu period end date in YYYY-MM-DD format.
	 *
	 * @var string
	 */
	private readonly string $menu_end_date;

	/**
	 * Laboratory result date in YYYY-MM-DD format.
	 *
	 * @var string
	 */
	private readonly string $result_date;

	/**
	 * Name extracted from the filename.
	 *
	 * @var string
	 */
	private readonly string $name;

	/**
	 * Creates a laboratory result document value object.
	 *
	 * @param string $original_filename Original filename.
	 * @param string $menu_start_date   Referenced menu period start date.
	 * @param string $menu_end_date     Referenced menu period end date.
	 * @param string $result_date       Laboratory result date.
	 * @param string $name              Name extracted from the filename.
	 */
	public function __construct(
		string $original_filename,
		string $menu_start_date,
		string $menu_end_date,
		string $result_date,
		string $name
	) {
		$this->original_filename = $original_filename;
		$this->menu_start_date   = $menu_start_date;
		$this->menu_end_date     = $menu_end_date;
		$this->result_date       = $result_date;
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
	 * Returns the referenced menu period start date.
	 *
	 * @return string
	 */
	public function get_menu_start_date(): string {
		return $this->menu_start_date;
	}

	/**
	 * Returns the referenced menu period end date.
	 *
	 * @return string
	 */
	public function get_menu_end_date(): string {
		return $this->menu_end_date;
	}

	/**
	 * Returns the laboratory result date.
	 *
	 * @return string
	 */
	public function get_result_date(): string {
		return $this->result_date;
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
