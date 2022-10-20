/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Modal } from '@wordpress/components';
import { useFormContext } from '@woocommerce/components';
import { useState, useCallback, useEffect } from '@wordpress/element';
import {
	ProductAttribute,
	ProductAttributeTerm,
	EXPERIMENTAL_PRODUCT_ATTRIBUTE_TERMS_STORE_NAME,
	Product,
} from '@woocommerce/data';

import { resolveSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import './add-attribute-modal.scss';
import { AttributeInputField } from '../attribute-input-field';
import { AttributeTermInputField } from '../attribute-term-input-field';

import './edit-attribute-modal.scss';

type CreateCategoryModalProps = {
	onCancel: () => void;
	onEdit: ( alteredAttribute: ProductAttribute ) => void;
	attribute: ProductAttribute | undefined;
};

export const EditAttributeModal: React.FC< CreateCategoryModalProps > = ( {
	onCancel,
	onEdit,
	attribute,
} ) => {
	const [ editableAttribute, setEditableAttribute ] = useState<
		Pick< ProductAttribute, 'id' | 'name' > | undefined
	>( attribute ? { id: attribute.id, name: attribute.name } : undefined );
	const [ attrTerms, setAttrTerms ] = useState< ProductAttributeTerm[] >(
		[]
	);
	const {
		values: { id: productId, attributes },
	} = useFormContext< Product >();

	const fetchTerms = useCallback( () => {
		return resolveSelect( EXPERIMENTAL_PRODUCT_ATTRIBUTE_TERMS_STORE_NAME )
			.getProductAttributeTerms< ProductAttributeTerm[] >( {
				attribute_id: editableAttribute?.id,
				product: productId,
			} )
			.then(
				( attributeTerms ) => {
					setAttrTerms( attributeTerms );
					return attributeTerms;
				},
				( error ) => {
					return error;
				}
			);
	}, [ editableAttribute?.id ] );

	useEffect( () => {
		if ( productId && editableAttribute?.id ) {
			fetchTerms();
		}
	}, [ editableAttribute, productId ] );

	const onEditingAttribute = () => {
		// const newAttributesToAdd: ProductAttribute[] = [];
		// values.attributes.forEach( ( attr ) => {
		// 	if (
		// 		attr.attribute &&
		// 		attr.attribute.name &&
		// 		attr.terms.length > 0
		// 	) {
		// 		newAttributesToAdd.push( {
		// 			...( attr.attribute as ProductAttribute ),
		// 			options: attr.terms.map( ( term ) => term.name ),
		// 		} );
		// 	}
		// } );
		// onEdit( newAttributesToAdd );
	};

	return (
		<Modal
			title={ __( 'Edit attribute', 'woocommerce' ) }
			onRequestClose={ () => onCancel() }
			className="woocommerce-edit-attribute-modal"
		>
			<div className="woocommerce-edit-attribute-modal__body">
				<h2>Attribute</h2>
				<AttributeInputField
					placeholder={ __(
						'Search or create attribute',
						'woocommerce'
					) }
					value={ editableAttribute }
					onChange={ ( val ) => {
						setEditableAttribute( val );
					} }
					onlyAttributeIds={ attributes.map( ( attr ) => attr.id ) }
				/>

				<h2>Values</h2>
				<AttributeTermInputField
					placeholder={ __(
						'Search or create value',
						'woocommerce'
					) }
					value={ attrTerms }
					disabled={ ! editableAttribute?.id }
					attributeId={ editableAttribute?.id }
					onChange={ ( val ) => {
						setAttrTerms( val );
					} }
				/>
			</div>
			<div className="woocommerce-add-attribute-modal__buttons">
				<Button
					isSecondary
					label={ __( 'Cancel', 'woocommerce' ) }
					onClick={ () => onCancel() }
				>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
				<Button
					isPrimary
					label={ __( 'Edit attribute', 'woocommerce' ) }
					//disabled={ false }
					onClick={ () => {
						onEditingAttribute();
					} }
				>
					{ __( 'Update', 'woocommerce' ) }
				</Button>
			</div>
		</Modal>
	);
};
