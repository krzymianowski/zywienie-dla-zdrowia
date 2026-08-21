<?php
/**
 * Tests the laboratory result to menu period matcher.
 *
 * @package ZywienieDlaZdrowia
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests deterministic exact-period laboratory result associations.
 */
final class ZFDZ_Lab_Result_Menu_Matcher_Test extends TestCase {

	/**
	 * Returns an empty association list for empty documents.
	 *
	 * @return void
	 */
	public function test_returns_empty_list_for_empty_documents(): void {
		$group = $this->create_group( '2026-08-21', '2026-08-31' );

		$result = ( new ZFDZ_Lab_Result_Menu_Matcher() )->match( array(), array( $group ) );

		$this->assertSame( array(), $result );
	}

	/**
	 * Treats a document as unmatched when no menu groups exist.
	 *
	 * @return void
	 */
	public function test_returns_unmatched_association_for_empty_menu_groups(): void {
		$document = $this->create_document( '2026-08-21', '2026-08-31', '2026-08-27', 'Badanie' );

		$result = ( new ZFDZ_Lab_Result_Menu_Matcher() )->match( array( $document ), array() );

		$this->assertCount( 1, $result );
		$this->assertFalse( $result[0]->is_matched() );
		$this->assertSame( $document, $result[0]->get_document() );
		$this->assertNull( $result[0]->get_menu_group() );
	}

	/**
	 * Matches one document to a group with the exact same date range.
	 *
	 * @return void
	 */
	public function test_matches_document_to_exact_menu_period(): void {
		$document = $this->create_document( '2026-08-21', '2026-08-31', '2026-08-27', 'Badanie' );
		$group    = $this->create_group( '2026-08-21', '2026-08-31' );

		$result = ( new ZFDZ_Lab_Result_Menu_Matcher() )->match( array( $document ), array( $group ) );

		$this->assertCount( 1, $result );
		$this->assertTrue( $result[0]->is_matched() );
		$this->assertSame( $document, $result[0]->get_document() );
		$this->assertSame( $group, $result[0]->get_menu_group() );
	}

	/**
	 * Leaves one valid document unmatched when its exact period is absent.
	 *
	 * @return void
	 */
	public function test_leaves_document_unmatched_when_exact_period_is_absent(): void {
		$document = $this->create_document( '2026-08-21', '2026-08-31', '2026-08-27', 'Badanie' );
		$group    = $this->create_group( '2026-09-01', '2026-09-10' );

		$result = ( new ZFDZ_Lab_Result_Menu_Matcher() )->match( array( $document ), array( $group ) );

		$this->assertFalse( $result[0]->is_matched() );
		$this->assertNull( $result[0]->get_menu_group() );
	}

	/**
	 * Allows multiple laboratory results to match the same menu period.
	 *
	 * @return void
	 */
	public function test_matches_multiple_results_to_the_same_menu_period(): void {
		$older = $this->create_document( '2026-08-21', '2026-08-31', '2026-08-24', 'Badanie mikrobiologiczne' );
		$newer = $this->create_document( '2026-08-21', '2026-08-31', '2026-08-28', 'Badanie kontrolne' );
		$group = $this->create_group( '2026-08-21', '2026-08-31' );

		$result = ( new ZFDZ_Lab_Result_Menu_Matcher() )->match( array( $older, $newer ), array( $group ) );

		$this->assertSame( $newer, $result[0]->get_document() );
		$this->assertSame( $older, $result[1]->get_document() );
		$this->assertSame( $group, $result[0]->get_menu_group() );
		$this->assertSame( $group, $result[1]->get_menu_group() );
	}

	/**
	 * Matches documents independently across several exact periods.
	 *
	 * @return void
	 */
	public function test_matches_documents_across_multiple_menu_periods(): void {
		$first_group   = $this->create_group( '2026-08-01', '2026-08-10' );
		$second_group  = $this->create_group( '2026-08-21', '2026-08-31' );
		$first_result  = $this->create_document( '2026-08-01', '2026-08-10', '2026-08-15', 'Badanie pierwsze' );
		$second_result = $this->create_document( '2026-08-21', '2026-08-31', '2026-08-27', 'Badanie drugie' );

		$result = ( new ZFDZ_Lab_Result_Menu_Matcher() )->match(
			array( $first_result, $second_result ),
			array( $first_group, $second_group )
		);

		$this->assertSame( $second_result, $result[0]->get_document() );
		$this->assertSame( $second_group, $result[0]->get_menu_group() );
		$this->assertSame( $first_result, $result[1]->get_document() );
		$this->assertSame( $first_group, $result[1]->get_menu_group() );
	}

