/* eslint-disable jest/no-mocks-import */
/**
 * External dependencies
 */
import { shallow, mount } from 'enzyme';
import renderer from 'react-test-renderer';

/**
 * Internal dependencies
 */
import Timeline from '..';
import mockData from '../__mocks__/timeline-mock-data';
import { groupItemsUsing, sortByDateUsing } from '../util.js';

describe( 'Timeline', () => {
	test( 'Renders empty correctly', () => {
		const timeline = shallow( <Timeline /> );
		expect( timeline.find( '.timeline_no_events' ).length ).toBe( 1 );
		expect(
			timeline
				.find( '.timeline_no_events' )
				.first()
				.contains( 'No data to display' )
		).toBe( true );
	} );

	test( 'Renders data correctly', () => {
		const timeline = mount( <Timeline items={ mockData } /> );

		// Ensure correct divs are loaded.
		expect( timeline.find( '.timeline_no_events' ).length ).toBe( 0 );
		expect( timeline.find( '.woocommerce-timeline' ).length ).toBe( 1 );

		// Ensure groups have the correct number of items.
		expect( timeline.find( '.woocommerce-timeline-group' ).length ).toBe(
			3
		);
		expect(
			timeline.find( '.woocommerce-timeline-group ul' ).at( 0 ).children()
				.length
		).toBe( 1 );
		expect(
			timeline.find( '.woocommerce-timeline-group ul' ).at( 1 ).children()
				.length
		).toBe( 2 );
		expect(
			timeline.find( '.woocommerce-timeline-group ul' ).at( 2 ).children()
				.length
		).toBe( 1 );

		// Ensure dates are correctly rendered.
		expect(
			timeline.find( '.woocommerce-timeline-group__title' ).first().text()
		).toBe( 'January 22, 2020' );
		expect(
			timeline.find( '.woocommerce-timeline-group__title' ).last().text()
		).toBe( 'January 17, 2020' );

		// Ensure the correct number of items is rendered.
		expect( timeline.find( '.woocommerce-timeline-item' ).length ).toBe(
			4
		);

		// Ensure hidden timestamps are actually hidden and vice versa.
		expect(
			timeline
				.find( '.woocommerce-timeline-item__timestamp' )
				.at( 2 )
				.text()
		).toBe( '' );
		expect(
			timeline
				.find( '.woocommerce-timeline-item__timestamp' )
				.at( 1 )
				.text()
		).not.toBe( '' );
	} );

	test( 'Empty snapshot', () => {
		const tree = renderer.create( <Timeline /> ).toJSON();
		expect( tree ).toMatchSnapshot();
	} );

	test( 'With data snapshot', () => {
		const tree = renderer
			.create( <Timeline items={ mockData } /> )
			.toJSON();
		expect( tree ).toMatchSnapshot();
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
