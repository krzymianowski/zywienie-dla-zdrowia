<?php
/**
 * Public WordPress laboratory result shortcode.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Registers and renders the public laboratory result shortcode.
 */
final class ZFDZ_WordPress_Lab_Result_Shortcode {

	public const SHORTCODE_TAG = 'zfdz_badania';

	/**
	 * Registers the WordPress hook that installs the shortcode callback.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'register_shortcode' ) );
	}

	/**
	 * Registers the shortcode without reading catalogs or resolving URLs.
	 *
	 * @return void
	 */
	public static function register_shortcode(): void {
		add_shortcode( self::SHORTCODE_TAG, array( self::class, 'render' ) );
	}

	/**
	 * Returns public laboratory result markup for the current presentation state.
	 *
	 * @return string
	 */
	public static function render(): string {
		$result = ZFDZ_WordPress_Lab_Result_Public_Presentation_Service::create_default()->get_result();

		return match ( $result->get_status() ) {
			ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::STATUS_UNAVAILABLE =>
				self::get_message_markup( __( 'Wyniki badań są obecnie niedostępne.', 'zywienie-dla-zdrowia' ) ),
			ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::STATUS_NO_RESULT =>
				self::get_message_markup( __( 'Brak wyników badań do wyświetlenia.', 'zywienie-dla-zdrowia' ) ),
			ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::STATUS_BLOCKED_UNMATCHED =>
				self::get_message_markup( __( 'Najnowszy wynik badania nie jest obecnie dostępny do publikacji.', 'zywienie-dla-zdrowia' ) ),
			ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::STATUS_CANDIDATE =>
				self::render_candidate( $result ),
		};
	}

	/**
	 * Renders a public candidate with its managed PDF URL.
	 *
	 * @param ZFDZ_WordPress_Lab_Result_Public_Presentation_Result $result Candidate presentation result.
	 * @return string
	 */
	private static function render_candidate(
		ZFDZ_WordPress_Lab_Result_Public_Presentation_Result $result
	): string {
		if ( ! $result->has_candidate() ) {
			return self::get_candidate_url_unavailable_markup();
		}

		$directory_url = ( new ZFDZ_WordPress_Lab_Result_Storage() )->get_lab_result_directory_url();

		if ( is_wp_error( $directory_url ) ) {
			return self::get_candidate_url_unavailable_markup();
		}

		$document_url = ( new ZFDZ_WordPress_Lab_Result_Public_Url_Resolver() )->resolve(
			$result,
			$directory_url
		);
		$document     = $result->get_document();

		if ( null === $document_url || null === $document ) {
			return self::get_candidate_url_unavailable_markup();
		}

		ob_start();
		?>
		<div class="zfdz-lab-result">
			<section class="zfdz-lab-result-section">
				<h2><?php echo esc_html__( 'Wynik badania laboratoryjnego', 'zywienie-dla-zdrowia' ); ?></h2>
				<dl class="zfdz-lab-result-details">
					<dt><?php echo esc_html__( 'Badanie', 'zywienie-dla-zdrowia' ); ?></dt>
					<dd><?php echo esc_html( $document->get_name() ); ?></dd>
					<dt><?php echo esc_html__( 'Data wyniku', 'zywienie-dla-zdrowia' ); ?></dt>
					<dd><?php echo esc_html( $document->get_result_date() ); ?></dd>
					<dt><?php echo esc_html__( 'Okres jadłospisu', 'zywienie-dla-zdrowia' ); ?></dt>
					<dd>
						<?php
						echo esc_html(
							sprintf(
								/* translators: 1: menu period start date, 2: menu period end date. */
								__( '%1$s – %2$s', 'zywienie-dla-zdrowia' ),
								$document->get_menu_start_date(),
								$document->get_menu_end_date()
							)
						);
						?>
					</dd>
				</dl>
				<p><a href="<?php echo esc_url( $document_url ); ?>"><?php echo esc_html__( 'Zobacz wynik badania', 'zywienie-dla-zdrowia' ); ?></a></p>
			</section>
		</div>
		<?php
		$markup = ob_get_clean();

		return false === $markup ? '' : $markup;
	}

	/**
	 * Returns a public message inside semantic laboratory result markup.
	 *
	 * @param string $message Safe public message.
	 * @return string
	 */
	private static function get_message_markup( string $message ): string {
		ob_start();
		?>
		<div class="zfdz-lab-result">
			<section class="zfdz-lab-result-section">
				<h2><?php echo esc_html__( 'Wynik badania laboratoryjnego', 'zywienie-dla-zdrowia' ); ?></h2>
				<p><?php echo esc_html( $message ); ?></p>
			</section>
		</div>
		<?php
		$markup = ob_get_clean();

		return false === $markup ? '' : $markup;
	}

	/**
	 * Returns a neutral message when a candidate URL cannot be resolved.
	 *
	 * @return string
	 */
	private static function get_candidate_url_unavailable_markup(): string {
		return self::get_message_markup(
			__( 'Wynik badania jest obecnie niedostępny.', 'zywienie-dla-zdrowia' )
		);
	}
}
