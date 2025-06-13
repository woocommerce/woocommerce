/**
 * External dependencies
 */
import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
import { FormStep } from '@woocommerce/blocks-components';
import { useShippingData } from '@woocommerce/base-context/hooks';
import { useDispatch, useSelect } from '@wordpress/data';
import { checkoutStore } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import CheckoutOrderNotes from '../../order-notes';

const Block = ( { className }: { className?: string } ): JSX.Element => {
	const { needsShipping } = useShippingData();
	const { isProcessing: checkoutIsProcessing, orderNotes } = useSelect(
		( select ) => {
			const store = select( checkoutStore );
			return {
				isProcessing: store.isProcessing(),
				orderNotes: store.getOrderNotes(),
			};
		}
	);
	const { __internalSetOrderNotes } = useDispatch( checkoutStore );

	return (
		<FormStep
			id="order-notes"
			showStepNumber={ false }
			className={ clsx( 'wc-block-checkout__order-notes', className ) }
			disabled={ checkoutIsProcessing }
		>
			<CheckoutOrderNotes
				disabled={ checkoutIsProcessing }
				onChange={ __internalSetOrderNotes }
				placeholder={
					needsShipping
						? __(
								'Notes about your order, e.g. special notes for delivery.',
								'woocommerce'
						  )
						: __( 'Notes about your order.', 'woocommerce' )
				}
				value={ orderNotes }
			/>
		</FormStep>
	);
};

export default Block;
