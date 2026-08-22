<?php
/**
 * Coordinated WordPress laboratory result catalog service result.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Represents coordinated menu and laboratory result catalog availability.
 */
final class ZFDZ_WordPress_Lab_Result_Catalog_Service_Result {

	public const STATUS_SUCCESS                  = 'success';
	public const STATUS_MENU_CATALOG_UNAVAILABLE = 'menu_catalog_unavailable';
	public const STATUS_LAB_CATALOG_UNAVAILABLE  = 'lab_catalog_unavailable';

	/**
	 * Coordinated result status.
	 *
	 * @var string
	 */
	private readonly string $status;

	/**
	 * Menu catalog used for coordination.
	 *
	 * @var ZFDZ_Menu_Catalog_Result
	 */
	private readonly ZFDZ_Menu_Catalog_Result $menu_catalog;

	/**
	 * Laboratory result catalog when the menu catalog is available.
	 *
	 * @var ZFDZ_Lab_Result_Catalog_Result|null
	 */
	private readonly ?ZFDZ_Lab_Result_Catalog_Result $lab_catalog;

	/**
	 * Creates a coordinated result while enforcing status invariants.
	 *
	 * @param string                              $status       Coordinated result status.
	 * @param ZFDZ_Menu_Catalog_Result            $menu_catalog Menu catalog used for coordination.
	 * @param ZFDZ_Lab_Result_Catalog_Result|null $lab_catalog  Laboratory result catalog when available.
	 * @throws InvalidArgumentException When the supplied state contradicts the status.
	 */
	private function __construct(
		string $status,
		ZFDZ_Menu_Catalog_Result $menu_catalog,
		?ZFDZ_Lab_Result_Catalog_Result $lab_catalog
	) {
		if (
			self::STATUS_SUCCESS === $status
			&& (
				! $menu_catalog->is_successful()
				|| null === $lab_catalog
				|| ! $lab_catalog->is_successful()
			)
		) {
			throw new InvalidArgumentException( 'A successful coordinated result requires successful menu and laboratory result catalogs.' );
		}

		if (
			self::STATUS_MENU_CATALOG_UNAVAILABLE === $status
			&& ( $menu_catalog->is_successful() || null !== $lab_catalog )
		) {
			throw new InvalidArgumentException( 'A menu catalog failure requires a failed menu catalog and no laboratory result catalog.' );
		}

		if (
			self::STATUS_LAB_CATALOG_UNAVAILABLE === $status
			&& (
				! $menu_catalog->is_successful()
				|| null === $lab_catalog
				|| $lab_catalog->is_successful()
			)
		) {
			throw new InvalidArgumentException( 'A laboratory result catalog failure requires a successful menu catalog and a failed laboratory result catalog.' );
		}

		if (
			self::STATUS_SUCCESS !== $status
			&& self::STATUS_MENU_CATALOG_UNAVAILABLE !== $status
			&& self::STATUS_LAB_CATALOG_UNAVAILABLE !== $status
		) {
			throw new InvalidArgumentException( 'Unknown coordinated catalog status.' );
		}

		$this->status       = $status;
		$this->menu_catalog = $menu_catalog;
		$this->lab_catalog  = $lab_catalog;
	}

	/**
	 * Creates a successful coordinated result.
	 *
	 * @param ZFDZ_Menu_Catalog_Result       $menu_catalog Successful menu catalog.
	 * @param ZFDZ_Lab_Result_Catalog_Result $lab_catalog  Successful laboratory result catalog.
	 * @return self
	 */
	public static function from_success(
		ZFDZ_Menu_Catalog_Result $menu_catalog,
		ZFDZ_Lab_Result_Catalog_Result $lab_catalog
	): self {
		return new self( self::STATUS_SUCCESS, $menu_catalog, $lab_catalog );
	}

	/**
	 * Creates a result for an unavailable menu catalog.
	 *
	 * @param ZFDZ_Menu_Catalog_Result $menu_catalog Failed menu catalog.
	 * @return self
	 */
	public static function from_menu_catalog_failure( ZFDZ_Menu_Catalog_Result $menu_catalog ): self {
		return new self( self::STATUS_MENU_CATALOG_UNAVAILABLE, $menu_catalog, null );
	}

	/**
	 * Creates a result for an unavailable laboratory result catalog.
	 *
	 * @param ZFDZ_Menu_Catalog_Result       $menu_catalog Successful menu catalog.
	 * @param ZFDZ_Lab_Result_Catalog_Result $lab_catalog  Failed laboratory result catalog.
	 * @return self
	 */
	public static function from_lab_catalog_failure(
		ZFDZ_Menu_Catalog_Result $menu_catalog,
		ZFDZ_Lab_Result_Catalog_Result $lab_catalog
	): self {
		return new self( self::STATUS_LAB_CATALOG_UNAVAILABLE, $menu_catalog, $lab_catalog );
	}

	/**
	 * Returns whether both coordinated catalogs are successful.
	 *
	 * @return bool
	 */
	public function is_successful(): bool {
		return self::STATUS_SUCCESS === $this->status;
	}

	/**
	 * Returns the coordinated status.
	 *
	 * @return string
	 */
	public function get_status(): string {
		return $this->status;
	}

	/**
	 * Returns the menu catalog used for coordination.
	 *
	 * @return ZFDZ_Menu_Catalog_Result
	 */
	public function get_menu_catalog(): ZFDZ_Menu_Catalog_Result {
		return $this->menu_catalog;
	}

	/**
	 * Returns the laboratory result catalog when the menu catalog was available.
	 *
	 * @return ZFDZ_Lab_Result_Catalog_Result|null
	 */
	public function get_lab_catalog(): ?ZFDZ_Lab_Result_Catalog_Result {
		return $this->lab_catalog;
	}
}
