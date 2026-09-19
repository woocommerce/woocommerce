/**
 * External dependencies
 */
import { render, fireEvent } from '@testing-library/react';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Tabs } from '../';

const generateTabs = () => {
	return [ '0', '1', '2', '3' ].map( ( name ) => ( {
		name,
		title: `Tab ${ name }`,
		icon: <span>icon</span>,
		unread: false,
	} ) );
};

const CustomTab = () => {
	return <div>Custom Tab</div>;
};

// Test wrapper that mirrors the parent ActivityPanel's open/close/switch
// intent decisions. Tabs is now a controlled component — selection state
// lives in the parent — so its rendered selection class is only correct
// when the parent updates `selectedTab` / `tabOpen` in response to clicks.
const ControlledTabs = ( { initialSelected = '', tabs } ) => {
	const [ selected, setSelected ] = useState( initialSelected );
	const [ open, setOpen ] = useState( !! initialSelected );
	return (
		<Tabs
			selectedTab={ selected }
			tabOpen={ open }
			tabs={ tabs }
			onTabClick={ ( tab ) => {
				const isSameTab = tab.name === selected;
				const isClosing = isSameTab && open;
				setSelected( tab.name );
				setOpen( ! isClosing );
			} }
		/>
	);
};

describe( 'Activity Panel Tabs', () => {
	it( 'renders the selected tab as active when both selectedTab and tabOpen prop in', () => {
		const { getAllByRole } = render(
			<ControlledTabs initialSelected="3" tabs={ generateTabs() } />
		);

		const tabs = getAllByRole( 'tab' );

		fireEvent.click( tabs[ 2 ] );
		expect( tabs[ 2 ] ).toHaveClass( 'is-active' );

		fireEvent.click( tabs[ 3 ] );
		expect( tabs[ 2 ] ).not.toHaveClass( 'is-active' );
		expect( tabs[ 3 ] ).toHaveClass( 'is-active' );
	} );

	it( 'unsets is-active when the same tab is clicked twice in a row', () => {
		const { getAllByRole } = render(
			<ControlledTabs initialSelected="3" tabs={ generateTabs() } />
		);

		const tabs = getAllByRole( 'tab' );

		fireEvent.click( tabs[ 2 ] );
		expect( tabs[ 2 ] ).toHaveClass( 'is-active' );
		fireEvent.click( tabs[ 2 ] );
		expect( tabs[ 2 ] ).not.toHaveClass( 'is-active' );
	} );

	it( 'forwards the clicked tab to onTabClick', () => {
		const tabClickSpy = jest.fn();
		const generatedTabs = generateTabs();

		const { getAllByRole } = render(
			<Tabs
				selectedTab={ '3' }
				tabs={ generatedTabs }
				onTabClick={ tabClickSpy }
			/>
		);

		const tabs = getAllByRole( 'tab' );

		fireEvent.click( tabs[ 3 ] );

		expect( tabClickSpy ).toHaveBeenCalledWith( generatedTabs[ 3 ] );
	} );

	it( 'should render tabs with a custom component defined in tab config', () => {
		const generatedTabs = generateTabs();
		generatedTabs.push( {
			component: CustomTab,
		} );

		const { getByText } = render(
			<Tabs tabs={ generatedTabs } onTabClick={ () => {} } />
		);
		expect( getByText( 'Custom Tab' ) ).toBeDefined();
	} );
} );
