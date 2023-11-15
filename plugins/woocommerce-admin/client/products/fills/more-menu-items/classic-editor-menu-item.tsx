/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
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
import { getAdminSetting } from '~/utils/admin-settings';

export const ClassicEditorMenuItem = ( {
	onClick,
	productId,
}: {
	productId: number;
	onClick: () => void;
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

	const _feature_nonce = getAdminSetting( '_feature_nonce' );

	const classicEditorUrl = productId
		? getAdminLink(
				`post.php?post=${ productId }&action=edit&product_block_editor=0&_feature_nonce=${ _feature_nonce }`
		  )
		: getAdminLink(
				`post-new.php?post_type=product&product_block_editor=0&_feature_nonce=${ _feature_nonce }`
		  );

	if ( isLoading ) {
		return null;
	}

	function handleMenuItemClick() {
		if ( allowTracking ) {
			showProductMVPFeedbackModal();
		} else {
			window.location.href = classicEditorUrl;
		}
		onClick();
	}

	return (
		<MenuItem
			onClick={ handleMenuItemClick }
			info={ __(
				'Save changes and go back to the classic product editing screen.',
				'woocommerce'
			) }
		>
			{ __( 'Turn off the new product form', 'woocommerce' ) }
		</MenuItem>
	);
};
