/* eslint-disable no-alert */
/**
 * External dependencies
 *
 */
import Gridicon from 'gridicons';
import renderer from 'react-test-renderer';

/**
 * Internal dependencies
 */
import SplitButton from '../';

const controls = [
	{
		label: 'Up',
		// eslint-disable-next-line no-undef
		onClick: () => alert( 'Primary Up clicked' ),
	},
	{
		label: 'Right',
		// eslint-disable-next-line no-undef
		onClick: () => alert( 'Primary Right clicked' ),
	},
];

describe( 'SplitButton', () => {
	test( 'it should render a simple split button', () => {
		const tree = renderer
			.create( <SplitButton controls={ controls } /> )
			.toJSON();
		expect( tree ).toMatchSnapshot();
	} );

	test( 'it should render a split button with isPrimary theme', () => {
		const tree = renderer
			.create( <SplitButton controls={ controls } isPrimary /> )
			.toJSON();
		expect( tree ).toMatchSnapshot();
	} );

	test( 'it should render a split button with Foo as main label', () => {
		const tree = renderer
			.create( <SplitButton controls={ controls } mainLabel="Foo" /> )
			.toJSON();
		expect( tree ).toMatchSnapshot();
	} );

	test( 'it should render a split button with pencil as main button', () => {
		const tree = renderer
			.create(
				<SplitButton
					controls={ controls }
					mainIcon={ <Gridicon icon="pencil" /> }
				/>
			)
			.toJSON();
		expect( tree ).toMatchSnapshot();
	} );

	test( 'it should render a split button with a menu label', () => {
		const tree = renderer
			.create(
				<SplitButton
					controls={ controls }
					menuLabel="Select an action"
				/>
			)
			.toJSON();
		expect( tree ).toMatchSnapshot();
	} );
} );
