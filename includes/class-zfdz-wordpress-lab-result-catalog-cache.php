<?php
/**
 * WordPress laboratory result catalog cache.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Stores successful laboratory result catalogs for a specific menu-period fingerprint.
 */
final class ZFDZ_WordPress_Lab_Result_Catalog_Cache {

	/**
	 * Versioned transient key for the laboratory result catalog cache payload.
	 */
	private const TRANSIENT_KEY = 'zfdz_lab_result_catalog_v1';

	/**
	 * Default cache lifetime in seconds.
	 */
	private const DEFAULT_TTL_SECONDS = 5 * 60;

	/**
	 * Returns a successful cached catalog matching the supplied menu fingerprint.
	 *
	 * @param string $menu_fingerprint Deterministic menu-period fingerprint.
	 * @throws InvalidArgumentException When the fingerprint is empty.
	 * @return ZFDZ_Lab_Result_Catalog_Result|null
	 */
	public function get( string $menu_fingerprint ): ?ZFDZ_Lab_Result_Catalog_Result {
		$this->validate_menu_fingerprint( $menu_fingerprint );

		$cached_payload = get_transient( self::TRANSIENT_KEY );

		if ( false === $cached_payload ) {
			return null;
		}

		if (
			! is_array( $cached_payload )
			|| 2 !== count( $cached_payload )
			|| ! array_key_exists( 'menu_fingerprint', $cached_payload )
			|| ! array_key_exists( 'catalog', $cached_payload )
			|| ! is_string( $cached_payload['menu_fingerprint'] )
			|| '' === trim( $cached_payload['menu_fingerprint'] )
			|| ! $cached_payload['catalog'] instanceof ZFDZ_Lab_Result_Catalog_Result
			|| ! $cached_payload['catalog']->is_successful()
			|| $cached_payload['menu_fingerprint'] !== $menu_fingerprint
		) {
			$this->clear();

			return null;
		}

		return $cached_payload['catalog'];
	}

	/**
	 * Stores a successful catalog for the supplied menu fingerprint.
	 *
	 * @param string                         $menu_fingerprint Deterministic menu-period fingerprint.
	 * @param ZFDZ_Lab_Result_Catalog_Result $catalog          Successful laboratory result catalog.
	 * @throws InvalidArgumentException When the fingerprint is empty or the catalog failed.
	 * @return bool
	 */
	public function set(
		string $menu_fingerprint,
		ZFDZ_Lab_Result_Catalog_Result $catalog
	): bool {
		$this->validate_menu_fingerprint( $menu_fingerprint );

		if ( ! $catalog->is_successful() ) {
			throw new InvalidArgumentException( 'Only successful laboratory result catalogs can be cached.' );
		}

		return set_transient(
			self::TRANSIENT_KEY,
			array(
				'menu_fingerprint' => $menu_fingerprint,
				'catalog'          => $catalog,
			),
			self::DEFAULT_TTL_SECONDS
		);
	}

	/**
	 * Deletes only the laboratory result catalog transient.
	 *
	 * @return bool
	 */
	public function clear(): bool {
		return delete_transient( self::TRANSIENT_KEY );
	}

	/**
	 * Validates the menu-period fingerprint contract.
	 *
	 * @param string $menu_fingerprint Deterministic menu-period fingerprint.
	 * @throws InvalidArgumentException When the fingerprint is empty.
	 * @return void
	 */
	private function validate_menu_fingerprint( string $menu_fingerprint ): void {
		if ( '' === trim( $menu_fingerprint ) ) {
			throw new InvalidArgumentException( 'The menu-period fingerprint cannot be empty.' );
		}
	}
}
