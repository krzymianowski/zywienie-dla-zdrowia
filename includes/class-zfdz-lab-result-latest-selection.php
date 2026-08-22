<?php
/**
 * Latest laboratory result selection value object.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Represents an empty, matched, or unmatched latest laboratory result selection.
 */
final class ZFDZ_Lab_Result_Latest_Selection {

	/**
	 * No laboratory result is available.
	 */
	public const STATUS_EMPTY = 'empty';

	/**
	 * The latest laboratory result has an exact menu-period match.
	 */
	public const STATUS_MATCHED = 'matched';

	/**
	 * The latest laboratory result has no exact menu-period match.
	 */
	public const STATUS_UNMATCHED = 'unmatched';

	/**
	 * Selection status.
	 *
	 * @var string
	 */
	private readonly string $status;

	/**
	 * Selected association, when available.
	 *
	 * @var ZFDZ_Lab_Result_Menu_Association|null
	 */
	private readonly ?ZFDZ_Lab_Result_Menu_Association $association;

	/**
	 * Creates an immutable selection.
	 *
	 * @param string                                $status      Selection status.
	 * @param ZFDZ_Lab_Result_Menu_Association|null $association Selected association.
	 */
	private function __construct( string $status, ?ZFDZ_Lab_Result_Menu_Association $association ) {
		$this->status      = $status;
		$this->association = $association;
	}

	/**
	 * Creates an empty selection.
	 *
	 * @return self
	 */
	public static function from_empty(): self {
		return new self( self::STATUS_EMPTY, null );
	}

	/**
	 * Creates a selection containing a matched latest result.
	 *
	 * @param ZFDZ_Lab_Result_Menu_Association $association Matched association.
	 * @throws InvalidArgumentException When the association is unmatched.
	 * @return self
	 */
	public static function from_matched( ZFDZ_Lab_Result_Menu_Association $association ): self {
		if ( ! $association->is_matched() ) {
			throw new InvalidArgumentException( 'A matched latest selection requires a matched association.' );
		}

		return new self( self::STATUS_MATCHED, $association );
	}

	/**
	 * Creates a selection containing an unmatched latest result.
	 *
	 * @param ZFDZ_Lab_Result_Menu_Association $association Unmatched association.
	 * @throws InvalidArgumentException When the association is matched.
	 * @return self
	 */
	public static function from_unmatched( ZFDZ_Lab_Result_Menu_Association $association ): self {
		if ( $association->is_matched() ) {
			throw new InvalidArgumentException( 'An unmatched latest selection requires an unmatched association.' );
		}

		return new self( self::STATUS_UNMATCHED, $association );
	}

	/**
	 * Returns the selection status.
	 *
	 * @return string
	 */
	public function get_status(): string {
		return $this->status;
	}

	/**
	 * Returns whether a laboratory result was selected.
	 *
	 * @return bool
	 */
	public function has_result(): bool {
		return null !== $this->association;
	}

	/**
	 * Returns whether the selected latest result has an exact menu-period match.
	 *
	 * @return bool
	 */
	public function is_matched(): bool {
		return self::STATUS_MATCHED === $this->status;
	}

	/**
	 * Returns the selected association, when available.
	 *
	 * @return ZFDZ_Lab_Result_Menu_Association|null
	 */
	public function get_association(): ?ZFDZ_Lab_Result_Menu_Association {
		return $this->association;
	}

	/**
	 * Returns the selected laboratory result document, when available.
	 *
	 * @return ZFDZ_Lab_Result_Document|null
	 */
	public function get_document(): ?ZFDZ_Lab_Result_Document {
		return null === $this->association
			? null
			: $this->association->get_document();
	}
}
