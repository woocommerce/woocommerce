/**
 * External dependencies
 */
import classNames from 'classnames';
import type { BlockEditProps } from '@wordpress/blocks';
import { Button } from '@wordpress/components';
import { Link } from '@woocommerce/components';
import { Product, ProductAttribute } from '@woocommerce/data';
import {
	createElement,
	useState,
	createInterpolateElement,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	// @ts-expect-error no exported member.
	useInnerBlocksProps,
} from '@wordpress/block-editor';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityProp, useEntityId } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { sanitizeHTML } from '../../utils/sanitize-html';
import { VariationsBlockAttributes } from './types';
import { EmptyVariationsImage } from './empty-variations-image';
import { NewAttributeModal } from '../../components/attribute-control/new-attribute-modal';
import {
	EnhancedProductAttribute,
	useProductAttributes,
} from '../../hooks/use-product-attributes';
import { getAttributeId } from '../../components/attribute-control/utils';
import { useProductVariationsHelper } from '../../hooks/use-product-variations-helper';
import { hasAttributesUsedForVariations } from '../../utils';

function getFirstOptionFromEachAttribute(
	attributes: Product[ 'attributes' ]
): Product[ 'default_attributes' ] {
	return attributes.map( ( attribute ) => ( {
		id: attribute.id,
		name: attribute.name,
		option: attribute.options[ 0 ],
	} ) );
}

export function Edit( {
	attributes,
}: BlockEditProps< VariationsBlockAttributes > ) {
	const { description } = attributes;

	const { generateProductVariations } = useProductVariationsHelper();
	const [ isNewModalVisible, setIsNewModalVisible ] = useState( false );
	const [ productAttributes, setProductAttributes ] = useEntityProp<
		Product[ 'attributes' ]
	>( 'postType', 'product', 'attributes' );
	const [ , setDefaultProductAttributes ] = useEntityProp<
		Product[ 'default_attributes' ]
	>( 'postType', 'product', 'default_attributes' );

	const { attributes: variationOptions, handleChange } = useProductAttributes(
		{
			allAttributes: productAttributes,
			isVariationAttributes: true,
			productId: useEntityId( 'postType', 'product' ),
			onChange( values ) {
				setProductAttributes( values );
				setDefaultProductAttributes(
					getFirstOptionFromEachAttribute( values )
				);
				generateProductVariations( values );
			},
		}
	);

	const hasAttributes = hasAttributesUsedForVariations( productAttributes );

	const blockProps = useBlockProps( {
		className: classNames( {
			'wp-block-woocommerce-product-variations-fields--has-attributes':
				hasAttributes,
		} ),
	} );
	const innerBlockProps = useInnerBlocksProps(
		{
			className:
				'wp-block-woocommerce-product-variations-fields__content',
		},
		{ templateLock: 'all' }
	);

	const openNewModal = () => {
		setIsNewModalVisible( true );
	};

	const closeNewModal = () => {
		setIsNewModalVisible( false );
	};

	const handleAdd = ( newOptions: EnhancedProductAttribute[] ) => {
		handleChange( [
			...newOptions.filter(
				( newAttr ) =>
					! variationOptions.find(
						( attr: ProductAttribute ) =>
							getAttributeId( newAttr ) === getAttributeId( attr )
					)
			),
		] );
		closeNewModal();
	};

	return (
		<div { ...blockProps }>
			<div className="wp-block-woocommerce-product-variations-fields__heading">
				<div className="wp-block-woocommerce-product-variations-fields__heading-image-container">
					<EmptyVariationsImage />
				</div>
				<p
					className="wp-block-woocommerce-product-variations-fields__heading-description"
					dangerouslySetInnerHTML={ sanitizeHTML( description ) }
				/>
				<div className="wp-block-woocommerce-product-variations-fields__heading-actions">
					<Button variant="primary" onClick={ openNewModal }>
						{ __( 'Add variation options', 'woocommerce' ) }
					</Button>
				</div>
			</div>

			<div { ...innerBlockProps } />
			{ isNewModalVisible && (
				<NewAttributeModal
					title={ __( 'Add variation options', 'woocommerce' ) }
					description={ createInterpolateElement(
						__(
							'Select from existing <globalAttributeLink>global attributes</globalAttributeLink> or create options for buyers to choose on the product page. You can change the order later.',
							'woocommerce'
						),
						{
							globalAttributeLink: (
								<Link
									href="https://woocommerce.com/document/variable-product/#add-attributes-to-use-for-variations"
									type="external"
									target="_blank"
								/>
							),
						}
					) }
					createNewAttributesAsGlobal={ true }
					notice={ '' }
					onCancel={ () => {
						closeNewModal();
					} }
					onAdd={ handleAdd }
					selectedAttributeIds={ variationOptions.map(
						( attr ) => attr.id
					) }
				/>
			) }
		</div>
	);
}
