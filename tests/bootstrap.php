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
require_once $zfdz_tests_root . '/includes/class-zfdz-lab-result-document.php';
require_once $zfdz_tests_root . '/includes/class-zfdz-lab-result-filename-parse-result.php';
require_once $zfdz_tests_root . '/includes/class-zfdz-lab-result-filename-parser.php';
require_once $zfdz_tests_root . '/includes/class-zfdz-lab-result-scan-issue.php';
require_once $zfdz_tests_root . '/includes/class-zfdz-lab-result-scan-result.php';
require_once $zfdz_tests_root . '/includes/class-zfdz-lab-result-directory-scanner.php';
require_once $zfdz_tests_root . '/includes/class-zfdz-menu-scan-issue.php';
require_once $zfdz_tests_root . '/includes/class-zfdz-menu-period-group.php';
require_once $zfdz_tests_root . '/includes/class-zfdz-lab-result-menu-association.php';
require_once $zfdz_tests_root . '/includes/class-zfdz-lab-result-menu-matcher.php';
require_once $zfdz_tests_root . '/includes/class-zfdz-lab-result-latest-selection.php';
require_once $zfdz_tests_root . '/includes/class-zfdz-lab-result-latest-selector.php';
require_once $zfdz_tests_root . '/includes/class-zfdz-lab-result-public-presentation-decision.php';
require_once $zfdz_tests_root . '/includes/class-zfdz-lab-result-public-presentation-policy.php';
require_once $zfdz_tests_root . '/includes/class-zfdz-menu-period-classification.php';
require_once $zfdz_tests_root . '/includes/class-zfdz-menu-period-classifier.php';
require_once $zfdz_tests_root . '/includes/class-zfdz-menu-scan-result.php';
require_once $zfdz_tests_root . '/includes/class-zfdz-menu-directory-scanner.php';
require_once $zfdz_tests_root . '/includes/class-zfdz-pdf-validation-result.php';
require_once $zfdz_tests_root . '/includes/class-zfdz-pdf-file-validator.php';
require_once $zfdz_tests_root . '/includes/class-zfdz-lab-result-catalog-result.php';
require_once $zfdz_tests_root . '/includes/class-zfdz-lab-result-catalog-builder.php';
require_once $zfdz_tests_root . '/includes/class-zfdz-menu-catalog-result.php';
require_once $zfdz_tests_root . '/includes/class-zfdz-wordpress-lab-result-catalog-service-result.php';
require_once $zfdz_tests_root . '/includes/class-zfdz-menu-catalog-builder.php';
