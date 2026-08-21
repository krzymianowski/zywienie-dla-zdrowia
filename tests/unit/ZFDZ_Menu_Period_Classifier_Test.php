<?php
/**
 * Tests the standalone menu period classifier.
 *
 * @package ZywienieDlaZdrowia
 */

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests deterministic period classification without WordPress or a clock.
 */
final class ZFDZ_Menu_Period_Classifier_Test extends TestCase {

	/**
	 * Classifies an empty list into three empty collections.
	 *
	 * @return void
	 */
	public function test_classifies_empty_group_list(): void {
		$result = ( new ZFDZ_Menu_Period_Classifier() )->classify( array(), '2026-09-01' );

		$this->assertSame( array(), $result->get_current_groups() );
		$this->assertSame( array(), $result->get_upcoming_groups() );
		$this->assertSame( array(), $result->get_archived_groups() );
	}

	/**
	 * Classifies a period ending before today as archived.
	 *
	 * @return void
	 */
	public function test_classifies_period_before_today_as_archived(): void {
		$group  = $this->create_group( '2026-08-01', '2026-08-31' );
		$result = ( new ZFDZ_Menu_Period_Classifier() )->classify( array( $group ), '2026-09-01' );

		$this->assertSame( array(), $result->get_current_groups() );
		$this->assertSame( array(), $result->get_upcoming_groups() );
		$this->assertSame( array( $group ), $result->get_archived_groups() );
	}

	/**
	 * Classifies a period starting after today as upcoming.
	 *
	 * @return void
	 */
	public function test_classifies_period_after_today_as_upcoming(): void {
		$group  = $this->create_group( '2026-09-02', '2026-09-10' );
		$result = ( new ZFDZ_Menu_Period_Classifier() )->classify( array( $group ), '2026-09-01' );

		$this->assertSame( array(), $result->get_current_groups() );
		$this->assertSame( array( $group ), $result->get_upcoming_groups() );
		$this->assertSame( array(), $result->get_archived_groups() );
	}

	/**
	 * Classifies a period containing today as current.
	 *
	 * @return void
	 */
	public function test_classifies_today_inside_period_as_current(): void {
		$group  = $this->create_group( '2026-08-25', '2026-09-05' );
		$result = ( new ZFDZ_Menu_Period_Classifier() )->classify( array( $group ), '2026-09-01' );

		$this->assertSame( array( $group ), $result->get_current_groups() );
		$this->assertSame( array(), $result->get_upcoming_groups() );
		$this->assertSame( array(), $result->get_archived_groups() );
	}

	/**
	 * Treats the inclusive start boundary as current.
	 *
	 * @return void
	 */
	public function test_classifies_start_date_as_current(): void {
		$group  = $this->create_group( '2026-09-01', '2026-09-10' );
		$result = ( new ZFDZ_Menu_Period_Classifier() )->classify( array( $group ), '2026-09-01' );

		$this->assertSame( array( $group ), $result->get_current_groups() );
	}

	/**
	 * Treats the inclusive end boundary as current.
	 *
	 * @return void
	 */
	public function test_classifies_end_date_as_current(): void {
		$group  = $this->create_group( '2026-09-01', '2026-09-10' );
		$result = ( new ZFDZ_Menu_Period_Classifier() )->classify( array( $group ), '2026-09-10' );

		$this->assertSame( array( $group ), $result->get_current_groups() );
	}

	/**
	 * Classifies a single-day period as current on that day.
	 *
	 * @return void
	 */
	public function test_classifies_single_day_period_as_current_on_same_day(): void {
		$group  = $this->create_group( '2026-09-01', '2026-09-01' );
		$result = ( new ZFDZ_Menu_Period_Classifier() )->classify( array( $group ), '2026-09-01' );

		$this->assertSame( array( $group ), $result->get_current_groups() );
	}

	/**
	 * Classifies a single-day period as archived on the following day.
	 *
	 * @return void
	 */
	public function test_classifies_single_day_period_as_archived_on_next_day(): void {
		$group  = $this->create_group( '2026-09-01', '2026-09-01' );
		$result = ( new ZFDZ_Menu_Period_Classifier() )->classify( array( $group ), '2026-09-02' );

		$this->assertSame( array( $group ), $result->get_archived_groups() );
	}

	/**
	 * Classifies a single-day period as upcoming on the preceding day.
	 *
	 * @return void
	 */
	public function test_classifies_single_day_period_as_upcoming_on_previous_day(): void {
		$group  = $this->create_group( '2026-09-02', '2026-09-02' );
		$result = ( new ZFDZ_Menu_Period_Classifier() )->classify( array( $group ), '2026-09-01' );

		$this->assertSame( array( $group ), $result->get_upcoming_groups() );
	}

