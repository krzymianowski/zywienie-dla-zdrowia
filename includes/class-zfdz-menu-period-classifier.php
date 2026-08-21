<?php
/**
 * Standalone menu period classifier.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Classifies validated menu period groups relative to an explicit date.
 */
final class ZFDZ_Menu_Period_Classifier {

	/**
	 * Classifies groups without changing their input order.
	 *
	 * @param array  $groups Validated period groups.
	 * @param string $today  Reference date in YYYY-MM-DD format.
	 * @return ZFDZ_Menu_Period_Classification
	 *
	 * @throws InvalidArgumentException When the reference date is invalid.
	 */
	public function classify( array $groups, string $today ): ZFDZ_Menu_Period_Classification {
		if ( ! $this->is_valid_date( $today ) ) {
			throw new InvalidArgumentException( 'The reference date must be a valid date in YYYY-MM-DD format.' );
		}

		$current_groups  = array();
		$upcoming_groups = array();
		$archived_groups = array();

		foreach ( $groups as $group ) {
			if ( $group->get_start_date() > $today ) {
				$upcoming_groups[] = $group;
				continue;
			}

			if ( $group->get_end_date() < $today ) {
				$archived_groups[] = $group;
				continue;
			}

			$current_groups[] = $group;
		}

		return ZFDZ_Menu_Period_Classification::from_groups(
			$current_groups,
			$upcoming_groups,
			$archived_groups
		);
	}

	/**
	 * Checks a calendar date in the strict YYYY-MM-DD format.
	 *
	 * @param string $date Date to validate.
	 * @return bool
	 */
	private function is_valid_date( string $date ): bool {
		if ( 1 !== preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return false;
		}

		$parts = array_map( 'intval', explode( '-', $date ) );

		return checkdate( $parts[1], $parts[2], $parts[0] );
	}
}
