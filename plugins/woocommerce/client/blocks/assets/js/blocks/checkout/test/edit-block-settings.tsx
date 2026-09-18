/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { Edit } from '../edit';
import type { Attributes } from '../types';

// Stands in for the site entity record that holds the checkout field settings.
jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: (
		mapSelect: ( select: ( store: unknown ) => unknown ) => unknown
	) =>
		mapSelect( () => ( {
			getEditedEntityRecord: () => ( {} ),
		} ) ),
} ) );

// Stands in for the editor canvas: the inspector renders inline so the panel
// and the block markup are in one tree.
jest.mock( '@wordpress/block-editor', () => {
	const InnerBlocks = Object.assign(
		jest.fn( () => null ),
		{
			Content: jest.fn( () => null ),
		}
	);
	const useBlockProps = Object.assign(
		jest.fn( () => ( {} ) ),
		{
			save: jest.fn( () => ( {} ) ),
		}
	);

	return {
		InnerBlocks,
		InspectorControls: jest.fn( ( { children } ) => <>{ children }</> ),
		useBlockProps,
	};
} );

jest.mock( '@woocommerce/base-context', () => ( {
	...jest.requireActual( '@woocommerce/base-context' ),
	CheckoutProvider: jest.fn( ( { children } ) => <>{ children }</> ),
	EditorProvider: jest.fn( ( { children } ) => <>{ children }</> ),
} ) );

jest.mock( '@woocommerce/base-components/sidebar-layout', () => ( {
	SidebarLayout: jest.fn( ( { children, className } ) => (
		<div className={ className }>{ children }</div>
	) ),
} ) );

jest.mock( '@woocommerce/blocks-checkout', () => ( {
	...jest.requireActual( '@woocommerce/blocks-checkout' ),
	SlotFillProvider: jest.fn( ( { children } ) => <>{ children }</> ),
} ) );

// The real Style panel, with the block locking helpers stubbed out because they
// need a block editor store.
jest.mock( '../../cart-checkout-shared', () => ( {
	addClassToBody: jest.fn(),
	useBlockPropsWithLocking: jest.fn( () => ( {} ) ),
	BlockSettings: jest.requireActual(
		'../../cart-checkout-shared/block-settings'
	).BlockSettings,
} ) );

jest.mock( '../inner-blocks', () => ( {} ) );

const checkoutAttributes: Attributes = {
	hasDarkControls: false,
	showFormStepNumbers: false,
	showOrderNotes: true,
	showPolicyLinks: true,
	showReturnToCart: false,
	showRateAfterTaxName: false,
	cartPageId: 1,
	showCompanyField: false,
	requireCompanyField: false,
	showApartmentField: false,
	requireApartmentField: false,
	showPhoneField: false,
	requirePhoneField: false,
};

describe( 'Checkout editor Style panel', () => {
	it( 'writes hasDarkControls back to the block and puts the class on the canvas', async () => {
		const user = userEvent.setup();
		const setAttributes = jest.fn();
		const { container, rerender } = render(
			<Edit
				clientId="checkout-client-id"
				attributes={ checkoutAttributes }
				setAttributes={ setAttributes }
			/>
		);

		expect(
			container.querySelector( '.wc-block-checkout' )
		).not.toHaveClass( 'has-dark-controls' );

		const darkModeToggle = screen.getByRole( 'checkbox', {
			name: 'Dark mode inputs',
		} );
		expect( darkModeToggle ).not.toBeChecked();

		await user.click( darkModeToggle );

		expect( setAttributes ).toHaveBeenCalledWith( {
			hasDarkControls: true,
		} );

		rerender(
			<Edit
				clientId="checkout-client-id"
				attributes={ { ...checkoutAttributes, hasDarkControls: true } }
				setAttributes={ setAttributes }
			/>
		);

		expect(
			screen.getByRole( 'checkbox', { name: 'Dark mode inputs' } )
		).toBeChecked();
		expect( container.querySelector( '.wc-block-checkout' ) ).toHaveClass(
			'has-dark-controls'
		);
	} );
} );
