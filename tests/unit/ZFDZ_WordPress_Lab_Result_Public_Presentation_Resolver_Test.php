<?php
/**
 * Tests the WordPress laboratory result public presentation result and resolver.
 *
 * @package ZywienieDlaZdrowia
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests coordinated availability mapping without loading WordPress.
 */
final class ZFDZ_WordPress_Lab_Result_Public_Presentation_Resolver_Test extends TestCase {

	/**
	 * Creates an unavailable result for a failed menu catalog.
	 *
	 * @return void
	 */
	public function test_menu_unavailable_result_contract(): void {
		$result = ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::from_unavailable(
			ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::UNAVAILABLE_REASON_MENU_CATALOG
		);

		$this->assertSame( ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::STATUS_UNAVAILABLE, $result->get_status() );
		$this->assertFalse( $result->is_available() );
		$this->assertSame(
			ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::UNAVAILABLE_REASON_MENU_CATALOG,
			$result->get_unavailable_reason()
		);
		$this->assertNull( $result->get_decision() );
		$this->assertFalse( $result->has_candidate() );
		$this->assertFalse( $result->is_blocked() );
		$this->assertNull( $result->get_association() );
		$this->assertNull( $result->get_document() );
	}

	/**
	 * Creates an unavailable result for a failed laboratory result catalog.
	 *
	 * @return void
	 */
	public function test_lab_unavailable_result_contract(): void {
		$result = ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::from_unavailable(
			ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::UNAVAILABLE_REASON_LAB_CATALOG
		);

		$this->assertSame( ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::STATUS_UNAVAILABLE, $result->get_status() );
		$this->assertFalse( $result->is_available() );
		$this->assertSame(
			ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::UNAVAILABLE_REASON_LAB_CATALOG,
			$result->get_unavailable_reason()
		);
		$this->assertNull( $result->get_decision() );
		$this->assertFalse( $result->has_candidate() );
		$this->assertFalse( $result->is_blocked() );
		$this->assertNull( $result->get_association() );
		$this->assertNull( $result->get_document() );
	}

	/**
	 * Rejects an unsupported unavailability reason.
	 *
	 * @return void
	 */
	public function test_unavailable_result_rejects_invalid_reason(): void {
		$this->expectException( InvalidArgumentException::class );

		ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::from_unavailable( 'invalid_reason' );
	}

	/**
	 * Creates an available no-result state from the exact decision instance.
	 *
	 * @return void
	 */
	public function test_no_result_decision_contract(): void {
		$decision = ZFDZ_Lab_Result_Public_Presentation_Decision::from_no_result();
		$result   = ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::from_decision( $decision );

		$this->assertSame( ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::STATUS_NO_RESULT, $result->get_status() );
		$this->assertTrue( $result->is_available() );
		$this->assertNull( $result->get_unavailable_reason() );
		$this->assertSame( $decision, $result->get_decision() );
		$this->assertFalse( $result->has_candidate() );
		$this->assertFalse( $result->is_blocked() );
		$this->assertNull( $result->get_association() );
		$this->assertNull( $result->get_document() );
	}

	/**
	 * Creates an available blocked state without exposing an unmatched document.
	 *
	 * @return void
	 */
	public function test_blocked_unmatched_decision_contract(): void {
		$decision = ZFDZ_Lab_Result_Public_Presentation_Decision::from_blocked_unmatched();
		$result   = ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::from_decision( $decision );

		$this->assertSame( ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::STATUS_BLOCKED_UNMATCHED, $result->get_status() );
		$this->assertTrue( $result->is_available() );
		$this->assertNull( $result->get_unavailable_reason() );
		$this->assertSame( $decision, $result->get_decision() );
		$this->assertFalse( $result->has_candidate() );
		$this->assertTrue( $result->is_blocked() );
		$this->assertNull( $result->get_association() );
		$this->assertNull( $result->get_document() );
	}

	/**
	 * Creates a candidate while preserving decision, association, and document identity.
	 *
	 * @return void
	 */
	public function test_candidate_decision_contract(): void {
		$association = $this->create_association( '2026-08-30', 'Candidate', true );
		$decision    = ZFDZ_Lab_Result_Public_Presentation_Decision::from_candidate( $association );
		$result      = ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::from_decision( $decision );

		$this->assertSame( ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::STATUS_CANDIDATE, $result->get_status() );
		$this->assertTrue( $result->is_available() );
		$this->assertNull( $result->get_unavailable_reason() );
		$this->assertSame( $decision, $result->get_decision() );
		$this->assertTrue( $result->has_candidate() );
		$this->assertFalse( $result->is_blocked() );
		$this->assertSame( $association, $result->get_association() );
		$this->assertSame( $association->get_document(), $result->get_document() );
	}

