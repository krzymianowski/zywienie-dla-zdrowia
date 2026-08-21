<?php
/**
 * Laboratory result to menu period matcher.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Associates laboratory results with menu groups by an exact date range.
 */
final class ZFDZ_Lab_Result_Menu_Matcher {

	/**
	 * Matches sorted laboratory result documents to exact menu period groups.
	 *
	 * @param array $documents   Laboratory result documents.
	 * @param array $menu_groups Existing menu period groups.
	 * @throws InvalidArgumentException When an input list contains an unexpected value.
	 * @throws LogicException When menu groups contain a duplicate date range.
	 * @return list<ZFDZ_Lab_Result_Menu_Association>
	 */
	public function match( array $documents, array $menu_groups ): array {
		$groups_by_period = array();

		foreach ( $menu_groups as $menu_group ) {
			if ( ! $menu_group instanceof ZFDZ_Menu_Period_Group ) {
				throw new InvalidArgumentException( 'Menu groups must contain only menu period groups.' );
			}

			$period_key = $this->get_period_key( $menu_group->get_start_date(), $menu_group->get_end_date() );

			if ( array_key_exists( $period_key, $groups_by_period ) ) {
				throw new LogicException( 'Menu period groups must have unique date ranges.' );
			}

			$groups_by_period[ $period_key ] = $menu_group;
		}

		$sorted_documents = array_values( $documents );

		foreach ( $sorted_documents as $document ) {
			if ( ! $document instanceof ZFDZ_Lab_Result_Document ) {
				throw new InvalidArgumentException( 'Documents must contain only laboratory result documents.' );
			}
		}

		usort( $sorted_documents, array( $this, 'compare_documents' ) );

		$associations = array();

		foreach ( $sorted_documents as $document ) {
			$period_key = $this->get_period_key(
				$document->get_menu_start_date(),
				$document->get_menu_end_date()
			);

			if ( array_key_exists( $period_key, $groups_by_period ) ) {
				$associations[] = ZFDZ_Lab_Result_Menu_Association::from_match(
					$document,
					$groups_by_period[ $period_key ]
				);
				continue;
			}

			$associations[] = ZFDZ_Lab_Result_Menu_Association::from_unmatched( $document );
		}

		return $associations;
	}

	/**
	 * Returns a collision-free key for a validated date range.
	 *
	 * @param string $start_date Period start date.
	 * @param string $end_date   Period end date.
	 * @return string
	 */
	private function get_period_key( string $start_date, string $end_date ): string {
		return $start_date . "\0" . $end_date;
	}

	/**
	 * Compares documents using the deterministic laboratory result order.
	 *
	 * @param ZFDZ_Lab_Result_Document $left  Left document.
	 * @param ZFDZ_Lab_Result_Document $right Right document.
	 * @return int
	 */
	private function compare_documents( ZFDZ_Lab_Result_Document $left, ZFDZ_Lab_Result_Document $right ): int {
		$result = strcmp( $right->get_result_date(), $left->get_result_date() );

		if ( 0 !== $result ) {
			return $result;
		}

		$result = strcmp( $right->get_menu_start_date(), $left->get_menu_start_date() );

		if ( 0 !== $result ) {
			return $result;
		}

		$result = strcmp( $right->get_menu_end_date(), $left->get_menu_end_date() );

		if ( 0 !== $result ) {
			return $result;
		}

		return strcmp( $left->get_original_filename(), $right->get_original_filename() );
	}
}
