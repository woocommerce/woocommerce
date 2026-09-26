/**
 * External dependencies
 */
import { render, fireEvent } from '@testing-library/react';
import PagesIcon from 'gridicons/dist/pages';

/**
 * Internal dependencies
 */
import { Tab } from '../';

const renderTab = () =>
	render(
		<Tab
			icon={ null }
			title={ 'Hello World' }
			name={ 'overview' }
			selected
			isPanelOpen
			index={ 0 }
			onTabClick={ () => {} }
		/>
	);

describe( 'ActivityPanel Tab', () => {
	it( 'displays a title based on props', () => {
		const { getByRole } = render(
			<Tab
				icon={ null }
				title={ 'Hello World' }
				name={ 'overview' }
				selected
				isPanelOpen
				index={ 0 }
				onTabClick={ () => {} }
			/>
		);

		// Title is exposed as the button's accessible name (aria-label) so
		// screen readers announce it, even though icon-only tabs no longer
		// render the title text visually.
		expect(
			getByRole( 'tab', { name: 'Hello World' } )
		).toBeInTheDocument();
	} );

	it( 'renders the node passed to icon', () => {
		const { getByText } = render(
			<Tab
				icon={ <div>Fake icon</div> }
				title={ 'Hello World' }
				name={ 'overview' }
				selected
				isPanelOpen
				index={ 0 }
				onTabClick={ () => {} }
			/>
		);

		expect( getByText( 'Fake icon' ) ).not.toBeNull();
	} );

	it( 'is always tabbable even if active', () => {
		const { getByRole, rerender } = render(
			<Tab
				icon={ <PagesIcon /> }
				title={ 'Hello World' }
				name={ 'overview' }
				selected
				isPanelOpen={ false }
				index={ 1 }
				onTabClick={ () => {} }
			/>
		);

		let tab = getByRole( 'tab' );

		// Tab index is set to null if its the currently selected item, or the panel is closed and the item is the first item.
		expect( tab ).not.toHaveAttribute( 'tabindex' );

		rerender(
			<Tab
				icon={ <PagesIcon /> }
				title={ 'Hello World' }
				name={ 'overview' }
				selected={ false }
				isPanelOpen={ false }
				index={ 0 }
				onTabClick={ () => {} }
			/>
		);

		tab = getByRole( 'tab' );

		expect( tab ).not.toHaveAttribute( 'tabindex' );

		rerender(
			<Tab
				icon={ <PagesIcon /> }
				title={ 'Hello World' }
				name={ 'overview' }
				selected={ true }
				isPanelOpen={ true }
				index={ 1 }
				onTabClick={ () => {} }
			/>
		);

		tab = getByRole( 'tab' );

		expect( tab ).not.toHaveAttribute( 'tabindex' );
	} );

	it( 'calls the onTabClick handler if a tab is clicked', () => {
		const onTabClickSpy = jest.fn();

		const { getByRole } = render(
			<Tab
				icon={ <PagesIcon /> }
				title={ 'Hello World' }
				name={ 'overview' }
				selected={ false }
				isPanelOpen={ true }
				index={ 1 }
				onTabClick={ onTabClickSpy }
			/>
		);

		fireEvent.click( getByRole( 'tab' ) );

		expect( onTabClickSpy ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'derives aria-controls and id from the name prop', () => {
		const nameProp = 'some-name';
		const { getByRole } = render(
			<Tab
				icon={ <PagesIcon /> }
				title={ 'Hello World' }
				name={ nameProp }
				selected={ false }
				isPanelOpen={ true }
				index={ 1 }
				onTabClick={ () => {} }
			/>
		);
		const tab = getByRole( 'tab' );

		expect( tab ).toHaveAttribute(
			'aria-controls',
			`activity-panel-${ nameProp }`
		);

		expect( tab ).toHaveAttribute(
			'id',
			`activity-panel-tab-${ nameProp }`
		);
	} );

	it( 'has an is-active class if isPanelOpen is true', () => {
		const { getByRole, rerender } = renderTab();
		expect( getByRole( 'tab' ) ).toHaveClass( 'is-active' );

		rerender(
			<Tab
				icon={ <PagesIcon /> }
				title={ 'Hello World' }
				name={ 'overview' }
				selected={ false }
				isPanelOpen={ false }
				index={ 1 }
				onTabClick={ () => {} }
			/>
		);

		expect( getByRole( 'tab' ) ).not.toHaveClass( 'is-active' );
	} );
} );
