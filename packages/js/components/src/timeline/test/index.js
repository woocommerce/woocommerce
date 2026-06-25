/* eslint-disable jest/no-mocks-import */
/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { date as formatSiteDate, dateI18n, format } from '@wordpress/date';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Timeline from '..';
import mockData from './__mocks__/timeline-mock-data';
import { groupItemsUsing, sortByDateUsing } from '../util.js';

jest.mock( '@wordpress/date', () => {
	const actualDateModule = jest.requireActual( '@wordpress/date' );

	return {
		...actualDateModule,
		date: jest.fn( actualDateModule.date ),
		dateI18n: jest.fn( actualDateModule.dateI18n ),
		format: jest.fn( actualDateModule.format ),
	};
} );

describe( 'Timeline', () => {
	const actualDateModule = jest.requireActual( '@wordpress/date' );
	const originalDateSettings = actualDateModule.getSettings();
	const originalIntl = global.Intl;
	const timezoneTestItem = {
		...mockData[ 1 ],
		date: new Date( Date.UTC( 2020, 0, 20, 23, 45 ) ),
	};

	afterEach( () => {
		actualDateModule.setSettings( originalDateSettings );
		global.Intl = originalIntl;
		formatSiteDate.mockImplementation( actualDateModule.date );
		dateI18n.mockImplementation( actualDateModule.dateI18n );
		format.mockImplementation( actualDateModule.format );
		jest.clearAllMocks();
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
		global.Intl = {
			DateTimeFormat: () => ( {
				resolvedOptions: () => ( {
					timeZone: 'Europe/London',
				} ),
			} ),
		};
		format.mockImplementation(
			( dateFormat, date ) =>
				`browser:${ dateFormat }:${ date.toISOString() }`
		);
		dateI18n.mockImplementation(
			( dateFormat, date, timezone ) =>
				`localized:${ timezone }:${ dateFormat }:${ date.toISOString() }`
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
		).toBe( 'localized:Europe/London:F j, Y:2020-01-20T23:45:00.000Z' );
		expect(
			container.querySelector( '.woocommerce-timeline-item__timestamp' )
				.textContent
		).toBe( 'localized:Europe/London:g:ia:2020-01-20T23:45:00.000Z' );
	} );

	test( 'uses site timezone date formatting when requested', () => {
		format.mockImplementation(
			( dateFormat, date ) =>
				`browser:${ dateFormat }:${ date.toISOString() }`
		);
		dateI18n.mockImplementation(
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

	test( 'falls back to browser timezone formatting when browser timezone is unavailable', () => {
		global.Intl = {
			DateTimeFormat: () => ( {
				resolvedOptions: () => ( {} ),
			} ),
		};
		format.mockImplementation(
			( dateFormat, date ) =>
				`browser:${ dateFormat }:${ date.toISOString() }`
		);
		dateI18n.mockImplementation(
			( dateFormat, date, timezone ) =>
				`localized:${ timezone }:${ dateFormat }:${ date.toISOString() }`
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

	test( 'falls back to browser timezone formatting when browser timezone lookup throws', () => {
		global.Intl = {
			DateTimeFormat: () => {
				throw new Error( 'Timezone unavailable' );
			},
		};
		format.mockImplementation(
			( dateFormat, date ) =>
				`browser:${ dateFormat }:${ date.toISOString() }`
		);
		dateI18n.mockImplementation(
			( dateFormat, date, timezone ) =>
				`localized:${ timezone }:${ dateFormat }:${ date.toISOString() }`
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

	test( 'falls back to browser timezone formatting when browser timezone is empty', () => {
		global.Intl = {
			DateTimeFormat: () => ( {
				resolvedOptions: () => ( {
					timeZone: '',
				} ),
			} ),
		};
		format.mockImplementation(
			( dateFormat, date ) =>
				`browser:${ dateFormat }:${ date.toISOString() }`
		);
		dateI18n.mockImplementation(
			( dateFormat, date, timezone ) =>
				`localized:${ timezone }:${ dateFormat }:${ date.toISOString() }`
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

	test( 'groups items using site timezone when requested', () => {
		const timezoneBoundaryItems = [
			{
				...mockData[ 1 ],
				date: new Date( Date.UTC( 2020, 0, 20, 23, 0 ) ),
			},
			{
				...mockData[ 2 ],
				date: new Date( Date.UTC( 2020, 0, 21, 1, 0 ) ),
			},
		];

		actualDateModule.setSettings( {
			...originalDateSettings,
			timezone: {
				offset: '9',
				offsetFormatted: '+09:00',
				string: '',
				abbr: '',
			},
		} );
		dateI18n.mockImplementation( ( dateFormat, date ) => {
			if ( dateFormat === 'F j, Y' ) {
				return 'January 21, 2020';
			}

			return `site:${ dateFormat }:${ date.toISOString() }`;
		} );

		const { container } = render(
			<Timeline
				items={ timezoneBoundaryItems }
				dateFormat="F j, Y"
				clockFormat="g:ia"
				timezone="site"
			/>
		);

		const groupTitles = container.querySelectorAll(
			'.woocommerce-timeline-group__title'
		);

		expect( groupTitles ).toHaveLength( 1 );
		expect( groupTitles[ 0 ].textContent ).toBe( 'January 21, 2020' );
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
