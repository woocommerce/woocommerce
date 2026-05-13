/**
 * External dependencies
 */
import { act, renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { useUpdateBanner } from '../hooks/use-update-banner';

// jsdom ships `crypto` (Node global) but not `crypto.subtle`, and
// doesn't expose `TextEncoder` either. The hook's `sha1Hex` helper
// relies on both. The actual hash value doesn't matter for these
// tests — `had_customizations` is asserted with `expect.any(Boolean)` —
// so a no-op mock that resolves to a fixed digest is sufficient.
if (
	typeof ( globalThis as { TextEncoder?: unknown } ).TextEncoder ===
	'undefined'
) {
	// eslint-disable-next-line @typescript-eslint/no-var-requires
	const { TextEncoder: NodeTextEncoder } = require( 'util' );
	(
		globalThis as unknown as { TextEncoder: typeof TextEncoder }
	 ).TextEncoder = NodeTextEncoder;
}
if ( ! ( globalThis as { crypto?: { subtle?: unknown } } ).crypto?.subtle ) {
	Object.defineProperty( globalThis, 'crypto', {
		value: {
			...( globalThis as { crypto?: object } ).crypto,
			subtle: {
				digest: jest
					.fn()
					.mockResolvedValue( new Uint8Array( 20 ).buffer ),
			},
		},
		configurable: true,
		writable: true,
	} );
}

// ---- recordEvent mock (Tracks) -------------------------------------------
const recordEventMock = jest.fn();
jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: ( ...args: unknown[] ) => recordEventMock( ...args ),
} ) );

// ---- @wordpress/data mock ------------------------------------------------
//
// `useSelect` is mocked to return whatever the per-test `selectShape`
// describes. The hook's `useSelect` lambda is bypassed; we hand back the
// flat shape directly (matches the lambda's computed return type).
//
// `useDispatch` returns spies for every action the hook may dispatch
// against the integration store. `select` (imperative) is used by the
// hook to consult the viewed-pair dedup selector at the moment of firing
// `_viewed`.
const dispatchMocks = {
	dismissUpdateBanner: jest.fn(),
	clearDismissedForPost: jest.fn(),
	markUpdateBannerViewed: jest.fn(),
	openReviewDrawer: jest.fn(),
};
let wasViewedReturn = false;
let useSelectReturn: Record< string, unknown > = {};
let useEntityRecordReturn: {
	record: {
		slug?: string;
		meta?: Record< string, unknown >;
		content?: { raw?: string };
	} | null;
} = { record: null };

jest.mock( '@wordpress/data', () => ( {
	// `useSelect` is called by the hook with a lambda + deps; we ignore both
	// and return the per-test flat shape directly.
	useSelect: () => useSelectReturn,
	useDispatch: () => dispatchMocks,
	select: () => ( {
		wasUpdateBannerViewedFor: () => wasViewedReturn,
	} ),
} ) );

// Stubs for store-shaped imports the hook makes from `@wordpress/core-data`
// and the project's own modules.
jest.mock( '@wordpress/core-data', () => ( {
	store: 'core',
	useEntityRecord: () => useEntityRecordReturn,
} ) );

// Default summary mock: returns whatever `useChangeSummaryReturn` says.
let useChangeSummaryReturn: {
	summary: unknown;
	isLoading: boolean;
	error: Error | null;
	refetch: () => void;
} = {
	summary: null,
	isLoading: false,
	error: null,
	refetch: jest.fn(),
};
jest.mock( '../hooks/use-change-summary', () => ( {
	useChangeSummary: () => useChangeSummaryReturn,
} ) );

// useApplyUpdate mock; per-test override available via `applyMock`.
let applyMock: jest.Mock = jest.fn().mockResolvedValue( {
	merged_content: 'merged',
	revision_id: 'rev-1',
	version_to: '9.5',
	status: 'applied',
	structural_skipped: false,
	aliases_migrated: [],
} );
jest.mock( '../hooks/use-apply-update', () => ( {
	useApplyUpdate: () => ( {
		apply: ( ...args: unknown[] ) => applyMock( ...args ),
		isApplying: false,
	} ),
} ) );

