/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { ProductMVPFeedbackModal } from '@woocommerce/customer-effort-score';
import { recordEvent } from '@woocommerce/tracks';
import { getAdminLink } from '@woocommerce/settings';
import { useFormContext } from '@woocommerce/components';
import { Product } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from './data/constants';

export const ProductMVPFeedbackModalContainer: React.FC = () => {
	const { values } = useFormContext< Product >();
	const { hideProductMVPFeedbackModal } = useDispatch( STORE_KEY );
	const { isProductMVPModalVisible } = useSelect( ( select ) => {
		const { isProductMVPFeedbackModalVisible } = select( STORE_KEY );
		return {
			isProductMVPModalVisible: isProductMVPFeedbackModalVisible(),
		};
	} );

	const classEditorUrl = values.id
		? getAdminLink( `post.php?post=${ values.id }&action=edit` )
		: getAdminLink( 'post-new.php?post_type=product' );

	const recordScore = ( checked: string[], comments: string ) => {
		recordEvent( 'product_mvp_feedback', {
			action: 'disable',
			checked: checked.join( ',' ),
			comments: comments || '',
		} );
		hideProductMVPFeedbackModal();
		window.location.href = `${ classEditorUrl }&new-product-experience-disabled=true`;
	};

	const onCloseModal = () => {
		recordEvent( 'product_mvp_feedback', {
			action: 'disable',
			checked: '',
			comments: '',
		} );
		hideProductMVPFeedbackModal();
		window.location.href = classEditorUrl;
	};

	if ( ! isProductMVPModalVisible ) {
		return null;
	}

	return (
		<ProductMVPFeedbackModal
			recordScoreCallback={ recordScore }
			onCloseModal={ onCloseModal }
		/>
	);
};