	/**
	 * Maps an unavailable menu catalog without producing a no-result decision.
	 *
	 * @return void
	 */
	public function test_resolver_maps_menu_catalog_unavailable(): void {
		$coordinated_result = ZFDZ_WordPress_Lab_Result_Catalog_Service_Result::from_menu_catalog_failure(
			ZFDZ_Menu_Catalog_Result::from_directory_error( 'directory_not_found' )
		);

		$result = $this->create_resolver()->resolve( $coordinated_result );

		$this->assertSame( ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::STATUS_UNAVAILABLE, $result->get_status() );
		$this->assertSame(
			ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::UNAVAILABLE_REASON_MENU_CATALOG,
			$result->get_unavailable_reason()
		);
		$this->assertNull( $result->get_decision() );
	}

	/**
	 * Maps an unavailable laboratory result catalog with a distinct reason.
	 *
	 * @return void
	 */
	public function test_resolver_maps_lab_catalog_unavailable(): void {
		$coordinated_result = ZFDZ_WordPress_Lab_Result_Catalog_Service_Result::from_lab_catalog_failure(
			$this->create_successful_menu_catalog(),
			ZFDZ_Lab_Result_Catalog_Result::from_directory_error( 'directory_not_found' )
		);

		$result = $this->create_resolver()->resolve( $coordinated_result );

		$this->assertSame( ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::STATUS_UNAVAILABLE, $result->get_status() );
		$this->assertSame(
			ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::UNAVAILABLE_REASON_LAB_CATALOG,
			$result->get_unavailable_reason()
		);
		$this->assertNull( $result->get_decision() );
	}

	/**
	 * Maps a successful empty laboratory catalog to no result.
	 *
	 * @return void
	 */
	public function test_resolver_maps_successful_empty_catalog_to_no_result(): void {
		$result = $this->resolve_successful_catalog( array(), array() );

		$this->assertSame( ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::STATUS_NO_RESULT, $result->get_status() );
		$this->assertTrue( $result->is_available() );
		$this->assertFalse( $result->has_candidate() );
		$this->assertFalse( $result->is_blocked() );
	}

	/**
	 * Maps one matched latest result to a candidate with preserved identity.
	 *
	 * @return void
	 */
	public function test_resolver_maps_single_matched_to_candidate(): void {
		$association = $this->create_association( '2026-08-30', 'Matched', true );
		$result      = $this->resolve_successful_catalog( array( $association ), array() );

		$this->assertSame( ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::STATUS_CANDIDATE, $result->get_status() );
		$this->assertTrue( $result->has_candidate() );
		$this->assertSame( $association, $result->get_association() );
		$this->assertSame( $association->get_document(), $result->get_document() );
	}

	/**
	 * Maps one unmatched latest result to a blocked result without exposing it.
	 *
	 * @return void
	 */
	public function test_resolver_maps_single_unmatched_to_blocked(): void {
		$association = $this->create_association( '2026-08-30', 'Unmatched', false );
		$result      = $this->resolve_successful_catalog( array( $association ), array() );

		$this->assertSame( ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::STATUS_BLOCKED_UNMATCHED, $result->get_status() );
		$this->assertFalse( $result->has_candidate() );
		$this->assertTrue( $result->is_blocked() );
		$this->assertNull( $result->get_association() );
		$this->assertNull( $result->get_document() );
	}

	/**
	 * Keeps a latest unmatched result blocked without falling back to an older match.
	 *
	 * @return void
	 */
	public function test_resolver_does_not_fall_back_from_latest_unmatched(): void {
		$older_matched    = $this->create_association( '2026-08-22', 'Older matched', true );
		$latest_unmatched = $this->create_association( '2026-08-30', 'Latest unmatched', false );

		$result = $this->resolve_successful_catalog(
			array( $older_matched, $latest_unmatched ),
			array()
		);

		$this->assertSame( ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::STATUS_BLOCKED_UNMATCHED, $result->get_status() );
		$this->assertFalse( $result->has_candidate() );
		$this->assertNull( $result->get_association() );
		$this->assertNull( $result->get_document() );
	}

	/**
	 * Keeps a latest matched result as a candidate despite an older unmatched result.
	 *
	 * @return void
	 */
	public function test_resolver_accepts_latest_matched_with_older_unmatched(): void {
		$older_unmatched = $this->create_association( '2026-08-22', 'Older unmatched', false );
		$latest_matched  = $this->create_association( '2026-08-30', 'Latest matched', true );

		$result = $this->resolve_successful_catalog(
			array( $latest_matched, $older_unmatched ),
			array()
		);

		$this->assertSame( ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::STATUS_CANDIDATE, $result->get_status() );
		$this->assertSame( $latest_matched, $result->get_association() );
		$this->assertSame( $latest_matched->get_document(), $result->get_document() );
	}

