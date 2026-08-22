<?php
/**
 * Tests the latest laboratory result selection policy.
 *
 * @package ZywienieDlaZdrowia
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests immutable latest selections and deterministic association selection.
 */
final class ZFDZ_Lab_Result_Latest_Selector_Test extends TestCase {

	/**
	 * Creates an empty selection with no association or document.
	 *
	 * @return void
	 */
	public function test_empty_selection_contract(): void {
		$selection = ZFDZ_Lab_Result_Latest_Selection::from_empty();

		$this->assertSame( ZFDZ_Lab_Result_Latest_Selection::STATUS_EMPTY, $selection->get_status() );
		$this->assertFalse( $selection->has_result() );
		$this->assertFalse( $selection->is_matched() );
		$this->assertNull( $selection->get_association() );
		$this->assertNull( $selection->get_document() );
	}

	/**
	 * Creates a matched selection while preserving object identity.
	 *
	 * @return void
	 */
	public function test_matched_selection_contract(): void {
		$association = $this->create_association( '2026-08-21', '2026-08-31', '2026-08-27', 'Badanie', true );
		$selection   = ZFDZ_Lab_Result_Latest_Selection::from_matched( $association );

		$this->assertSame( ZFDZ_Lab_Result_Latest_Selection::STATUS_MATCHED, $selection->get_status() );
		$this->assertTrue( $selection->has_result() );
		$this->assertTrue( $selection->is_matched() );
		$this->assertSame( $association, $selection->get_association() );
		$this->assertSame( $association->get_document(), $selection->get_document() );
	}

	/**
	 * Creates an unmatched selection while preserving object identity.
	 *
	 * @return void
	 */
	public function test_unmatched_selection_contract(): void {
		$association = $this->create_association( '2026-08-21', '2026-08-31', '2026-08-27', 'Badanie', false );
		$selection   = ZFDZ_Lab_Result_Latest_Selection::from_unmatched( $association );

		$this->assertSame( ZFDZ_Lab_Result_Latest_Selection::STATUS_UNMATCHED, $selection->get_status() );
		$this->assertTrue( $selection->has_result() );
		$this->assertFalse( $selection->is_matched() );
		$this->assertSame( $association, $selection->get_association() );
		$this->assertSame( $association->get_document(), $selection->get_document() );
	}

	/**
	 * Rejects an unmatched association in the matched factory.
	 *
	 * @return void
	 */
	public function test_matched_selection_rejects_unmatched_association(): void {
		$association = $this->create_association( '2026-08-21', '2026-08-31', '2026-08-27', 'Badanie', false );

		$this->expectException( InvalidArgumentException::class );

		ZFDZ_Lab_Result_Latest_Selection::from_matched( $association );
	}

	/**
	 * Rejects a matched association in the unmatched factory.
	 *
	 * @return void
	 */
	public function test_unmatched_selection_rejects_matched_association(): void {
		$association = $this->create_association( '2026-08-21', '2026-08-31', '2026-08-27', 'Badanie', true );

		$this->expectException( InvalidArgumentException::class );

		ZFDZ_Lab_Result_Latest_Selection::from_unmatched( $association );
	}

	/**
	 * Selects an empty result for an empty association list.
	 *
	 * @return void
	 */
	public function test_selects_empty_result_for_empty_associations(): void {
		$selection = ( new ZFDZ_Lab_Result_Latest_Selector() )->select( array() );

		$this->assertSame( ZFDZ_Lab_Result_Latest_Selection::STATUS_EMPTY, $selection->get_status() );
		$this->assertFalse( $selection->has_result() );
	}

	/**
	 * Selects one matched association.
	 *
	 * @return void
	 */
	public function test_selects_single_matched_association(): void {
		$association = $this->create_association( '2026-08-21', '2026-08-31', '2026-08-27', 'Badanie', true );
		$selection   = ( new ZFDZ_Lab_Result_Latest_Selector() )->select( array( $association ) );

		$this->assertSame( ZFDZ_Lab_Result_Latest_Selection::STATUS_MATCHED, $selection->get_status() );
		$this->assertSame( $association, $selection->get_association() );
	}

