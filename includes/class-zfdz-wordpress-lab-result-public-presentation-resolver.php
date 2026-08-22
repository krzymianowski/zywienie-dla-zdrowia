<?php
/**
 * WordPress laboratory result public presentation resolver.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Resolves coordinated catalog availability into a public presentation result.
 */
final class ZFDZ_WordPress_Lab_Result_Public_Presentation_Resolver {

	/**
	 * Standalone latest laboratory result selector.
	 *
	 * @var ZFDZ_Lab_Result_Latest_Selector
	 */
	private readonly ZFDZ_Lab_Result_Latest_Selector $latest_selector;

	/**
	 * Standalone public presentation policy.
	 *
	 * @var ZFDZ_Lab_Result_Public_Presentation_Policy
	 */
	private readonly ZFDZ_Lab_Result_Public_Presentation_Policy $presentation_policy;

	/**
	 * Creates a public presentation resolver.
	 *
	 * @param ZFDZ_Lab_Result_Latest_Selector            $latest_selector      Latest result selector.
	 * @param ZFDZ_Lab_Result_Public_Presentation_Policy $presentation_policy Standalone presentation policy.
	 */
	public function __construct(
		ZFDZ_Lab_Result_Latest_Selector $latest_selector,
		ZFDZ_Lab_Result_Public_Presentation_Policy $presentation_policy
	) {
		$this->latest_selector     = $latest_selector;
		$this->presentation_policy = $presentation_policy;
	}

	/**
	 * Creates the default standalone resolver pipeline.
	 *
	 * @return self
	 */
	public static function create_default(): self {
		return new self(
			new ZFDZ_Lab_Result_Latest_Selector(),
			new ZFDZ_Lab_Result_Public_Presentation_Policy()
		);
	}

	/**
	 * Resolves coordinated availability and successful catalog associations.
	 *
	 * @param ZFDZ_WordPress_Lab_Result_Catalog_Service_Result $coordinated_result Coordinated catalog result.
	 * @throws LogicException When a successful coordinated result has no successful laboratory catalog.
	 * @return ZFDZ_WordPress_Lab_Result_Public_Presentation_Result
	 */
	public function resolve(
		ZFDZ_WordPress_Lab_Result_Catalog_Service_Result $coordinated_result
	): ZFDZ_WordPress_Lab_Result_Public_Presentation_Result {
		$status = $coordinated_result->get_status();

		if (
			ZFDZ_WordPress_Lab_Result_Catalog_Service_Result::STATUS_MENU_CATALOG_UNAVAILABLE
			=== $status
		) {
			return ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::from_unavailable(
				ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::UNAVAILABLE_REASON_MENU_CATALOG
			);
		}

		if (
			ZFDZ_WordPress_Lab_Result_Catalog_Service_Result::STATUS_LAB_CATALOG_UNAVAILABLE
			=== $status
		) {
			return ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::from_unavailable(
				ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::UNAVAILABLE_REASON_LAB_CATALOG
			);
		}

		if ( ZFDZ_WordPress_Lab_Result_Catalog_Service_Result::STATUS_SUCCESS !== $status ) {
			throw new LogicException( 'Unknown coordinated laboratory result status.' );
		}

		$lab_catalog = $coordinated_result->get_lab_catalog();

		if ( null === $lab_catalog || ! $lab_catalog->is_successful() ) {
			throw new LogicException( 'A successful coordinated result requires a successful laboratory result catalog.' );
		}

		$selection = $this->latest_selector->select( $lab_catalog->get_associations() );
		$decision  = $this->presentation_policy->decide( $selection );

		return ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::from_decision( $decision );
	}
}