// ---- Per-test setup helpers ---------------------------------------------

interface SelectShape {
	postId: number | null;
	postType: string | null;
	isDirty: boolean;
	canUserUpdate: boolean;
	isDismissed: boolean;
	wasViewed: boolean;
}

const defaultSelectShape: SelectShape = {
	postId: 42,
	postType: 'woo_email',
	isDirty: false,
	canUserUpdate: true,
	isDismissed: false,
	wasViewed: false,
};

const defaultRecord = {
	slug: 'customer_processing_order',
	meta: {
		_wc_email_template_status: 'core_updated_customized',
		_wc_email_template_version: '9.4',
		_wc_email_template_source_hash: 'abc123',
		_wc_email_backfilled: false,
	},
	content: { raw: '<!-- wp:paragraph --><p>hi</p><!-- /wp:paragraph -->' },
};

function summaryFixture( overrides: Record< string, unknown > = {} ) {
	return {
		version_from: '9.4',
		version_to: '9.5',
		source_hash_to: 'def456',
		added_blocks: [],
		removed_blocks: [],
		copy_changes: [],
		structural_changes: [],
		summary_lines: [ 'Header logo updated.', 'Footer text refreshed.' ],
		is_fallback: false,
		cache_hit: false,
		...overrides,
	};
}

function setUpMocks(
	overrides: {
		selectShape?: Partial< SelectShape >;
		record?: typeof defaultRecord | null | Partial< typeof defaultRecord >;
		recordMeta?: Record< string, unknown >;
		summary?: unknown;
		summaryLoading?: boolean;
		summaryError?: Error | null;
		wasViewed?: boolean;
		apply?: jest.Mock;
	} = {}
) {
	useSelectReturn = {
		...defaultSelectShape,
		...( overrides.selectShape ?? {} ),
	};

	if ( overrides.record === null ) {
		useEntityRecordReturn = { record: null };
	} else {
		const baseRecord = {
			...defaultRecord,
			...( overrides.record ?? {} ),
		};
		if ( overrides.recordMeta ) {
			baseRecord.meta = { ...baseRecord.meta, ...overrides.recordMeta };
		}
		useEntityRecordReturn = { record: baseRecord };
	}

	useChangeSummaryReturn = {
		summary: overrides.summary !== undefined ? overrides.summary : null,
		isLoading: overrides.summaryLoading ?? false,
		error: overrides.summaryError ?? null,
		refetch: jest.fn(),
	};

	wasViewedReturn = overrides.wasViewed ?? false;

	if ( overrides.apply ) {
		applyMock = overrides.apply;
	} else {
		applyMock = jest.fn().mockResolvedValue( {
			merged_content: 'merged',
			revision_id: 'rev-1',
			version_to: '9.5',
			status: 'applied',
			structural_skipped: false,
			aliases_migrated: [],
		} );
	}
}

beforeEach( () => {
	dispatchMocks.dismissUpdateBanner.mockClear();
	dispatchMocks.clearDismissedForPost.mockClear();
	dispatchMocks.markUpdateBannerViewed.mockClear();
	dispatchMocks.openReviewDrawer.mockClear();
	recordEventMock.mockClear();
	setUpMocks();
} );

