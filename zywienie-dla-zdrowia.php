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
