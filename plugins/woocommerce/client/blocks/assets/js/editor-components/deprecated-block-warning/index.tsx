/**
 * External dependencies
 */
import { useBlockProps, Warning } from '@wordpress/block-editor';
import { __, sprintf } from '@wordpress/i18n';

export const DeprecatedBlockWarning = ( {
	blockName,
}: {
	blockName: string;
} ): JSX.Element => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<Warning className="wc-block-components-actions">
				{ sprintf(
					/* translators: %s block name */
					__(
						'This version of the %s block is outdated. You can delete it and use the Product Collection block instead.',
						'woocommerce'
					),
					blockName
				) }
			</Warning>
		</div>
	);
};
