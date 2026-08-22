<?php
/**
 * Laboratory result public presentation policy.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Maps a latest laboratory result selection to a technical presentation decision.
 */
final class ZFDZ_Lab_Result_Public_Presentation_Policy {

	/**
	 * Decides whether the latest selection provides a technical candidate.
	 *
	 * This policy must only receive selections derived from an available,
	 * successful laboratory result catalog.
	 *
	 * @param ZFDZ_Lab_Result_Latest_Selection $selection Latest result selection.
	 * @return ZFDZ_Lab_Result_Public_Presentation_Decision
	 */
	public function decide(
		ZFDZ_Lab_Result_Latest_Selection $selection
	): ZFDZ_Lab_Result_Public_Presentation_Decision {
		return match ( $selection->get_status() ) {
			ZFDZ_Lab_Result_Latest_Selection::STATUS_EMPTY =>
				ZFDZ_Lab_Result_Public_Presentation_Decision::from_no_result(),
			ZFDZ_Lab_Result_Latest_Selection::STATUS_MATCHED =>
				ZFDZ_Lab_Result_Public_Presentation_Decision::from_candidate( $selection->get_association() ),
			ZFDZ_Lab_Result_Latest_Selection::STATUS_UNMATCHED =>
				ZFDZ_Lab_Result_Public_Presentation_Decision::from_blocked_unmatched(),
		};
	}
}
