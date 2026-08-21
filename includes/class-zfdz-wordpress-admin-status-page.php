<?php
/**
 * WordPress publication status administration page.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Renders the technical publication status for the menu module.
 */
final class ZFDZ_WordPress_Admin_Status_Page {

	/**
	 * Renders the publication status page.
	 *
	 * @return void
	 */
	public static function render(): void {
		if ( ! current_user_can( ZFDZ_WordPress_Admin::CAPABILITY ) ) {
			wp_die( esc_html__( 'Nie masz uprawnień do wyświetlenia tej strony.', 'zywienie-dla-zdrowia' ) );
		}

		$catalog        = ZFDZ_WordPress_Menu_Catalog_Service::create_default()->get_catalog();
		$document_count = count( $catalog->get_documents() );
		$group_count    = count( $catalog->get_groups() );
		$issue_count    = count( $catalog->get_issues() );
		$status         = self::get_status( $catalog, $issue_count );
		$classification = null;
		$current_date   = '';

		if ( $catalog->is_successful() ) {
			$current_datetime = current_datetime();
			$current_date     = $current_datetime->format( 'Y-m-d' );
			$classification   = ( new ZFDZ_Menu_Period_Classifier() )->classify( $catalog->get_groups(), $current_date );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Żywienie dla Zdrowia', 'zywienie-dla-zdrowia' ); ?></h1>
			<?php self::render_refresh_notice(); ?>

			<h2><?php esc_html_e( 'Status publikacji', 'zywienie-dla-zdrowia' ); ?></h2>
			<p><?php esc_html_e( 'Panel przedstawia techniczny stan publikacji i nie jest oceną zgodności z prawem.', 'zywienie-dla-zdrowia' ); ?></p>

			<h2><?php esc_html_e( 'Jadłospisy', 'zywienie-dla-zdrowia' ); ?></h2>
			<div class="notice <?php echo esc_attr( $status['notice_class'] ); ?> inline">
				<p>
					<strong><?php esc_html_e( 'Status techniczny:', 'zywienie-dla-zdrowia' ); ?></strong>
					<?php echo esc_html( $status['label'] ); ?>
				</p>
			</div>

			<?php if ( ! $catalog->is_successful() ) : ?>
				<p><?php echo esc_html( self::get_directory_error_message( $catalog->get_directory_error_code() ) ); ?></p>
			<?php endif; ?>

			<ul>
				<li>
					<strong><?php esc_html_e( 'Poprawne dokumenty:', 'zywienie-dla-zdrowia' ); ?></strong>
					<?php echo esc_html( (string) $document_count ); ?>
				</li>
				<li>
					<strong><?php esc_html_e( 'Okresy:', 'zywienie-dla-zdrowia' ); ?></strong>
					<?php echo esc_html( (string) $group_count ); ?>
				</li>
				<li>
					<strong><?php esc_html_e( 'Problemy:', 'zywienie-dla-zdrowia' ); ?></strong>
					<?php echo esc_html( (string) $issue_count ); ?>
				</li>
			</ul>

			<?php if ( $classification instanceof ZFDZ_Menu_Period_Classification ) : ?>
				<?php self::render_period_classification( $classification, $current_date ); ?>
			<?php endif; ?>

			<?php self::render_issues( $catalog->get_issues() ); ?>

			<h2><?php esc_html_e( 'Odświeżenie katalogu', 'zywienie-dla-zdrowia' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Wymusza ponowne odczytanie katalogu jadłospisów z pominięciem cache.', 'zywienie-dla-zdrowia' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( ZFDZ_WordPress_Admin::REFRESH_ACTION ); ?>">
				<?php wp_nonce_field( ZFDZ_WordPress_Admin::REFRESH_NONCE_ACTION, ZFDZ_WordPress_Admin::REFRESH_NONCE_FIELD ); ?>
				<?php submit_button( __( 'Odśwież', 'zywienie-dla-zdrowia' ), 'secondary' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Renders period counts classified against the current WordPress site date.
	 *
	 * @param ZFDZ_Menu_Period_Classification $classification Classified period groups.
	 * @param string                          $current_date   Current WordPress site date.
	 * @return void
	 */
	private static function render_period_classification( ZFDZ_Menu_Period_Classification $classification, string $current_date ): void {
		$current_count  = count( $classification->get_current_groups() );
		$upcoming_count = count( $classification->get_upcoming_groups() );
		$archived_count = count( $classification->get_archived_groups() );
		?>
		<h2><?php esc_html_e( 'Okresy jadłospisów', 'zywienie-dla-zdrowia' ); ?></h2>
		<p>
			<strong><?php esc_html_e( 'Data odniesienia:', 'zywienie-dla-zdrowia' ); ?></strong>
			<?php echo esc_html( $current_date ); ?>
			<?php esc_html_e( '(według strefy czasu skonfigurowanej w WordPressie)', 'zywienie-dla-zdrowia' ); ?>
		</p>
		<ul>
			<li>
				<strong><?php esc_html_e( 'Aktualne okresy:', 'zywienie-dla-zdrowia' ); ?></strong>
				<?php echo esc_html( (string) $current_count ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Nadchodzące okresy:', 'zywienie-dla-zdrowia' ); ?></strong>
				<?php echo esc_html( (string) $upcoming_count ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Archiwalne okresy:', 'zywienie-dla-zdrowia' ); ?></strong>
				<?php echo esc_html( (string) $archived_count ); ?>
			</li>
		</ul>

		<?php if ( 0 < $current_count ) : ?>
			<div class="notice notice-success inline">
				<p><?php esc_html_e( 'Znaleziono co najmniej jeden okres jadłospisu obowiązujący dzisiaj.', 'zywienie-dla-zdrowia' ); ?></p>
			</div>
		<?php else : ?>
			<div class="notice notice-warning inline">
				<p><?php esc_html_e( 'Brak jadłospisu obowiązującego dzisiaj.', 'zywienie-dla-zdrowia' ); ?></p>
			</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Returns the module status label and native notice class.
	 *
	 * @param ZFDZ_Menu_Catalog_Result $catalog     Catalog result.
	 * @param int                      $issue_count Number of entry-level issues.
	 * @return array{label: string, notice_class: string}
	 */
	private static function get_status( ZFDZ_Menu_Catalog_Result $catalog, int $issue_count ): array {
		if ( ! $catalog->is_successful() ) {
			return array(
				'label'        => __( 'Błąd', 'zywienie-dla-zdrowia' ),
				'notice_class' => 'notice-error',
			);
		}

		if ( 0 < $issue_count ) {
			return array(
				'label'        => __( 'Wymaga uwagi', 'zywienie-dla-zdrowia' ),
				'notice_class' => 'notice-warning',
			);
		}

		return array(
			'label'        => __( 'OK', 'zywienie-dla-zdrowia' ),
			'notice_class' => 'notice-success',
		);
	}

	/**
	 * Renders a whitelisted refresh result notice after PRG redirect.
	 *
	 * @return void
	 */
	private static function render_refresh_notice(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only PRG status is type-checked, unslashed, sanitized, and whitelisted below.
		$refresh_status = isset( $_GET['zfdz_refresh'] ) ? wp_unslash( $_GET['zfdz_refresh'] ) : '';

		if ( ! is_string( $refresh_status ) ) {
			return;
		}

		$refresh_status = sanitize_key( $refresh_status );

		if ( ! in_array( $refresh_status, array( 'success', 'error' ), true ) ) {
			return;
		}

		if ( 'success' === $refresh_status ) {
			$notice_class = 'notice-success';
			$message      = __( 'Katalog jadłospisów został odświeżony.', 'zywienie-dla-zdrowia' );
		} else {
			$notice_class = 'notice-error';
			$message      = __( 'Nie udało się odświeżyć katalogu jadłospisów. Sprawdź stan storage i zgłoszone problemy.', 'zywienie-dla-zdrowia' );
		}
		?>
		<div class="notice <?php echo esc_attr( $notice_class ); ?>">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
		<?php
	}

	/**
	 * Renders entry-level issues as a native WordPress table.
	 *
	 * @param ZFDZ_Menu_Scan_Issue[] $issues Catalog issues.
	 * @return void
	 */
	private static function render_issues( array $issues ): void {
		if ( array() === $issues ) {
			return;
		}
		?>
		<h2><?php esc_html_e( 'Problemy', 'zywienie-dla-zdrowia' ); ?></h2>
		<table class="widefat striped">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Plik / wpis', 'zywienie-dla-zdrowia' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Problem', 'zywienie-dla-zdrowia' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $issues as $issue ) : ?>
					<tr>
						<td><?php echo esc_html( $issue->get_entry_name() ); ?></td>
						<td><?php echo esc_html( self::get_issue_message( $issue->get_error_code() ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Maps a directory-level error code to a safe Polish message.
	 *
	 * @param string|null $error_code Directory error code.
	 * @return string
	 */
	private static function get_directory_error_message( ?string $error_code ): string {
		return match ( $error_code ) {
			ZFDZ_Menu_Directory_Scanner::ERROR_DIRECTORY_NOT_FOUND => __( 'Katalog jadłospisów nie istnieje.', 'zywienie-dla-zdrowia' ),
			ZFDZ_Menu_Directory_Scanner::ERROR_NOT_A_DIRECTORY => __( 'W lokalizacji katalogu jadłospisów znajduje się wpis innego typu.', 'zywienie-dla-zdrowia' ),
			ZFDZ_Menu_Directory_Scanner::ERROR_DIRECTORY_NOT_READABLE,
			ZFDZ_WordPress_Menu_Storage::ERROR_STORAGE_NOT_READABLE => __( 'Katalog jadłospisów nie jest dostępny do odczytu.', 'zywienie-dla-zdrowia' ),
			ZFDZ_Menu_Directory_Scanner::ERROR_DIRECTORY_SCAN_FAILED => __( 'Nie udało się odczytać zawartości katalogu jadłospisów.', 'zywienie-dla-zdrowia' ),
			ZFDZ_WordPress_Menu_Storage::ERROR_UPLOADS_UNAVAILABLE => __( 'Katalog przesyłanych plików WordPress jest niedostępny.', 'zywienie-dla-zdrowia' ),
			ZFDZ_WordPress_Menu_Storage::ERROR_STORAGE_UNSAFE_SYMLINK => __( 'Zarządzany katalog nie może być dowiązaniem symbolicznym.', 'zywienie-dla-zdrowia' ),
			ZFDZ_WordPress_Menu_Storage::ERROR_STORAGE_PATH_CONFLICT => __( 'W lokalizacji zarządzanego katalogu znajduje się kolidujący wpis.', 'zywienie-dla-zdrowia' ),
			ZFDZ_WordPress_Menu_Storage::ERROR_STORAGE_CREATE_FAILED => __( 'Nie udało się przygotować katalogu jadłospisów.', 'zywienie-dla-zdrowia' ),
			default => __( 'Nie udało się pobrać stanu katalogu jadłospisów.', 'zywienie-dla-zdrowia' ),
		};
	}

	/**
	 * Maps an entry-level issue code to a safe Polish message.
	 *
	 * @param string $error_code Entry-level issue code.
	 * @return string
	 */
	private static function get_issue_message( string $error_code ): string {
		return match ( $error_code ) {
			ZFDZ_Menu_Filename_Parser::ERROR_INVALID_PATH => __( 'Nazwa wpisu zawiera niedozwolone elementy ścieżki.', 'zywienie-dla-zdrowia' ),
			ZFDZ_Menu_Filename_Parser::ERROR_UNSUPPORTED_EXTENSION => __( 'Nieobsługiwane rozszerzenie pliku.', 'zywienie-dla-zdrowia' ),
			ZFDZ_Menu_Filename_Parser::ERROR_INVALID_FORMAT => __( 'Nazwa pliku nie jest zgodna z wymaganą konwencją.', 'zywienie-dla-zdrowia' ),
			ZFDZ_Menu_Filename_Parser::ERROR_INVALID_START_DATE => __( 'Nieprawidłowa data początku w nazwie pliku.', 'zywienie-dla-zdrowia' ),
			ZFDZ_Menu_Filename_Parser::ERROR_INVALID_END_DATE => __( 'Nieprawidłowa data końca w nazwie pliku.', 'zywienie-dla-zdrowia' ),
			ZFDZ_Menu_Filename_Parser::ERROR_INVALID_DATE_RANGE => __( 'Data końca jest wcześniejsza niż data początku.', 'zywienie-dla-zdrowia' ),
			ZFDZ_Menu_Filename_Parser::ERROR_INVALID_NAME => __( 'Nazwa dokumentu jest nieprawidłowa.', 'zywienie-dla-zdrowia' ),
			ZFDZ_Menu_Scan_Issue::ERROR_UNSAFE_SYMLINK => __( 'Dowiązania symboliczne nie są obsługiwane.', 'zywienie-dla-zdrowia' ),
			ZFDZ_Menu_Scan_Issue::ERROR_UNSUPPORTED_ENTRY_TYPE => __( 'Nieobsługiwany typ wpisu w katalogu.', 'zywienie-dla-zdrowia' ),
			ZFDZ_PDF_File_Validator::ERROR_FILE_NOT_FOUND => __( 'Plik nie istnieje lub został usunięty podczas odczytu.', 'zywienie-dla-zdrowia' ),
			ZFDZ_PDF_File_Validator::ERROR_NOT_A_REGULAR_FILE => __( 'Wpis nie jest zwykłym plikiem.', 'zywienie-dla-zdrowia' ),
			ZFDZ_PDF_File_Validator::ERROR_EMPTY_FILE => __( 'Plik jest pusty.', 'zywienie-dla-zdrowia' ),
			ZFDZ_PDF_File_Validator::ERROR_UNSUPPORTED_MIME_TYPE => __( 'Zawartość pliku nie została rozpoznana jako PDF.', 'zywienie-dla-zdrowia' ),
			ZFDZ_PDF_File_Validator::ERROR_INVALID_PDF_HEADER => __( 'Plik nie ma prawidłowego nagłówka PDF.', 'zywienie-dla-zdrowia' ),
			ZFDZ_PDF_File_Validator::ERROR_INVALID_PDF_EOF => __( 'Plik PDF nie zawiera oczekiwanego markera końcowego.', 'zywienie-dla-zdrowia' ),
			ZFDZ_PDF_File_Validator::ERROR_FILE_NOT_READABLE,
			ZFDZ_PDF_File_Validator::ERROR_FILE_OPEN_FAILED,
			ZFDZ_PDF_File_Validator::ERROR_FILE_STAT_FAILED,
			ZFDZ_PDF_File_Validator::ERROR_MIME_DETECTION_FAILED,
			ZFDZ_PDF_File_Validator::ERROR_FILE_READ_FAILED => __( 'Nie udało się prawidłowo odczytać pliku.', 'zywienie-dla-zdrowia' ),
			default => __( 'Wykryto problem z plikiem lub wpisem.', 'zywienie-dla-zdrowia' ),
		};
	}
}
