/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { getAdminLink } from '@woocommerce/settings';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { MenuItem } from '@wordpress/components';
import {
	ALLOW_TRACKING_OPTION_NAME,
	STORE_KEY as CES_STORE_KEY,
} from '@woocommerce/customer-effort-score';
import { NEW_PRODUCT_MANAGEMENT_ENABLED_OPTION_NAME } from '@woocommerce/product-editor';

/**
 * Internal dependencies
 */
import { ClassicEditorIcon } from '../../images/classic-editor-icon';

export const ClassicEditorMenuItem = ( {
	onClose,
	productId,
}: {
	productId: number;
	onClose: () => void;
} ) => {
	const { showProductMVPFeedbackModal } = useDispatch( CES_STORE_KEY );
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

	const classicEditorUrl = productId
		? getAdminLink( `post.php?post=${ productId }&action=edit` )
		: getAdminLink( 'post-new.php?post_type=product' );

	if ( isLoading ) {
		return null;
	}

	return (
		<MenuItem
			onClick={ () => {
				if ( allowTracking ) {
					updateOptions( {
						[ NEW_PRODUCT_MANAGEMENT_ENABLED_OPTION_NAME ]: 'no',
					} );
					showProductMVPFeedbackModal();
					onClose();
				} else {
					window.location.href = classicEditorUrl;
					onClose();
				}
			} }
			icon={ <ClassicEditorIcon /> }
			iconPosition="right"
		>
			{ __( 'Use the classic editor', 'woocommerce' ) }
		</MenuItem>
	);
};
