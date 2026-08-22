<?php
/**
 * Laboratory result public presentation decision value object.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Represents the technical outcome of laboratory result presentation policy.
 */
final class ZFDZ_Lab_Result_Public_Presentation_Decision {

	/**
	 * No validated laboratory result is available.
	 */
	public const STATUS_NO_RESULT = 'no_result';

	/**
	 * The latest laboratory result is a technical presentation candidate.
	 */
	public const STATUS_CANDIDATE = 'candidate';

	/**
	 * The latest laboratory result is unmatched and blocks a candidate.
	 */
	public const STATUS_BLOCKED_UNMATCHED = 'blocked_unmatched';

	/**
	 * Decision status.
	 *
	 * @var string
	 */
	private readonly string $status;

	/**
	 * Matched candidate association, when available.
	 *
	 * @var ZFDZ_Lab_Result_Menu_Association|null
	 */
	private readonly ?ZFDZ_Lab_Result_Menu_Association $association;

	/**
	 * Creates an immutable public presentation decision.
	 *
	 * @param string                                $status      Decision status.
	 * @param ZFDZ_Lab_Result_Menu_Association|null $association Matched candidate association.
	 */
	private function __construct( string $status, ?ZFDZ_Lab_Result_Menu_Association $association ) {
		$this->status      = $status;
		$this->association = $association;
	}

	/**
	 * Creates a decision for an empty latest selection.
	 *
	 * @return self
	 */
	public static function from_no_result(): self {
		return new self( self::STATUS_NO_RESULT, null );
	}

	/**
	 * Creates a decision containing a matched technical candidate.
	 *
	 * @param ZFDZ_Lab_Result_Menu_Association $association Matched latest association.
	 * @throws InvalidArgumentException When the association is unmatched.
	 * @return self
	 */
	public static function from_candidate( ZFDZ_Lab_Result_Menu_Association $association ): self {
		if ( ! $association->is_matched() ) {
			throw new InvalidArgumentException( 'A public presentation candidate requires a matched association.' );
		}

		return new self( self::STATUS_CANDIDATE, $association );
	}

	/**
	 * Creates a decision blocked by an unmatched latest selection.
	 *
	 * @return self
	 */
	public static function from_blocked_unmatched(): self {
		return new self( self::STATUS_BLOCKED_UNMATCHED, null );
	}

	/**
	 * Returns the decision status.
	 *
	 * @return string
	 */
	public function get_status(): string {
		return $this->status;
	}

	/**
	 * Returns whether a technical presentation candidate is available.
	 *
	 * @return bool
	 */
	public function has_candidate(): bool {
		return self::STATUS_CANDIDATE === $this->status;
	}

	/**
	 * Returns whether an unmatched latest result blocks a candidate.
	 *
	 * @return bool
	 */
	public function is_blocked(): bool {
		return self::STATUS_BLOCKED_UNMATCHED === $this->status;
	}

	/**
	 * Returns the matched candidate association, when available.
	 *
	 * @return ZFDZ_Lab_Result_Menu_Association|null
	 */
	public function get_association(): ?ZFDZ_Lab_Result_Menu_Association {
		return $this->association;
	}

	/**
	 * Returns the candidate document, when available.
	 *
	 * @return ZFDZ_Lab_Result_Document|null
	 */
	public function get_document(): ?ZFDZ_Lab_Result_Document {
		return null === $this->association
			? null
			: $this->association->get_document();
	}
}
