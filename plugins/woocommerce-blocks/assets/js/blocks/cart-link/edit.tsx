/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

export interface Attributes {
	className?: string;
}

const Edit = () => {
	const blockProps = useBlockProps( {
		className: 'woocommerce wc-block-cart-link',
	} );

	return (
		<div { ...blockProps }>
			<span>Go to Cart</span>
		</div>
	);
};

export default Edit;
