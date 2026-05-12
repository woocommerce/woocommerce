/**
 * External dependencies
 */
import { useCallback, useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Shape of a single block-level entry on the `added_blocks` /
 * `removed_blocks` arrays returned by the change-summary REST endpoint.
 */
export interface ChangeSummaryBlockEntry {
	/** Post-alias-normalized block name, e.g. `core/heading`. */
	name: string;
	/** Humanized label for display, e.g. `Heading`. */
	label: string;
	/** Index path through the parsed block tree on the relevant side. */
	path: Array< number | string >;
}

/**
 * Shape of a single text-conflict entry on the `copy_changes` array.
 */
export interface ChangeSummaryCopyChange {
	/** Humanized block label, e.g. `Paragraph`. */
	block: string;
	/** Merchant's current text, truncated to 120 chars. */
	before: string;
	/** Canonical core text, truncated to 120 chars. */
	after: string;
	/** 1-indexed position among matched blocks of this type. */
	occurrence: number;
	/** Total matched blocks of this type on the core side. */
	total: number;
	/** Post-side index path of the conflicting block. */
	path: Array< number | string >;
	/**
	 * Three-way path only. `true` = merchant unchanged, core changed
	 * (drawer can apply silently). `false` = both changed (true conflict).
	 * Absent on two-way fallback.
	 */
	auto_resolvable?: boolean;
}

/**
 * Shape of a single structural-change entry.
 */
export interface ChangeSummaryStructuralChange {
	/** `nest` or `reorder`. */
	kind: string;
	/** Pre-localized one-line description. */
	description: string;
	/** Index path of the affected block; absent for `kind: 'reorder'`. */
	path?: Array< number | string >;
}

/**
 * Full change-summary payload returned by
 * `GET /woocommerce-email-editor/v1/emails/{id}/change-summary`.
 */
export interface ChangeSummary {
	version_from: string;
	version_to: string;
	/** sha1 of the canonical core content; consumed by RSM-145 Tracks instrumentation. */
	source_hash_to: string;
	added_blocks: ChangeSummaryBlockEntry[];
	removed_blocks: ChangeSummaryBlockEntry[];
	copy_changes: ChangeSummaryCopyChange[];
	structural_changes: ChangeSummaryStructuralChange[];
	summary_lines: string[];
	is_fallback: boolean;
	cache_hit: boolean;
}

interface UseChangeSummaryResult {
	summary: ChangeSummary | null;
	isLoading: boolean;
	error: Error | null;
	refetch: () => void;
}

/**
 * Fetch the change-summary for a `woo_email` post and re-render when it
 * arrives. Aborts the in-flight request on unmount or post-id change.
 *
 * @param postId  The `woo_email` post ID.
 * @param enabled When false, no fetch is issued and `summary` stays null.
 *                Useful when the drawer is closed.
 */
export function useChangeSummary(
	postId: number | null,
	enabled: boolean
): UseChangeSummaryResult {
	const [ summary, setSummary ] = useState< ChangeSummary | null >( null );
	const [ isLoading, setIsLoading ] = useState< boolean >( false );
	const [ error, setError ] = useState< Error | null >( null );
	const [ refreshKey, setRefreshKey ] = useState< number >( 0 );

	const refetch = useCallback( () => {
		setRefreshKey( ( k ) => k + 1 );
	}, [] );

	useEffect( () => {
		if ( ! enabled || ! postId ) {
			// Reset state so the drawer never renders a previous template's
			// summary (or keeps Apply enabled) after the post-id changes or
			// the drawer is re-disabled.
			setSummary( null );
			setError( null );
			setIsLoading( false );
			return;
		}

		let cancelled = false;
		setSummary( null );
		setIsLoading( true );
		setError( null );

		apiFetch< ChangeSummary >( {
			path: `/woocommerce-email-editor/v1/emails/${ postId }/change-summary`,
		} )
			.then( ( res ) => {
				if ( ! cancelled ) {
					setSummary( res );
				}
			} )
			.catch( ( err: unknown ) => {
				if ( ! cancelled ) {
					setError(
						err instanceof Error ? err : new Error( String( err ) )
					);
				}
			} )
			.finally( () => {
				if ( ! cancelled ) {
					setIsLoading( false );
				}
			} );

		return () => {
			cancelled = true;
		};
	}, [ postId, enabled, refreshKey ] );

	return { summary, isLoading, error, refetch };
}
