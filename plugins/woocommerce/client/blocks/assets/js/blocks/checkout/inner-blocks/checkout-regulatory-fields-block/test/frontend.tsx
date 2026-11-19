/**
 * External dependencies
 */
import { act, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { dispatch, select } from '@wordpress/data';
import { cartStore, checkoutStore } from '@woocommerce/block-data';
import type { CartResponseRegulatoryField } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import FrontendBlock from '../frontend';
import { previewCart } from '../../../../../previews/cart';

const BRAZIL_TAX_ID: CartResponseRegulatoryField = {
	id: 'br_tax_id',
	label: 'CPF/CNPJ',
	required: true,
};

/**
 * Renders the section for a cart the server lists the given regulatory fields for.
 *
 * @param regulatoryFields Fields the cart response lists.
 */
const renderWith = ( regulatoryFields: CartResponseRegulatoryField[] ) => {
	act( () => {
		dispatch( cartStore ).finishResolution( 'getCartData' );
		dispatch( cartStore ).receiveCart( {
			...previewCart,
			regulatory_fields: regulatoryFields,
		} );
	} );

	return render(
		<FrontendBlock title="Additional required information" description="">
			<></>
		</FrontendBlock>
	);
};

describe( 'Checkout regulatory fields block', () => {
	it( 'shows nothing when no regulatory field applies to the cart', () => {
		const { container } = renderWith( [] );

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'asks for the CPF/CNPJ as a required field when the cart lists it', () => {
		renderWith( [ BRAZIL_TAX_ID ] );

		expect(
			screen.getByRole( 'heading', {
				name: 'Additional required information',
			} )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'textbox', { name: 'CPF/CNPJ' } )
		).toBeRequired();
	} );

	it( 'keeps what the shopper types so it is sent with the order', async () => {
		const user = userEvent.setup();
		renderWith( [ BRAZIL_TAX_ID ] );

		// eslint-disable-next-line testing-library/no-unnecessary-act -- The store updates each keystroke triggers settle after user.type() returns.
		await act( async () => {
			await user.type(
				screen.getByRole( 'textbox', { name: 'CPF/CNPJ' } ),
				'529.982.247-25'
			);
		} );

		expect( select( checkoutStore ).getRegulatoryFields() ).toEqual( {
			br_tax_id: '529.982.247-25',
		} );
	} );
} );
