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
import { useState, createElement } from '@wordpress/element';
import { __experimentalTooltip as Tooltip } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import {
	AttributeTermInputField,
	CustomAttributeTermInputField,
} from '../attribute-term-input-field';
import { EnhancedProductAttribute } from '../../hooks/use-product-attributes';

type EditAttributeModalProps = {
	title?: string;
	nameLabel?: string;
	globalAttributeHelperMessage?: JSX.Element;
	customAttributeHelperMessage?: string;
	termsLabel?: string;
	termsPlaceholder?: string;
	isDefaultLabel?: string;
	isDefaultTooltip?: string;
	useAsFilterLabel?: string;
	useAsFilterTooltip?: string;
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
	isDefaultLabel = __( 'Set default value', 'woocommerce' ),
	isDefaultTooltip = __(
		'Check to preselect the first choice when customers enter the product page.',
		'woocommerce'
	),
	useAsFilterLabel = __( 'Use as filter', 'woocommerce' ),
	useAsFilterTooltip = __(
		'Check to allow customers to search and filter by this option in your store.',
		'woocommerce'
	),
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

				<div className="woocommerce-edit-attribute-modal__options">
					{ attribute.variation && (
						<div className="woocommerce-edit-attribute-modal__option-container">
							<CheckboxControl
								onChange={ ( checked ) =>
									setEditableAttribute( {
										...( editableAttribute as EnhancedProductAttribute ),
										isDefault: checked,
									} )
								}
								checked={ editableAttribute?.isDefault }
								label={ isDefaultLabel }
							/>
							<Tooltip text={ isDefaultTooltip } />
						</div>
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
					{ attribute.id !== 0 && (
						/* Only supported for global attributes, and disabled for now as the 'Filter by Attributes' block does not support this yet. */
						<div className="woocommerce-edit-attribute-modal__option-container">
							<CheckboxControl
								disabled={ true }
								onChange={ () => {
									// Disabled.
								} }
								checked={ true }
								label={ useAsFilterLabel }
							/>
							<Tooltip text={ useAsFilterTooltip } />
						</div>
					) }
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
