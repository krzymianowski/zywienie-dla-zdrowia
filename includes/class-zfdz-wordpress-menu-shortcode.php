<?php
/**
 * Public WordPress menu shortcode.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Registers and renders the public menu document shortcode.
 */
final class ZFDZ_WordPress_Menu_Shortcode {

	public const SHORTCODE_TAG = 'zfdz_jadlospisy';

	/**
	 * Registers the WordPress hook that installs the shortcode callback.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'register_shortcode' ) );
	}

	/**
	 * Registers the shortcode without reading the catalog.
	 *
	 * @return void
	 */
	public static function register_shortcode(): void {
		add_shortcode( self::SHORTCODE_TAG, array( self::class, 'render' ) );
	}

	/**
	 * Returns the public current and upcoming menu markup.
	 *
	 * @return string
	 */
	public static function render(): string {
		$catalog = ZFDZ_WordPress_Menu_Catalog_Service::create_default()->get_catalog();

		if ( ! $catalog->is_successful() ) {
			return self::get_unavailable_markup();
		}

		$menu_directory_url = ( new ZFDZ_WordPress_Menu_Storage() )->get_menu_directory_url();

		if ( is_wp_error( $menu_directory_url ) ) {
			return self::get_unavailable_markup();
		}

		$current_datetime = current_datetime();
		$current_date     = $current_datetime->format( 'Y-m-d' );
		$classification   = ( new ZFDZ_Menu_Period_Classifier() )->classify( $catalog->get_groups(), $current_date );
		$current_groups   = $classification->get_current_groups();
		$upcoming_groups  = array_reverse( $classification->get_upcoming_groups() );
		$date_format      = get_option( 'date_format' );
		$timezone         = wp_timezone();

		if ( ! is_string( $date_format ) || '' === trim( $date_format ) ) {
			$date_format = 'Y-m-d';
		}

		ob_start();
		?>
		<div class="zfdz-menu">
			<?php
			self::render_section(
				__( 'Aktualne jadłospisy', 'zywienie-dla-zdrowia' ),
				$current_groups,
				__( 'Brak aktualnych jadłospisów.', 'zywienie-dla-zdrowia' ),
				$menu_directory_url,
				$date_format,
				$timezone
			);
			self::render_section(
				__( 'Nadchodzące jadłospisy', 'zywienie-dla-zdrowia' ),
				$upcoming_groups,
				__( 'Brak nadchodzących jadłospisów.', 'zywienie-dla-zdrowia' ),
				$menu_directory_url,
				$date_format,
				$timezone
			);
			?>
		</div>
		<?php
		$markup = ob_get_clean();

		return false === $markup ? '' : $markup;
	}

	/**
	 * Renders one public menu section inside the shortcode output buffer.
	 *
	 * @param string       $heading            Section heading.
	 * @param array        $groups             Period groups in public display order.
	 * @param string       $empty_message      Message used when the section is empty.
	 * @param string       $menu_directory_url Public menu directory URL.
	 * @param string       $date_format        WordPress date format.
	 * @param DateTimeZone $timezone           WordPress site timezone.
	 * @return void
	 */
	private static function render_section(
		string $heading,
		array $groups,
		string $empty_message,
		string $menu_directory_url,
		string $date_format,
		DateTimeZone $timezone
	): void {
		?>
		<section class="zfdz-menu-section">
			<h2><?php echo esc_html( $heading ); ?></h2>
			<?php if ( array() === $groups ) : ?>
				<p><?php echo esc_html( $empty_message ); ?></p>
			<?php else : ?>
				<?php foreach ( $groups as $group ) : ?>
					<?php self::render_period( $group, $menu_directory_url, $date_format, $timezone ); ?>
				<?php endforeach; ?>
			<?php endif; ?>
		</section>
		<?php
	}

	/**
	 * Renders a period heading and its documents.
	 *
	 * @param ZFDZ_Menu_Period_Group $group              Period group.
	 * @param string                 $menu_directory_url Public menu directory URL.
	 * @param string                 $date_format        WordPress date format.
	 * @param DateTimeZone           $timezone           WordPress site timezone.
	 * @return void
	 */
	private static function render_period(
		ZFDZ_Menu_Period_Group $group,
		string $menu_directory_url,
		string $date_format,
		DateTimeZone $timezone
	): void {
		$period_label = self::get_period_label( $group, $date_format, $timezone );
		?>
		<section class="zfdz-menu-period">
			<h3><?php echo esc_html( $period_label ); ?></h3>
			<ul>
				<?php foreach ( $group->get_documents() as $document ) : ?>
					<?php
					$document_url = trailingslashit( $menu_directory_url ) . rawurlencode( $document->get_original_filename() );
					$link_label   = sprintf(
						/* translators: %s: menu document name extracted from its filename. */
						__( '%s (PDF)', 'zywienie-dla-zdrowia' ),
						$document->get_name()
					);
					?>
					<li><a href="<?php echo esc_url( $document_url ); ?>"><?php echo esc_html( $link_label ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php
	}

	/**
	 * Returns a localized period label with a safe ISO fallback.
	 *
	 * @param ZFDZ_Menu_Period_Group $group       Period group.
	 * @param string                 $date_format WordPress date format.
	 * @param DateTimeZone           $timezone    WordPress site timezone.
	 * @return string
	 */
	private static function get_period_label( ZFDZ_Menu_Period_Group $group, string $date_format, DateTimeZone $timezone ): string {
		$start_date = self::format_date( $group->get_start_date(), $date_format, $timezone );

		if ( $group->get_start_date() === $group->get_end_date() ) {
			return $start_date;
		}

		$end_date = self::format_date( $group->get_end_date(), $date_format, $timezone );

		return sprintf(
			/* translators: 1: menu period start date, 2: menu period end date. */
			__( '%1$s – %2$s', 'zywienie-dla-zdrowia' ),
			$start_date,
			$end_date
		);
	}

	/**
	 * Formats one validated ISO date according to WordPress settings.
	 *
	 * @param string       $date        Date in YYYY-MM-DD format.
	 * @param string       $date_format WordPress date format.
	 * @param DateTimeZone $timezone    WordPress site timezone.
	 * @return string
	 */
	private static function format_date( string $date, string $date_format, DateTimeZone $timezone ): string {
		$date_value = DateTimeImmutable::createFromFormat( '!Y-m-d', $date, $timezone );

		if ( false === $date_value || $date !== $date_value->format( 'Y-m-d' ) ) {
			return $date;
		}

		$formatted_date = wp_date( $date_format, $date_value->getTimestamp(), $timezone );

		return false === $formatted_date ? $date : $formatted_date;
	}

	/**
	 * Returns a safe public message for directory or URL failures.
	 *
	 * @return string
	 */
	private static function get_unavailable_markup(): string {
		return '<div class="zfdz-menu"><p>'
			. esc_html__( 'Jadłospisy są obecnie niedostępne. Prosimy spróbować ponownie później.', 'zywienie-dla-zdrowia' )
			. '</p></div>';
	}
}