	/**
	 * Divides a mixed list into three complete and exclusive categories.
	 *
	 * @return void
	 */
	public function test_classifies_current_upcoming_and_archived_groups(): void {
		$current  = $this->create_group( '2026-08-25', '2026-09-05' );
		$upcoming = $this->create_group( '2026-09-02', '2026-09-10' );
		$archived = $this->create_group( '2026-08-01', '2026-08-31' );
		$result   = ( new ZFDZ_Menu_Period_Classifier() )->classify(
			array( $current, $upcoming, $archived ),
			'2026-09-01'
		);

		$this->assertSame( array( $current ), $result->get_current_groups() );
		$this->assertSame( array( $upcoming ), $result->get_upcoming_groups() );
		$this->assertSame( array( $archived ), $result->get_archived_groups() );
	}

	/**
	 * Allows multiple overlapping groups to be current together.
	 *
	 * @return void
	 */
	public function test_classifies_multiple_overlapping_groups_as_current(): void {
		$first  = $this->create_group( '2026-09-01', '2026-09-10' );
		$second = $this->create_group( '2026-09-05', '2026-09-12' );
		$result = ( new ZFDZ_Menu_Period_Classifier() )->classify( array( $first, $second ), '2026-09-06' );

		$this->assertSame( array( $first, $second ), $result->get_current_groups() );
	}

	/**
	 * Preserves relative input order inside every category.
	 *
	 * @return void
	 */
	public function test_preserves_input_order_within_categories(): void {
		$current_first   = $this->create_group( '2026-08-20', '2026-09-03' );
		$upcoming_first  = $this->create_group( '2026-09-10', '2026-09-20' );
		$current_second  = $this->create_group( '2026-08-25', '2026-09-02' );
		$archived_first  = $this->create_group( '2026-08-01', '2026-08-05' );
		$upcoming_second = $this->create_group( '2026-10-01', '2026-10-10' );
		$archived_second = $this->create_group( '2026-08-10', '2026-08-15' );
		$result          = ( new ZFDZ_Menu_Period_Classifier() )->classify(
			array(
				$current_first,
				$upcoming_first,
				$current_second,
				$archived_first,
				$upcoming_second,
				$archived_second,
			),
			'2026-09-01'
		);

		$this->assertSame( array( $current_first, $current_second ), $result->get_current_groups() );
		$this->assertSame( array( $upcoming_first, $upcoming_second ), $result->get_upcoming_groups() );
		$this->assertSame( array( $archived_first, $archived_second ), $result->get_archived_groups() );
	}

	/**
	 * Accepts a real leap-day reference date.
	 *
	 * @return void
	 */
	public function test_accepts_valid_leap_day_reference_date(): void {
		$group  = $this->create_group( '2024-02-29', '2024-02-29' );
		$result = ( new ZFDZ_Menu_Period_Classifier() )->classify( array( $group ), '2024-02-29' );

		$this->assertSame( array( $group ), $result->get_current_groups() );
	}

	/**
	 * Rejects an invalid reference date as a programming contract error.
	 *
	 * @param string $today Invalid reference date.
	 * @return void
	 */
	#[DataProvider( 'provide_invalid_reference_dates' )]
	public function test_rejects_invalid_reference_date( string $today ): void {
		$this->expectException( InvalidArgumentException::class );

		( new ZFDZ_Menu_Period_Classifier() )->classify( array(), $today );
	}

	/**
	 * Provides malformed and non-calendar reference dates.
	 *
	 * @return array<string, array{string}>
	 */
	public static function provide_invalid_reference_dates(): array {
		return array(
			'empty'               => array( '' ),
			'slash format'        => array( '2026/09/01' ),
			'nonexistent day'     => array( '2026-02-30' ),
			'invalid month'       => array( '2026-13-01' ),
			'leading whitespace'  => array( ' 2026-09-01' ),
			'trailing whitespace' => array( '2026-09-01 ' ),
			'time suffix'         => array( '2026-09-01T00:00:00' ),
		);
	}

	/**
	 * Creates a validated period-group fixture.
	 *
	 * @param string $start_date Period start date.
	 * @param string $end_date   Period end date.
	 * @return ZFDZ_Menu_Period_Group
	 */
	private function create_group( string $start_date, string $end_date ): ZFDZ_Menu_Period_Group {
		return new ZFDZ_Menu_Period_Group( $start_date, $end_date, array() );
	}
}
