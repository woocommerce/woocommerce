/**
 * External dependencies
 */
import { render, screen, queryByText } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { Edit } from '../edit';
import {
	termsCheckboxDefaultText,
	termsConsentDefaultText,
} from '../../checkout-terms-block/constants';

const mockUpdateBlockAttributes = jest.fn();
const mockRemoveBlock = jest.fn();
const mockSelectBlock = jest.fn();
let mockCheckoutBlocks = [];

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useDispatch: () => ( {
		updateBlockAttributes: mockUpdateBlockAttributes,
		removeBlock: mockRemoveBlock,
		selectBlock: mockSelectBlock,
	} ),
	useSelect: jest.fn().mockImplementation( ( fn ) => {
		const select = () => {
			return {
				getSelectionStart: () => ( {
					clientId: null,
				} ),
				getSelectionEnd: () => ( {
					clientId: null,
				} ),
				getFormatTypes: () => [],
				getCurrentPostId: () => 1,
				getBlockRootClientId: () => 'fields-1',
				getBlockOrder: () =>
					mockCheckoutBlocks.map( ( block ) => block.clientId ),
				getBlockName: ( clientId ) =>
					mockCheckoutBlocks.find(
						( block ) => block.clientId === clientId
					)?.name,
				getBlockAttributes: ( clientId ) =>
					mockCheckoutBlocks.find(
						( block ) => block.clientId === clientId
					)?.attributes,
			};
		};

		if ( typeof fn === 'function' ) {
			return fn( select );
		}

		return {
			isCaretWithinFormattedText: () => false,
		};
	} ),
} ) );

jest.mock( '@wordpress/block-editor', () => ( {
	...jest.requireActual( '@wordpress/block-editor' ),
	useBlockProps: jest.fn( () => ( { className: '' } ) ),
	InspectorControls: jest.fn( ( { children } ) => <div>{ children }</div> ),
} ) );

jest.mock( '@woocommerce/base-components/cart-checkout', () => ( {
	PlaceOrderButton: ( { label } ) => (
		<div data-testid="place-order">{ label }</div>
	),
	ReturnToCartButton: ( { children } ) => <span>{ children }</span>,
} ) );

jest.mock( '@woocommerce/editor-components/page-selector', () => () => null );

jest.mock( '@woocommerce/block-settings', () => ( {
	...jest.requireActual( '@woocommerce/block-settings' ),
	PRIVACY_URL: '/privacy-policy',
	TERMS_URL: '/terms-and-conditions',
} ) );

const blockSettingsMock = jest.requireMock( '@woocommerce/block-settings' );

let mockCheckoutShowTerms = true;
jest.mock( '@woocommerce/settings', () => {
	const actual = jest.requireActual( '@woocommerce/settings' );
	return {
		...actual,
		getSetting: ( key, fallback ) =>
			key === 'checkoutShowTerms'
				? mockCheckoutShowTerms
				: actual.getSetting( key, fallback ),
	};
} );

const defaultAttributes = {
	cartPageId: 0,
	showReturnToCart: false,
	placeOrderButtonLabel: 'Place Order',
	returnToCartButtonLabel: 'Return to Cart',
	priceSeparator: '·',
};

const renderEdit = ( attributes = {} ) =>
	render(
		<Edit
			clientId="actions-1"
			attributes={ { ...defaultAttributes, ...attributes } }
			setAttributes={ () => void 0 }
		/>
	);

const isBefore = ( a, b ) => {
	const elements = Array.from( document.body.querySelectorAll( '*' ) );
	return elements.indexOf( a ) < elements.indexOf( b );
};

