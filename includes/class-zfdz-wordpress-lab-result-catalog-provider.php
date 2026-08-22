<?php
/**
 * WordPress laboratory result catalog provider.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Connects WordPress laboratory result storage with the standalone catalog pipeline.
 */
final class ZFDZ_WordPress_Lab_Result_Catalog_Provider {

	/**
	 * WordPress-specific laboratory result storage.
	 *
	 * @var ZFDZ_WordPress_Lab_Result_Storage
	 */
	private readonly ZFDZ_WordPress_Lab_Result_Storage $storage;

	/**
	 * Standalone validated laboratory result catalog builder.
	 *
	 * @var ZFDZ_Lab_Result_Catalog_Builder
	 */
	private readonly ZFDZ_Lab_Result_Catalog_Builder $catalog_builder;

	/**
	 * Creates a WordPress laboratory result catalog provider.
	 *
	 * @param ZFDZ_WordPress_Lab_Result_Storage $storage         WordPress laboratory result storage.
	 * @param ZFDZ_Lab_Result_Catalog_Builder   $catalog_builder Standalone catalog builder.
	 */
	public function __construct(
		ZFDZ_WordPress_Lab_Result_Storage $storage,
		ZFDZ_Lab_Result_Catalog_Builder $catalog_builder
	) {
		$this->storage         = $storage;
		$this->catalog_builder = $catalog_builder;
	}

	/**
	 * Creates the default WordPress-to-standalone laboratory result pipeline.
	 *
	 * @return self
	 */
	public static function create_default(): self {
		return new self(
			new ZFDZ_WordPress_Lab_Result_Storage(),
			new ZFDZ_Lab_Result_Catalog_Builder(
				new ZFDZ_Lab_Result_Directory_Scanner( new ZFDZ_Lab_Result_Filename_Parser() ),
				new ZFDZ_PDF_File_Validator(),
				new ZFDZ_Lab_Result_Menu_Matcher()
			)
		);
	}

	/**
	 * Returns a validated laboratory result catalog for explicitly supplied menu groups.
	 *
	 * @param array $menu_groups Existing validated menu period groups.
	 * @return ZFDZ_Lab_Result_Catalog_Result
	 */
	public function get_catalog( array $menu_groups ): ZFDZ_Lab_Result_Catalog_Result {
		$lab_result_directory = $this->storage->get_lab_result_directory_path();

		if ( is_wp_error( $lab_result_directory ) ) {
			$error_code = $lab_result_directory->get_error_code();

			if ( ! is_string( $error_code ) || '' === trim( $error_code ) ) {
				$error_code = ZFDZ_WordPress_Lab_Result_Storage::ERROR_UPLOADS_UNAVAILABLE;
			}

			return ZFDZ_Lab_Result_Catalog_Result::from_directory_error( $error_code );
		}

		return $this->catalog_builder->build( $lab_result_directory, $menu_groups );
	}
}
