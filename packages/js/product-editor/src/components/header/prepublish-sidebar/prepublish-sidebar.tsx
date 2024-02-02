/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { PublishButton } from '../publish-button';
import { PrepublishModalProps } from './types';
import { store as productEditorUiStore } from '../../../store/product-editor-ui';
import { TRACKS_SOURCE } from '../../../constants';

export function PrePublishModal( {
	productId,
	productType = 'product',
}: PrepublishModalProps ) {
	const lastPersistedProduct = useSelect(
		( select ) => {
			const { getEntityRecord } = select( 'core' );
			return getEntityRecord( 'postType', productType, productId );
		},
		[ productType, productId ]
	);

	const { closePrepublishSidebar } = useDispatch( productEditorUiStore );

	return (
		<div className="woocommerce-product-publish-panel">
			<div className="woocommerce-product-publish-panel__header">
				<div className="woocommerce-product-publish-panel__header-publish-button">
					<PublishButton
						productType={ productType }
						productStatus={ lastPersistedProduct?.status }
						onSuccess={ closePrepublishSidebar }
					/>
				</div>
				<div className="woocommerce-product-publish-panel__header-cancel-button">
					<Button
						variant={ 'secondary' }
						onClick={ () => {
							recordEvent( 'product_prepublish_cancel', {
								source: TRACKS_SOURCE,
							} );
							closePrepublishSidebar();
						} }
					>
						{ __( 'Cancel', 'woocommerce' ) }
					</Button>
				</div>
			</div>
			<div className="woocommerce-product-publish-panel__content">
				<div className="woocommerce-product-publish-panel__header-title">
					<div>Title</div>
				</div>
			</div>
			<div className="woocommerce-product-publish-panel__footer">
				<div>footer</div>
			</div>
		</div>
	);
}
