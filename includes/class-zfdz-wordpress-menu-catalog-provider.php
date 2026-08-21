<?php
/**
 * WordPress menu catalog provider.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Connects WordPress menu storage with the standalone catalog pipeline.
 */
final class ZFDZ_WordPress_Menu_Catalog_Provider {

	/**
	 * WordPress-specific menu storage.
	 *
	 * @var ZFDZ_WordPress_Menu_Storage
	 */
	private readonly ZFDZ_WordPress_Menu_Storage $storage;

	/**
	 * Standalone validated catalog builder.
	 *
	 * @var ZFDZ_Menu_Catalog_Builder
	 */
	private readonly ZFDZ_Menu_Catalog_Builder $catalog_builder;

	/**
	 * Creates a WordPress menu catalog provider.
	 *
	 * @param ZFDZ_WordPress_Menu_Storage $storage         WordPress menu storage.
	 * @param ZFDZ_Menu_Catalog_Builder   $catalog_builder Standalone catalog builder.
	 */
	public function __construct(
		ZFDZ_WordPress_Menu_Storage $storage,
		ZFDZ_Menu_Catalog_Builder $catalog_builder
	) {
		$this->storage         = $storage;
		$this->catalog_builder = $catalog_builder;
	}

	/**
	 * Creates the default WordPress-to-standalone pipeline.
	 *
	 * @return self
	 */
	public static function create_default(): self {
		return new self(
			new ZFDZ_WordPress_Menu_Storage(),
			new ZFDZ_Menu_Catalog_Builder(
				new ZFDZ_Menu_Directory_Scanner( new ZFDZ_Menu_Filename_Parser() ),
				new ZFDZ_PDF_File_Validator()
			)
		);
	}

	/**
	 * Returns a validated menu catalog without creating storage directories.
	 *
	 * @return ZFDZ_Menu_Catalog_Result
	 */
	public function get_catalog(): ZFDZ_Menu_Catalog_Result {
		$menu_directory = $this->storage->get_menu_directory_path();

		if ( is_wp_error( $menu_directory ) ) {
			$error_code = $menu_directory->get_error_code();

			if ( ! is_string( $error_code ) || '' === $error_code ) {
				$error_code = ZFDZ_WordPress_Menu_Storage::ERROR_UPLOADS_UNAVAILABLE;
			}

			return ZFDZ_Menu_Catalog_Result::from_directory_error( $error_code );
		}

		return $this->catalog_builder->build( $menu_directory );
	}
}
