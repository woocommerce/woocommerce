/**
 * External dependencies
 */
import { useBlockProps, Warning } from '@wordpress/block-editor';
import { Disabled } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

export const DeprecatedBlockWarning = ( {
	blockTitle,
	blockName,
	attributes,
}: {
	blockTitle: string;
	blockName: string;
	attributes: Record< string, unknown >;
} ): JSX.Element => {
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
					blockTitle
				) }
			</Warning>
			<Disabled>
				<ServerSideRender
					block={ blockName }
					attributes={ attributes }
				/>
			</Disabled>
		</div>
	);
};