	/**
	 * Requires both menu boundary dates to match exactly.
	 *
	 * @return void
	 */
	public function test_requires_both_menu_dates_for_a_match(): void {
		$same_start = $this->create_group( '2026-08-21', '2026-08-30' );
		$same_end   = $this->create_group( '2026-08-20', '2026-08-31' );
		$document   = $this->create_document( '2026-08-21', '2026-08-31', '2026-08-27', 'Badanie' );

		$result = ( new ZFDZ_Lab_Result_Menu_Matcher() )->match(
			array( $document ),
			array( $same_start, $same_end )
		);

		$this->assertFalse( $result[0]->is_matched() );
	}

	/**
	 * Does not match partially overlapping periods.
	 *
	 * @return void
	 */
	public function test_does_not_match_partially_overlapping_period(): void {
		$document = $this->create_document( '2026-08-21', '2026-08-31', '2026-08-27', 'Badanie' );
		$group    = $this->create_group( '2026-08-25', '2026-09-05' );

		$result = ( new ZFDZ_Lab_Result_Menu_Matcher() )->match( array( $document ), array( $group ) );

		$this->assertFalse( $result[0]->is_matched() );
	}

	/**
	 * Ignores result-date containment when matching menu periods.
	 *
	 * @return void
	 */
	public function test_does_not_match_by_result_date_inside_another_period(): void {
		$document = $this->create_document( '2026-08-01', '2026-08-10', '2026-08-25', 'Badanie' );
		$group    = $this->create_group( '2026-08-21', '2026-08-31' );

		$result = ( new ZFDZ_Lab_Result_Menu_Matcher() )->match( array( $document ), array( $group ) );

		$this->assertFalse( $result[0]->is_matched() );
	}

	/**
	 * Sorts by result date, menu range, and binary filename tie-breaker.
	 *
	 * @return void
	 */
	public function test_sorts_documents_deterministically_without_modifying_input(): void {
		$newest_result = $this->create_document(
			'2026-08-01',
			'2026-08-10',
			'2026-09-10',
			'Newest',
			'2026-08-01_2026-08-10_2026-09-10_Newest.pdf'
		);
		$filename_b    = $this->create_document(
			'2026-08-21',
			'2026-08-31',
			'2026-09-09',
			'B',
			'2026-08-21_2026-08-31_2026-09-09_B.pdf'
		);
		$earlier_start = $this->create_document(
			'2026-08-20',
			'2026-08-31',
			'2026-09-09',
			'Earlier start',
			'2026-08-20_2026-08-31_2026-09-09_Earlier-start.pdf'
		);
		$earlier_end   = $this->create_document(
			'2026-08-21',
			'2026-08-30',
			'2026-09-09',
			'Earlier end',
			'2026-08-21_2026-08-30_2026-09-09_Earlier-end.pdf'
		);
		$filename_a    = $this->create_document(
			'2026-08-21',
			'2026-08-31',
			'2026-09-09',
			'A',
			'2026-08-21_2026-08-31_2026-09-09_A.pdf'
		);
		$oldest_result = $this->create_document(
			'2026-09-01',
			'2026-09-10',
			'2026-09-08',
			'Oldest',
			'2026-09-01_2026-09-10_2026-09-08_Oldest.pdf'
		);
		$documents     = array(
			$filename_b,
			$oldest_result,
			$earlier_start,
			$newest_result,
			$earlier_end,
			$filename_a,
		);

		$result = ( new ZFDZ_Lab_Result_Menu_Matcher() )->match( $documents, array() );

		$this->assertSame(
			array(
				$newest_result,
				$filename_a,
				$filename_b,
				$earlier_end,
				$earlier_start,
				$oldest_result,
			),
			array_map(
				static fn ( ZFDZ_Lab_Result_Menu_Association $association ): ZFDZ_Lab_Result_Document => $association->get_document(),
				$result
			)
		);
		$this->assertSame(
			array(
				$filename_b,
				$oldest_result,
				$earlier_start,
				$newest_result,
				$earlier_end,
				$filename_a,
			),
			$documents
		);
	}