	/**
	 * Selects one unmatched association.
	 *
	 * @return void
	 */
	public function test_selects_single_unmatched_association(): void {
		$association = $this->create_association( '2026-08-21', '2026-08-31', '2026-08-27', 'Badanie', false );
		$selection   = ( new ZFDZ_Lab_Result_Latest_Selector() )->select( array( $association ) );

		$this->assertSame( ZFDZ_Lab_Result_Latest_Selection::STATUS_UNMATCHED, $selection->get_status() );
		$this->assertSame( $association, $selection->get_association() );
	}

	/**
	 * Selects the association with the greatest result date.
	 *
	 * @return void
	 */
	public function test_selects_greatest_result_date_and_preserves_identity(): void {
		$older  = $this->create_association( '2026-08-21', '2026-08-31', '2026-08-24', 'Starsze', false );
		$newest = $this->create_association( '2026-08-01', '2026-08-10', '2026-08-30', 'Najnowsze', false );
		$middle = $this->create_association( '2026-09-01', '2026-09-10', '2026-08-27', 'Środkowe', false );

		$selection = ( new ZFDZ_Lab_Result_Latest_Selector() )->select( array( $older, $newest, $middle ) );

		$this->assertSame( $newest, $selection->get_association() );
		$this->assertSame( $newest->get_document(), $selection->get_document() );
	}

	/**
	 * Uses descending menu start date when result dates are equal.
	 *
	 * @return void
	 */
	public function test_result_date_tie_uses_descending_menu_start_date(): void {
		$earlier_start = $this->create_association( '2026-08-20', '2026-08-31', '2026-09-01', 'Earlier', false );
		$later_start   = $this->create_association( '2026-08-21', '2026-08-31', '2026-09-01', 'Later', false );

		$selection = ( new ZFDZ_Lab_Result_Latest_Selector() )->select( array( $earlier_start, $later_start ) );

		$this->assertSame( $later_start, $selection->get_association() );
	}

	/**
	 * Uses descending menu end date when result and start dates are equal.
	 *
	 * @return void
	 */
	public function test_result_and_start_date_tie_uses_descending_menu_end_date(): void {
		$earlier_end = $this->create_association( '2026-08-21', '2026-08-30', '2026-09-01', 'Earlier', false );
		$later_end   = $this->create_association( '2026-08-21', '2026-08-31', '2026-09-01', 'Later', false );

		$selection = ( new ZFDZ_Lab_Result_Latest_Selector() )->select( array( $earlier_end, $later_end ) );

		$this->assertSame( $later_end, $selection->get_association() );
	}

	/**
	 * Uses ascending binary filename order when all dates are equal.
	 *
	 * @return void
	 */
	public function test_date_tie_uses_ascending_binary_filename(): void {
		$filename_b = '2026-08-21_2026-08-31_2026-09-01_B.pdf';
		$filename_a = '2026-08-21_2026-08-31_2026-09-01_A.pdf';
		$second     = $this->create_association( '2026-08-21', '2026-08-31', '2026-09-01', 'B', false, $filename_b );
		$first      = $this->create_association( '2026-08-21', '2026-08-31', '2026-09-01', 'A', false, $filename_a );

		$selection = ( new ZFDZ_Lab_Result_Latest_Selector() )->select( array( $second, $first ) );

		$this->assertSame( $first, $selection->get_association() );
	}

	/**
	 * Confirms case-sensitive binary filename ordering.
	 *
	 * @return void
	 */
	public function test_filename_tie_breaker_is_case_sensitive_binary_strcmp(): void {
		$lowercase = $this->create_association(
			'2026-08-21',
			'2026-08-31',
			'2026-09-01',
			'lowercase',
			false,
			'2026-08-21_2026-08-31_2026-09-01_a.pdf'
		);
		$uppercase = $this->create_association(
			'2026-08-21',
			'2026-08-31',
			'2026-09-01',
			'uppercase',
			false,
			'2026-08-21_2026-08-31_2026-09-01_A.pdf'
		);

		$selection = ( new ZFDZ_Lab_Result_Latest_Selector() )->select( array( $lowercase, $uppercase ) );

		$this->assertSame( $uppercase, $selection->get_association() );
	}