// ==========================================================================
// Sub-phase 6a — eligibility predicate
// ==========================================================================
describe( 'useUpdateBanner — eligibility (6a)', () => {
	it.each( [
		[ 'in_sync' ],
		[ 'core_updated_uncustomized' ],
		[ null ],
		[ 'something_unexpected' ],
	] )(
		'shouldRender is false when status is %p',
		( status: string | null ) => {
			setUpMocks( {
				recordMeta: { _wc_email_template_status: status },
			} );
			const { result } = renderHook( () => useUpdateBanner() );
			expect( result.current.shouldRender ).toBe( false );
		}
	);

	it( 'shouldRender is true when status is core_updated_customized and all gates pass', () => {
		setUpMocks();
		const { result } = renderHook( () => useUpdateBanner() );
		expect( result.current.shouldRender ).toBe( true );
	} );

	it( 'shouldRender is false when postId is null', () => {
		setUpMocks( { selectShape: { postId: null } } );
		const { result } = renderHook( () => useUpdateBanner() );
		expect( result.current.shouldRender ).toBe( false );
	} );

	it( 'shouldRender is false when postType is not woo_email', () => {
		setUpMocks( { selectShape: { postType: 'post' } } );
		const { result } = renderHook( () => useUpdateBanner() );
		expect( result.current.shouldRender ).toBe( false );
	} );

	it( 'shouldRender flips to false when isDismissed is true', () => {
		setUpMocks( { selectShape: { isDismissed: true } } );
		const { result } = renderHook( () => useUpdateBanner() );
		expect( result.current.shouldRender ).toBe( false );
	} );
} );

// ==========================================================================
// Sub-phase 6b — change-summary integration + conflict gate
// ==========================================================================
describe( 'useUpdateBanner — change summary + conflicts (6b)', () => {
	it( 'exposes the summary returned by useChangeSummary', () => {
		const fixture = summaryFixture();
		setUpMocks( { summary: fixture } );
		const { result } = renderHook( () => useUpdateBanner() );
		expect( result.current.summary ).toEqual( fixture );
	} );

	it( 'hasConflicts is true when copy_changes is non-empty', () => {
		setUpMocks( {
			summary: summaryFixture( {
				copy_changes: [
					{
						block: 'Paragraph',
						before: 'old',
						after: 'new',
						occurrence: 1,
						total: 1,
						path: [ 0 ],
					},
				],
			} ),
		} );
		const { result } = renderHook( () => useUpdateBanner() );
		expect( result.current.hasConflicts ).toBe( true );
	} );

	it( 'hasConflicts stays false when only structural_changes are present', () => {
		setUpMocks( {
			summary: summaryFixture( {
				structural_changes: [
					{
						kind: 'reorder',
						description: 'Two top-level blocks reordered.',
					},
				],
			} ),
		} );
		const { result } = renderHook( () => useUpdateBanner() );
		expect( result.current.hasConflicts ).toBe( false );
	} );

	it( 'shouldRender flips to false when summary reports merchant reviewed this version (version_from >= version_to)', () => {
		// Canonical detector check: when stored version >= current registry
		// version the merchant has already reviewed this release — hide the
		// indicator even if status is still customized (which happens after
		// a drawer apply that kept any customizations).
		setUpMocks( {
			summary: summaryFixture( {
				version_from: '10.7.0',
				version_to: '10.7.0',
			} ),
		} );
		const { result } = renderHook( () => useUpdateBanner() );
		expect( result.current.shouldRender ).toBe( false );
	} );

	it( 'shouldRender stays true when merchant version is older than current (version_from < version_to)', () => {
		setUpMocks( {
			summary: summaryFixture( {
				version_from: '10.6.0',
				version_to: '10.7.0',
			} ),
		} );
		const { result } = renderHook( () => useUpdateBanner() );
		expect( result.current.shouldRender ).toBe( true );
	} );

	it( 'shouldRender flips to false when summary reports no real diff (status stale despite older version)', () => {
		// Defensive guard: even when version-compare says merchant hasn't
		// reviewed yet, hide the banner if the summary has no real changes.
		// Avoids surfacing a "Review update" → drawer with `Apply (0)` dead
		// end (test fixtures or stale meta can produce this state).
		setUpMocks( {
			summary: summaryFixture( {
				version_from: '9.4.0-test',
				version_to: '10.7.0',
				summary_lines: [],
				added_blocks: [],
				removed_blocks: [],
				copy_changes: [],
				structural_changes: [],
				is_fallback: false,
			} ),
		} );
		const { result } = renderHook( () => useUpdateBanner() );
		expect( result.current.shouldRender ).toBe( false );
	} );
} );

