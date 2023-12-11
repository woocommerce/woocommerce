/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './style.scss';

const Edit = (): JSX.Element => {
	const blockProps = useBlockProps( {
		className: 'wc-block-order-confirmation-status',
	} );

	return (
		<div { ...blockProps }>
			<p>
				{ __(
					'Thank you. Your order has been received.',
					'woo-gutenberg-products-block'
				) }
			</p>
		</div>
	);
};

export default Edit;
