/**
 * External dependencies
 */
import { useBlockProps, Warning } from '@wordpress/block-editor';
import { __, sprintf } from '@wordpress/i18n';

const Edit = () => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<Warning>
				{ sprintf(
					/* translators: %s block name */
					__(
						'This version of the %s block is outdated. You can delete it and use the Product Collection block instead.',
						'woocommerce'
					),
					__( 'All Products', 'woocommerce' )
				) }
			</Warning>
		</div>
	);
};

export default Edit;
