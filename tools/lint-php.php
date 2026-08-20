<?php
/**
 * Recursively checks the syntax of project PHP files.
 *
 * @package ZywienieDlaZdrowia
 */

$zfdz_lint_php_files = static function (): int {
	$root_directory       = dirname( __DIR__ );
	$excluded_directories = array( '.git', '.phpunit.cache', 'vendor' );
	$directory_iterator   = new RecursiveDirectoryIterator(
		$root_directory,
		FilesystemIterator::SKIP_DOTS
	);
	$filter_iterator      = new RecursiveCallbackFilterIterator(
		$directory_iterator,
		static function ( SplFileInfo $file ) use ( $excluded_directories ): bool {
			return ! $file->isDir() || ! in_array( $file->getFilename(), $excluded_directories, true );
		}
	);
	$iterator             = new RecursiveIteratorIterator( $filter_iterator );
	$php_files            = array();

	foreach ( $iterator as $file ) {
		if ( $file->isFile() && 'php' === strtolower( $file->getExtension() ) ) {
			$php_files[] = $file->getPathname();
		}
	}

	sort( $php_files, SORT_STRING );

	$has_errors = false;

	foreach ( $php_files as $php_file ) {
		$relative_path = str_replace(
			DIRECTORY_SEPARATOR,
			'/',
			substr( $php_file, strlen( $root_directory ) + 1 )
		);

		try {
			$file     = new SplFileObject( $php_file, 'r' );
			$contents = '';

			while ( ! $file->eof() ) {
				$contents .= $file->fgets();
			}

			token_get_all( $contents, TOKEN_PARSE );
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI-only development output.
			printf( "No syntax errors detected in %s\n", $relative_path );
		} catch ( Throwable $exception ) {
			$has_errors = true;
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI-only STDERR output.
			fwrite(
				STDERR,
				sprintf( "Syntax error in %s: %s\n", $relative_path, $exception->getMessage() )
			);
		}
	}

	printf( "Checked syntax of %d project PHP files.\n", count( $php_files ) );

	return $has_errors ? 1 : 0;
};

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Integer process exit code, not output.
exit( $zfdz_lint_php_files() );
