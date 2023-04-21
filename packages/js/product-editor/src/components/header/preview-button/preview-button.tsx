/**
 * External dependencies
 */
import { Product, ProductStatus } from '@woocommerce/data';
import { getNewPath, navigateTo } from '@woocommerce/navigation';
import { Button } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useDispatch } from '@wordpress/data';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { usePreview } from '../hooks/use-preview';

export function PreviewButton( {
	...props
}: Omit<
	Button.AnchorProps,
	'aria-disabled' | 'variant' | 'href' | 'children'
> ) {
	const [ productStatus ] = useEntityProp< ProductStatus >(
		'postType',
		'product',
		'status'
	);

	const { createErrorNotice } = useDispatch( 'core/notices' );

	const previewButtonProps = usePreview( {
		...props,
		onSaveSuccess( savedProduct: Product ) {
			if ( productStatus === 'auto-draft' ) {
				const url = getNewPath( {}, `/product/${ savedProduct.id }` );
				navigateTo( { url } );
			}
		},
		onSaveError() {
			createErrorNotice(
				__( 'Failed to preview product.', 'woocommerce' )
			);
		},
	} );

	return <Button { ...previewButtonProps } />;
}
