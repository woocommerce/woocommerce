/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import EllipsisMenu from '../';

describe( 'EllipsisMenu', () => {
	it( 'adds the passed in classname', () => {
		const { container } = render(
			<EllipsisMenu
				label={ 'foo' }
				className="custom-classname"
				renderContent={ () => <div>content</div> }
			/>
		);

		const menu = container.querySelector( '.custom-classname' );
		expect( menu ).toBeInTheDocument();
	} );

	it( 'should call onToggle when clicking on the ellipsis', () => {
		const onClickMock = jest.fn();
		const { getByTitle } = render(
			<EllipsisMenu
				label={ 'foo' }
				onToggle={ onClickMock }
				renderContent={ () => <div>content</div> }
			/>
		);

		userEvent.click( getByTitle( 'foo' ) );
		expect( onClickMock ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'should render content when clicking on the ellipsis', () => {
		const { getByTitle, getByText } = render(
			<EllipsisMenu
				label={ 'foo' }
				renderContent={ () => <div>content</div> }
			/>
		);

		userEvent.click( getByTitle( 'foo' ) );
		expect( getByText( 'content' ) ).toBeInTheDocument();
	} );
} );
