/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	createElement,
	Fragment,
	createInterpolateElement,
	useState,
} from '@wordpress/element';
import { TextControl } from '@woocommerce/components';
import { Button } from '@wordpress/components';
import { useBlockProps } from '@wordpress/block-editor';
import { cleanForSlug } from '@wordpress/url';
import { useSelect, useDispatch } from '@wordpress/data';

import {
	PRODUCTS_STORE_NAME,
	WCDataSelector,
	Product,
} from '@woocommerce/data';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityProp, useEntityId } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { AUTO_DRAFT_NAME } from '../../utils';
import { EditProductLinkModal } from '../edit-product-link-modal';
import { useValidation } from '../../hooks/use-validation';
import { useProductHelper } from '../../hooks/use-product-helper';

export function Edit() {
	const blockProps = useBlockProps();

	const { updateProductWithStatus } = useProductHelper();

	const [ showProductLinkEditModal, setShowProductLinkEditModal ] =
		useState( false );

	const { editEntityRecord } = useDispatch( 'core' );
	const productId = useEntityId( 'postType', 'product' );
	const product: Product = useSelect( ( select ) =>
		select( 'core' ).getEditedEntityRecord(
			'postType',
			'product',
			productId
		)
	);

	const [ name, setName ] = useEntityProp< string >(
		'postType',
		'product',
		'name'
	);

	const { permalinkPrefix, permalinkSuffix } = useSelect(
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore
		( select: WCDataSelector ) => {
			const { getPermalinkParts } = select( PRODUCTS_STORE_NAME );
			if ( productId ) {
				const parts = getPermalinkParts( productId );
				return {
					permalinkPrefix: parts?.prefix,
					permalinkSuffix: parts?.suffix,
				};
			}
			return {};
		}
	);

	const nameIsValid = useValidation(
		'product/name',
		() => Boolean( name ) && name !== AUTO_DRAFT_NAME
	);

	return (
		<>
			<div { ...blockProps }>
				<TextControl
					label={ createInterpolateElement(
						__( 'Name <required />', 'woocommerce' ),
						{
							required: (
								<span className="woocommerce-product-form__optional-input">
									{ __( '(required)', 'woocommerce' ) }
								</span>
							),
						}
					) }
					name={ 'woocommerce-product-name' }
					placeholder={ __( 'e.g. 12 oz Coffee Mug', 'woocommerce' ) }
					onChange={ setName }
					value={ name || '' }
				/>
				{ productId &&
					nameIsValid &&
					product.status === 'publish' &&
					permalinkPrefix && (
						<span className="woocommerce-product-form__secondary-text product-details-section__product-link">
							{ __( 'Product link', 'woocommerce' ) }
							:&nbsp;
							<a
								href={ product.permalink }
								target="_blank"
								rel="noreferrer"
							>
								{ permalinkPrefix }
								{ product.slug || cleanForSlug( name ) }
								{ permalinkSuffix }
							</a>
							<Button
								variant="link"
								onClick={ () =>
									setShowProductLinkEditModal( true )
								}
							>
								{ __( 'Edit', 'woocommerce' ) }
							</Button>
						</span>
					) }
				{ showProductLinkEditModal && (
					<EditProductLinkModal
						permalinkPrefix={ permalinkPrefix || '' }
						permalinkSuffix={ permalinkSuffix || '' }
						product={ product }
						onCancel={ () => setShowProductLinkEditModal( false ) }
						onSaved={ () => setShowProductLinkEditModal( false ) }
						saveHandler={ async ( updatedSlug ) => {
							const { slug, permalink } =
								await updateProductWithStatus(
									product.id,
									{
										slug: updatedSlug,
									},
									product.status,
									true
								);

							if ( slug && permalink ) {
								editEntityRecord(
									'postType',
									'product',
									product.id,
									{
										slug,
										permalink,
									}
								);

								return {
									slug,
									permalink,
								};
							}
						} }
					/>
				) }
			</div>
		</>
	);
}
