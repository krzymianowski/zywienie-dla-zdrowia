<?php
/**
 * Tests the coordinated laboratory result catalog service result.
 *
 * @package ZywienieDlaZdrowia
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests coordinated result status invariants without loading WordPress.
 */
final class ZFDZ_WordPress_Lab_Result_Catalog_Service_Result_Test extends TestCase {

	/**
	 * Creates a successful result containing both catalog objects.
	 *
	 * @return void
	 */
	public function test_creates_successful_result(): void {
		$menu_catalog = $this->create_successful_menu_catalog();
		$lab_catalog  = $this->create_successful_lab_catalog();

		$result = ZFDZ_WordPress_Lab_Result_Catalog_Service_Result::from_success(
			$menu_catalog,
			$lab_catalog
		);

		$this->assertTrue( $result->is_successful() );
		$this->assertSame( ZFDZ_WordPress_Lab_Result_Catalog_Service_Result::STATUS_SUCCESS, $result->get_status() );
		$this->assertSame( $menu_catalog, $result->get_menu_catalog() );
		$this->assertSame( $lab_catalog, $result->get_lab_catalog() );
	}

	/**
	 * Creates a menu-unavailable result without a laboratory result catalog.
	 *
	 * @return void
	 */
	public function test_creates_menu_catalog_unavailable_result(): void {
		$menu_catalog = $this->create_failed_menu_catalog();
		$result       = ZFDZ_WordPress_Lab_Result_Catalog_Service_Result::from_menu_catalog_failure( $menu_catalog );

		$this->assertFalse( $result->is_successful() );
		$this->assertSame( ZFDZ_WordPress_Lab_Result_Catalog_Service_Result::STATUS_MENU_CATALOG_UNAVAILABLE, $result->get_status() );
		$this->assertSame( $menu_catalog, $result->get_menu_catalog() );
		$this->assertNull( $result->get_lab_catalog() );
	}

	/**
	 * Creates a laboratory-result-unavailable result with both catalog objects.
	 *
	 * @return void
	 */
	public function test_creates_lab_catalog_unavailable_result(): void {
		$menu_catalog = $this->create_successful_menu_catalog();
		$lab_catalog  = $this->create_failed_lab_catalog();

		$result = ZFDZ_WordPress_Lab_Result_Catalog_Service_Result::from_lab_catalog_failure(
			$menu_catalog,
			$lab_catalog
		);

		$this->assertFalse( $result->is_successful() );
		$this->assertSame( ZFDZ_WordPress_Lab_Result_Catalog_Service_Result::STATUS_LAB_CATALOG_UNAVAILABLE, $result->get_status() );
		$this->assertSame( $menu_catalog, $result->get_menu_catalog() );
		$this->assertSame( $lab_catalog, $result->get_lab_catalog() );
	}

	/**
	 * Rejects success with a failed menu catalog.
	 *
	 * @return void
	 */
	public function test_success_rejects_failed_menu_catalog(): void {
		$this->expectException( InvalidArgumentException::class );

		ZFDZ_WordPress_Lab_Result_Catalog_Service_Result::from_success(
			$this->create_failed_menu_catalog(),
			$this->create_successful_lab_catalog()
		);
	}

	/**
	 * Rejects success with a failed laboratory result catalog.
	 *
	 * @return void
	 */
	public function test_success_rejects_failed_lab_catalog(): void {
		$this->expectException( InvalidArgumentException::class );

		ZFDZ_WordPress_Lab_Result_Catalog_Service_Result::from_success(
			$this->create_successful_menu_catalog(),
			$this->create_failed_lab_catalog()
		);
	}

	/**
	 * Rejects a menu failure state with a successful menu catalog.
	 *
	 * @return void
	 */
	public function test_menu_failure_rejects_successful_menu_catalog(): void {
		$this->expectException( InvalidArgumentException::class );

		ZFDZ_WordPress_Lab_Result_Catalog_Service_Result::from_menu_catalog_failure(
			$this->create_successful_menu_catalog()
		);
	}

	/**
	 * Rejects a laboratory result failure state with a failed menu catalog.
	 *
	 * @return void
	 */
	public function test_lab_failure_rejects_failed_menu_catalog(): void {
		$this->expectException( InvalidArgumentException::class );

		ZFDZ_WordPress_Lab_Result_Catalog_Service_Result::from_lab_catalog_failure(
			$this->create_failed_menu_catalog(),
			$this->create_failed_lab_catalog()
		);
	}

	/**
	 * Rejects a laboratory result failure state with a successful lab catalog.
	 *
	 * @return void
	 */
	public function test_lab_failure_rejects_successful_lab_catalog(): void {
		$this->expectException( InvalidArgumentException::class );

		ZFDZ_WordPress_Lab_Result_Catalog_Service_Result::from_lab_catalog_failure(
			$this->create_successful_menu_catalog(),
			$this->create_successful_lab_catalog()
		);
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
	 * Creates a failed menu catalog.
	 *
	 * @return ZFDZ_Menu_Catalog_Result
	 */
	private function create_failed_menu_catalog(): ZFDZ_Menu_Catalog_Result {
		return ZFDZ_Menu_Catalog_Result::from_directory_error( 'directory_not_found' );
	}

	/**
	 * Creates a successful empty laboratory result catalog.
	 *
	 * @return ZFDZ_Lab_Result_Catalog_Result
	 */
	private function create_successful_lab_catalog(): ZFDZ_Lab_Result_Catalog_Result {
		return ZFDZ_Lab_Result_Catalog_Result::from_catalog( array(), array() );
	}

	/**
	 * Creates a failed laboratory result catalog.
	 *
	 * @return ZFDZ_Lab_Result_Catalog_Result
	 */
	private function create_failed_lab_catalog(): ZFDZ_Lab_Result_Catalog_Result {
		return ZFDZ_Lab_Result_Catalog_Result::from_directory_error( 'directory_not_found' );
	}
}
