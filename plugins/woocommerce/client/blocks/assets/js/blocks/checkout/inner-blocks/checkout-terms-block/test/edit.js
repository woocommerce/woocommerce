/**
 * External dependencies
 */
import { render, queryByText, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { Edit } from '../edit';

const mockReplaceInnerBlocks = jest.fn();
const mockSelectBlock = jest.fn();
let mockCheckoutBlocks = [];

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useDispatch: () => ( {
		replaceInnerBlocks: mockReplaceInnerBlocks,
		selectBlock: mockSelectBlock,
	} ),
	useRegistry: () => ( {
		select: () => ( {
			getBlockRootClientId: () => 'fields-1',
			getBlocks: () => mockCheckoutBlocks,
		} ),
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
	useBlockProps: jest.fn(),
	InspectorControls: jest.fn( ( { children } ) => <div>{ children }</div> ),
} ) );

jest.mock( '@woocommerce/block-settings', () => ( {
	...jest.requireActual( '@woocommerce/block-settings' ),
	PRIVACY_URL: '/privacy-policy',
	TERMS_URL: '/terms-and-conditions',
} ) );

const blockSettingsMock = jest.requireMock( '@woocommerce/block-settings' );

describe( 'Edit', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		mockCheckoutBlocks = [];
	} );

	it( 'Renders a checkbox if the checkbox attribute is true', async () => {
		const { container } = render(
			<Edit
				attributes={ {
					text: 'I agree to the terms and conditions',
					checkbox: true,
				} }
				setAttributes={ () => void 0 }
			/>
		);

		expect(
			queryByText( container, 'I agree to the terms and conditions' )
		).toBeTruthy();
	} );

	it( 'Renders a notice if either the terms and conditions or privacy url attribute are unset', async () => {
		blockSettingsMock.PRIVACY_URL = '';
		blockSettingsMock.TERMS_URL = '';
		const { container } = render(
			<Edit
				attributes={ {
					text: 'I agree to the terms and conditions',
					checkbox: true,
				} }
				setAttributes={ () => void 0 }
			/>
		);

		expect(
			queryByText( container, 'Set up a Terms and Conditions page' )
		).toBeInTheDocument();

		expect(
			queryByText( container, 'Set up a Privacy Policy page' )
		).toBeInTheDocument();

		expect(
			queryByText(
				container,
				"Link to your store's Terms and Conditions and Privacy Policy pages by creating pages for them."
			)
		).toBeInTheDocument();
	} );

	it( 'Reminds users to set a URL for their terms and conditions if they are not in the terms textbox', () => {
		blockSettingsMock.TERMS_URL = '/terms';
		blockSettingsMock.PRIVACY_URL = '/privacy';
		const { container } = render(
			<Edit
				attributes={ {
					text: 'I agree to the terms and conditions',
					checkbox: true,
				} }
				setAttributes={ () => void 0 }
			/>
		);

		expect(
			queryByText(
				container,
				'Ensure you add links to your policy pages in this section.'
			)
		).toBeInTheDocument();
	} );

	it( 'Shows no notices if the terms and privacy urls are set up and in the textbox', () => {
		blockSettingsMock.TERMS_URL = '/terms';
		blockSettingsMock.PRIVACY_URL = '/privacy';
		const { container } = render(
			<Edit
				attributes={ {
					text: 'I agree to the <a href="/terms">terms</a> and <a href="/privacy">privacy</a>',
					checkbox: true,
				} }
				setAttributes={ () => void 0 }
			/>
		);

		expect(
			queryByText(
				container,
				'Ensure you add links to your policy pages in this section'
			)
		).not.toBeInTheDocument();
	} );

	it.each( [ true, false ] )(
		'only moves terms after clicking the switch button (checkbox: %s)',
		( checkbox ) => {
			mockCheckoutBlocks = [
				{
					clientId: 'terms-1',
					name: 'woocommerce/checkout-terms-block',
					attributes: {
						checkbox,
						text: 'Custom terms',
						showSeparator: true,
					},
				},
				{
					clientId: 'payment-1',
					name: 'woocommerce/checkout-payment-block',
					attributes: {},
				},
				{
					clientId: 'actions-1',
					name: 'woocommerce/checkout-actions-block',
					attributes: { showReturnToCart: true, cartPageId: 123 },
				},
			];
			const { container } = render(
				<Edit
					clientId="terms-1"
					attributes={ mockCheckoutBlocks[ 0 ].attributes }
					setAttributes={ () => void 0 }
				/>
			);
			expect( mockReplaceInnerBlocks ).not.toHaveBeenCalled();
			expect( mockSelectBlock ).not.toHaveBeenCalled();

			expect(
				queryByText( container, /This block is deprecated/ )
			).toBeInTheDocument();
			expect( queryByText( container, 'Switch' ) ).toBeInTheDocument();

			fireEvent.click(
				screen.getByRole( 'button', {
					name: 'Switch',
				} )
			);

			expect( mockReplaceInnerBlocks ).toHaveBeenCalledTimes( 1 );
			expect( mockReplaceInnerBlocks ).toHaveBeenCalledWith(
				'fields-1',
				[
					mockCheckoutBlocks[ 1 ],
					{
						...mockCheckoutBlocks[ 2 ],
						attributes: {
							...mockCheckoutBlocks[ 2 ].attributes,
							showTerms: true,
							termsCheckbox: checkbox,
							termsText: 'Custom terms',
						},
					},
				],
				false
			);
			expect( mockSelectBlock ).toHaveBeenCalledWith( 'actions-1' );
		}
	);
} );