describe( 'Checkout Actions Block Edit', () => {
	describe( 'terms and conditions', () => {
		it( 'shows a "Show terms and conditions" toggle in a "Terms and conditions" panel', () => {
			renderEdit( { showTerms: false } );

			expect(
				screen.getByRole( 'button', { name: 'Terms and conditions' } )
			).toBeInTheDocument();
			expect(
				screen.getByRole( 'checkbox', {
					name: 'Show terms and conditions',
				} )
			).not.toBeChecked();
			expect(
				screen.queryByRole( 'checkbox', { name: 'Require checkbox' } )
			).not.toBeInTheDocument();
		} );

		it( 'shows the "Require checkbox" toggle when terms are shown', () => {
			renderEdit( { showTerms: true, termsCheckbox: true } );

			expect(
				screen.getByRole( 'checkbox', { name: 'Require checkbox' } )
			).toBeChecked();
		} );

		it( 'renders editable checkbox terms above the Place order button', () => {
			const { container } = renderEdit( {
				showTerms: true,
				termsCheckbox: true,
				termsText: 'Custom terms',
			} );

			const terms = queryByText( container, 'Custom terms' );
			expect( terms ).toHaveAttribute( 'contenteditable', 'true' );
			expect(
				isBefore( terms, screen.getByTestId( 'place-order' ) )
			).toBe( true );
		} );

		it( 'renders editable text-only terms below the Place order button', () => {
			const { container } = renderEdit( {
				showTerms: true,
				termsCheckbox: false,
				termsText: 'Custom terms',
			} );

			const terms = queryByText( container, 'Custom terms' );
			expect(
				isBefore( screen.getByTestId( 'place-order' ), terms )
			).toBe( true );
		} );

		it( 'renders no terms when showTerms is false', () => {
			const { container } = renderEdit( {
				showTerms: false,
				termsText: 'Custom terms',
			} );

			expect(
				queryByText( container, 'Custom terms' )
			).not.toBeInTheDocument();
		} );

		it( 'falls back to the default checkbox text when termsText is empty', () => {
			const { container } = renderEdit( {
				showTerms: true,
				termsCheckbox: true,
				termsText: '',
			} );

			expect( container.innerHTML ).toContain( termsCheckboxDefaultText );
		} );

		it( 'falls back to the default consent text when termsText is empty', () => {
			const { container } = renderEdit( {
				showTerms: true,
				termsCheckbox: false,
				termsText: '',
			} );

			expect( container.innerHTML ).toContain( termsConsentDefaultText );
		} );

		it( 'shows a notice when the terms or privacy pages are not set up', () => {
			blockSettingsMock.PRIVACY_URL = '';
			blockSettingsMock.TERMS_URL = '';
			const { container } = renderEdit( {
				showTerms: true,
				termsText: 'I agree to the terms and conditions',
			} );

			expect(
				queryByText( container, 'Set up a Terms and Conditions page' )
			).toBeInTheDocument();
			expect(
				queryByText( container, 'Set up a Privacy Policy page' )
			).toBeInTheDocument();
		} );

		it( 'reminds merchants to link their policy pages in custom text', () => {
			blockSettingsMock.TERMS_URL = '/terms';
			blockSettingsMock.PRIVACY_URL = '/privacy';
			const { container } = renderEdit( {
				showTerms: true,
				termsText: 'I agree to the terms and conditions',
			} );

			expect(
				queryByText(
					container,
					'Ensure you add links to your policy pages in this section.'
				)
			).toBeInTheDocument();
			expect(
				screen.getByRole( 'button', { name: 'Restore default text' } )
			).toBeInTheDocument();
		} );

		it( 'shows no policy notices when both links are in the text', () => {
			blockSettingsMock.TERMS_URL = '/terms';
			blockSettingsMock.PRIVACY_URL = '/privacy';
			const { container } = renderEdit( {
				showTerms: true,
				termsText:
					'I agree to the <a href="/terms">terms</a> and <a href="/privacy">privacy</a>',
			} );

			expect(
				queryByText(
					container,
					'Ensure you add links to your policy pages in this section.'
				)
			).not.toBeInTheDocument();
		} );
	} );

	describe( 'Return to Cart', () => {
		it( 'has a Return to Cart toggle', () => {
			renderEdit( { showReturnToCart: true } );

			expect(
				screen.getByRole( 'checkbox', {
					name: 'Show a "Return to Cart" link',
				} )
			).toBeChecked();
		} );

		it( 'renders the Return to Cart link when enabled', () => {
			const { container } = renderEdit( { showReturnToCart: true } );

			expect(
				queryByText( container, 'Return to Cart' )
			).toBeInTheDocument();
		} );
	} );

	describe( 'terms hidden by an extension', () => {
		afterEach( () => {
			mockCheckoutShowTerms = true;
		} );

		it( 'shows a notice when woocommerce_checkout_show_terms hides the terms', () => {
			mockCheckoutShowTerms = false;
			const { container } = renderEdit( { showTerms: true } );

			expect(
				queryByText(
					container,
					'Terms are hidden by an extension on your store.'
				)
			).toBeInTheDocument();
		} );

		it( 'does not render terms in the canvas when hidden by an extension', () => {
			mockCheckoutShowTerms = false;
			const { container } = renderEdit( {
				showTerms: true,
				termsText: 'Custom terms',
			} );

			expect(
				queryByText( container, 'Custom terms' )
			).not.toBeInTheDocument();
		} );

		it( 'shows no notice by default', () => {
			const { container } = renderEdit( { showTerms: true } );

			expect(
				queryByText(
					container,
					'Terms are hidden by an extension on your store.'
				)
			).not.toBeInTheDocument();
		} );
	} );

	describe( 'switching from the Terms and Conditions block', () => {
		beforeEach( () => {
			mockCheckoutBlocks = [
				{
					clientId: 'terms-1',
					name: 'woocommerce/checkout-terms-block',
					attributes: { checkbox: true, text: 'Custom terms' },
				},
				{
					clientId: 'actions-1',
					name: 'woocommerce/checkout-actions-block',
					attributes: {},
				},
			];
		} );

		afterEach( () => {
			mockCheckoutBlocks = [];
		} );

		it( 'replaces the terms settings with a switch notice while the Terms block is in the checkout', () => {
			renderEdit();

			expect(
				screen.getByText(
					/The Terms and Conditions block is deprecated/,
					{
						selector: 'p',
					}
				)
			).toBeInTheDocument();
			expect(
				screen.queryByRole( 'checkbox', {
					name: 'Show terms and conditions',
				} )
			).not.toBeInTheDocument();
		} );

		it( 'moves the Terms block settings onto the Actions block and removes the Terms block', () => {
			renderEdit();

			screen
				.getByRole( 'button', { name: 'Switch to Place order terms' } )
				.click();

			expect( mockUpdateBlockAttributes ).toHaveBeenCalledWith(
				'actions-1',
				{
					showTerms: true,
					termsCheckbox: true,
					termsText: 'Custom terms',
				}
			);
			expect( mockRemoveBlock ).toHaveBeenCalledWith( 'terms-1', false );
			expect( mockSelectBlock ).toHaveBeenCalledWith( 'actions-1' );
		} );
	} );
} );
