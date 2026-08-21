<?php
/**
 * Menu period group value object.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Groups recognized menu documents with exactly the same date range.
 */
final class ZFDZ_Menu_Period_Group {

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
	 * Documents in deterministic order.
	 *
	 * @var list<ZFDZ_Menu_Document>
	 */
	private readonly array $documents;

	/**
	 * Creates a menu period group.
	 *
	 * @param string $start_date Menu period start date.
	 * @param string $end_date   Menu period end date.
	 * @param array  $documents  Documents in the period.
	 */
	public function __construct( string $start_date, string $end_date, array $documents ) {
		$this->start_date = $start_date;
		$this->end_date   = $end_date;
		$this->documents  = array_values( $documents );
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
	 * Returns documents in deterministic order.
	 *
	 * @return list<ZFDZ_Menu_Document>
	 */
	public function get_documents(): array {
		return $this->documents;
	}
}