	/**
	 * Produces the same result independently of association input order.
	 *
	 * @return void
	 */
	public function test_resolver_is_independent_of_association_order(): void {
		$older_matched    = $this->create_association( '2026-08-22', 'Older matched', true );
		$latest_unmatched = $this->create_association( '2026-08-30', 'Latest unmatched', false );
		$first_result     = $this->resolve_successful_catalog(
			array( $older_matched, $latest_unmatched ),
			array()
		);
		$second_result    = $this->resolve_successful_catalog(
			array( $latest_unmatched, $older_matched ),
			array()
		);

		$this->assertSame( $first_result->get_status(), $second_result->get_status() );
		$this->assertSame( ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::STATUS_BLOCKED_UNMATCHED, $first_result->get_status() );
	}

	/**
	 * Does not block a latest matched candidate because of an unrelated issue.
	 *
	 * @return void
	 */
	public function test_resolver_does_not_analyze_unrelated_issues(): void {
		$association = $this->create_association( '2026-08-30', 'Matched', true );
		$issue       = new ZFDZ_Lab_Result_Scan_Issue( 'ignored.txt', 'unsupported_extension' );

		$result = $this->resolve_successful_catalog( array( $association ), array( $issue ) );

		$this->assertSame( ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::STATUS_CANDIDATE, $result->get_status() );
		$this->assertSame( $association, $result->get_association() );
	}

	/**
	 * Keeps unavailable and no-result statuses distinct, with distinct failure reasons.
	 *
	 * @return void
	 */
	public function test_unavailable_and_no_result_are_distinct_states(): void {
		$menu_unavailable = $this->create_resolver()->resolve(
			ZFDZ_WordPress_Lab_Result_Catalog_Service_Result::from_menu_catalog_failure(
				ZFDZ_Menu_Catalog_Result::from_directory_error( 'directory_not_found' )
			)
		);
		$lab_unavailable  = $this->create_resolver()->resolve(
			ZFDZ_WordPress_Lab_Result_Catalog_Service_Result::from_lab_catalog_failure(
				$this->create_successful_menu_catalog(),
				ZFDZ_Lab_Result_Catalog_Result::from_directory_error( 'directory_not_found' )
			)
		);
		$no_result        = $this->resolve_successful_catalog( array(), array() );

		$this->assertNotSame( $menu_unavailable->get_status(), $no_result->get_status() );
		$this->assertNotSame( $lab_unavailable->get_status(), $no_result->get_status() );
		$this->assertNotSame(
			$menu_unavailable->get_unavailable_reason(),
			$lab_unavailable->get_unavailable_reason()
		);
	}

	/**
	 * Creates the real standalone resolver dependencies.
	 *
	 * @return ZFDZ_WordPress_Lab_Result_Public_Presentation_Resolver
	 */
	private function create_resolver(): ZFDZ_WordPress_Lab_Result_Public_Presentation_Resolver {
		return new ZFDZ_WordPress_Lab_Result_Public_Presentation_Resolver(
			new ZFDZ_Lab_Result_Latest_Selector(),
			new ZFDZ_Lab_Result_Public_Presentation_Policy()
		);
	}

	/**
	 * Resolves a successful coordinated result containing supplied associations and issues.
	 *
	 * @param array $associations Laboratory result associations.
	 * @param array $issues       Laboratory result entry-level issues.
	 * @return ZFDZ_WordPress_Lab_Result_Public_Presentation_Result
	 */
	private function resolve_successful_catalog(
		array $associations,
		array $issues
	): ZFDZ_WordPress_Lab_Result_Public_Presentation_Result {
		$coordinated_result = ZFDZ_WordPress_Lab_Result_Catalog_Service_Result::from_success(
			$this->create_successful_menu_catalog(),
			ZFDZ_Lab_Result_Catalog_Result::from_catalog( $associations, $issues )
		);

		return $this->create_resolver()->resolve( $coordinated_result );
	}

	/**
	 * Creates a successful empty menu catalog.
	 *
	 * @return ZFDZ_Menu_Catalog_Result
	 */
	private function create_successful_menu_catalog(): ZFDZ_Menu_Catalog_Result {
		return ZFDZ_Menu_Catalog_Result::from_catalog( array(), array(), array() );
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
