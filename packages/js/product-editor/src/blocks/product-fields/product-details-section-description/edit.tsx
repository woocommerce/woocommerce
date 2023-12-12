/**
 * External dependencies
 */
import { Fill } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { createElement } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { useWooBlockProps } from '@woocommerce/block-templates';

/**
 * Internal dependencies
 */
import { ProductEditorBlockEditProps } from '../../../types';
import { ProductDetailsSectionDescriptionBlockAttributes } from './types';
import { useEntityProp } from '@wordpress/core-data';

export function ProductDetailsSectionDescriptionBlockEdit( {
	attributes,
	clientId,
}: ProductEditorBlockEditProps< ProductDetailsSectionDescriptionBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );
	const [ productType ] = useEntityProp( 'postType', 'product', 'type' );

	const rootClientId = useSelect(
		( select ) => {
			const { getBlockRootClientId } = select( 'core/block-editor' );
			return getBlockRootClientId( clientId );
		},
		[ clientId ]
	);

	if ( ! rootClientId ) return;

	return (
		<Fill name={ rootClientId }>
			<div { ...blockProps }>
				<p>
					{ sprintf(
						/* translators: %s: the product type. */
						__( 'This is a %s product.', 'woocommerce' ),
						productType
					) }
				</p>
			</div>
		</Fill>
	);
}
