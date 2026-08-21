<?php
/**
 * PHPUnit bootstrap for standalone unit tests.
 *
 * @package ZywienieDlaZdrowia
 */

$zfdz_tests_root = dirname( __DIR__ );

require_once $zfdz_tests_root . '/includes/class-zfdz-menu-document.php';
require_once $zfdz_tests_root . '/includes/class-zfdz-menu-filename-parse-result.php';
require_once $zfdz_tests_root . '/includes/class-zfdz-menu-filename-parser.php';
require_once $zfdz_tests_root . '/includes/class-zfdz-menu-scan-issue.php';
require_once $zfdz_tests_root . '/includes/class-zfdz-menu-period-group.php';
require_once $zfdz_tests_root . '/includes/class-zfdz-menu-scan-result.php';
require_once $zfdz_tests_root . '/includes/class-zfdz-menu-directory-scanner.php';
require_once $zfdz_tests_root . '/includes/class-zfdz-pdf-validation-result.php';
require_once $zfdz_tests_root . '/includes/class-zfdz-pdf-file-validator.php';