// ==========================================================================
// Sub-phase 6c — apply state machine + dirty/read-only gates + dispatchers
// ==========================================================================
describe( 'useUpdateBanner — apply / gates / dispatchers (6c)', () => {
	it( 'apply transitions idle -> applying -> applied on success', async () => {
		// `applyResolve` lets us hold the in-flight promise so we can
		// observe the intermediate `applying` state.
		let applyResolve: ( v: unknown ) => void = () => {};
		const apply = jest.fn(
			() =>
				new Promise( ( resolve ) => {
					applyResolve = resolve;
				} )
		);
		setUpMocks( {
			summary: summaryFixture(),
			apply: apply as unknown as jest.Mock,
		} );

		const { result } = renderHook( () => useUpdateBanner() );
		expect( result.current.applyState ).toBe( 'idle' );

		let applyPromise: Promise< void > = Promise.resolve();
		// `apply()` flips to `'applying'` synchronously before the sha1
		// microtask, so a single microtask flush is enough.
		await act( async () => {
			applyPromise = result.current.apply();
			await Promise.resolve();
		} );
		// In flight.
		expect( result.current.applyState ).toBe( 'applying' );

		await act( async () => {
			applyResolve( {
				merged_content: 'merged',
				revision_id: 'rev-1',
				version_to: '9.5',
				status: 'applied',
				structural_skipped: false,
				aliases_migrated: [],
			} );
			await applyPromise;
		} );

		expect( result.current.applyState ).toBe( 'applied' );
	} );

	it( 'apply transitions idle -> applying -> failed when doApply resolves with null (falsy result treated as failure)', async () => {
		setUpMocks( {
			summary: summaryFixture(),
			apply: jest.fn().mockResolvedValue( null ),
		} );

		const { result } = renderHook( () => useUpdateBanner() );

		await act( async () => {
			await result.current.apply();
		} );

		expect( result.current.applyState ).toBe( 'failed' );
	} );

	it( 'apply transitions to failed when doApply rejects (so banner can recover)', async () => {
		setUpMocks( {
			summary: summaryFixture(),
			apply: jest.fn().mockRejectedValue( new Error( 'network down' ) ),
		} );

		const { result } = renderHook( () => useUpdateBanner() );

		await act( async () => {
			await result.current.apply();
		} );

		expect( result.current.applyState ).toBe( 'failed' );
	} );

	it( 'canApply is false and disabledReason is "dirty" when post is dirty; canReview is true', () => {
		setUpMocks( {
			summary: summaryFixture(),
			selectShape: { isDirty: true },
		} );
		const { result } = renderHook( () => useUpdateBanner() );
		expect( result.current.canApply ).toBe( false );
		expect( result.current.canReview ).toBe( true );
		expect( result.current.disabledReason ).toBe( 'dirty' );
	} );

	it( 'canApply and canReview are both false when canUserUpdate is false; disabledReason is "read_only"', () => {
		setUpMocks( {
			summary: summaryFixture(),
			selectShape: { canUserUpdate: false },
		} );
		const { result } = renderHook( () => useUpdateBanner() );
		expect( result.current.canApply ).toBe( false );
		expect( result.current.canReview ).toBe( false );
		expect( result.current.disabledReason ).toBe( 'read_only' );
	} );

	it( 'canApply is false and canReview is true when conflicts exist; disabledReason is "has_conflicts"', () => {
		setUpMocks( {
			summary: summaryFixture( {
				copy_changes: [
					{
						block: 'Paragraph',
						before: 'old',
						after: 'new',
						occurrence: 1,
						total: 1,
						path: [ 0 ],
					},
				],
			} ),
		} );
		const { result } = renderHook( () => useUpdateBanner() );
		expect( result.current.canApply ).toBe( false );
		expect( result.current.canReview ).toBe( true );
		expect( result.current.disabledReason ).toBe( 'has_conflicts' );
	} );

	it( 'dismiss() dispatches dismissUpdateBanner with the postId', () => {
		setUpMocks( { summary: summaryFixture() } );
		const { result } = renderHook( () => useUpdateBanner() );
		act( () => result.current.dismiss() );
		expect( dispatchMocks.dismissUpdateBanner ).toHaveBeenCalledWith( 42 );
	} );

	it( 'openReview() dispatches openReviewDrawer', () => {
		setUpMocks( { summary: summaryFixture() } );
		const { result } = renderHook( () => useUpdateBanner() );
		act( () => result.current.openReview() );
		expect( dispatchMocks.openReviewDrawer ).toHaveBeenCalledTimes( 1 );
	} );
} );

