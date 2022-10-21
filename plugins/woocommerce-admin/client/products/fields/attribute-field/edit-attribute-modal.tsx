/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Modal } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './add-attribute-modal.scss';
import { AttributeInputField } from '../attribute-input-field';
import { AttributeTermInputField } from '../attribute-term-input-field';
import { HydratedAttributeType } from './attribute-field';

import './edit-attribute-modal.scss';

type EditAttributeModalProps = {
	onCancel: () => void;
	onEdit: ( alteredAttribute: HydratedAttributeType ) => void;
	allAttributes: HydratedAttributeType[];
	clickedAttributeId: number;
};

export const EditAttributeModal: React.FC< EditAttributeModalProps > = ( {
	onCancel,
	onEdit,
	allAttributes,
	clickedAttributeId,
} ) => {
	const [ editableAttribute, setEditableAttribute ] = useState<
		HydratedAttributeType | undefined
	>( undefined );
	const [ selectedAttributeId, setSelectedAttributeId ] =
		useState< number >( clickedAttributeId );

	useEffect( () => {
		const selectedAttribute = allAttributes.find(
			( attribute ) => attribute.id === selectedAttributeId
		);
		if ( selectedAttribute ) {
			setEditableAttribute( { ...selectedAttribute } );
		}
	}, [ allAttributes, selectedAttributeId ] );

	return (
		<Modal
			title={ __( 'Edit attribute', 'woocommerce' ) }
			onRequestClose={ () => onCancel() }
			className="woocommerce-edit-attribute-modal"
		>
			<div className="woocommerce-edit-attribute-modal__body">
				<AttributeInputField
					placeholder={ __(
						'Search or create attribute',
						'woocommerce'
					) }
					value={ editableAttribute }
					onChange={ ( val ) => {
						if ( val?.id ) {
							setSelectedAttributeId( val.id );
						}
					} }
					onlyAttributeIds={ allAttributes.map(
						( attr ) => attr.id
					) }
					label={ __( 'Attribute', 'woocommerce' ) }
				/>
				<>
					<AttributeTermInputField
						label={ __( 'Values', 'woocommerce' ) }
						placeholder={ __(
							'Search or create value',
							'woocommerce'
						) }
						value={ editableAttribute?.terms }
						disabled={ ! editableAttribute?.id }
						attributeId={ editableAttribute?.id }
						onChange={ ( val ) => {
							setEditableAttribute( {
								...( editableAttribute as HydratedAttributeType ),
								terms: val,
							} );
						} }
					/>
				</>
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
					onClick={ () => {
						onEdit( editableAttribute as HydratedAttributeType );
					} }
				>
					{ __( 'Update', 'woocommerce' ) }
				</Button>
			</div>
		</Modal>
	);
};
