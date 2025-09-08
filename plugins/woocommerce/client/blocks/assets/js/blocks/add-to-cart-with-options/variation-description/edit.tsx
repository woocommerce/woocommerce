/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

export default function VariationDescriptionEdit() {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<p>
				{ __(
					'This block displays the variation description. When the shopper selects a variation, the description content will automatically appear here.',
					'woocommerce'
				) }
			</p>
		</div>
	);
}
