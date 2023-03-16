/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import {
	ProductMVPFeedbackModal,
	STORE_KEY,
} from '@woocommerce/customer-effort-score';
import { recordEvent } from '@woocommerce/tracks';
import { getAdminLink } from '@woocommerce/settings';
import { useFormContext } from '@woocommerce/components';
import { Product } from '@woocommerce/data';

export const ProductMVPFeedbackModalContainer: React.FC = () => {
	const { values } = useFormContext< Product >();
	const { hideProductMVPFeedbackModal } = useDispatch( STORE_KEY );
	const { isProductMVPModalVisible } = useSelect( ( select ) => {
		const { isProductMVPFeedbackModalVisible } = select( STORE_KEY );
		return {
			isProductMVPModalVisible: isProductMVPFeedbackModalVisible(),
		};
	} );

	const classicEditorUrl = values.id
		? getAdminLink( `post.php?post=${ values.id }&action=edit` )
		: getAdminLink( 'post-new.php?post_type=product' );

	const recordScore = ( checked: string[], comments: string ) => {
		recordEvent( 'product_mvp_feedback', {
			action: 'disable',
			checked,
			comments: comments || '',
		} );
		hideProductMVPFeedbackModal();
		window.location.href = `${ classicEditorUrl }&new-product-experience-disabled=true`;
	};

	const onCloseModal = () => {
		recordEvent( 'product_mvp_feedback', {
			action: 'disable',
			checked: '',
			comments: '',
		} );
		hideProductMVPFeedbackModal();
		window.location.href = classicEditorUrl;
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
