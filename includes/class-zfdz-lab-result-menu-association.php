<?php
/**
 * Laboratory result to menu association value object.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Represents an exact-period match or an unmatched laboratory result.
 */
final class ZFDZ_Lab_Result_Menu_Association {

	/**
	 * Laboratory result document.
	 *
	 * @var ZFDZ_Lab_Result_Document
	 */
	private readonly ZFDZ_Lab_Result_Document $document;

	/**
	 * Exactly matching menu period group, when available.
	 *
	 * @var ZFDZ_Menu_Period_Group|null
	 */
	private readonly ?ZFDZ_Menu_Period_Group $menu_group;

	/**
	 * Creates an association.
	 *
	 * @param ZFDZ_Lab_Result_Document    $document   Laboratory result document.
	 * @param ZFDZ_Menu_Period_Group|null $menu_group Exactly matching menu group.
	 */
	private function __construct( ZFDZ_Lab_Result_Document $document, ?ZFDZ_Menu_Period_Group $menu_group ) {
		$this->document   = $document;
		$this->menu_group = $menu_group;
	}

	/**
	 * Creates an exact-period match.
	 *
	 * @param ZFDZ_Lab_Result_Document $document   Laboratory result document.
	 * @param ZFDZ_Menu_Period_Group   $menu_group Exactly matching menu group.
	 * @throws InvalidArgumentException When the document and group periods differ.
	 * @return self
	 */
	public static function from_match(
		ZFDZ_Lab_Result_Document $document,
		ZFDZ_Menu_Period_Group $menu_group
	): self {
		if (
			$document->get_menu_start_date() !== $menu_group->get_start_date()
			|| $document->get_menu_end_date() !== $menu_group->get_end_date()
		) {
			throw new InvalidArgumentException( 'A matched association requires identical menu period dates.' );
		}

		return new self( $document, $menu_group );
	}

	/**
	 * Creates an unmatched association.
	 *
	 * @param ZFDZ_Lab_Result_Document $document Laboratory result document.
	 * @return self
	 */
	public static function from_unmatched( ZFDZ_Lab_Result_Document $document ): self {
		return new self( $document, null );
	}

	/**
	 * Returns whether an exact menu period match exists.
	 *
	 * @return bool
	 */
	public function is_matched(): bool {
		return null !== $this->menu_group;
	}

	/**
	 * Returns the laboratory result document.
	 *
	 * @return ZFDZ_Lab_Result_Document
	 */
	public function get_document(): ZFDZ_Lab_Result_Document {
		return $this->document;
	}

	/**
	 * Returns the exactly matching menu group, when available.
	 *
	 * @return ZFDZ_Menu_Period_Group|null
	 */
	public function get_menu_group(): ?ZFDZ_Menu_Period_Group {
		return $this->menu_group;
	}
}