	/**
	 * Returns the original group instance without modifying its documents.
	 *
	 * @return void
	 */
	public function test_preserves_menu_group_identity_and_contents(): void {
		$menu_document   = new ZFDZ_Menu_Document(
			'2026-08-21_2026-08-31_Dieta.pdf',
			'2026-08-21',
			'2026-08-31',
			'Dieta'
		);
		$group           = $this->create_group( '2026-08-21', '2026-08-31', array( $menu_document ) );
		$documents       = $group->get_documents();
		$result_document = $this->create_document( '2026-08-21', '2026-08-31', '2026-08-27', 'Badanie' );

		$result = ( new ZFDZ_Lab_Result_Menu_Matcher() )->match( array( $result_document ), array( $group ) );

		$this->assertSame( $group, $result[0]->get_menu_group() );
		$this->assertSame( $documents, $group->get_documents() );
		$this->assertSame( $menu_document, $group->get_documents()[0] );
	}

	/**
	 * Rejects duplicate menu period keys as a programming contract error.
	 *
	 * @return void
	 */
	public function test_rejects_duplicate_menu_period_groups(): void {
		$first  = $this->create_group( '2026-08-21', '2026-08-31' );
		$second = $this->create_group( '2026-08-21', '2026-08-31' );

		$this->expectException( LogicException::class );

		( new ZFDZ_Lab_Result_Menu_Matcher() )->match( array(), array( $first, $second ) );
	}

	/**
	 * Creates a matched association for identical document and group periods.
	 *
	 * @return void
	 */
	public function test_association_factory_creates_exact_period_match(): void {
		$document = $this->create_document( '2026-08-21', '2026-08-31', '2026-08-27', 'Badanie' );
		$group    = $this->create_group( '2026-08-21', '2026-08-31' );

		$association = ZFDZ_Lab_Result_Menu_Association::from_match( $document, $group );

		$this->assertTrue( $association->is_matched() );
		$this->assertSame( $document, $association->get_document() );
		$this->assertSame( $group, $association->get_menu_group() );
	}

	/**
	 * Rejects a matched association with a different menu start date.
	 *
	 * @return void
	 */
	public function test_association_factory_rejects_different_menu_start_date(): void {
		$document = $this->create_document( '2026-08-21', '2026-08-31', '2026-08-27', 'Badanie' );
		$group    = $this->create_group( '2026-08-20', '2026-08-31' );

		$this->expectException( InvalidArgumentException::class );

		ZFDZ_Lab_Result_Menu_Association::from_match( $document, $group );
	}

	/**
	 * Rejects a matched association with a different menu end date.
	 *
	 * @return void
	 */
	public function test_association_factory_rejects_different_menu_end_date(): void {
		$document = $this->create_document( '2026-08-21', '2026-08-31', '2026-08-27', 'Badanie' );
		$group    = $this->create_group( '2026-08-21', '2026-08-30' );

		$this->expectException( InvalidArgumentException::class );

		ZFDZ_Lab_Result_Menu_Association::from_match( $document, $group );
	}

	/**
	 * Confirms that associations expose no filesystem paths or URLs.
	 *
	 * @return void
	 */
	public function test_association_model_does_not_expose_paths_or_urls(): void {
		$this->assertFalse( method_exists( ZFDZ_Lab_Result_Menu_Association::class, 'get_path' ) );
		$this->assertFalse( method_exists( ZFDZ_Lab_Result_Menu_Association::class, 'get_file_path' ) );
		$this->assertFalse( method_exists( ZFDZ_Lab_Result_Menu_Association::class, 'get_absolute_path' ) );
		$this->assertFalse( method_exists( ZFDZ_Lab_Result_Menu_Association::class, 'get_url' ) );
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

	/**
	 * Creates a menu period group fixture.
	 *
	 * @param string $start_date Period start date.
	 * @param string $end_date   Period end date.
	 * @param array  $documents  Menu documents.
	 * @return ZFDZ_Menu_Period_Group
	 */
	private function create_group( string $start_date, string $end_date, array $documents = array() ): ZFDZ_Menu_Period_Group {
		return new ZFDZ_Menu_Period_Group( $start_date, $end_date, $documents );
	}
}
