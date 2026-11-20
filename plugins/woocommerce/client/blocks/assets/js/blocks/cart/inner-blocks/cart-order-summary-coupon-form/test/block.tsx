/**
 * External dependencies
 */
import { act, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useStoreCartCoupons } from '@woocommerce/base-context/hooks';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import Block from '../block';
import Input from '../../cart-order-summary-coupon-form-input/block';
import Button from '../../cart-order-summary-coupon-form-button/block';
import buttonMetadata from '../../cart-order-summary-coupon-form-button/block.json';

/* eslint-disable testing-library/no-unnecessary-act -- Ariakit and validation updates need explicit act with this workspace's user-event setup. */

jest.mock( '@woocommerce/base-context/hooks', () => ( {
	...jest.requireActual( '@woocommerce/base-context/hooks' ),
	useStoreCartCoupons: jest.fn(),
} ) );

jest.mock( '@woocommerce/settings', () => ( {
	...jest.requireActual( '@woocommerce/settings' ),
	getSetting: jest.fn( ( key, fallback ) => fallback ),
} ) );

const mockApplyCoupon = jest.fn();
const mockCouponState = {
	appliedCoupons: [],
	isLoading: false,
	applyCoupon: mockApplyCoupon,
	removeCoupon: jest.fn(),
	isApplyingCoupon: false,
	isRemovingCoupon: false,
};

describe( 'Cart coupon form inner blocks', () => {
	beforeEach( () => {
		mockApplyCoupon.mockReset().mockResolvedValue( false );
		jest.mocked( useStoreCartCoupons ).mockReturnValue( {
			...mockCouponState,
		} );
		jest.mocked( getSetting ).mockImplementation(
			( key, fallback ) => fallback
		);
	} );

	const openForm = async () => {
		await act( () =>
			userEvent.click(
				screen.getByRole( 'button', { name: 'Add coupons' } )
			)
		);
	};

	it.each( [ 'click', 'enter' ] )(
		'submits the input value from the separate blocks using %s',
		async ( method ) => {
			render(
				<Block>
					<Input />
					<Button />
				</Block>
			);
			await openForm();
			const input = screen.getByLabelText( 'Enter code' );
			expect(
				screen.getByRole( 'button', { name: 'Apply' } )
			).toBeDisabled();
			await act( () => userEvent.type( input, 'SAVE10' ) );
			if ( method === 'enter' ) {
				await act( () => userEvent.keyboard( '{Enter}' ) );
			} else {
				await act( () =>
					userEvent.click(
						screen.getByRole( 'button', { name: 'Apply' } )
					)
				);
			}
			expect( mockApplyCoupon ).toHaveBeenCalledTimes( 1 );
			expect( mockApplyCoupon ).toHaveBeenCalledWith( 'SAVE10' );
			expect( input ).toHaveValue( 'SAVE10' );
			expect( input ).toHaveFocus();
		}
	);

	it( 'clears the input and closes the form after success', async () => {
		mockApplyCoupon.mockResolvedValue( true );
		render(
			<Block>
				<Input />
				<Button />
			</Block>
		);
		await openForm();
		await act( () =>
			userEvent.type( screen.getByLabelText( 'Enter code' ), 'SAVE10' )
		);
		await act( () =>
			userEvent.click( screen.getByRole( 'button', { name: 'Apply' } ) )
		);
		await waitFor( () =>
			expect(
				screen.queryByLabelText( 'Enter code' )
			).not.toBeInTheDocument()
		);
		await openForm();
		expect( screen.getByLabelText( 'Enter code' ) ).toHaveValue( '' );
	} );

	it.each( [ undefined, [ [], [] ] ] )(
		'renders the legacy form when saved inner blocks are missing (%s)',
		async ( children ) => {
			render( <Block>{ children }</Block> );
			await openForm();
			expect( screen.getAllByLabelText( 'Enter code' ) ).toHaveLength(
				1
			);
			expect(
				screen.getAllByRole( 'button', { name: 'Apply' } )
			).toHaveLength( 1 );
		}
	);

	it( 'does not render when coupons are disabled', () => {
		jest.mocked( getSetting ).mockImplementation( ( key, fallback ) =>
			key === 'couponsEnabled' ? false : fallback
		);
		const { container } = render(
			<Block>
				<Input />
				<Button />
			</Block>
		);
		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'disables the button and shows its spinner while applying', async () => {
		jest.mocked( useStoreCartCoupons ).mockReturnValue( {
			...mockCouponState,
			isApplyingCoupon: true,
		} );
		render(
			<Block>
				<Input />
				<Button />
			</Block>
		);
		await openForm();
		const button = screen.getByText( 'Apply' ).closest( 'button' );
		expect( button ).toBeDisabled();
		expect( button ).toHaveClass(
			'wc-block-components-totals-coupon__button--loading'
		);
		expect(
			button?.querySelector( '.wc-block-components-spinner' )
		).not.toBeNull();
	} );

	it.each( [ false, true ] )(
		'applies styles only to the button (serialized: %s)',
		async ( serialized ) => {
			const style = {
				color: { background: '#123456', text: '#ffffff' },
				border: { radius: '12px', width: '2px', style: 'solid' },
				spacing: { padding: { top: '20px' } },
				typography: { fontSize: '24px', textAlign: 'right' },
			};
			const { rerender } = render(
				<Block>
					<Input className="coupon-input" />
					<Button
						className="coupon-button"
						textColor="contrast"
						style={ serialized ? JSON.stringify( style ) : style }
					/>
				</Block>
			);
			await openForm();
			const button = screen.getByRole( 'button', { name: 'Apply' } );
			expect( button ).toHaveClass(
				'has-contrast-color',
				'has-background'
			);
			expect( button ).toHaveStyle( {
				backgroundColor: '#123456',
				color: '#ffffff',
				borderRadius: '12px',
				borderWidth: '2px',
				paddingTop: '20px',
				fontSize: '24px',
				textAlign: 'right',
			} );
			expect( button.closest( '.coupon-button' ) ).not.toHaveAttribute(
				'style'
			);
			expect( screen.getByLabelText( 'Enter code' ) ).not.toHaveStyle( {
				color: '#ffffff',
			} );
			rerender(
				<Block>
					<Input />
					<Button />
				</Block>
			);
			expect( button ).not.toHaveClass( 'has-contrast-color' );
			expect( button ).not.toHaveStyle( { backgroundColor: '#123456' } );
		}
	);

	it( 'enables button style controls without serializing onto the wrapper', () => {
		const { supports } = buttonMetadata;
		for ( const feature of [
			supports.color,
			supports.typography,
			supports.__experimentalBorder,
			supports.spacing,
		] ) {
			expect( feature.__experimentalSkipSerialization ).toBe( true );
		}
		expect( supports.color.__experimentalDefaultControls ).toEqual( {
			background: true,
			text: true,
		} );
		expect( supports.typography.textAlign ).toBe( true );
		expect(
			supports.__experimentalBorder.__experimentalDefaultControls
		).toEqual( {
			color: true,
			radius: true,
			style: true,
			width: true,
		} );
		expect( supports.spacing.padding ).toBe( true );
	} );
} );
