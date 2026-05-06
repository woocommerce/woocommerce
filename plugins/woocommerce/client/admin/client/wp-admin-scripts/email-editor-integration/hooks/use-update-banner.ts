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

/**
 * Shape of the Tracks payload shared by every banner-fired event.
 *
 * RSM-145 standardizes these keys across the divergence-related events
 * so they can be correlated end-to-end (`viewed` → `applied`/`dismissed`).
 */
interface SharedTracksPayload {
	email_type: string;
	template_version_from: string;
	template_version_to: string | null;
	source_hash_from: string | null;
	source_hash_to: string | null;
	classification: string;
	was_backfilled: boolean;
	// `recordEvent` types its payload as `{ [k: string]: unknown }`;
	// the index signature lets us pass `SharedTracksPayload` directly.
	[ key: string ]: unknown;
}

interface SharedPayloadInputs {
	record: {
		slug?: unknown;
		meta?: Record< string, unknown >;
	} | null;
	summary: ChangeSummary | null;
}

/**
 * Build the shared Tracks payload from the current entity record + summary.
 *
 * Returns `null` when there's no record yet (eligibility hasn't fired,
 * so no event should fire either). Falsy `summary` is permitted: in
 * that case the version_to / source_hash_to fields are `null`.
 */
function buildSharedTracksPayload( {
	record,
	summary,
}: SharedPayloadInputs ): SharedTracksPayload | null {
	const meta = record?.meta;
	if ( ! meta ) {
		return null;
	}
	const slug = typeof record?.slug === 'string' ? record.slug : '';
	const versionFrom =
		typeof meta._wc_email_template_version === 'string'
			? ( meta._wc_email_template_version as string )
			: '';
	const sourceHashFrom =
		typeof meta._wc_email_template_source_hash === 'string'
			? ( meta._wc_email_template_source_hash as string )
			: null;
	const wasBackfilled =
		meta._wc_email_backfilled === true ||
		meta._wc_email_backfilled === '1' ||
		meta._wc_email_backfilled === 1;
	const classification =
		typeof meta._wc_email_template_status === 'string'
			? ( meta._wc_email_template_status as string )
			: '';

	return {
		email_type: slug,
		template_version_from: versionFrom,
		template_version_to: summary?.version_to ?? null,
		source_hash_from: sourceHashFrom,
		source_hash_to: summary?.source_hash_to ?? null,
		classification,
		was_backfilled: wasBackfilled,
	};
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
	dismiss: () => void;
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
			// eslint-disable-next-line @typescript-eslint/no-explicit-any
			const editor = ( selectFn as any )( 'core/editor' );
			const id =
				typeof editor?.getCurrentPostId?.() === 'number'
					? ( editor.getCurrentPostId() as number )
					: null;
			const type =
				typeof editor?.getCurrentPostType?.() === 'string'
					? ( editor.getCurrentPostType() as string )
					: null;
			const dirty = Boolean( editor?.isEditedPostDirty?.() );

			// `canUser` is `undefined` while the resolver is in flight; treat
			// undefined as permissive so we don't flicker the banner away
			// during the initial load. Only an explicit `false` denies.
			// eslint-disable-next-line @typescript-eslint/no-explicit-any
			const core = ( selectFn as any )( 'core' );
			const canUpdateRaw =
				id !== null
					? core?.canUser?.( 'update', {
							kind: 'postType',
							name: 'woo_email',
							id,
					  } )
					: undefined;
			const canUpdate = canUpdateRaw === false ? false : true;

			const dismissed =
				id !== null
					? Boolean(
							selectFn( STORE_NAME ).isUpdateBannerDismissedFor(
								id
							)
					  )
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
	} = useChangeSummary( postId, shouldRender );

	// Defensive: if the meta still says `core_updated_customized` but the
	// change-summary reports identical from/to versions, the meta is
	// stale (e.g. the upgrade routine already ran but the post entity
	// cache hasn't refreshed). Don't render — and warn so the
	// inconsistency shows up in dev consoles.
	const summaryShowsNoDiff =
		rawSummary !== null &&
		rawSummary.version_from === rawSummary.version_to;

	if ( summaryShowsNoDiff ) {
		// eslint-disable-next-line no-console
		console.warn(
			'[RSM-141] _wc_email_template_status is %s but change-summary reports version_from === version_to (%s). Treating as stale; banner will not render.',
			status,
			rawSummary?.version_to
		);
	}

	const finalShouldRender = shouldRender && ! summaryShowsNoDiff;
	const summary: ChangeSummary | null = finalShouldRender ? rawSummary : null;
	const hasConflicts = summary !== null && summary.copy_changes.length > 0;

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
		recordEvent( 'woocommerce_block_email_update_viewed', {
			...sharedPayload,
			viewed_from: 'editor_banner',
		} );
	}, [ finalShouldRender, postId, sharedPayload, markUpdateBannerViewed ] );

	const apply = useCallback( async () => {
		if ( ! sharedPayload || ! record ) {
			setApplyState( 'failed' );
			return;
		}
		// Compute `had_customizations` BEFORE the apply round-trip so
		// the comparison is against the pre-apply content, not the
		// merged content that core-data caches mid-flight.
		const contentRaw =
			( record as { content?: { raw?: string } } ).content?.raw ?? '';
		const hadCustomizations = sharedPayload.source_hash_from
			? ( await sha1Hex( contentRaw ) ) !== sharedPayload.source_hash_from
			: false;

		setApplyState( 'applying' );
		const res = await doApply( [] );
		if ( res ) {
			setApplyState( 'applied' );
			recordEvent( 'woocommerce_block_email_update_applied', {
				...sharedPayload,
				applied_from: 'editor_banner',
				auto_resolved: true,
				had_customizations: hadCustomizations,
			} );
		} else {
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
		recordEvent(
			'woocommerce_block_email_update_dismissed',
			sharedPayload
		);
	}, [ postId, sharedPayload, dismissUpdateBanner ] );

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
	};
}
