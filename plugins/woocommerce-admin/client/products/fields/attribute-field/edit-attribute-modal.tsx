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
	title?: string;
	nameLabel?: string;
	globalAttributeHelperMessage?: string;
	customAttributeHelperMessage?: string;
	termsLabel?: string;
	termsPlaceholder?: string;
	visibleLabel?: string;
	visibleTooltip?: string;
	filtersLabel?: string;
	filtersTooltip?: string;
	cancelAccessibleLabel?: string;
	cancelLabel?: string;
	updateAccessibleLabel?: string;
	updateLabel?: string;
	onCancel: () => void;
	onEdit: ( alteredAttribute: HydratedAttributeType ) => void;
	attribute: HydratedAttributeType;
};

export const EditAttributeModal: React.FC< EditAttributeModalProps > = ( {
	title = __( 'Edit attribute', 'woocommerce' ),
	nameLabel = __( 'Name', 'woocommerce' ),
	globalAttributeHelperMessage = interpolateComponents( {
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
	} ),
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
	filtersLabel = __( 'Used for filters', 'woocommerce' ),
	filtersTooltip = __(
		`Show or hide this attribute in the filters section on your store's category and shop pages`,
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
		HydratedAttributeType | undefined
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
							...( editableAttribute as HydratedAttributeType ),
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
								...( editableAttribute as HydratedAttributeType ),
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
						label={ visibleLabel }
					/>
					<Tooltip text={ visibleTooltip } />
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
						label={ filtersLabel }
					/>
					<Tooltip text={ filtersTooltip } />
				</div>
			</div>
			<div className="woocommerce-add-attribute-modal__buttons">
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
						onEdit( editableAttribute as HydratedAttributeType );
					} }
				>
					{ updateLabel }
				</Button>
			</div>
		</Modal>
	);
};
