<?php
/**
 * WordPress menu catalog cache.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Stores successful menu catalog results with the WordPress Transients API.
 */
final class ZFDZ_WordPress_Menu_Catalog_Cache {

	/**
	 * Versioned transient key for the catalog model format.
	 */
	private const TRANSIENT_KEY = 'zfdz_menu_catalog_v1';

	/**
	 * Default cache lifetime in seconds.
	 */
	private const DEFAULT_TTL_SECONDS = 5 * 60;

	/**
	 * Returns the cached catalog or null on a miss or invalid cached value.
	 *
	 * @return ZFDZ_Menu_Catalog_Result|null
	 */
	public function get(): ?ZFDZ_Menu_Catalog_Result {
		$cached_catalog = get_transient( self::TRANSIENT_KEY );

		if ( false === $cached_catalog ) {
			return null;
		}

		if ( $cached_catalog instanceof ZFDZ_Menu_Catalog_Result ) {
			return $cached_catalog;
		}

		$this->delete();

		return null;
	}

	/**
	 * Stores a successful catalog as a best-effort optimization.
	 *
	 * @param ZFDZ_Menu_Catalog_Result $catalog Catalog to cache.
	 * @return void
	 */
	public function put( ZFDZ_Menu_Catalog_Result $catalog ): void {
		if ( ! $catalog->is_successful() ) {
			return;
		}

		set_transient( self::TRANSIENT_KEY, $catalog, self::DEFAULT_TTL_SECONDS );
	}

	/**
	 * Deletes the cached catalog as a best-effort operation.
	 *
	 * @return void
	 */
	public function delete(): void {
		delete_transient( self::TRANSIENT_KEY );
	}
}
