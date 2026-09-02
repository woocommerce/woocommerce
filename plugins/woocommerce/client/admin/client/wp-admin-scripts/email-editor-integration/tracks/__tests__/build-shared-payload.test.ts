/**
 * Unit tests for the shared Tracks payload helper (RSM-145 §5.2).
 *
 * The helper mirrors the PHP-side `WCEmailTemplateSyncTracker::build_base_payload()`
 * — these tests pin the wire shape so client and server emit the same six base
 * keys for the same logical transition.
 */

/**
 * Internal dependencies
 */
import { buildSharedTracksPayload } from '../build-shared-payload';
import type { ChangeSummary } from '../../hooks/use-change-summary';

const baseRecord = {
	slug: 'customer_processing_order',
	meta: {
		_wc_email_template_version: '10.6.0',
		_wc_email_template_source_hash:
			'deadbeef0011223344556677889900aabbccddee',
		_wc_email_template_status: 'core_updated_customized',
		_wc_email_backfilled: true,
	},
};

const baseSummary: ChangeSummary = {
	version_from: '10.6.0',
	version_to: '10.7.0',
	source_hash_to: 'cafebabe0011223344556677889900aabbccddee',
	is_fallback: false,
	added_blocks: [],
	removed_blocks: [],
	copy_changes: [],
	structural_changes: [],
	summary_lines: [],
	cache_hit: false,
};

describe( 'buildSharedTracksPayload', () => {
	it( 'returns the six base keys exactly when both record and summary are present', () => {
		const result = buildSharedTracksPayload( {
			record: baseRecord,
			summary: baseSummary,
		} );

		expect( result ).toEqual( {
			email_id: 'customer_processing_order',
			template_version_from: '10.6.0',
			template_version_to: '10.7.0',
			source_hash_to: 'cafebabe0011223344556677889900aabbccddee',
			classification: 'core_updated_customized',
			was_backfilled: true,
		} );
	} );

	it( 'does not include source_hash_from in the wire payload (RSM-145 §15.4)', () => {
		const result = buildSharedTracksPayload( {
			record: baseRecord,
			summary: baseSummary,
		} );

		expect( result ).not.toBeNull();
		expect( result ).not.toHaveProperty( 'source_hash_from' );
	} );

	it( 'returns null when the record has no meta', () => {
		expect(
			buildSharedTracksPayload( {
				record: { slug: 'customer_processing_order' },
				summary: baseSummary,
			} )
		).toBeNull();
	} );

	it( 'returns null when the record is null', () => {
		expect(
			buildSharedTracksPayload( {
				record: null,
				summary: baseSummary,
			} )
		).toBeNull();
	} );

	it( 'treats missing summary as null template_version_to and source_hash_to', () => {
		const result = buildSharedTracksPayload( {
			record: baseRecord,
			summary: null,
		} );

		expect( result ).toMatchObject( {
			template_version_to: null,
			source_hash_to: null,
		} );
	} );

	it( 'coerces was_backfilled from string "1" and number 1', () => {
		const stringRecord = {
			...baseRecord,
			meta: { ...baseRecord.meta, _wc_email_backfilled: '1' },
		};
		expect(
			buildSharedTracksPayload( {
				record: stringRecord,
				summary: baseSummary,
			} )?.was_backfilled
		).toBe( true );

		const numberRecord = {
			...baseRecord,
			meta: { ...baseRecord.meta, _wc_email_backfilled: 1 },
		};
		expect(
			buildSharedTracksPayload( {
				record: numberRecord,
				summary: baseSummary,
			} )?.was_backfilled
		).toBe( true );
	} );

	it( 'treats missing _wc_email_backfilled as false', () => {
		const noBackfillRecord = {
			...baseRecord,
			meta: {
				_wc_email_template_version: '10.6.0',
				_wc_email_template_status: 'core_updated_customized',
			},
		};
		expect(
			buildSharedTracksPayload( {
				record: noBackfillRecord,
				summary: baseSummary,
			} )?.was_backfilled
		).toBe( false );
	} );

	it( 'falls back to empty strings for missing meta fields', () => {
		const sparseRecord = {
			slug: 'customer_processing_order',
			meta: {},
		};
		expect(
			buildSharedTracksPayload( {
				record: sparseRecord,
				summary: baseSummary,
			} )
		).toMatchObject( {
			template_version_from: '',
			classification: '',
		} );
	} );
} );
