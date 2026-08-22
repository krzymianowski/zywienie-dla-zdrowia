<?php
/**
 * Plugin Name:       Żywienie dla Zdrowia
 * Description:       Wspiera publikowanie informacji związanych z sekcją „Żywienie dla zdrowia” na stronach placówek medycznych.
 * Version:           0.1.0
 * Requires at least: 6.8
 * Requires PHP:      8.2
 * Author:            Żywienie dla Zdrowia contributors
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       zywienie-dla-zdrowia
 *
 * @package ZywienieDlaZdrowia
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/includes/class-zfdz-menu-document.php';
require_once __DIR__ . '/includes/class-zfdz-menu-filename-parse-result.php';
require_once __DIR__ . '/includes/class-zfdz-menu-filename-parser.php';
require_once __DIR__ . '/includes/class-zfdz-lab-result-document.php';
require_once __DIR__ . '/includes/class-zfdz-lab-result-filename-parse-result.php';
require_once __DIR__ . '/includes/class-zfdz-lab-result-filename-parser.php';
require_once __DIR__ . '/includes/class-zfdz-lab-result-scan-issue.php';
require_once __DIR__ . '/includes/class-zfdz-lab-result-scan-result.php';
require_once __DIR__ . '/includes/class-zfdz-lab-result-directory-scanner.php';
require_once __DIR__ . '/includes/class-zfdz-menu-scan-issue.php';
require_once __DIR__ . '/includes/class-zfdz-menu-period-group.php';
require_once __DIR__ . '/includes/class-zfdz-lab-result-menu-association.php';
require_once __DIR__ . '/includes/class-zfdz-lab-result-menu-matcher.php';
require_once __DIR__ . '/includes/class-zfdz-lab-result-latest-selection.php';
require_once __DIR__ . '/includes/class-zfdz-lab-result-latest-selector.php';
require_once __DIR__ . '/includes/class-zfdz-lab-result-public-presentation-decision.php';
require_once __DIR__ . '/includes/class-zfdz-lab-result-public-presentation-policy.php';
require_once __DIR__ . '/includes/class-zfdz-menu-period-classification.php';
require_once __DIR__ . '/includes/class-zfdz-menu-period-classifier.php';
require_once __DIR__ . '/includes/class-zfdz-menu-scan-result.php';
require_once __DIR__ . '/includes/class-zfdz-menu-directory-scanner.php';
require_once __DIR__ . '/includes/class-zfdz-pdf-validation-result.php';
require_once __DIR__ . '/includes/class-zfdz-pdf-file-validator.php';
require_once __DIR__ . '/includes/class-zfdz-lab-result-catalog-result.php';
require_once __DIR__ . '/includes/class-zfdz-lab-result-catalog-builder.php';
require_once __DIR__ . '/includes/class-zfdz-wordpress-lab-result-storage.php';
require_once __DIR__ . '/includes/class-zfdz-wordpress-lab-result-catalog-provider.php';
require_once __DIR__ . '/includes/class-zfdz-menu-catalog-result.php';
require_once __DIR__ . '/includes/class-zfdz-wordpress-lab-result-catalog-service-result.php';
require_once __DIR__ . '/includes/class-zfdz-menu-catalog-builder.php';
require_once __DIR__ . '/includes/class-zfdz-wordpress-menu-storage.php';
require_once __DIR__ . '/includes/class-zfdz-wordpress-menu-catalog-provider.php';
require_once __DIR__ . '/includes/class-zfdz-wordpress-menu-catalog-cache.php';
require_once __DIR__ . '/includes/class-zfdz-wordpress-menu-catalog-service.php';
require_once __DIR__ . '/includes/class-zfdz-wordpress-lab-result-catalog-cache.php';
require_once __DIR__ . '/includes/class-zfdz-wordpress-lab-result-catalog-service.php';
require_once __DIR__ . '/includes/class-zfdz-wordpress-lab-result-public-presentation-result.php';
require_once __DIR__ . '/includes/class-zfdz-wordpress-lab-result-public-presentation-resolver.php';
require_once __DIR__ . '/includes/class-zfdz-wordpress-lab-result-public-presentation-service.php';
require_once __DIR__ . '/includes/class-zfdz-wordpress-menu-shortcode.php';
require_once __DIR__ . '/includes/class-zfdz-wordpress-admin-status-page.php';
require_once __DIR__ . '/includes/class-zfdz-wordpress-admin.php';

ZFDZ_WordPress_Menu_Shortcode::register();
ZFDZ_WordPress_Admin::register();

register_activation_hook(
	__FILE__,
	static function (): void {
		$result = ( new ZFDZ_WordPress_Menu_Storage() )->ensure_menu_directory();

		if ( is_wp_error( $result ) ) {
			wp_die(
				esc_html__( 'Nie udało się przygotować katalogu jadłospisów. Sprawdź konfigurację i uprawnienia katalogu przesyłanych plików WordPress.', 'zywienie-dla-zdrowia' ),
				esc_html__( 'Błąd aktywacji wtyczki', 'zywienie-dla-zdrowia' )
			);
		}

		$result = ( new ZFDZ_WordPress_Lab_Result_Storage() )->ensure_lab_result_directory();

		if ( is_wp_error( $result ) ) {
			wp_die(
				esc_html__( 'Nie udało się przygotować katalogu wyników badań. Sprawdź konfigurację i uprawnienia katalogu przesyłanych plików WordPress.', 'zywienie-dla-zdrowia' ),
				esc_html__( 'Błąd aktywacji wtyczki', 'zywienie-dla-zdrowia' )
			);
		}
	}
);
