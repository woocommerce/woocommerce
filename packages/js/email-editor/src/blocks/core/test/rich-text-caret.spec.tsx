import * as React from 'react';

/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { dispatch, select, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { activatePersonalizationTagsReplacing } from '../rich-text';
import { addFilterForEmail } from '../../../config-tools/filters';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	select: jest.fn(),
	dispatch: jest.fn(),
	useSelect: jest.fn(),
} ) );

jest.mock( '@wordpress/block-editor', () => ( {
	store: {},
	BlockControls: () => null,
} ) );

jest.mock( '../../../store', () => ( { storeName: 'email-editor' } ) );

jest.mock( '../../../events', () => ( { recordEvent: jest.fn() } ) );

jest.mock( '../../../config-tools/filters', () => ( {
	addFilterForEmail: jest.fn(),
} ) );

const TAG = '[woocommerce/customer-first-name]';

describe( 'personalizationTagsLiveContentUpdate caret', () => {
	const selectionChange = jest.fn();
	let setContent: ( content: string ) => void;

	beforeEach( () => {
		selectionChange.mockClear();
		( useSelect as jest.Mock ).mockReturnValue( [
			{ token: TAG, name: 'First name' },
		] );
		( select as jest.Mock ).mockReturnValue( {
			getFormatTypeForBareElement: () => undefined,
			getFormatTypeForClassName: () => undefined,
			getSelectionStart: () => ( {
				clientId: 'block',
				attributeKey: 'content',
				offset: 39,
			} ),
		} );
		( dispatch as jest.Mock ).mockReturnValue( { selectionChange } );

		activatePersonalizationTagsReplacing();
		const withLiveUpdate = ( addFilterForEmail as jest.Mock ).mock
			.calls[ 0 ][ 2 ];
		const Edit = withLiveUpdate(
			( props: { setAttributes: ( a: object ) => void } ) => {
				setContent = ( content ) => props.setAttributes( { content } );
				return null;
			}
		);
		render(
			<Edit
				name="core/paragraph"
				clientId="block"
				attributes={ { content: '' } }
				setAttributes={ jest.fn() }
			/>
		);
	} );

	it( 'moves the caret when a typed tag is converted', () => {
		setContent( `Hello ${ TAG }` );

		expect( selectionChange ).toHaveBeenCalledWith(
			'block',
			'content',
			7,
			7
		);
	} );

	it( 'leaves the selection alone when no tag is converted', () => {
		setContent( '<strong>Hello</strong> world' );

		expect( selectionChange ).not.toHaveBeenCalled();
	} );
} );
