/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';
import { getNewPath, navigateTo } from '@woocommerce/navigation';
import { Button } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getProductErrorMessage } from '../../../utils/get-product-error-message';
import { usePreview } from '../hooks/use-preview';
import { PreviewButtonProps } from './types';

export function PreviewButton( {
	productStatus,
	...props
}: PreviewButtonProps ) {
	const { createErrorNotice } = useDispatch( 'core/notices' );

	const previewButtonProps = usePreview( {
		productStatus,
		...props,
		onSaveSuccess( savedProduct: Product ) {
			if ( productStatus === 'auto-draft' ) {
				const url = getNewPath( {}, `/product/${ savedProduct.id }` );
				navigateTo( { url } );
			}
		},
		onSaveError( error ) {
			const message = getProductErrorMessage( error );
			createErrorNotice( message );
		},
	} );

	return <Button { ...previewButtonProps } />;
}
