/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { ProductAttribute } from '@woocommerce/data';
import {
	Sortable,
	__experimentalSelectControlMenuSlot as SelectControlMenuSlot,
	Link,
} from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';
import interpolateComponents from '@automattic/interpolate-components';
import { getAdminLink } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import './attribute-field.scss';
import { AddAttributeModal } from './add-attribute-modal';
import { EditAttributeModal } from './edit-attribute-modal';
import { EnhancedProductAttribute } from '~/products/hooks/use-product-attributes';
import {
	getAttributeKey,
	reorderSortableProductAttributePositions,
} from './utils';
import { AttributeEmptyState } from '../attribute-empty-state';
import {
	AddAttributeListItem,
	AttributeListItem,
} from '../attribute-list-item';

type AttributeControlProps = {
	value: ProductAttribute[];
	onChange: ( value: ProductAttribute[] ) => void;
	// TODO: should we support an 'any' option to show all attributes?
	attributeType?: 'regular' | 'for-variations';
};

export const AttributeControl: React.FC< AttributeControlProps > = ( {
	value,
	attributeType = 'regular',
	onChange,
} ) => {
	const [ showAddAttributeModal, setShowAddAttributeModal ] =
		useState( false );
	const [ editingAttributeId, setEditingAttributeId ] = useState<
		null | string
	>( null );

	const isOnlyForVariations = attributeType === 'for-variations';

	const CANCEL_BUTTON_EVENT_NAME = isOnlyForVariations
		? 'product_add_options_modal_cancel_button_click'
		: 'product_add_attributes_modal_cancel_button_click';

	const fetchAttributeId = ( attribute: { id: number; name: string } ) =>
		`${ attribute.id }-${ attribute.name }`;

	const handleChange = ( newAttributes: EnhancedProductAttribute[] ) => {
		onChange(
			newAttributes.map( ( attr ) => {
				return {
					...attr,
					options: attr.terms
						? attr.terms.map( ( term ) => term.name )
						: ( attr.options as string[] ),
					terms: undefined,
					visible: attr.visible || false,
				};
			} )
		);
	};

	const onRemove = ( attribute: ProductAttribute ) => {
		// eslint-disable-next-line no-alert
		if ( window.confirm( __( 'Remove this attribute?', 'woocommerce' ) ) ) {
			recordEvent(
				'product_remove_attribute_confirmation_confirm_click'
			);
			handleChange(
				value.filter(
					( attr ) =>
						fetchAttributeId( attr ) !==
						fetchAttributeId( attribute )
				)
			);
		} else {
			recordEvent( 'product_remove_attribute_confirmation_cancel_click' );
		}
	};

	const onAddNewAttributes = (
		newAttributes: EnhancedProductAttribute[]
	) => {
		handleChange( [
			...( value || [] ),
			...newAttributes.filter(
				( newAttr ) =>
					! ( value || [] ).find( ( attr ) =>
						newAttr.id === 0
							? newAttr.name === attr.name // check name if custom attribute = id === 0.
							: attr.id === newAttr.id
					)
			),
		] );
		recordEvent( 'product_add_attributes_modal_add_button_click' );
		setShowAddAttributeModal( false );
	};

	if ( ! value.length ) {
		return (
			<>
				<AttributeEmptyState
					addNewLabel={
						isOnlyForVariations
							? __( 'Add options', 'woocommerce' )
							: undefined
					}
					onNewClick={ () => {
						recordEvent(
							'product_add_first_attribute_button_click'
						);
						setShowAddAttributeModal( true );
					} }
					subtitle={
						isOnlyForVariations
							? __( 'No options yet', 'woocommerce' )
							: undefined
					}
				/>
				{ showAddAttributeModal && (
					<AddAttributeModal
						onCancel={ () => {
							recordEvent( CANCEL_BUTTON_EVENT_NAME );
							setShowAddAttributeModal( false );
						} }
						onAdd={ onAddNewAttributes }
						selectedAttributeIds={ [] }
					/>
				) }
				<SelectControlMenuSlot />
			</>
		);
	}

	const sortedAttributes = value.sort( ( a, b ) => a.position - b.position );

	const attributeKeyValues = value.reduce(
		(
			keyValue: Record< number | string, ProductAttribute >,
			attribute: ProductAttribute
		) => {
			keyValue[ getAttributeKey( attribute ) ] = attribute;
			return keyValue;
		},
		{} as Record< number | string, ProductAttribute >
	);

	const editingAttribute = value.find(
		( attr ) => fetchAttributeId( attr ) === editingAttributeId
	) as EnhancedProductAttribute;

	const editAttributeCopy = isOnlyForVariations
		? __(
				`You can change the option's name in {{link}}Attributes{{/link}}.`,
				'woocommerce'
		  )
		: __(
				`You can change the attribute's name in {{link}}Attributes{{/link}}.`,
				'woocommerce'
		  );

	return (
		<div className="woocommerce-attribute-field">
			<Sortable
				onOrderChange={ ( items ) => {
					const itemPositions = items.reduce(
						( positions, { props }, index ) => {
							positions[ getAttributeKey( props.attribute ) ] =
								index;
							return positions;
						},
						{} as Record< number | string, number >
					);
					onChange(
						reorderSortableProductAttributePositions(
							itemPositions,
							attributeKeyValues
						)
					);
				} }
			>
				{ sortedAttributes.map( ( attr ) => (
					<AttributeListItem
						attribute={ attr }
						key={ fetchAttributeId( attr ) }
						onEditClick={ () =>
							setEditingAttributeId( fetchAttributeId( attr ) )
						}
						onRemoveClick={ () => onRemove( attr ) }
					/>
				) ) }
			</Sortable>
			<AddAttributeListItem
				label={
					isOnlyForVariations
						? __( 'Add option', 'woocommerce' )
						: undefined
				}
				onAddClick={ () => {
					recordEvent(
						isOnlyForVariations
							? 'product_add_option_button'
							: 'product_add_attribute_button'
					);
					setShowAddAttributeModal( true );
				} }
			/>
			{ showAddAttributeModal && (
				<AddAttributeModal
					title={
						isOnlyForVariations
							? __( 'Add options', 'woocommerce' )
							: undefined
					}
					onCancel={ () => {
						recordEvent( CANCEL_BUTTON_EVENT_NAME );
						setShowAddAttributeModal( false );
					} }
					onAdd={ onAddNewAttributes }
					selectedAttributeIds={ value.map( ( attr ) => attr.id ) }
				/>
			) }
			<SelectControlMenuSlot />
			{ editingAttribute && (
				<EditAttributeModal
					title={ sprintf(
						/* translators: %s is the attribute name */
						__( 'Edit %s', 'woocommerce' ),
						editingAttribute.name
					) }
					globalAttributeHelperMessage={ interpolateComponents( {
						mixedString: editAttributeCopy,
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
					} ) }
					onCancel={ () => setEditingAttributeId( null ) }
					onEdit={ ( changedAttribute ) => {
						const newAttributesSet = [ ...value ];
						const changedAttributeIndex: number =
							newAttributesSet.findIndex( ( attr ) =>
								attr.id !== 0
									? attr.id === changedAttribute.id
									: attr.name === changedAttribute.name
							);

						newAttributesSet.splice(
							changedAttributeIndex,
							1,
							changedAttribute
						);

						handleChange( newAttributesSet );
						setEditingAttributeId( null );
					} }
					attribute={ editingAttribute }
				/>
			) }
		</div>
	);
};
