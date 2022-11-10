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
import {
	__experimentalTooltip as Tooltip,
	Link,
} from '@woocommerce/components';
import interpolateComponents from '@automattic/interpolate-components';
import { getAdminLink } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import {
	AttributeTermInputField,
	CustomAttributeTermInputField,
} from '../attribute-term-input-field';
import { HydratedAttributeType } from './attribute-field';

import './edit-attribute-modal.scss';

type EditAttributeModalProps = {
	onCancel: () => void;
	onEdit: ( alteredAttribute: HydratedAttributeType ) => void;
	attribute: HydratedAttributeType;
};

export const EditAttributeModal: React.FC< EditAttributeModalProps > = ( {
	onCancel,
	onEdit,
	attribute,
} ) => {
	const [ editableAttribute, setEditableAttribute ] = useState<
		HydratedAttributeType | undefined
	>( { ...attribute } );

	const isCustomAttribute = editableAttribute?.id === 0;

	return (
		<Modal
			title={ __( 'Edit attribute', 'woocommerce' ) }
			onRequestClose={ () => onCancel() }
			className="woocommerce-edit-attribute-modal"
		>
			<div className="woocommerce-edit-attribute-modal__body">
				<TextControl
					label={ __( 'Name', 'woocommerce' ) }
					disabled={ ! isCustomAttribute }
					value={
						editableAttribute?.name ? editableAttribute?.name : ''
					}
					onChange={ ( val ) =>
						setEditableAttribute( {
							...( editableAttribute as HydratedAttributeType ),
							name: val,
						} )
					}
				/>
				<p className="woocommerce-edit-attribute-modal__helper-text">
					{ ! isCustomAttribute
						? interpolateComponents( {
								mixedString: __(
									`You can change the attribute's name in {{link}}Attributes{{/link}}.`,
									'woocommerce'
								),
								components: {
									link: (
										<Link
											href={ getAdminLink(
												'edit.php?post_type=product&page=product_attributes'
											) }
											target="_blank"
											type="wp-admin"
										>
											<></>
										</Link>
									),
								},
						  } )
						: __(
								'Your customers will see this on the product page',
								'woocommerce'
						  ) }
				</p>
				{ attribute.terms ? (
					<AttributeTermInputField
						label={ __( 'Values', 'woocommerce' ) }
						placeholder={ __(
							'Search or create value',
							'woocommerce'
						) }
						value={ editableAttribute?.terms }
						attributeId={ editableAttribute?.id }
						onChange={ ( val ) => {
							setEditableAttribute( {
								...( editableAttribute as HydratedAttributeType ),
								terms: val,
							} );
						} }
					/>
				) : (
					<CustomAttributeTermInputField
						label={ __( 'Values', 'woocommerce' ) }
						placeholder={ __(
							'Search or create value',
							'woocommerce'
						) }
						disabled={ ! attribute?.name }
						value={ editableAttribute?.options }
						onChange={ ( val ) => {
							setEditableAttribute( {
								...( editableAttribute as HydratedAttributeType ),
								options: val,
							} );
						} }
					/>
				) }

				<div className="woocommerce-edit-attribute-modal__option-container">
					<CheckboxControl
						onChange={ ( val ) =>
							setEditableAttribute( {
								...( editableAttribute as HydratedAttributeType ),
								visible: val,
							} )
						}
						checked={ editableAttribute?.visible }
						label={ __( 'Visible to customers', 'woocommerce' ) }
					/>
					<Tooltip
						text={ __(
							'Show or hide this attribute on the product page',
							'woocommerce'
						) }
					/>
				</div>
				<div className="woocommerce-edit-attribute-modal__option-container">
					<CheckboxControl
						onChange={ ( val ) =>
							setEditableAttribute( {
								...( editableAttribute as HydratedAttributeType ),
								variation: val,
							} )
						}
						checked={ editableAttribute?.variation }
						label={ __( 'Used for filters', 'woocommerce' ) }
					/>
					<Tooltip
						text={ __(
							`Show or hide this attribute in the filters section on your store's category and shop pages`,
							'woocommerce'
						) }
					/>
				</div>
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
