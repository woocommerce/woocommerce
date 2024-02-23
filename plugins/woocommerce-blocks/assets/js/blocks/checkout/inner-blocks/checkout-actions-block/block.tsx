/**
 * External dependencies
 */
import classnames from 'classnames';
import { getSetting } from '@woocommerce/settings';
import {
	PlaceOrderButton,
	ReturnToCartButton,
} from '@woocommerce/base-components/cart-checkout';
import { noticeContexts } from '@woocommerce/base-context';
import { StoreNoticesContainer } from '@woocommerce/blocks-components';
import { Experiment } from '@woocommerce/explat';

/**
 * Internal dependencies
 */
import './style.scss';

const Block = ( {
	cartPageId,
	showReturnToCart,
	className,
	placeOrderButtonLabel,
}: {
	cartPageId: number;
	showReturnToCart: boolean;
	className?: string;
	placeOrderButtonLabel: string;
} ): JSX.Element => {

	const placeOrderButton = (
		<PlaceOrderButton
			label='Just pay already!! (default)'
			fullWidth={ ! showReturnToCart }
		/>
	);

	const placeOrderButtonWithExperiment = (
		<PlaceOrderButton
			label="Gimme ur monies mate! (experiment)"
			fullWidth={ ! showReturnToCart }
		/>
	);

	return (
		<div
			className={ classnames( 'wc-block-checkout__actions', className ) }
		>
			<StoreNoticesContainer
				context={ noticeContexts.CHECKOUT_ACTIONS }
			/>
			<div className="wc-block-checkout__actions_row">
				{ showReturnToCart && (
					<ReturnToCartButton
						link={ getSetting( 'page-' + cartPageId, false ) }
					/>
				) }
				<Experiment
					name="woocommerce_example_place_order_experiment"
					defaultExperience={ placeOrderButton }
					treatmentExperience={ placeOrderButtonWithExperiment }
					loadingExperience={ <div>⏳</div> }
				/>
			</div>
		</div>
	);
};

export default Block;
