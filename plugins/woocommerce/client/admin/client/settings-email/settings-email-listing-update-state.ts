/**
 * Internal dependencies
 */
import type { EmailType } from './settings-email-listing-slotfill';

/**
 * Numeric semver compare. Negative if `a < b`, zero if equal, positive
 * if `a > b`. Lightweight on purpose — template versions don't carry
 * pre-release / build metadata so we don't need full semver semantics.
 */
export function compareTemplateVersions( a: string, b: string ): number {
	const partsA = a.split( '.' ).map( ( s ) => parseInt( s, 10 ) || 0 );
	const partsB = b.split( '.' ).map( ( s ) => parseInt( s, 10 ) || 0 );
	const len = Math.max( partsA.length, partsB.length );
	for ( let i = 0; i < len; i++ ) {
		const diff = ( partsA[ i ] ?? 0 ) - ( partsB[ i ] ?? 0 );
		if ( diff !== 0 ) {
			return diff;
		}
	}
	return 0;
}

/**
 * Mirrors the canonical "update available" check from
 * {@see WCEmailTemplateDivergenceDetector}'s docblock:
 *
 *   $reviewed = (string) get_post_meta( $post_id, VERSION_META_KEY, true );
 *   $current  = (string) ( $sync_registry[ $email_id ]['version'] ?? '' );
 *   $show_indicator = $current !== '' && version_compare( $reviewed, $current, '<' );
 *
 * Status alone isn't enough — a post stays `core_updated_customized` after
 * a drawer apply where the merchant kept some customizations on purpose,
 * but they have reviewed the version. Both surfaces (the email list cell
 * and RSM-141's editor banner) gate on this combined check so they stay
 * in lockstep.
 *
 * Falls back to status-only when version metadata is missing — that
 * happens for legacy posts before the RSM-149 backfill, so we surface
 * the indicator rather than silently hide it.
 */
export function shouldShowReviewUpdate( post: EmailType ): boolean {
	if ( post.templateStatus !== 'core_updated_customized' ) {
		return false;
	}
	if ( ! post.templateVersion || ! post.currentVersion ) {
		return true;
	}
	return (
		compareTemplateVersions( post.templateVersion, post.currentVersion ) < 0
	);
}
