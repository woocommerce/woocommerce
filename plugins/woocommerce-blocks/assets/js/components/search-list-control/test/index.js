/**
 * External dependencies
 */
import renderer from 'react-test-renderer';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import { SearchListControl } from '../';

const list = [
	{ id: 1, name: 'Apricots' },
	{ id: 2, name: 'Clementine' },
	{ id: 3, name: 'Elderberry' },
	{ id: 4, name: 'Guava' },
	{ id: 5, name: 'Lychee' },
	{ id: 6, name: 'Mulberry' },
];

describe( 'SearchListControl', () => {
	test( 'should render a search box and list of options', () => {
		const component = renderer.create(
			<SearchListControl list={ list } selected={ [] } onChange={ noop } />
		);
		expect( component.toJSON() ).toMatchSnapshot();
	} );

	test( 'should render a search box and list of options with a custom className', () => {
		const component = renderer.create(
			<SearchListControl className="test-search" list={ list } selected={ [] } onChange={ noop } />
		);
		expect( component.toJSON() ).toMatchSnapshot();
	} );

	test( 'should render a search box, a list of options, and 1 selected item', () => {
		const component = renderer.create(
			<SearchListControl list={ list } selected={ [ list[ 1 ] ] } onChange={ noop } />
		);
		expect( component.toJSON() ).toMatchSnapshot();
	} );

	test( 'should render a search box, a list of options, and 2 selected item', () => {
		const component = renderer.create(
			<SearchListControl list={ list } selected={ [ list[ 1 ], list[ 3 ] ] } onChange={ noop } />
		);
		expect( component.toJSON() ).toMatchSnapshot();
	} );

	test( 'should render a search box and no options', () => {
		const component = renderer.create(
			<SearchListControl list={ [] } selected={ [] } onChange={ noop } />
		);
		expect( component.toJSON() ).toMatchSnapshot();
	} );

	test( 'should render a search box with a search term, and only matching options', () => {
		const component = renderer.create(
			<SearchListControl list={ list } search="berry" selected={ [] } onChange={ noop } debouncedSpeak={ noop } />
		);
		expect( component.toJSON() ).toMatchSnapshot();
	} );

	test( 'should render a search box with a search term, and only matching options, regardless of case sensitivity', () => {
		const component = renderer.create(
			<SearchListControl list={ list } search="bERry" selected={ [] } onChange={ noop } debouncedSpeak={ noop } />
		);
		expect( component.toJSON() ).toMatchSnapshot();
	} );

	test( 'should render a search box with a search term, and no matching options', () => {
		const component = renderer.create(
			<SearchListControl list={ list } search="no matches" selected={ [] } onChange={ noop } debouncedSpeak={ noop } />
		);
		expect( component.toJSON() ).toMatchSnapshot();
	} );

	test( 'should render a search box and list of options, with a custom search input message', () => {
		const messages = { search: 'Testing search label' };
		const component = renderer.create(
			<SearchListControl list={ list } selected={ [] } onChange={ noop } messages={ messages } />
		);
		expect( component.toJSON() ).toMatchSnapshot();
	} );

	test( 'should render a search box and list of options, with a custom render callback for each item', () => {
		const renderItem = ( { item } ) => <div key={ item.id }>{ item.name }!</div>; // eslint-disable-line
		const component = renderer.create(
			<SearchListControl list={ list } selected={ [] } onChange={ noop } renderItem={ renderItem } />
		);
		expect( component.toJSON() ).toMatchSnapshot();
	} );
} );
