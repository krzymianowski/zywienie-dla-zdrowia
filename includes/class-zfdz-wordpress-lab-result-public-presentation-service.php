<?php
/**
 * WordPress laboratory result public presentation service.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Connects the coordinated catalog service to the public presentation resolver.
 */
final class ZFDZ_WordPress_Lab_Result_Public_Presentation_Service {

	/**
	 * Coordinated WordPress laboratory result catalog service.
	 *
	 * @var ZFDZ_WordPress_Lab_Result_Catalog_Service
	 */
	private readonly ZFDZ_WordPress_Lab_Result_Catalog_Service $catalog_service;

	/**
	 * Public presentation resolver.
	 *
	 * @var ZFDZ_WordPress_Lab_Result_Public_Presentation_Resolver
	 */
	private readonly ZFDZ_WordPress_Lab_Result_Public_Presentation_Resolver $resolver;

	/**
	 * Creates a WordPress public presentation service.
	 *
	 * @param ZFDZ_WordPress_Lab_Result_Catalog_Service              $catalog_service Coordinated catalog service.
	 * @param ZFDZ_WordPress_Lab_Result_Public_Presentation_Resolver $resolver        Public presentation resolver.
	 */
	public function __construct(
		ZFDZ_WordPress_Lab_Result_Catalog_Service $catalog_service,
		ZFDZ_WordPress_Lab_Result_Public_Presentation_Resolver $resolver
	) {
		$this->catalog_service = $catalog_service;
		$this->resolver        = $resolver;
	}

	/**
	 * Creates the default WordPress public presentation pipeline.
	 *
	 * @return self
	 */
	public static function create_default(): self {
		return new self(
			ZFDZ_WordPress_Lab_Result_Catalog_Service::create_default(),
			ZFDZ_WordPress_Lab_Result_Public_Presentation_Resolver::create_default()
		);
	}

	/**
	 * Returns the derived public presentation result for current coordinated data.
	 *
	 * @return ZFDZ_WordPress_Lab_Result_Public_Presentation_Result
	 */
	public function get_result(): ZFDZ_WordPress_Lab_Result_Public_Presentation_Result {
		return $this->resolver->resolve( $this->catalog_service->get_result() );
	}
}
