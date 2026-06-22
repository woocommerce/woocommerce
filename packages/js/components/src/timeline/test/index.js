/* eslint-disable jest/no-mocks-import */
/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import * as dateModule from '@wordpress/date';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Timeline from '..';
import mockData from './__mocks__/timeline-mock-data';
import { groupItemsUsing, sortByDateUsing } from '../util.js';

describe( 'Timeline', () => {
	const timezoneTestItem = {
		...mockData[ 1 ],
		date: new Date( Date.UTC( 2020, 0, 20, 23, 45 ) ),
	};

	afterEach( () => {
		jest.restoreAllMocks();
	} );

	test( 'Empty snapshot', () => {
		const { container } = render( <Timeline /> );
		expect( container ).toMatchSnapshot();
	} );

	test( 'With data snapshot', () => {
		const { container } = render( <Timeline items={ mockData } /> );
		expect( container ).toMatchSnapshot();
	} );

	test( 'uses browser timezone date formatting by default', () => {
		jest.spyOn( dateModule, 'format' ).mockImplementation(
			( dateFormat, date ) =>
				`browser:${ dateFormat }:${ date.toISOString() }`
		);
		jest.spyOn( dateModule, 'dateI18n' ).mockImplementation(
			( dateFormat, date ) =>
				`site:${ dateFormat }:${ date.toISOString() }`
		);

		const { container } = render(
			<Timeline
				items={ [ timezoneTestItem ] }
				dateFormat="F j, Y"
				clockFormat="g:ia"
			/>
		);

		expect(
			container.querySelector( '.woocommerce-timeline-group__title' )
				.textContent
		).toBe( 'browser:F j, Y:2020-01-20T23:45:00.000Z' );
		expect(
			container.querySelector( '.woocommerce-timeline-item__timestamp' )
				.textContent
		).toBe( 'browser:g:ia:2020-01-20T23:45:00.000Z' );
	} );

	test( 'uses site timezone date formatting when requested', () => {
		jest.spyOn( dateModule, 'format' ).mockImplementation(
			( dateFormat, date ) =>
				`browser:${ dateFormat }:${ date.toISOString() }`
		);
		jest.spyOn( dateModule, 'dateI18n' ).mockImplementation(
			( dateFormat, date ) =>
				`site:${ dateFormat }:${ date.toISOString() }`
		);

		const { container } = render(
			<Timeline
				items={ [ timezoneTestItem ] }
				dateFormat="F j, Y"
				clockFormat="g:ia"
				timezone="site"
			/>
		);

		expect(
			container.querySelector( '.woocommerce-timeline-group__title' )
				.textContent
		).toBe( 'site:F j, Y:2020-01-20T23:45:00.000Z' );
		expect(
			container.querySelector( '.woocommerce-timeline-item__timestamp' )
				.textContent
		).toBe( 'site:g:ia:2020-01-20T23:45:00.000Z' );
	} );

	describe( 'Timeline utilities', () => {
		test( 'Sorts correctly', () => {
			const jan21 = new Date( 2020, 0, 21 );
			const jan22 = new Date( 2020, 0, 22 );
			const jan23 = new Date( 2020, 0, 23 );

			const data = [
				{ id: 0, date: jan22 },
				{ id: 1, date: jan21 },
				{ id: 2, date: jan23 },
			];
			const expectedAsc = [
				{ id: 1, date: jan21 },
				{ id: 0, date: jan22 },
				{ id: 2, date: jan23 },
			];
			const expectedDesc = [
				{ id: 2, date: jan23 },
				{ id: 0, date: jan22 },
				{ id: 1, date: jan21 },
			];

			expect( data.sort( sortByDateUsing( 'asc' ) ) ).toStrictEqual(
				expectedAsc
			);
			expect( data.sort( sortByDateUsing( 'desc' ) ) ).toStrictEqual(
				expectedDesc
			);
		} );

		test( "Empty item list doesn't break sort", () => {
			expect( [].sort( sortByDateUsing( 'asc' ) ) ).toStrictEqual( [] );
		} );

		test( "Single item doesn't change on sort", () => {
			const items = [ { date: new Date( 2020, 0, 1 ) } ];
			expect( items.sort( sortByDateUsing( 'asc' ) ) ).toBe( items );
		} );

		test( 'Groups correctly', () => {
			const jan22 = new Date( 2020, 0, 22 );
			const jan23 = new Date( 2020, 0, 23 );
			const items = [
				{ id: 0, date: jan22 },
				{ id: 1, date: jan23 },
				{ id: 2, date: jan22 },
			];
			const expected = [
				{
					date: jan22,
					items: [
						{ id: 0, date: jan22 },
						{ id: 2, date: jan22 },
					],
				},
				{
					date: jan23,
					items: [ { id: 1, date: jan23 } ],
				},
			];

			expect(
				items.reduce( groupItemsUsing( 'days' ), [] )
			).toStrictEqual( expected );
		} );

		test( "Empty item list doesn't break grouping", () => {
			expect( [].reduce( groupItemsUsing( 'days' ), [] ) ).toStrictEqual(
				[]
			);
		} );

		test( 'Single item grouped correctly', () => {
			const jan22 = new Date( 2020, 0, 22 );
			const items = [ { id: 0, date: jan22 } ];
			const expected = [
				{
					date: jan22,
					items: [ { id: 0, date: jan22 } ],
				},
			];
			expect(
				items.reduce( groupItemsUsing( 'days' ), [] )
			).toStrictEqual( expected );
		} );
	} );
} );
