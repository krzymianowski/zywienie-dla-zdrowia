<?php
/**
 * WordPress menu catalog service.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Provides cached access to the WordPress menu catalog provider.
 */
final class ZFDZ_WordPress_Menu_Catalog_Service {

	/**
	 * Fresh catalog provider.
	 *
	 * @var ZFDZ_WordPress_Menu_Catalog_Provider
	 */
	private readonly ZFDZ_WordPress_Menu_Catalog_Provider $provider;

	/**
	 * WordPress transient cache.
	 *
	 * @var ZFDZ_WordPress_Menu_Catalog_Cache
	 */
	private readonly ZFDZ_WordPress_Menu_Catalog_Cache $cache;

	/**
	 * Creates a cached menu catalog service.
	 *
	 * @param ZFDZ_WordPress_Menu_Catalog_Provider $provider Fresh catalog provider.
	 * @param ZFDZ_WordPress_Menu_Catalog_Cache    $cache    WordPress transient cache.
	 */
	public function __construct(
		ZFDZ_WordPress_Menu_Catalog_Provider $provider,
		ZFDZ_WordPress_Menu_Catalog_Cache $cache
	) {
		$this->provider = $provider;
		$this->cache    = $cache;
	}

	/**
	 * Creates the default cached WordPress catalog pipeline.
	 *
	 * @return self
	 */
	public static function create_default(): self {
		return new self(
			ZFDZ_WordPress_Menu_Catalog_Provider::create_default(),
			new ZFDZ_WordPress_Menu_Catalog_Cache()
		);
	}

	/**
	 * Returns a cached catalog or builds a fresh one on a cache miss.
	 *
	 * @return ZFDZ_Menu_Catalog_Result
	 */
	public function get_catalog(): ZFDZ_Menu_Catalog_Result {
		$cached_catalog = $this->cache->get();

		if ( null !== $cached_catalog ) {
			return $cached_catalog;
		}

		return $this->get_fresh_catalog();
	}

	/**
	 * Deletes the old cache and returns a freshly built catalog.
	 *
	 * @return ZFDZ_Menu_Catalog_Result
	 */
	public function refresh_catalog(): ZFDZ_Menu_Catalog_Result {
		$this->cache->delete();

		return $this->get_fresh_catalog();
	}

	/**
	 * Deletes the cached catalog without scanning the filesystem.
	 *
	 * @return void
	 */
	public function clear_cache(): void {
		$this->cache->delete();
	}

	/**
	 * Builds a fresh catalog and caches it when successful.
	 *
	 * @return ZFDZ_Menu_Catalog_Result
	 */
	private function get_fresh_catalog(): ZFDZ_Menu_Catalog_Result {
		$catalog = $this->provider->get_catalog();

		if ( $catalog->is_successful() ) {
			$this->cache->put( $catalog );
		}

		return $catalog;
	}
}
