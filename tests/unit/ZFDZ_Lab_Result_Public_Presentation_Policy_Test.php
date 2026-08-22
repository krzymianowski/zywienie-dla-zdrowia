<?php
/**
 * Tests the laboratory result public presentation policy.
 *
 * @package ZywienieDlaZdrowia
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests immutable presentation decisions and latest-selection mapping.
 */
final class ZFDZ_Lab_Result_Public_Presentation_Policy_Test extends TestCase {

	/**
	 * Creates a no-result decision without exposing a candidate.
	 *
	 * @return void
	 */
	public function test_no_result_decision_contract(): void {
		$decision = ZFDZ_Lab_Result_Public_Presentation_Decision::from_no_result();

		$this->assertSame( ZFDZ_Lab_Result_Public_Presentation_Decision::STATUS_NO_RESULT, $decision->get_status() );
		$this->assertFalse( $decision->has_candidate() );
		$this->assertFalse( $decision->is_blocked() );
		$this->assertNull( $decision->get_association() );
		$this->assertNull( $decision->get_document() );
	}

	/**
	 * Creates a candidate while preserving association and document identity.
	 *
	 * @return void
	 */
	public function test_candidate_decision_contract(): void {
		$association = $this->create_association( '2026-08-22', 'Matched', true );
		$decision    = ZFDZ_Lab_Result_Public_Presentation_Decision::from_candidate( $association );

		$this->assertSame( ZFDZ_Lab_Result_Public_Presentation_Decision::STATUS_CANDIDATE, $decision->get_status() );
		$this->assertTrue( $decision->has_candidate() );
		$this->assertFalse( $decision->is_blocked() );
		$this->assertSame( $association, $decision->get_association() );
		$this->assertSame( $association->get_document(), $decision->get_document() );
	}

	/**
	 * Rejects an unmatched association as a presentation candidate.
	 *
	 * @return void
	 */
	public function test_candidate_decision_rejects_unmatched_association(): void {
		$this->expectException( InvalidArgumentException::class );

		ZFDZ_Lab_Result_Public_Presentation_Decision::from_candidate(
			$this->create_association( '2026-08-22', 'Unmatched', false )
		);
	}

	/**
	 * Creates a blocked decision without exposing an association or document.
	 *
	 * @return void
	 */
	public function test_blocked_unmatched_decision_contract(): void {
		$decision = ZFDZ_Lab_Result_Public_Presentation_Decision::from_blocked_unmatched();

		$this->assertSame( ZFDZ_Lab_Result_Public_Presentation_Decision::STATUS_BLOCKED_UNMATCHED, $decision->get_status() );
		$this->assertFalse( $decision->has_candidate() );
		$this->assertTrue( $decision->is_blocked() );
		$this->assertNull( $decision->get_association() );
		$this->assertNull( $decision->get_document() );
	}

	/**
	 * Maps an empty latest selection to no result.
	 *
	 * @return void
	 */
	public function test_policy_maps_empty_selection_to_no_result(): void {
		$decision = ( new ZFDZ_Lab_Result_Public_Presentation_Policy() )->decide(
			ZFDZ_Lab_Result_Latest_Selection::from_empty()
		);

		$this->assertSame( ZFDZ_Lab_Result_Public_Presentation_Decision::STATUS_NO_RESULT, $decision->get_status() );
		$this->assertFalse( $decision->has_candidate() );
	}

	/**
	 * Maps a matched latest selection to a candidate with preserved identity.
	 *
	 * @return void
	 */
	public function test_policy_maps_matched_selection_to_candidate(): void {
		$association = $this->create_association( '2026-08-22', 'Matched', true );
		$selection   = ZFDZ_Lab_Result_Latest_Selection::from_matched( $association );
		$decision    = ( new ZFDZ_Lab_Result_Public_Presentation_Policy() )->decide( $selection );

		$this->assertSame( ZFDZ_Lab_Result_Public_Presentation_Decision::STATUS_CANDIDATE, $decision->get_status() );
		$this->assertTrue( $decision->has_candidate() );
		$this->assertSame( $association, $decision->get_association() );
		$this->assertSame( $association->get_document(), $decision->get_document() );
	}

	/**
	 * Maps an unmatched latest selection to a blocked decision without a candidate.
	 *
	 * @return void
	 */
	public function test_policy_maps_unmatched_selection_to_blocked_without_exposing_candidate(): void {
		$association = $this->create_association( '2026-08-22', 'Unmatched', false );
		$selection   = ZFDZ_Lab_Result_Latest_Selection::from_unmatched( $association );
		$decision    = ( new ZFDZ_Lab_Result_Public_Presentation_Policy() )->decide( $selection );

		$this->assertSame( ZFDZ_Lab_Result_Public_Presentation_Decision::STATUS_BLOCKED_UNMATCHED, $decision->get_status() );
		$this->assertFalse( $decision->has_candidate() );
		$this->assertTrue( $decision->is_blocked() );
		$this->assertNull( $decision->get_association() );
		$this->assertNull( $decision->get_document() );
	}

