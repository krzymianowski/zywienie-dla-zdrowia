<?php
/**
 * Coordinated WordPress laboratory result catalog service.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Coordinates menu availability, laboratory result matching, and laboratory result cache access.
 */
final class ZFDZ_WordPress_Lab_Result_Catalog_Service {

	/**
	 * Menu contract fingerprint prefix.
	 */
	private const MENU_FINGERPRINT_PREFIX = 'zfdz-menu-periods-v1';

	/**
	 * Cached menu catalog service.
	 *
	 * @var ZFDZ_WordPress_Menu_Catalog_Service
	 */
	private readonly ZFDZ_WordPress_Menu_Catalog_Service $menu_catalog_service;

	/**
	 * Fresh laboratory result catalog provider.
	 *
	 * @var ZFDZ_WordPress_Lab_Result_Catalog_Provider
	 */
	private readonly ZFDZ_WordPress_Lab_Result_Catalog_Provider $lab_catalog_provider;

	/**
	 * Fingerprint-aware laboratory result catalog cache.
	 *
	 * @var ZFDZ_WordPress_Lab_Result_Catalog_Cache
	 */
	private readonly ZFDZ_WordPress_Lab_Result_Catalog_Cache $lab_catalog_cache;

	/**
	 * Creates a coordinated laboratory result catalog service.
	 *
	 * @param ZFDZ_WordPress_Menu_Catalog_Service        $menu_catalog_service Menu catalog owner.
	 * @param ZFDZ_WordPress_Lab_Result_Catalog_Provider $lab_catalog_provider Fresh laboratory result provider.
	 * @param ZFDZ_WordPress_Lab_Result_Catalog_Cache    $lab_catalog_cache    Laboratory result transient cache.
	 */
	public function __construct(
		ZFDZ_WordPress_Menu_Catalog_Service $menu_catalog_service,
		ZFDZ_WordPress_Lab_Result_Catalog_Provider $lab_catalog_provider,
		ZFDZ_WordPress_Lab_Result_Catalog_Cache $lab_catalog_cache
	) {
		$this->menu_catalog_service = $menu_catalog_service;
		$this->lab_catalog_provider = $lab_catalog_provider;
		$this->lab_catalog_cache    = $lab_catalog_cache;
	}

	/**
	 * Creates the default coordinated WordPress pipeline.
	 *
	 * @return self
	 */
	public static function create_default(): self {
		return new self(
			ZFDZ_WordPress_Menu_Catalog_Service::create_default(),
			ZFDZ_WordPress_Lab_Result_Catalog_Provider::create_default(),
			new ZFDZ_WordPress_Lab_Result_Catalog_Cache()
		);
	}

	/**
	 * Returns a coordinated result using compatible cached catalogs when available.
	 *
	 * @return ZFDZ_WordPress_Lab_Result_Catalog_Service_Result
	 */
	public function get_result(): ZFDZ_WordPress_Lab_Result_Catalog_Service_Result {
		$menu_catalog = $this->menu_catalog_service->get_catalog();

		if ( ! $menu_catalog->is_successful() ) {
			return ZFDZ_WordPress_Lab_Result_Catalog_Service_Result::from_menu_catalog_failure( $menu_catalog );
		}

		$menu_groups      = $menu_catalog->get_groups();
		$menu_fingerprint = $this->create_menu_fingerprint( $menu_groups );
		$lab_catalog      = $this->lab_catalog_cache->get( $menu_fingerprint );

		if ( null !== $lab_catalog ) {
			return ZFDZ_WordPress_Lab_Result_Catalog_Service_Result::from_success(
				$menu_catalog,
				$lab_catalog
			);
		}

		return $this->get_fresh_result( $menu_catalog, $menu_groups, $menu_fingerprint );
	}

	/**
	 * Clears the laboratory result cache and freshly rebuilds both coordinated catalogs.
	 *
	 * @return ZFDZ_WordPress_Lab_Result_Catalog_Service_Result
	 */
	public function refresh_result(): ZFDZ_WordPress_Lab_Result_Catalog_Service_Result {
		$this->lab_catalog_cache->clear();

		$menu_catalog = $this->menu_catalog_service->refresh_catalog();

		if ( ! $menu_catalog->is_successful() ) {
			return ZFDZ_WordPress_Lab_Result_Catalog_Service_Result::from_menu_catalog_failure( $menu_catalog );
		}

		$menu_groups      = $menu_catalog->get_groups();
		$menu_fingerprint = $this->create_menu_fingerprint( $menu_groups );

		return $this->get_fresh_result( $menu_catalog, $menu_groups, $menu_fingerprint );
	}

	/**
	 * Clears only the laboratory result catalog cache, leaving the menu cache unchanged.
	 *
	 * @return bool
	 */
	public function clear_cache(): bool {
		return $this->lab_catalog_cache->clear();
	}

	/**
	 * Builds a fresh laboratory result catalog and caches it only when successful.
	 *
	 * @param ZFDZ_Menu_Catalog_Result $menu_catalog     Successful menu catalog.
	 * @param array                    $menu_groups      Validated menu period groups.
	 * @param string                   $menu_fingerprint Deterministic menu-period fingerprint.
	 * @return ZFDZ_WordPress_Lab_Result_Catalog_Service_Result
	 */
	private function get_fresh_result(
		ZFDZ_Menu_Catalog_Result $menu_catalog,
		array $menu_groups,
		string $menu_fingerprint
	): ZFDZ_WordPress_Lab_Result_Catalog_Service_Result {
		$lab_catalog = $this->lab_catalog_provider->get_catalog( $menu_groups );

		if ( ! $lab_catalog->is_successful() ) {
			return ZFDZ_WordPress_Lab_Result_Catalog_Service_Result::from_lab_catalog_failure(
				$menu_catalog,
				$lab_catalog
			);
		}

		$this->lab_catalog_cache->set( $menu_fingerprint, $lab_catalog );

		return ZFDZ_WordPress_Lab_Result_Catalog_Service_Result::from_success(
			$menu_catalog,
			$lab_catalog
		);
	}

	/**
	 * Creates an order-independent fingerprint from menu period start and end dates only.
	 *
	 * @param array $menu_groups Validated menu period groups.
	 * @throws InvalidArgumentException When a group has an unexpected type.
	 * @return string
	 */
	private function create_menu_fingerprint( array $menu_groups ): string {
		$period_pairs = array();

		foreach ( $menu_groups as $menu_group ) {
			if ( ! $menu_group instanceof ZFDZ_Menu_Period_Group ) {
				throw new InvalidArgumentException( 'Menu groups must contain only menu period groups.' );
			}

			$period_pairs[] = $menu_group->get_start_date() . "\0" . $menu_group->get_end_date();
		}

		usort( $period_pairs, 'strcmp' );

		return hash(
			'sha256',
			self::MENU_FINGERPRINT_PREFIX . "\0" . implode( "\n", $period_pairs )
		);
	}
}
