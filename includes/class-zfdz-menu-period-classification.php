<?php
/**
 * Menu period classification value object.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Contains menu period groups divided by their relation to a reference date.
 */
final class ZFDZ_Menu_Period_Classification {

	/**
	 * Groups that include the reference date.
	 *
	 * @var list<ZFDZ_Menu_Period_Group>
	 */
	private readonly array $current_groups;

	/**
	 * Groups that start after the reference date.
	 *
	 * @var list<ZFDZ_Menu_Period_Group>
	 */
	private readonly array $upcoming_groups;

	/**
	 * Groups that end before the reference date.
	 *
	 * @var list<ZFDZ_Menu_Period_Group>
	 */
	private readonly array $archived_groups;

	/**
	 * Creates an immutable period classification.
	 *
	 * @param array $current_groups  Current groups in input order.
	 * @param array $upcoming_groups Upcoming groups in input order.
	 * @param array $archived_groups Archived groups in input order.
	 */
	private function __construct( array $current_groups, array $upcoming_groups, array $archived_groups ) {
		$this->current_groups  = array_values( $current_groups );
		$this->upcoming_groups = array_values( $upcoming_groups );
		$this->archived_groups = array_values( $archived_groups );
	}

	/**
	 * Creates a classification from three ordered group collections.
	 *
	 * @param array $current_groups  Current groups in input order.
	 * @param array $upcoming_groups Upcoming groups in input order.
	 * @param array $archived_groups Archived groups in input order.
	 * @return self
	 */
	public static function from_groups( array $current_groups, array $upcoming_groups, array $archived_groups ): self {
		return new self( $current_groups, $upcoming_groups, $archived_groups );
	}

	/**
	 * Returns groups that include the reference date.
	 *
	 * @return list<ZFDZ_Menu_Period_Group>
	 */
	public function get_current_groups(): array {
		return $this->current_groups;
	}

	/**
	 * Returns groups that start after the reference date.
	 *
	 * @return list<ZFDZ_Menu_Period_Group>
	 */
	public function get_upcoming_groups(): array {
		return $this->upcoming_groups;
	}

	/**
	 * Returns groups that end before the reference date.
	 *
	 * @return list<ZFDZ_Menu_Period_Group>
	 */
	public function get_archived_groups(): array {
		return $this->archived_groups;
	}
}