	/**
	 * Keeps no-result and blocked decisions as distinct outcomes.
	 *
	 * @return void
	 */
	public function test_no_result_and_blocked_are_distinct_statuses(): void {
		$no_result = ZFDZ_Lab_Result_Public_Presentation_Decision::from_no_result();
		$blocked   = ZFDZ_Lab_Result_Public_Presentation_Decision::from_blocked_unmatched();

		$this->assertNotSame( $no_result->get_status(), $blocked->get_status() );
		$this->assertFalse( $no_result->is_blocked() );
		$this->assertTrue( $blocked->is_blocked() );
	}

	/**
	 * Does not mutate the supplied immutable selection.
	 *
	 * @return void
	 */
	public function test_policy_does_not_mutate_selection(): void {
		$association = $this->create_association( '2026-08-22', 'Matched', true );
		$selection   = ZFDZ_Lab_Result_Latest_Selection::from_matched( $association );

		( new ZFDZ_Lab_Result_Public_Presentation_Policy() )->decide( $selection );

		$this->assertSame( ZFDZ_Lab_Result_Latest_Selection::STATUS_MATCHED, $selection->get_status() );
		$this->assertSame( $association, $selection->get_association() );
		$this->assertSame( $association->get_document(), $selection->get_document() );
	}

	/**
	 * Blocks a latest unmatched result instead of falling back to an older match.
	 *
	 * @return void
	 */
	public function test_selector_policy_pipeline_blocks_latest_unmatched_without_fallback(): void {
		$older_matched    = $this->create_association( '2026-08-22', 'Older matched', true );
		$latest_unmatched = $this->create_association( '2026-08-30', 'Latest unmatched', false );
		$selection        = ( new ZFDZ_Lab_Result_Latest_Selector() )->select(
			array( $older_matched, $latest_unmatched )
		);
		$decision         = ( new ZFDZ_Lab_Result_Public_Presentation_Policy() )->decide( $selection );

		$this->assertSame( $latest_unmatched, $selection->get_association() );
		$this->assertSame( ZFDZ_Lab_Result_Public_Presentation_Decision::STATUS_BLOCKED_UNMATCHED, $decision->get_status() );
		$this->assertFalse( $decision->has_candidate() );
		$this->assertNull( $decision->get_association() );
		$this->assertNull( $decision->get_document() );
	}

	/**
	 * Allows a latest matched result despite an older unmatched association.
	 *
	 * @return void
	 */
	public function test_selector_policy_pipeline_accepts_latest_matched_with_older_unmatched(): void {
		$older_unmatched = $this->create_association( '2026-08-22', 'Older unmatched', false );
		$latest_matched  = $this->create_association( '2026-08-30', 'Latest matched', true );
		$selection       = ( new ZFDZ_Lab_Result_Latest_Selector() )->select(
			array( $latest_matched, $older_unmatched )
		);
		$decision        = ( new ZFDZ_Lab_Result_Public_Presentation_Policy() )->decide( $selection );

		$this->assertSame( $latest_matched, $selection->get_association() );
		$this->assertSame( ZFDZ_Lab_Result_Public_Presentation_Decision::STATUS_CANDIDATE, $decision->get_status() );
		$this->assertTrue( $decision->has_candidate() );
		$this->assertSame( $latest_matched, $decision->get_association() );
		$this->assertSame( $latest_matched->get_document(), $decision->get_document() );
	}

	/**
	 * Creates a laboratory result association fixture.
	 *
	 * @param string $result_date Laboratory result date.
	 * @param string $name        Document name.
	 * @param bool   $matched     Whether to create a matched association.
	 * @return ZFDZ_Lab_Result_Menu_Association
	 */
	private function create_association(
		string $result_date,
		string $name,
		bool $matched
	): ZFDZ_Lab_Result_Menu_Association {
		$menu_start_date = '2026-08-21';
		$menu_end_date   = '2026-08-31';
		$document        = new ZFDZ_Lab_Result_Document(
			sprintf( '%1$s_%2$s_%3$s_%4$s.pdf', $menu_start_date, $menu_end_date, $result_date, $name ),
			$menu_start_date,
			$menu_end_date,
			$result_date,
			$name
		);

		if ( ! $matched ) {
			return ZFDZ_Lab_Result_Menu_Association::from_unmatched( $document );
		}

		return ZFDZ_Lab_Result_Menu_Association::from_match(
			$document,
			new ZFDZ_Menu_Period_Group( $menu_start_date, $menu_end_date, array() )
		);
	}
}
