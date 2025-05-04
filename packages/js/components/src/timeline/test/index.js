/* eslint-disable jest/no-mocks-import */
/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { createElement } from '@wordpress/element';
import GridIcon from 'gridicons';
import { setSettings, __experimentalGetSettings } from '@wordpress/date'; // eslint-disable-line @wordpress/no-unsafe-wp-apis -- safe to use in tests, not in production

/**
 * Internal dependencies
 */
import Timeline from '..';
import mockData from '../__mocks__/timeline-mock-data';
import { groupItemsUsing, sortByDateUsing } from '../util.js';

describe( 'Timeline', () => {
	test( 'Empty snapshot', () => {
		const { container } = render( <Timeline /> );
		expect( container ).toMatchSnapshot();
	} );

	test( 'With data snapshot', () => {
		const { container } = render( <Timeline items={ mockData } /> );
		expect( container ).toMatchSnapshot();
	} );

	describe( 'Timezone handling', () => {
		const originalWPTimeSettings = __experimentalGetSettings();

		const mockItems = [
			{
				date: new Date( Date.UTC( 2020, 0, 20, 1, 30 ) ),
				body: [
					<p key={ '1' }>{ 'p element in body' }</p>,
					'string in body',
				],
				headline: <p>{ 'p tag in headline' }</p>,
				icon: (
					<GridIcon
						className={ 'is-success' }
						icon={ 'checkmark' }
						size={ 16 }
					/>
				),
			},
		];

		beforeAll( () => {
			// Set custom WP Date settings.
			setSettings( {
				...originalWPTimeSettings,
				timezone: {
					offset: '+3',
					offsetFormatted: '+03:00',
					string: 'Africa/Nairobi',
					abbr: 'EAT',
				},
			} );
		} );

		afterAll( () => {
			setSettings( originalWPTimeSettings );
		} );

		test( 'Renders with browser timezone (default)', () => {
			const { getByText } = render(
				<Timeline
					items={ mockItems }
					dateFormat="c"
					clockFormat="g:ia"
				/>
			);
			// Expect the UTC time 2020-01-20 01:30 to be displayed as browser local time: January 20, 2020 5:30am
			expect(
				getByText( '2020-01-20T05:30:00+04:00' )
			).toBeInTheDocument();
			expect( getByText( '5:30am' ) ).toBeInTheDocument();
		} );

		test( 'Renders with store timezone when useStoreTimezone is true', () => {
			const { getByText } = render(
				<Timeline
					items={ mockItems }
					dateFormat="c"
					clockFormat="g:ia"
					useStoreTimezone
				/>
			);
			// Expect the UTC time 2020-01-20 01:30 to be displayed as the store time: January 20, 2020 4:30am
			expect(
				getByText( '2020-01-20T04:30:00+03:00' )
			).toBeInTheDocument();
			expect( getByText( '4:30am' ) ).toBeInTheDocument();
		} );
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
