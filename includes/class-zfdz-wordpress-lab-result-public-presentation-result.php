<?php
/**
 * WordPress laboratory result public presentation result value object.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Represents public presentation availability and a derived technical decision.
 */
final class ZFDZ_WordPress_Lab_Result_Public_Presentation_Result {

	public const STATUS_UNAVAILABLE       = 'unavailable';
	public const STATUS_NO_RESULT         = 'no_result';
	public const STATUS_BLOCKED_UNMATCHED = 'blocked_unmatched';
	public const STATUS_CANDIDATE         = 'candidate';

	public const UNAVAILABLE_REASON_MENU_CATALOG = 'menu_catalog_unavailable';
	public const UNAVAILABLE_REASON_LAB_CATALOG  = 'lab_catalog_unavailable';

	/**
	 * Integration result status.
	 *
	 * @var string
	 */
	private readonly string $status;

	/**
	 * Unavailability reason, when coordinated source data is unavailable.
	 *
	 * @var string|null
	 */
	private readonly ?string $unavailable_reason;

	/**
	 * Standalone presentation decision, when source data is available.
	 *
	 * @var ZFDZ_Lab_Result_Public_Presentation_Decision|null
	 */
	private readonly ?ZFDZ_Lab_Result_Public_Presentation_Decision $decision;

	/**
	 * Creates an immutable WordPress integration result.
	 *
	 * @param string                                            $status             Integration result status.
	 * @param string|null                                       $unavailable_reason Source unavailability reason.
	 * @param ZFDZ_Lab_Result_Public_Presentation_Decision|null $decision           Standalone presentation decision.
	 * @throws InvalidArgumentException When the supplied state is contradictory.
	 */
	private function __construct(
		string $status,
		?string $unavailable_reason,
		?ZFDZ_Lab_Result_Public_Presentation_Decision $decision
	) {
		if (
			self::STATUS_UNAVAILABLE === $status
			&& (
				null === $unavailable_reason
				|| null !== $decision
			)
		) {
			throw new InvalidArgumentException( 'An unavailable presentation result requires a reason and no decision.' );
		}

		if (
			self::STATUS_UNAVAILABLE !== $status
			&& (
				null !== $unavailable_reason
				|| null === $decision
				|| $status !== $decision->get_status()
			)
		) {
			throw new InvalidArgumentException( 'An available presentation result requires a matching decision and no unavailability reason.' );
		}

		$this->status             = $status;
		$this->unavailable_reason = $unavailable_reason;
		$this->decision           = $decision;
	}

	/**
	 * Creates a result for unavailable coordinated source data.
	 *
	 * @param string $reason Supported unavailability reason.
	 * @throws InvalidArgumentException When the reason is unsupported.
	 * @return self
	 */
	public static function from_unavailable( string $reason ): self {
		if (
			self::UNAVAILABLE_REASON_MENU_CATALOG !== $reason
			&& self::UNAVAILABLE_REASON_LAB_CATALOG !== $reason
		) {
			throw new InvalidArgumentException( 'Unsupported public presentation unavailability reason.' );
		}

		return new self( self::STATUS_UNAVAILABLE, $reason, null );
	}

	/**
	 * Creates an available result from a standalone presentation decision.
	 *
	 * @param ZFDZ_Lab_Result_Public_Presentation_Decision $decision Standalone presentation decision.
	 * @return self
	 */
	public static function from_decision(
		ZFDZ_Lab_Result_Public_Presentation_Decision $decision
	): self {
		return new self( $decision->get_status(), null, $decision );
	}

	/**
	 * Returns the integration result status.
	 *
	 * @return string
	 */
	public function get_status(): string {
		return $this->status;
	}

	/**
	 * Returns whether coordinated source data was available.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return self::STATUS_UNAVAILABLE !== $this->status;
	}

	/**
	 * Returns the coordinated source unavailability reason.
	 *
	 * @return string|null
	 */
	public function get_unavailable_reason(): ?string {
		return $this->unavailable_reason;
	}

	/**
	 * Returns the standalone presentation decision when available.
	 *
	 * @return ZFDZ_Lab_Result_Public_Presentation_Decision|null
	 */
	public function get_decision(): ?ZFDZ_Lab_Result_Public_Presentation_Decision {
		return $this->decision;
	}

	/**
	 * Returns whether a technical presentation candidate exists.
	 *
	 * @return bool
	 */
	public function has_candidate(): bool {
		return null !== $this->decision && $this->decision->has_candidate();
	}

	/**
	 * Returns whether an unmatched latest result blocks a candidate.
	 *
	 * @return bool
	 */
	public function is_blocked(): bool {
		return null !== $this->decision && $this->decision->is_blocked();
	}

	/**
	 * Returns the matched candidate association, when available.
	 *
	 * @return ZFDZ_Lab_Result_Menu_Association|null
	 */
	public function get_association(): ?ZFDZ_Lab_Result_Menu_Association {
		return null === $this->decision
			? null
			: $this->decision->get_association();
	}

	/**
	 * Returns the candidate document, when available.
	 *
	 * @return ZFDZ_Lab_Result_Document|null
	 */
	public function get_document(): ?ZFDZ_Lab_Result_Document {
		return null === $this->decision
			? null
			: $this->decision->get_document();
	}
}
