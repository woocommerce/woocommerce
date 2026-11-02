/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { Icon, starEmpty } from '@wordpress/icons';

const Edit = (): JSX.Element => {
	const blockProps = useBlockProps( {
		className: 'wc-block-add-to-wishlist-button',
	} );

	return (
		<div { ...blockProps }>
			<button
				type="button"
				className="wc-block-add-to-wishlist-button__toggle"
				disabled
			>
				<span className="wc-block-add-to-wishlist-button__icon wc-block-add-to-wishlist-button__icon--empty">
					<Icon icon={ starEmpty } size={ 24 } />
				</span>
				<span className="wc-block-add-to-wishlist-button__label">
					{ __( 'Add to wishlist', 'woocommerce' ) }
				</span>
			</button>
		</div>
	);
};

export default Edit;
