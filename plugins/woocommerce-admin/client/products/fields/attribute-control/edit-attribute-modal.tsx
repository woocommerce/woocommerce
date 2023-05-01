/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	Button,
	Modal,
	CheckboxControl,
	TextControl,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __experimentalTooltip as Tooltip } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import {
	AttributeTermInputField,
	CustomAttributeTermInputField,
} from '../attribute-term-input-field';
import { EnhancedProductAttribute } from '../../hooks/use-product-attributes';

import './edit-attribute-modal.scss';

type EditAttributeModalProps = {
	title?: string;
	nameLabel?: string;
	globalAttributeHelperMessage?: JSX.Element;
	customAttributeHelperMessage?: string;
	termsLabel?: string;
	termsPlaceholder?: string;
	visibleLabel?: string;
	visibleTooltip?: string;
	cancelAccessibleLabel?: string;
	cancelLabel?: string;
	updateAccessibleLabel?: string;
	updateLabel?: string;
	onCancel: () => void;
	onEdit: ( alteredAttribute: EnhancedProductAttribute ) => void;
	attribute: EnhancedProductAttribute;
};

export const EditAttributeModal: React.FC< EditAttributeModalProps > = ( {
	title = __( 'Edit attribute', 'woocommerce' ),
	nameLabel = __( 'Name', 'woocommerce' ),
	globalAttributeHelperMessage,
	customAttributeHelperMessage = __(
		'Your customers will see this on the product page',
		'woocommerce'
	),
	termsLabel = __( 'Values', 'woocommerce' ),
	termsPlaceholder = __( 'Search or create value', 'woocommerce' ),
	visibleLabel = __( 'Visible to customers', 'woocommerce' ),
	visibleTooltip = __(
		'Show or hide this attribute on the product page',
		'woocommerce'
	),
	cancelAccessibleLabel = __( 'Cancel', 'woocommerce' ),
	cancelLabel = __( 'Cancel', 'woocommerce' ),
	updateAccessibleLabel = __( 'Edit attribute', 'woocommerce' ),
	updateLabel = __( 'Update', 'woocommerce' ),
	onCancel,
	onEdit,
	attribute,
} ) => {
	const [ editableAttribute, setEditableAttribute ] = useState<
		EnhancedProductAttribute | undefined
	>( { ...attribute } );

	const isCustomAttribute = editableAttribute?.id === 0;

	return (
		<Modal
			title={ title }
			onRequestClose={ () => onCancel() }
			className="woocommerce-edit-attribute-modal"
		>
			<div className="woocommerce-edit-attribute-modal__body">
				<TextControl
					label={ nameLabel }
					disabled={ ! isCustomAttribute }
					value={
						editableAttribute?.name ? editableAttribute?.name : ''
					}
					onChange={ ( val ) =>
						setEditableAttribute( {
							...( editableAttribute as EnhancedProductAttribute ),
							name: val,
						} )
					}
				/>
				<p className="woocommerce-edit-attribute-modal__helper-text">
					{ ! isCustomAttribute
						? globalAttributeHelperMessage
						: customAttributeHelperMessage }
				</p>
				{ attribute.terms ? (
					<AttributeTermInputField
						label={ termsLabel }
						placeholder={ termsPlaceholder }
						value={ editableAttribute?.terms }
						attributeId={ editableAttribute?.id }
						onChange={ ( val ) => {
							setEditableAttribute( {
								...( editableAttribute as EnhancedProductAttribute ),
								terms: val,
							} );
						} }
					/>
				) : (
					<CustomAttributeTermInputField
						label={ termsLabel }
						placeholder={ termsPlaceholder }
						disabled={ ! attribute?.name }
						value={ editableAttribute?.options }
						onChange={ ( val ) => {
							setEditableAttribute( {
								...( editableAttribute as EnhancedProductAttribute ),
								options: val,
							} );
						} }
					/>
				) }

				<div className="woocommerce-edit-attribute-modal__option-container">
					<CheckboxControl
						onChange={ ( val ) =>
							setEditableAttribute( {
								...( editableAttribute as EnhancedProductAttribute ),
								visible: val,
							} )
						}
						checked={ editableAttribute?.visible }
						label={ visibleLabel }
					/>
					<Tooltip text={ visibleTooltip } />
				</div>
			</div>
			<div className="woocommerce-edit-attribute-modal__buttons">
				<Button
					isSecondary
					label={ cancelAccessibleLabel }
					onClick={ () => onCancel() }
				>
					{ cancelLabel }
				</Button>
				<Button
					isPrimary
					label={ updateAccessibleLabel }
					onClick={ () => {
						onEdit( editableAttribute as EnhancedProductAttribute );
					} }
				>
					{ updateLabel }
				</Button>
			</div>
		</Modal>
	);
};
