<?php
/**
 * Latest laboratory result selector.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Selects the latest laboratory result association deterministically.
 */
final class ZFDZ_Lab_Result_Latest_Selector {

	/**
	 * Selects the latest association independently of input order.
	 *
	 * @param array $associations Laboratory result associations.
	 * @throws InvalidArgumentException When the input contains an unexpected value.
	 * @return ZFDZ_Lab_Result_Latest_Selection
	 */
	public function select( array $associations ): ZFDZ_Lab_Result_Latest_Selection {
		$latest = null;

		foreach ( $associations as $association ) {
			if ( ! $association instanceof ZFDZ_Lab_Result_Menu_Association ) {
				throw new InvalidArgumentException( 'Associations must contain only laboratory result menu associations.' );
			}

			if ( null === $latest || 0 > $this->compare_associations( $association, $latest ) ) {
				$latest = $association;
			}
		}

		if ( null === $latest ) {
			return ZFDZ_Lab_Result_Latest_Selection::from_empty();
		}

		return $latest->is_matched()
			? ZFDZ_Lab_Result_Latest_Selection::from_matched( $latest )
			: ZFDZ_Lab_Result_Latest_Selection::from_unmatched( $latest );
	}

	/**
	 * Compares associations using the deterministic laboratory result order.
	 *
	 * @param ZFDZ_Lab_Result_Menu_Association $left  Left association.
	 * @param ZFDZ_Lab_Result_Menu_Association $right Right association.
	 * @return int
	 */
	private function compare_associations(
		ZFDZ_Lab_Result_Menu_Association $left,
		ZFDZ_Lab_Result_Menu_Association $right
	): int {
		$left_document  = $left->get_document();
		$right_document = $right->get_document();
		$result         = strcmp( $right_document->get_result_date(), $left_document->get_result_date() );

		if ( 0 !== $result ) {
			return $result;
		}

		$result = strcmp( $right_document->get_menu_start_date(), $left_document->get_menu_start_date() );

		if ( 0 !== $result ) {
			return $result;
		}

		$result = strcmp( $right_document->get_menu_end_date(), $left_document->get_menu_end_date() );

		if ( 0 !== $result ) {
			return $result;
		}

		return strcmp( $left_document->get_original_filename(), $right_document->get_original_filename() );
	}
}
