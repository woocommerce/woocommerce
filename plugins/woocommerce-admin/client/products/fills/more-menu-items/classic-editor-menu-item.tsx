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
		? getAdminLink(
				`post.php?post=${ productId }&action=edit&product_block_editor=0`
		  )
		: getAdminLink(
				'post-new.php?post_type=product&product_block_editor=0'
		  );

	if ( isLoading ) {
		return null;
	}

	return (
		<MenuItem
			onClick={ () => {
				if ( allowTracking ) {
					showProductMVPFeedbackModal();
				} else {
					window.location.href = classicEditorUrl;
				}
				onClose();
			} }
			icon={ <ClassicEditorIcon /> }
			iconPosition="right"
		>
			{ __( 'Use the classic editor', 'woocommerce' ) }
		</MenuItem>
	);
};