// ==========================================================================
// Sub-phase 6d — Tracks events
// ==========================================================================
describe( 'useUpdateBanner — Tracks (6d)', () => {
	const sharedPayloadMatcher = {
		email_id: 'customer_processing_order',
		template_version_from: '9.4',
		template_version_to: '9.5',
		source_hash_to: 'def456',
		classification: 'core_updated_customized',
		was_backfilled: false,
	};

	beforeEach( () => {
		recordEventMock.mockClear();
	} );

	it( '_viewed fires on first eligible render with the shared payload', () => {
		setUpMocks( { summary: summaryFixture() } );
		renderHook( () => useUpdateBanner() );
		expect( recordEventMock ).toHaveBeenCalledWith(
			'block_email_update_viewed',
			expect.objectContaining( {
				...sharedPayloadMatcher,
				viewed_from: 'editor_banner',
			} )
		);
	} );

	it( '_viewed does NOT fire when the dedup selector reports the pair was already viewed', () => {
		setUpMocks( { summary: summaryFixture(), wasViewed: true } );
		renderHook( () => useUpdateBanner() );
		expect( recordEventMock ).not.toHaveBeenCalledWith(
			'block_email_update_viewed',
			expect.anything()
		);
	} );

	it( '_dismissed fires when dismiss() is called, with the shared payload', () => {
		setUpMocks( { summary: summaryFixture() } );
		const { result } = renderHook( () => useUpdateBanner() );
		recordEventMock.mockClear();
		act( () => result.current.dismiss() );
		expect( recordEventMock ).toHaveBeenCalledWith(
			'block_email_update_dismissed',
			expect.objectContaining( sharedPayloadMatcher )
		);
	} );

	it( 'autoDismiss() does NOT fire _dismissed but DOES dispatch dismissUpdateBanner', () => {
		// Spec §9.2: the success-morph auto-dismiss path must NOT fire the
		// `_dismissed` Tracks event. The store dispatch still has to fire,
		// otherwise the banner wouldn't unmount on success morph timeout.
		setUpMocks( { summary: summaryFixture() } );
		const { result } = renderHook( () => useUpdateBanner() );
		recordEventMock.mockClear();
		dispatchMocks.dismissUpdateBanner.mockClear();
		act( () => result.current.autoDismiss() );
		expect( recordEventMock ).not.toHaveBeenCalledWith(
			'block_email_update_dismissed',
			expect.anything()
		);
		expect( dispatchMocks.dismissUpdateBanner ).toHaveBeenCalledWith( 42 );
	} );

	it( '_applied fires on apply success with shared payload + applied_from + auto_resolved + had_customizations', async () => {
		setUpMocks( { summary: summaryFixture() } );
		const { result } = renderHook( () => useUpdateBanner() );
		recordEventMock.mockClear();
		await act( async () => {
			await result.current.apply();
		} );
		expect( recordEventMock ).toHaveBeenCalledWith(
			'block_email_update_applied',
			expect.objectContaining( {
				...sharedPayloadMatcher,
				applied_from: 'editor_banner',
				auto_resolved: true,
				had_customizations: expect.any( Boolean ),
			} )
		);
	} );

	it( '_applied does NOT fire on apply failure', async () => {
		setUpMocks( {
			summary: summaryFixture(),
			apply: jest.fn().mockResolvedValue( null ),
		} );
		const { result } = renderHook( () => useUpdateBanner() );
		recordEventMock.mockClear();
		await act( async () => {
			await result.current.apply();
		} );
		expect( recordEventMock ).not.toHaveBeenCalledWith(
			'block_email_update_applied',
			expect.anything()
		);
	} );
} );