	/**
	 * Produces the same selection for every permutation of ordered metadata.
	 *
	 * @return void
	 */
	public function test_selection_is_independent_of_input_order(): void {
		$oldest   = $this->create_association( '2026-08-01', '2026-08-10', '2026-08-15', 'Oldest', false );
		$middle   = $this->create_association( '2026-08-11', '2026-08-20', '2026-08-25', 'Middle', true );
		$newest   = $this->create_association( '2026-08-21', '2026-08-31', '2026-08-30', 'Newest', false );
		$inputs   = array(
			array( $oldest, $middle, $newest ),
			array( $oldest, $newest, $middle ),
			array( $middle, $oldest, $newest ),
			array( $middle, $newest, $oldest ),
			array( $newest, $oldest, $middle ),
			array( $newest, $middle, $oldest ),
		);
		$selector = new ZFDZ_Lab_Result_Latest_Selector();

		foreach ( $inputs as $input ) {
			$this->assertSame( $newest, $selector->select( $input )->get_association() );
		}
	}

	/**
	 * Keeps the latest unmatched result instead of falling back to an older match.
	 *
	 * @return void
	 */
	public function test_latest_unmatched_does_not_fall_back_to_older_matched(): void {
		$older_matched    = $this->create_association( '2026-08-01', '2026-08-10', '2026-08-20', 'Matched', true );
		$latest_unmatched = $this->create_association( '2026-08-21', '2026-08-31', '2026-08-30', 'Unmatched', false );

		$selection = ( new ZFDZ_Lab_Result_Latest_Selector() )->select( array( $older_matched, $latest_unmatched ) );

		$this->assertSame( ZFDZ_Lab_Result_Latest_Selection::STATUS_UNMATCHED, $selection->get_status() );
		$this->assertSame( $latest_unmatched, $selection->get_association() );
		$this->assertFalse( $selection->is_matched() );
	}

	/**
	 * Selects a latest matched result even when an older result is unmatched.
	 *
	 * @return void
	 */
	public function test_latest_matched_wins_over_older_unmatched(): void {
		$older_unmatched = $this->create_association( '2026-08-01', '2026-08-10', '2026-08-20', 'Unmatched', false );
		$latest_matched  = $this->create_association( '2026-08-21', '2026-08-31', '2026-08-30', 'Matched', true );

		$selection = ( new ZFDZ_Lab_Result_Latest_Selector() )->select( array( $latest_matched, $older_unmatched ) );

		$this->assertSame( ZFDZ_Lab_Result_Latest_Selection::STATUS_MATCHED, $selection->get_status() );
		$this->assertSame( $latest_matched, $selection->get_association() );
	}

	/**
	 * Selects the highest result date among results for one menu group.
	 *
	 * @return void
	 */
	public function test_selects_latest_of_multiple_results_for_one_menu_group(): void {
		$group     = new ZFDZ_Menu_Period_Group( '2026-08-21', '2026-08-31', array() );
		$older     = ZFDZ_Lab_Result_Menu_Association::from_match(
			$this->create_document( '2026-08-21', '2026-08-31', '2026-08-25', 'Older' ),
			$group
		);
		$newer     = ZFDZ_Lab_Result_Menu_Association::from_match(
			$this->create_document( '2026-08-21', '2026-08-31', '2026-08-29', 'Newer' ),
			$group
		);
		$selection = ( new ZFDZ_Lab_Result_Latest_Selector() )->select( array( $older, $newer ) );

		$this->assertSame( $newer, $selection->get_association() );
		$this->assertSame( $group, $selection->get_association()->get_menu_group() );
	}

