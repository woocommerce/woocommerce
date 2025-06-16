/* eslint-disable @woocommerce/dependency-group -- because we import mocks first, we deactivate this rule to avoid ESLint errors */
import '../../__mocks__/setup-shared-mocks';

/**
 * External dependencies
 */
import { render, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';
import { useSelect } from '@wordpress/data';
import {
	// @ts-expect-error -- It is not exported yet.
	useEntitiesSavedStatesIsDirty,
} from '@wordpress/editor';

/**
 * Internal dependencies
 */
import { SendButton } from '../send-button';
import { storeName } from '../../../store';
import { recordEvent } from '../../../events';

jest.mock( '@wordpress/components', () => ( {
	Button: ( props ) => <button { ...props }>{ props.children }</button>,
} ) );

jest.mock( '../../../events', () => ( {
	recordEvent: jest.fn(),
} ) );

const useSelectMock = useSelect as jest.Mock;
const useEntitiesSavedStatesIsDirtyMock =
	useEntitiesSavedStatesIsDirty as jest.Mock;
const recordEventMock = recordEvent as jest.Mock;

const mockStoreValues = {
	hasEmptyContent: false,
	isEmailSent: false,
};

describe( 'SendButton', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		mockStoreValues.hasEmptyContent = false;
		mockStoreValues.isEmailSent = false;

		useEntitiesSavedStatesIsDirtyMock.mockReturnValue( { isDirty: false } );

		useSelectMock.mockImplementation( ( selector ) =>
			selector( ( store ) => {
				if ( store === storeName ) {
					return {
						hasEmptyContent: () => mockStoreValues.hasEmptyContent,
						isEmailSent: () => mockStoreValues.isEmailSent,
						getUrls: () => ( { send: 'https://example.com/send' } ),
					};
				}
				return {};
			} )
		);
	} );

	it( 'should render with the correct label', () => {
		const { getByRole } = render( <SendButton /> );
		expect( getByRole( 'button', { name: 'Send' } ) ).toBeInTheDocument();
	} );

	it( 'should be disabled if isDirty is true', () => {
		useEntitiesSavedStatesIsDirtyMock.mockReturnValue( { isDirty: true } );
		const { getByRole } = render( <SendButton /> );
		expect( getByRole( 'button' ) ).toBeDisabled();
	} );

	it( 'should be disabled if hasEmptyContent is true', () => {
		mockStoreValues.hasEmptyContent = true;

		const { getByRole } = render( <SendButton /> );
		expect( getByRole( 'button' ) ).toBeDisabled();
	} );

	it( 'should be disabled if isEmailSent is true', () => {
		mockStoreValues.isEmailSent = true;

		const { getByRole } = render( <SendButton /> );
		expect( getByRole( 'button' ) ).toBeDisabled();
	} );

	it( 'should trigger sendAction and recordEvent on click', () => {
		mockStoreValues.hasEmptyContent = false;
		mockStoreValues.isEmailSent = false;

		const originalLocation = window.location;
		Object.defineProperty( window, 'location', {
			value: { href: '' },
			writable: true,
		} );

		const { getByRole } = render( <SendButton /> );
		fireEvent.click( getByRole( 'button' ) );

		expect( recordEventMock ).toHaveBeenCalledWith(
			'header_send_button_clicked'
		);
		expect( window.location.href ).toBe( 'https://example.com/send' );

		window.location = originalLocation;
	} );
} );
