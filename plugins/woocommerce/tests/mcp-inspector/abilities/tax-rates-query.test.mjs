import assert from 'node:assert/strict';
import { before, describe, test } from 'node:test';
import { execute } from '../lib/abilities.mjs';
import { seed } from '../lib/fixtures.mjs';

const ABILITY = 'woocommerce/tax-rates-query';

describe( ABILITY, () => {
	let rates;
	before( async () => {
		rates = await seed( 'tax-rates' );
	} );

	const fixtureIds = () => Object.values( rates );
	const idsOf = ( data ) => data.rates.map( ( { id } ) => id );
	const query = ( parameters = {} ) =>
		execute( ABILITY, { include: fixtureIds(), ...parameters } );

	test( 'returns a rate with its locations and class', async () => {
		const [ standard ] = (
			await execute( ABILITY, { include: [ rates.standard ] } )
		).rates;

		assert.deepEqual( standard, {
			id: rates.standard,
			country: 'DE',
			state: '',
			postcodes: [ '10115', '10117' ],
			cities: [],
			rate: '19.0000',
			name: 'MCP DE standard',
			priority: 1,
			compound: false,
			shipping: true,
			class: 'standard',
		} );
	} );

	test( 'lists the standard rates before the other classes', async () => {
		const data = await query();

		assert.deepEqual(
			data.rates.map( ( { class: taxClass } ) => taxClass ),
			[ 'standard', 'standard', 'reduced-rate' ]
		);
		assert.equal( data.total, 3 );
	} );

	test( 'filters by class', async () => {
		assert.deepEqual( idsOf( await query( { class: 'reduced-rate' } ) ), [
			rates.reduced,
		] );
		assert.deepEqual(
			idsOf( await query( { class: 'standard' } ) ).sort(),
			[ rates.standard, rates.everywhere ].sort()
		);
	} );

	test( 'includes the rates for every country in a country filter', async () => {
		assert.deepEqual(
			idsOf( await query( { country: 'de', class: 'standard' } ) ).sort(),
			[ rates.standard, rates.everywhere ].sort()
		);
		assert.deepEqual( idsOf( await query( { country: 'FR' } ) ), [
			rates.everywhere,
		] );
	} );

	test( 'pages through the rates', async () => {
		const data = await query( { per_page: 2, page: 2 } );

		assert.deepEqual( idsOf( data ), [ rates.reduced ] );
		assert.deepEqual(
			{ total: data.total, total_pages: data.total_pages },
			{ total: 3, total_pages: 2 }
		);
	} );
} );
