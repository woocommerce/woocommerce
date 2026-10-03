/**
 * External dependencies
 */
import {
	useCallback,
	useEffect,
	useMemo,
	useRef,
	useState,
} from '@wordpress/element';
import { select, useDispatch, useSelect } from '@wordpress/data';
import { useEntityRecord } from '@wordpress/core-data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { useChangeSummary, type ChangeSummary } from './use-change-summary';
import { useApplyUpdate } from './use-apply-update';
import { STORE_NAME } from '../store';
import {
	buildSharedTracksPayload,
	APPLIED_FROM_EDITOR_BANNER,
	VIEWED_FROM_EDITOR_BANNER,
	type SharedTracksPayload,
} from '../tracks/build-shared-payload';

/**
 * Numeric semver compare. Returns negative if `a < b`, zero if equal, positive
 * if `a > b`. Lightweight implementation — template versions don't carry
 * pre-release / build metadata so we don't need full semver semantics.
 */
function compareTemplateVersions( a: string, b: string ): number {
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
 * Compute sha1(input) as a lowercase hex string. Used to detect whether
 * the merchant has customized the post body (`had_customizations`) by
 * comparing against the `source_hash_from` recorded at upgrade time.
 */
async function sha1Hex( input: string ): Promise< string > {
	const buf = new TextEncoder().encode( input );
	const digest = await crypto.subtle.digest( 'SHA-1', buf );
	return Array.from( new Uint8Array( digest ) )
		.map( ( b ) => b.toString( 16 ).padStart( 2, '0' ) )
		.join( '' );
}

/**
 * Apply state machine values surfaced by `useUpdateBanner`.
 *
 * `idle`     — no apply has been initiated, or the previous apply settled
 *              and the banner is back to its default surface.
 * `applying` — `/apply` is in flight.
 * `applied`  — `/apply` succeeded; the editor canvas now reflects the
 *              merged content.
 * `failed`   — `/apply` failed (network, permission, conflict, …).
 */
export type ApplyState = 'idle' | 'applying' | 'applied' | 'failed';

interface UseUpdateBannerResult {
	shouldRender: boolean;
	summary: ChangeSummary | null;
	isLoadingSummary: boolean;
	summaryError: Error | null;
	applyState: ApplyState;
	canApply: boolean;
	canReview: boolean;
	disabledReason: 'dirty' | 'read_only' | 'has_conflicts' | null;
	hasConflicts: boolean;
	expanded: boolean;
	toggleExpanded: () => void;
	apply: () => Promise< void >;
	openReview: () => void;
	/**
	 * User-initiated dismiss. Removes the banner for this session and
	 * fires the `_dismissed` Tracks event (spec §9.2).
	 */
	dismiss: () => void;
	/**
	 * Non-user dismiss path used by the success morph (auto-dismiss timer
	 * and the success-state × click). Removes the banner without firing
	 * the `_dismissed` Tracks event — spec §9.2 explicitly excludes the
	 * post-success unmount from the dismissed-event surface.
	 */
	autoDismiss: () => void;
}

/**
 * The single template-divergence status that should surface the banner.
 * Anything else (`in_sync`, `core_updated_uncustomized`, null, unknown
 * future values) is treated as "do nothing".
 */
const VALID_STATUS = 'core_updated_customized' as const;

/**
 * Glue hook for the "update available" editor banner (RSM-141).
 *
 * Reads the editor's current post + integration store state, decides
 * whether the banner should render, and exposes the actions the
 * `<UpdateBannerPlugin>` component needs to drive review / apply /
 * dismiss interactions.
 *
 * Owns:
 *   - eligibility predicate (status / postId / postType / dismiss state)
 *   - change-summary fetch + conflict derivation
 *   - apply state machine (`idle → applying → applied|failed`)
 *   - dirty / read-only / has_conflicts gates
 *   - per-(postId, version_to) dedup of the `_viewed` Tracks event
 *   - `_viewed` / `_dismissed` / `_applied` Tracks event firing
 */
export function useUpdateBanner(): UseUpdateBannerResult {
	// One `useSelect` lambda computes everything that depends on store
	// reads. Keeping the shape flat (and the property names matching the
	// test mock) means the lambda's contract is obvious from one
	// glance — and the test can bypass the lambda entirely.
	const { postId, postType, isDirty, canUserUpdate, isDismissed } = useSelect(
		( selectFn ) => {
			// We pass string store keys here because importing `store as
			// editorStore` from `@wordpress/editor` pulls in a transitive
			// module that Jest can't resolve in this package's test setup.
			// Stable WP store keys: `core/editor`, `core` (core-data).
			const { getCurrentPostId, getCurrentPostType, isEditedPostDirty } =
				selectFn( 'core/editor' );
			const { canUser } = selectFn( 'core' );
			const { isUpdateBannerDismissedFor } = selectFn( STORE_NAME );

			const rawId = getCurrentPostId();
			const id = typeof rawId === 'number' ? rawId : null;
			const type = getCurrentPostType() ?? null;
			const dirty = Boolean( isEditedPostDirty() );

			// `canUser` is `undefined` while the resolver is in flight; treat
			// undefined as permissive so we don't flicker the banner away
			// during the initial load. Only an explicit `false` denies.
			const canUpdateRaw =
				id !== null
					? canUser( 'update', {
							kind: 'postType',
							name: 'woo_email',
							id,
					  } )
					: undefined;
			const canUpdate = canUpdateRaw === false ? false : true;

			const dismissed =
				id !== null
					? Boolean( isUpdateBannerDismissedFor( id ) )
					: false;

			return {
				postId: id,
				postType: type,
				isDirty: dirty,
				canUserUpdate: canUpdate,
				isDismissed: dismissed,
			};
		},
		[]
	);

	// `useEntityRecord` always wants a non-null id; pass a harmless `0`
	// when there's no post yet and ignore the result in that case.
	const { record } = useEntityRecord(
		'postType',
		'woo_email',
		postId ?? 0
	) as {
		record: {
			slug?: unknown;
			meta?: Record< string, unknown >;
			content?: { raw?: string };
		} | null;
	};

	const meta = postId !== null ? record?.meta ?? null : null;
	const status =
		meta && typeof meta._wc_email_template_status === 'string'
			? ( meta._wc_email_template_status as string )
			: null;

	const isEligibleByStatus = status === VALID_STATUS;
	const isCorrectPostType = postType === 'woo_email';
	const shouldRender =
		postId !== null &&
		isCorrectPostType &&
		isEligibleByStatus &&
		! isDismissed;

	// Fetch the change-summary only once we've decided the banner is
	// eligible to render — avoids a needless network call on every
	// editor load.
	const {
		summary: rawSummary,
		isLoading: isLoadingSummary,
		error: summaryError,
		refetch: refetchSummary,
	} = useChangeSummary( postId, shouldRender );

	// When the review drawer closes — typically right after a drawer-driven
	// `/apply` succeeds — refresh the change-summary so its `version_from`
	// reflects the merchant's now-bumped meta. The canonical
	// `summaryShowsReviewed` check below then sees `version_from >= version_to`
	// and unmounts the banner. A merchant who just opens and closes the
	// drawer without applying triggers an extra fetch — acceptable cost.
	const isReviewDrawerOpen = useSelect(
		( selectFn ) => selectFn( STORE_NAME ).isReviewDrawerOpen(),
		[]
	);
	const prevDrawerOpenRef = useRef< boolean >( false );
	useEffect( () => {
		if ( prevDrawerOpenRef.current && ! isReviewDrawerOpen ) {
			refetchSummary();
		}
		prevDrawerOpenRef.current = isReviewDrawerOpen;
	}, [ isReviewDrawerOpen, refetchSummary ] );

	// Cache the most recent non-null summary so the banner doesn't briefly
	// flip variants while a refetch is in flight (which clears `rawSummary`
	// to null inside `useChangeSummary` before the new response arrives).
	const lastNonNullSummaryRef = useRef< ChangeSummary | null >( null );
	if ( rawSummary !== null ) {
		lastNonNullSummaryRef.current = rawSummary;
	}
	const effectiveSummary: ChangeSummary | null =
		rawSummary ?? lastNonNullSummaryRef.current;

	// Canonical "has the merchant reviewed this version?" check, mirroring
	// the detector docblock's `version_compare( $reviewed, $current, '<' )`
	// formula. When the change-summary reports `version_from >= version_to`
	// the merchant's stored version is at-or-above the registry's current,
	// so they've reviewed this release — even if status stays
	// `core_updated_customized` because they kept some customizations on
	// purpose during a drawer apply. Hide the indicator.
	const summaryShowsReviewed =
		effectiveSummary !== null &&
		effectiveSummary.version_from !== '' &&
		effectiveSummary.version_to !== '' &&
		compareTemplateVersions(
			effectiveSummary.version_from,
			effectiveSummary.version_to
		) >= 0;

	// Defensive: even when version-compare says the merchant hasn't reviewed
	// yet, hide the banner if there's nothing actually different — sending
	// the merchant into a drawer that says `Apply (0)` is a dead end. This
	// catches stale-status scenarios where meta says `core_updated_customized`
	// but the post content matches canonical core (test fixtures, race
	// conditions during core upgrade, manual meta edits).
	const summaryShowsNoChanges =
		effectiveSummary !== null &&
		! effectiveSummary.is_fallback &&
		effectiveSummary.summary_lines.length === 0 &&
		effectiveSummary.added_blocks.length === 0 &&
		effectiveSummary.removed_blocks.length === 0 &&
		effectiveSummary.copy_changes.length === 0 &&
		effectiveSummary.structural_changes.length === 0;

	const finalShouldRender =
		shouldRender && ! summaryShowsReviewed && ! summaryShowsNoChanges;
	const summary: ChangeSummary | null = finalShouldRender
		? effectiveSummary
		: null;
	// Only `auto_resolvable !== true` counts as a true conflict that blocks
	// Apply. `undefined` is the two-way fallback (no base) and stays gated.
	const hasConflicts =
		summary !== null &&
		summary.copy_changes.some( ( cc ) => cc.auto_resolvable !== true );

	// `@wordpress/data`'s typed dispatch surface isn't exhaustive for
	// custom stores; cast loosely to grab our integration-store actions.
	const integrationDispatch = useDispatch( STORE_NAME ) as unknown as {
		dismissUpdateBanner: ( id: number ) => void;
		clearDismissedForPost: ( id: number ) => void;
		markUpdateBannerViewed: ( id: number, versionTo: string ) => void;
		openReviewDrawer: () => void;
	};
	const {
		dismissUpdateBanner,
		clearDismissedForPost,
		markUpdateBannerViewed,
		openReviewDrawer,
	} = integrationDispatch;

	// Build the shared Tracks payload once per render of the eligible
	// banner; reused by `_viewed`, `_dismissed`, `_applied`.
	const sharedPayload = useMemo< SharedTracksPayload | null >(
		() =>
			finalShouldRender
				? buildSharedTracksPayload( { record, summary } )
				: null,
		// `record` is the upstream entity reference; `summary` is the
		// fetched change-summary. Both are stable across renders unless
		// the underlying data actually changed.
		[ finalShouldRender, record, summary ]
	);

	const { apply: doApply } = useApplyUpdate( postId, {
		// The banner surfaces its own failure state via `applyState`;
		// suppress the global snackbar to avoid double-error UI.
		suppressSnackbarOnError: true,
	} );

	const [ applyState, setApplyState ] = useState< ApplyState >( 'idle' );

	const canApply =
		finalShouldRender &&
		canUserUpdate &&
		! isDirty &&
		! hasConflicts &&
		applyState === 'idle';

	const canReview =
		finalShouldRender && canUserUpdate && applyState === 'idle';

	let disabledReason: UseUpdateBannerResult[ 'disabledReason' ] = null;
	if ( ! canUserUpdate ) {
		disabledReason = 'read_only';
	} else if ( isDirty ) {
		disabledReason = 'dirty';
	} else if ( hasConflicts ) {
		disabledReason = 'has_conflicts';
	}

	const [ expanded, setExpanded ] = useState< boolean >( false );
	const toggleExpanded = useCallback( () => setExpanded( ( v ) => ! v ), [] );

	// Clear the previous post's dismiss flag when the editor swaps to a
	// different `woo_email` post, so the user dismissing the banner on
	// post A doesn't keep it suppressed on post B.
	const prevPostIdRef = useRef< number | null >( null );
	useEffect( () => {
		const prev = prevPostIdRef.current;
		if ( prev !== null && prev !== postId ) {
			clearDismissedForPost( prev );
		}
		prevPostIdRef.current = postId;
	}, [ postId, clearDismissedForPost ] );

	// Fire the `_viewed` Tracks event exactly once per
	// (postId, version_to) pair — store-backed dedup survives
	// re-renders and unmount/remount cycles within a session.
	useEffect( () => {
		if (
			! finalShouldRender ||
			! sharedPayload ||
			postId === null ||
			sharedPayload.template_version_to === null
		) {
			return;
		}
		// Imperative consultation (not a `useSelect` lambda dep) so the
		// effect doesn't re-run just because the dedup set mutates.
		const integration = select( STORE_NAME ) as unknown as {
			wasUpdateBannerViewedFor: ( id: number, v: string ) => boolean;
		};
		if (
			integration.wasUpdateBannerViewedFor(
				postId,
				sharedPayload.template_version_to
			)
		) {
			return;
		}
		markUpdateBannerViewed( postId, sharedPayload.template_version_to );
		recordEvent( 'block_email_update_viewed', {
			...sharedPayload,
			viewed_from: VIEWED_FROM_EDITOR_BANNER,
		} );
	}, [ finalShouldRender, postId, sharedPayload, markUpdateBannerViewed ] );

	const apply = useCallback( async () => {
		if ( ! sharedPayload || ! record ) {
			setApplyState( 'failed' );
			return;
		}
		// Flip to `applying` synchronously so the UI reflects the click
		// even before the (fast) sha1 microtask + the apply round-trip.
		setApplyState( 'applying' );
		try {
			// Compute `had_customizations` BEFORE the apply round-trip so
			// the comparison is against the pre-apply content, not the
			// merged content that core-data caches mid-flight.
			//
			// `source_hash_from` is not part of the shared wire payload
			// (RSM-145 §15.4) — read it directly from the entity record's
			// meta and use it only as an in-memory comparison input.
			const contentRaw =
				( record as { content?: { raw?: string } } ).content?.raw ?? '';
			const sourceHashFrom =
				record?.meta &&
				typeof record.meta._wc_email_template_source_hash === 'string'
					? ( record.meta._wc_email_template_source_hash as string )
					: '';
			const hadCustomizations = sourceHashFrom
				? ( await sha1Hex( contentRaw ) ) !== sourceHashFrom
				: false;

			const res = await doApply( [] );
			if ( res ) {
				setApplyState( 'applied' );
				recordEvent( 'block_email_update_applied', {
					...sharedPayload,
					applied_from: APPLIED_FROM_EDITOR_BANNER,
					auto_resolved: true,
					had_customizations: hadCustomizations,
				} );
			} else {
				setApplyState( 'failed' );
			}
		} catch {
			// `doApply` already swallows fetch errors, but `sha1Hex` (Web
			// Crypto) and any future async work in here can throw — make
			// sure the banner can recover instead of getting stuck in
			// `applying`.
			setApplyState( 'failed' );
		}
	}, [ doApply, sharedPayload, record ] );

	const openReview = useCallback( () => {
		openReviewDrawer();
	}, [ openReviewDrawer ] );

	const dismiss = useCallback( () => {
		if ( postId === null || ! sharedPayload ) {
			return;
		}
		dismissUpdateBanner( postId );
		recordEvent( 'block_email_update_dismissed', sharedPayload );
	}, [ postId, sharedPayload, dismissUpdateBanner ] );

	// Auto-dismiss path — used by the success morph (timer + ×). Mirrors
	// `dismiss` minus the Tracks event; spec §9.2 excludes the success
	// auto-dismiss from the `_dismissed` event surface.
	const autoDismiss = useCallback( () => {
		if ( postId === null ) {
			return;
		}
		dismissUpdateBanner( postId );
	}, [ postId, dismissUpdateBanner ] );

	return {
		shouldRender: finalShouldRender,
		summary,
		isLoadingSummary,
		summaryError,
		applyState,
		canApply,
		canReview,
		disabledReason,
		hasConflicts,
		expanded,
		toggleExpanded,
		apply,
		openReview,
		dismiss,
		autoDismiss,
	};
}
