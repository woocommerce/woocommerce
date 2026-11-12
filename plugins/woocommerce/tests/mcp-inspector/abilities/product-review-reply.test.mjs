import assert from 'node:assert/strict';
import { beforeEach, describe, test } from 'node:test';
import { execute, refused } from '../lib/abilities.mjs';
import { seed } from '../lib/fixtures.mjs';

const ABILITY = 'woocommerce/product-review-reply';

describe( ABILITY, () => {
	let reviews;
	beforeEach( async () => {
		reviews = await seed( 'review', { sku: 'mcp-review-reply' } );
	} );

	test( 'publishes the reply under the review', async () => {
		const reply = await execute( ABILITY, {
			id: reviews.approved,
			content: 'Glad you like it',
		} );

		assert.deepEqual(
			{ ...reply, id: undefined, date_created: undefined },
			{
				id: undefined,
				review_id: reviews.approved,
				status: 'approved',
				reply: 'Glad you like it',
				date_created: undefined,
			}
		);
		assert.ok( reply.id > reviews.reply );
	} );

	test( 'refuses a held review', () =>
		refused(
			ABILITY,
			{ id: reviews.held, content: 'Thanks' },
			/Only approved reviews can get a reply/
		) );

	test( 'refuses a reply to a reply', () =>
		refused(
			ABILITY,
			{ id: reviews.reply, content: 'Thanks' },
			/does not exist/
		) );

	test( 'refuses the same reply twice', async () => {
		const input = { id: reviews.approved, content: 'Glad you like it' };

		await execute( ABILITY, input );
		await refused( ABILITY, input, /Duplicate comment detected/ );
	} );
} );