	/**
	 * Includes a result dated before its referenced menu period.
	 *
	 * @return void
	 */
	public function test_result_date_before_menu_period_participates_in_selection(): void {
		$older  = $this->create_association( '2026-08-01', '2026-08-10', '2026-07-31', 'Older', false );
		$latest = $this->create_association( '2026-09-01', '2026-09-10', '2026-08-01', 'Before period', false );

		$selection = ( new ZFDZ_Lab_Result_Latest_Selector() )->select( array( $older, $latest ) );

		$this->assertSame( $latest, $selection->get_association() );
	}

	/**
	 * Includes a result dated after its referenced menu period.
	 *
	 * @return void
	 */
	public function test_result_date_after_menu_period_participates_in_selection(): void {
		$older  = $this->create_association( '2026-08-21', '2026-08-31', '2026-09-14', 'Older', false );
		$latest = $this->create_association( '2026-08-01', '2026-08-10', '2026-09-15', 'After period', false );

		$selection = ( new ZFDZ_Lab_Result_Latest_Selector() )->select( array( $older, $latest ) );

		$this->assertSame( $latest, $selection->get_association() );
	}

	/**
	 * Rejects a non-association input element.
	 *
	 * @return void
	 */
	public function test_rejects_invalid_association_element(): void {
		$this->expectException( InvalidArgumentException::class );

		( new ZFDZ_Lab_Result_Latest_Selector() )->select( array( 'invalid' ) );
	}

	/**
	 * Does not modify the caller's input array.
	 *
	 * @return void
	 */
	public function test_does_not_modify_input_array(): void {
		$first  = $this->create_association( '2026-08-01', '2026-08-10', '2026-08-15', 'First', false );
		$second = $this->create_association( '2026-08-21', '2026-08-31', '2026-08-30', 'Second', true );
		$input  = array(
			9 => $first,
			3 => $second,
		);
		$copy   = $input;

		( new ZFDZ_Lab_Result_Latest_Selector() )->select( $input );

		$this->assertSame( $copy, $input );
	}

	/**
	 * Creates a laboratory result association fixture.
	 *
	 * @param string      $menu_start_date Referenced menu start date.
	 * @param string      $menu_end_date   Referenced menu end date.
	 * @param string      $result_date     Laboratory result date.
	 * @param string      $name            Document name.
	 * @param bool        $matched         Whether to create a matched association.
	 * @param string|null $filename        Optional original filename.
	 * @return ZFDZ_Lab_Result_Menu_Association
	 */
	private function create_association(
		string $menu_start_date,
		string $menu_end_date,
		string $result_date,
		string $name,
		bool $matched,
		?string $filename = null
	): ZFDZ_Lab_Result_Menu_Association {
		$document = $this->create_document(
			$menu_start_date,
			$menu_end_date,
			$result_date,
			$name,
			$filename
		);

		if ( ! $matched ) {
			return ZFDZ_Lab_Result_Menu_Association::from_unmatched( $document );
		}

		return ZFDZ_Lab_Result_Menu_Association::from_match(
			$document,
			new ZFDZ_Menu_Period_Group( $menu_start_date, $menu_end_date, array() )
		);
	}

	/**
	 * Creates a laboratory result document fixture.
	 *
	 * @param string      $menu_start_date Referenced menu start date.
	 * @param string      $menu_end_date   Referenced menu end date.
	 * @param string      $result_date     Laboratory result date.
	 * @param string      $name            Document name.
	 * @param string|null $filename        Optional original filename.
	 * @return ZFDZ_Lab_Result_Document
	 */
	private function create_document(
		string $menu_start_date,
		string $menu_end_date,
		string $result_date,
		string $name,
		?string $filename = null
	): ZFDZ_Lab_Result_Document {
		$original_filename = $filename ?? sprintf(
			'%1$s_%2$s_%3$s_%4$s.pdf',
			$menu_start_date,
			$menu_end_date,
			$result_date,
			$name
		);

		return new ZFDZ_Lab_Result_Document(
			$original_filename,
			$menu_start_date,
			$menu_end_date,
			$result_date,
			$name
		);
	}
}
