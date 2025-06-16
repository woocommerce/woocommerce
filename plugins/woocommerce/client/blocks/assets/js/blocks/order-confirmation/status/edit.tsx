/**
 * External dependencies
 */
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './style.scss';

const Edit = (): JSX.Element => {
	const blockProps = useBlockProps( {
		className: 'wc-block-order-confirmation-status',
	} );

	// @TODO Think about wrapping with Noninteractive component?
	return (
		<div { ...blockProps }>
			<InnerBlocks
				template={ [
					[
						'core/heading',
						{
							level: 1,
							content: __( 'Order received', 'woocommerce' ),
						},
					],
					[
						'core/paragraph',
						{
							content: __(
								'Thank you. Your order has been received.',
								'woocommerce'
							),
						},
					],
				] }
				templateLock="all"
			/>
		</div>
	);
};

export default Edit;
