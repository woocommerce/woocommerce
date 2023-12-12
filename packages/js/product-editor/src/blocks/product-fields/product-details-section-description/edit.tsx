/**
 * External dependencies
 */
import { Fill } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useWooBlockProps } from '@woocommerce/block-templates';

/**
 * Internal dependencies
 */
import { ProductEditorBlockEditProps } from '../../../types';
import { ProductDetailsSectionDescriptionBlockAttributes } from './types';

export function ProductDetailsSectionDescriptionBlockEdit( {
	attributes,
	clientId,
}: ProductEditorBlockEditProps< ProductDetailsSectionDescriptionBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );

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
				<p>{ __( 'This is a standard product.', 'woocommerce' ) }</p>
			</div>
		</Fill>
	);
}
