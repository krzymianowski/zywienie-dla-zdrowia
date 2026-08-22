<?php
/**
 * WordPress laboratory result public URL resolver.
 *
 * @package ZywienieDlaZdrowia
 */

/**
 * Resolves the exact public URL of a laboratory result presentation candidate.
 */
final class ZFDZ_WordPress_Lab_Result_Public_Url_Resolver {

	/**
	 * Returns a candidate URL or null for a result without a public candidate.
	 *
	 * @param ZFDZ_WordPress_Lab_Result_Public_Presentation_Result $result        Public presentation result.
	 * @param string                                               $directory_url Managed laboratory result directory URL.
	 * @throws LogicException When a candidate contains contradictory document metadata.
	 * @return string|null
	 */
	public function resolve(
		ZFDZ_WordPress_Lab_Result_Public_Presentation_Result $result,
		string $directory_url
	): ?string {
		if ( ZFDZ_WordPress_Lab_Result_Public_Presentation_Result::STATUS_CANDIDATE !== $result->get_status() ) {
			return null;
		}

		if ( ! $result->has_candidate() ) {
			throw new LogicException( 'A candidate presentation result must expose a candidate document.' );
		}

		$document = $result->get_document();

		if ( null === $document ) {
			throw new LogicException( 'A candidate presentation result must expose a candidate document.' );
		}

		$original_filename = $document->get_original_filename();

		if (
			str_contains( $original_filename, "\0" )
			|| str_contains( $original_filename, '/' )
			|| str_contains( $original_filename, '\\' )
		) {
			throw new LogicException( 'A candidate document filename must be one URL path segment.' );
		}

		return rtrim( $directory_url, '/' ) . '/' . rawurlencode( $original_filename );
	}
}
