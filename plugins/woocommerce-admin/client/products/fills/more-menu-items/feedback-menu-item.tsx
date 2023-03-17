/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { MenuItem } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { STORE_KEY as CES_STORE_KEY } from '@woocommerce/customer-effort-score';
import { useContext } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { FeedbackIcon } from '../../images/feedback-icon';
import { LayoutContext } from '~/layout';

export const FeedbackMenuItem = ( { onClose }: { onClose: () => void } ) => {
	const { showCesModal } = useDispatch( CES_STORE_KEY );
	const layoutContext = useContext( LayoutContext );

	return (
		<MenuItem
			onClick={ () => {
				showCesModal(
					{
						action: 'new_product',
						title: __(
							"How's your experience with the product editor?",
							'woocommerce'
						),
						firstQuestion: __(
							'The product editing screen is easy to use',
							'woocommerce'
						),
						secondQuestion: __(
							"The product editing screen's functionality meets my needs",
							'woocommerce'
						),
					},
					{ shouldShowComments: () => true },
					{
						type: 'snackbar',
						icon: <span>🌟</span>,
					},
					{
						context: layoutContext.toString(),
					}
				);
				onClose();
			} }
			icon={ <FeedbackIcon /> }
			iconPosition="right"
		>
			{ __( 'Share feedback', 'woocommerce' ) }
		</MenuItem>
	);
};
