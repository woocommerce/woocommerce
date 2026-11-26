import assert from 'node:assert/strict';
import { beforeEach, describe, test } from 'node:test';
import { execute, refused } from '../lib/abilities.mjs';
import { seed } from '../lib/fixtures.mjs';

const ABILITY = 'woocommerce/product-review-update';

describe( ABILITY, () => {
	let reviews;
	beforeEach( async () => {
		reviews = await seed( 'review', { sku: 'mcp-review-update' } );
	} );

	const moderate = ( id, status ) => execute( ABILITY, { id, status } );

	test( 'holds an approved review', async () => {
		const data = await moderate( reviews.approved, 'hold' );

		assert.deepEqual(
			{
				changed: data.changed,
				previous_status: data.previous_status,
				id: data.review.id,
				status: data.review.status,
				store_reply_count: data.review.store_reply_count,
			},
			{
				changed: true,
				previous_status: 'approved',
				id: reviews.approved,
				status: 'hold',
				store_reply_count: 1,
			}
		);
	} );

	test( 'changes nothing for the status the review already has', async () => {
		const data = await moderate( reviews.held, 'hold' );

		assert.equal( data.changed, false );
		assert.equal( data.review.status, 'hold' );
	} );

	test( 'brings a review back from spam and the trash', async () => {
		for ( const [ status, previous ] of [
			[ 'spam', 'approved' ],
			[ 'trash', 'spam' ],
			[ 'approved', 'trash' ],
		] ) {
			const data = await moderate( reviews.approved, status );

			assert.deepEqual(
				[ data.previous_status, data.review.status ],
				[ previous, status ]
			);
		}
	} );

	test( 'moderates a reply to a review', async () => {
		const data = await moderate( reviews.reply, 'hold' );

		assert.deepEqual(
			{ ...data.reply, date_created: undefined },
			{
				id: reviews.reply,
				review_id: reviews.approved,
				status: 'hold',
				reply: 'Thank you',
				date_created: undefined,
			}
		);
		assert.equal( data.review, undefined );
	} );

	test( 'tells the agent where to find a review ID', () =>
		refused(
			ABILITY,
			{ id: 999999999, status: 'hold' },
			/Product review 999999999 does not exist/
		) );
} );
