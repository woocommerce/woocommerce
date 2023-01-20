/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { DropdownMenu, MenuItem } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { getAdminLink } from '@woocommerce/settings';
import { moreVertical } from '@wordpress/icons';
import { OPTIONS_STORE_NAME, Product } from '@woocommerce/data';
import { useFormContext } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { ClassicEditorIcon } from './images/classic-editor-icon';
import { FeedbackIcon } from './images/feedback-icon';
import { WooHeaderItem } from '~/header/utils';
import { STORE_KEY as CES_STORE_KEY } from '~/customer-effort-score-tracks/data/constants';
import { NEW_PRODUCT_MANAGEMENT } from '~/customer-effort-score-tracks/product-mvp-ces-footer';
import { ALLOW_TRACKING_OPTION_NAME } from '~/customer-effort-score-tracks/constants';
import './product-more-menu.scss';

export const ProductMoreMenu = () => {
	const { values } = useFormContext< Product >();
	const { showCesModal, showProductMVPFeedbackModal } =
		useDispatch( CES_STORE_KEY );
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );

	const { allowTracking, resolving: isLoading } = useSelect( ( select ) => {
		const { getOption, hasFinishedResolution } =
			select( OPTIONS_STORE_NAME );

		const allowTrackingOption =
			getOption( ALLOW_TRACKING_OPTION_NAME ) || 'no';

		const resolving = ! hasFinishedResolution( 'getOption', [
			ALLOW_TRACKING_OPTION_NAME,
		] );

		return {
			allowTracking: allowTrackingOption === 'yes',
			resolving,
		};
	} );

	const classEditorUrl = values.id
		? getAdminLink( `post.php?post=${ values.id }&action=edit` )
		: getAdminLink( 'post-new.php?post_type=product' );

	if ( isLoading ) {
		return null;
	}

	return (
		<WooHeaderItem>
			<DropdownMenu
				className="woocommerce-product-form-more-menu"
				label={ __( 'More product options', 'woocommerce' ) }
				icon={ moreVertical }
				popoverProps={ { position: 'bottom left' } }
			>
				{ ( { onClose } ) => (
					<>
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
									{},
									{
										type: 'snackbar',
										icon: <span>🌟</span>,
									}
								);
								onClose();
							} }
							icon={ <FeedbackIcon /> }
							iconPosition="right"
						>
							{ __( 'Share feedback', 'woocommerce' ) }
						</MenuItem>
						<MenuItem
							onClick={ () => {
								if ( allowTracking ) {
									updateOptions( {
										[ NEW_PRODUCT_MANAGEMENT ]: 'no',
									} );
									showProductMVPFeedbackModal();
									onClose();
								} else {
									window.location.href = classEditorUrl;
									onClose();
								}
							} }
							icon={ <ClassicEditorIcon /> }
							iconPosition="right"
						>
							{ __( 'Use the classic editor', 'woocommerce' ) }
						</MenuItem>
					</>
				) }
			</DropdownMenu>
		</WooHeaderItem>
	);
};
