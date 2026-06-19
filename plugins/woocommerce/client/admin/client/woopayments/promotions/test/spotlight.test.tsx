/**
 * External dependencies
 */
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { SpotlightPromotion } from '../spotlight';
import { usePmPromotionActions, usePmPromotions } from '../data/hooks';
import type { PmPromotion } from '../types';

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

jest.mock( '../data/store', () => ( {} ) );

jest.mock( '../data/hooks', () => ( {
	usePmPromotions: jest.fn(),
	usePmPromotionActions: jest.fn(),
} ) );

const mockUsePmPromotions = usePmPromotions as jest.MockedFunction<
	typeof usePmPromotions
>;
const mockUsePmPromotionActions = usePmPromotionActions as jest.MockedFunction<
	typeof usePmPromotionActions
>;
const mockRecordEvent = recordEvent as jest.MockedFunction<
	typeof recordEvent
>;
const activatePmPromotion = jest.fn();
const dismissPmPromotion = jest.fn();

const spotlightPromotion: PmPromotion = {
	id: 'affirm-spotlight',
	promo_id: 'affirm_2026',
	payment_method: 'affirm',
	type: 'spotlight',
	title: 'Offer Affirm and save',
	description: '<p>Enable Affirm for eligible customers.</p>',
	cta_label: 'Activate Affirm',
	tc_url: 'https://example.com/terms',
	tc_label: 'Promotion terms',
	badge_text: 'Limited time',
	badge_type: 'primary',
	footnote: '<p>Terms apply.</p>',
	image: 'https://example.com/promo.png',
};

describe( 'SpotlightPromotion', () => {
	beforeEach( () => {
		mockRecordEvent.mockClear();
		activatePmPromotion.mockReset();
		dismissPmPromotion.mockReset();
		mockUsePmPromotions.mockReturnValue( {
			pmPromotions: [ spotlightPromotion ],
			isLoading: false,
		} );
		mockUsePmPromotionActions.mockReturnValue( {
			activatePmPromotion,
			dismissPmPromotion,
		} );
		window.history.pushState(
			{},
			'',
			'/wp-admin/admin.php?page=wc-settings&tab=checkout&path=%2Fwoopayments%2Foverview'
		);
	} );

	it( 'renders nothing while PM promotions are still loading', () => {
		mockUsePmPromotions.mockReturnValue( {
			pmPromotions: [],
			isLoading: true,
		} );

		const { container } = render( <SpotlightPromotion /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'renders the first spotlight promotion and records a view with the native route source', async () => {
		render( <SpotlightPromotion /> );

		expect(
			screen.getByRole( 'heading', { name: 'Offer Affirm and save' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Activate Affirm' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Dismiss promotion' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: /Promotion terms/ } )
		).toHaveAttribute( 'href', 'https://example.com/terms' );
		expect( screen.getByAltText( '' ) ).toHaveAttribute(
			'src',
			'https://example.com/promo.png'
		);

		await waitFor( () => {
			expect( mockRecordEvent ).toHaveBeenCalledWith(
				'wcpay_payment_method_promotion_view',
				expect.objectContaining( {
					promo_id: 'affirm_2026',
					payment_method: 'affirm',
					display_context: 'spotlight',
					source: 'wcpay-overview',
				} )
			);
		} );
		const viewEventProperties = mockRecordEvent.mock.calls.find(
			( [ eventName ] ) =>
				eventName === 'wcpay_payment_method_promotion_view'
		)?.[ 1 ];
		expect( viewEventProperties ).not.toHaveProperty( 'path' );
	} );

	it( 'activates the spotlight promotion and records the CTA click', async () => {
		render( <SpotlightPromotion /> );

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Activate Affirm' } )
		);

		expect( activatePmPromotion ).toHaveBeenCalledWith(
			'affirm-spotlight'
		);
		expect( mockRecordEvent ).toHaveBeenCalledWith(
			'wcpay_payment_method_promotion_activate_click',
			expect.objectContaining( {
				promo_id: 'affirm_2026',
				source: 'wcpay-overview',
			} )
		);
	} );

	it( 'restores focus when the focused spotlight action removes the card', async () => {
		let currentPromotions: PmPromotion[] = [ spotlightPromotion ];
		mockUsePmPromotions.mockImplementation( () => ( {
			pmPromotions: currentPromotions,
			isLoading: false,
		} ) );

		const view = render(
			<>
				<SpotlightPromotion />
				<button type="button">Stable target</button>
			</>
		);
		activatePmPromotion.mockImplementation( () => {
			currentPromotions = [];
			view.rerender(
				<>
					<SpotlightPromotion />
					<button type="button">Stable target</button>
				</>
			);
		} );

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Activate Affirm' } )
		);

		await waitFor( () => {
			expect(
				screen.getByRole( 'button', { name: 'Stable target' } )
			).toHaveFocus();
		} );
	} );

	it( 'dismisses the spotlight promotion and records the dismiss click', async () => {
		render( <SpotlightPromotion /> );

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Dismiss promotion' } )
		);

		expect( dismissPmPromotion ).toHaveBeenCalledWith( 'affirm-spotlight' );
		expect( mockRecordEvent ).toHaveBeenCalledWith(
			'wcpay_payment_method_promotion_dismiss_click',
			expect.objectContaining( {
				promo_id: 'affirm_2026',
				source: 'wcpay-overview',
			} )
		);
	} );

	it( 'omits unsafe terms URLs', () => {
		mockUsePmPromotions.mockReturnValue( {
			pmPromotions: [
				{
					...spotlightPromotion,
					tc_url: 'javascript:alert(1)',
				},
			],
			isLoading: false,
		} );

		render( <SpotlightPromotion /> );

		expect(
			screen.queryByRole( 'link', { name: 'Promotion terms' } )
		).not.toBeInTheDocument();
	} );
} );
