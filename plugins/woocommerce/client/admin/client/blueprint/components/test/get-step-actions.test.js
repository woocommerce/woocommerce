/**
 * Internal dependencies
 */

import { getStepActions } from '../get-step-actions';

describe( 'getStepActions', () => {
	it( 'should return nothing for a Blueprint that only writes settings', () => {
		const steps = [
			{
				step: 'setSiteOptions',
				options: { woocommerce_store_address: '' },
			},
		];

		expect( getStepActions( steps ) ).toEqual( [] );
	} );

	it( 'should surface SQL steps, which are otherwise invisible before import', () => {
		const steps = [
			{ step: 'runSql', sql: { contents: 'UPDATE wp_posts SET ID = 1' } },
		];

		expect( getStepActions( steps ) ).toEqual( [ 'Run 1 database query' ] );
	} );

	it( 'should count repeated steps rather than list them individually', () => {
		const steps = [
			{ step: 'runSql' },
			{ step: 'runSql' },
			{ step: 'runSql' },
		];

		expect( getStepActions( steps ) ).toEqual( [
			'Run 3 database queries',
		] );
	} );

	it( 'should list every kind of action a Blueprint takes, most consequential first', () => {
		const steps = [
			{ step: 'activateTheme' },
			{ step: 'installPlugin' },
			{ step: 'setSiteOptions', options: {} },
			{ step: 'runSql' },
			{ step: 'installPlugin' },
			{ step: 'activatePlugin' },
			{ step: 'installTheme' },
		];

		expect( getStepActions( steps ) ).toEqual( [
			'Run 1 database query',
			'Install 2 plugins',
			'Activate 1 plugin',
			'Install 1 theme',
			'Activate 1 theme',
		] );
	} );

	it( 'should report unrecognised steps rather than hide them', () => {
		const steps = [
			{ step: 'runSql' },
			{ step: 'someFutureStep' },
			{ step: 'someFutureStep' },
			{ step: 'anotherUnknownStep' },
		];

		expect( getStepActions( steps ) ).toEqual( [
			'Run 1 database query',
			'Run 3 other steps (anotherUnknownStep, someFutureStep)',
		] );
	} );

	it( 'should report unrecognised steps named after object prototype properties', () => {
		const steps = [
			{ step: '__proto__' },
			{ step: 'constructor' },
			{ step: 'toString' },
		];

		expect( getStepActions( steps ) ).toEqual( [
			'Run 3 other steps (__proto__, constructor, toString)',
		] );
	} );

	it( 'should tolerate an empty or malformed step list', () => {
		expect( getStepActions( [] ) ).toEqual( [] );
		expect(
			getStepActions( [ {}, { step: '' }, { step: null } ] )
		).toEqual( [] );
	} );
} );
