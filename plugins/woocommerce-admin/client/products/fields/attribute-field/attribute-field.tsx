/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useCallback, useEffect } from '@wordpress/element';
import {
	ProductAttribute,
	EXPERIMENTAL_PRODUCT_ATTRIBUTE_TERMS_STORE_NAME,
	ProductAttributeTerm,
} from '@woocommerce/data';
import { resolveSelect } from '@wordpress/data';
import {
	Sortable,
	__experimentalSelectControlMenuSlot as SelectControlMenuSlot,
} from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './attribute-field.scss';
import { AddAttributeModal } from './add-attribute-modal';
import { EditAttributeModal } from './edit-attribute-modal';
import { reorderSortableProductAttributePositions } from './utils';
import { sift } from '../../../utils';
import { AttributeEmptyState } from '../attribute-empty-state';
import {
	AddAttributeListItem,
	AttributeListItem,
} from '../attribute-list-item';

type AttributeFieldProps = {
	value: ProductAttribute[];
	onChange: ( value: ProductAttribute[] ) => void;
	productId?: number;
};

export type HydratedAttributeType = Omit< ProductAttribute, 'options' > & {
	options?: string[];
	terms?: ProductAttributeTerm[];
};

export const AttributeField: React.FC< AttributeFieldProps > = ( {
	value,
	onChange,
	productId,
} ) => {
	const [ showAddAttributeModal, setShowAddAttributeModal ] =
		useState( false );
	const [ hydrationComplete, setHydrationComplete ] = useState< boolean >(
		value ? false : true
	);
	const [ hydratedAttributes, setHydratedAttributes ] = useState<
		HydratedAttributeType[]
	>( [] );
	const [ editingAttributeId, setEditingAttributeId ] = useState<
		null | string
	>( null );

	const CANCEL_BUTTON_EVENT_NAME =
		'product_add_attributes_modal_cancel_button_click';

	const fetchTerms = useCallback(
		( attributeId: number ) => {
			return resolveSelect(
				EXPERIMENTAL_PRODUCT_ATTRIBUTE_TERMS_STORE_NAME
			)
				.getProductAttributeTerms< ProductAttributeTerm[] >( {
					attribute_id: attributeId,
					product: productId,
				} )
				.then(
					( attributeTerms ) => {
						return attributeTerms;
					},
					( error ) => {
						return error;
					}
				);
		},
		[ productId ]
	);

	useEffect( () => {
		if ( ! value || hydrationComplete ) {
			return;
		}

		const [ customAttributes, globalAttributes ]: ProductAttribute[][] =
			sift( value, ( attr: ProductAttribute ) => attr.id === 0 );

		Promise.all(
			globalAttributes.map( ( attr ) => fetchTerms( attr.id ) )
		).then( ( allResults ) => {
			setHydratedAttributes( [
				...globalAttributes.map( ( attr, index ) => {
					const newAttr = {
						...attr,
						terms: allResults[ index ],
						options: undefined,
					};

					return newAttr;
				} ),
				...customAttributes,
			] );
			setHydrationComplete( true );
		} );
	}, [ productId, value, hydrationComplete ] );

	const fetchAttributeId = ( attribute: { id: number; name: string } ) =>
		`${ attribute.id }-${ attribute.name }`;

	const updateAttributes = ( attributes: HydratedAttributeType[] ) => {
		setHydratedAttributes( attributes );
		onChange(
			attributes.map( ( attr ) => {
				return {
					...attr,
					options: attr.terms
						? attr.terms.map( ( term ) => term.name )
						: ( attr.options as string[] ),
					terms: undefined,
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
			updateAttributes(
				hydratedAttributes.filter(
					( attr ) =>
						fetchAttributeId( attr ) !==
						fetchAttributeId( attribute )
				)
			);
		} else {
			recordEvent( 'product_remove_attribute_confirmation_cancel_click' );
		}
	};

	const onAddNewAttributes = ( newAttributes: HydratedAttributeType[] ) => {
		updateAttributes( [
			...( hydratedAttributes || [] ),
			...newAttributes
				.filter(
					( newAttr ) =>
						! ( value || [] ).find( ( attr ) =>
							newAttr.id === 0
								? newAttr.name === attr.name // check name if custom attribute = id === 0.
								: attr.id === newAttr.id
						)
				)
				.map( ( newAttr, index ) => {
					newAttr.position = ( value || [] ).length + index;
					return newAttr;
				} ),
		] );
		recordEvent( 'product_add_attributes_modal_add_button_click' );
		setShowAddAttributeModal( false );
	};

	if ( ! value || value.length === 0 || hydratedAttributes.length === 0 ) {
		return (
			<>
				<AttributeEmptyState
					onNewClick={ () => {
						recordEvent(
							'product_add_first_attribute_button_click'
						);
						setShowAddAttributeModal( true );
					} }
				/>
				{ showAddAttributeModal && (
					<AddAttributeModal
						onCancel={ () => {
							recordEvent( CANCEL_BUTTON_EVENT_NAME );
							setShowAddAttributeModal( false );
						} }
						onAdd={ onAddNewAttributes }
						selectedAttributeIds={ ( value || [] ).map(
							( attr ) => attr.id
						) }
					/>
				) }
				<SelectControlMenuSlot />
			</>
		);
	}

	const sortedAttributes = value.sort( ( a, b ) => a.position - b.position );
	const attributeKeyValues = value.reduce(
		(
			keyValue: Record< number, ProductAttribute >,
			attribute: ProductAttribute
		) => {
			keyValue[ attribute.id ] = attribute;
			return keyValue;
		},
		{} as Record< number, ProductAttribute >
	);

	return (
		<div className="woocommerce-attribute-field">
			<Sortable
				onOrderChange={ ( items ) => {
					onChange(
						reorderSortableProductAttributePositions(
							items,
							attributeKeyValues
						)
					);
				} }
			>
				{ sortedAttributes.map( ( attribute ) => (
					<AttributeListItem
						attribute={ attribute }
						key={ fetchAttributeId( attribute ) }
						onEditClick={ () =>
							setEditingAttributeId(
								fetchAttributeId( attribute )
							)
						}
						onRemoveClick={ () => onRemove( attribute ) }
					/>
				) ) }
			</Sortable>
			<AddAttributeListItem
				onAddClick={ () => {
					recordEvent( 'product_add_attribute_button' );
					setShowAddAttributeModal( true );
				} }
			/>
			{ showAddAttributeModal && (
				<AddAttributeModal
					onCancel={ () => {
						recordEvent( CANCEL_BUTTON_EVENT_NAME );
						setShowAddAttributeModal( false );
					} }
					onAdd={ onAddNewAttributes }
					selectedAttributeIds={ value.map( ( attr ) => attr.id ) }
				/>
			) }
			<SelectControlMenuSlot />
			{ editingAttributeId && (
				<EditAttributeModal
					onCancel={ () => setEditingAttributeId( null ) }
					onEdit={ ( changedAttribute ) => {
						const newAttributesSet = [ ...hydratedAttributes ];
						const changedAttributeIndex: number =
							newAttributesSet.findIndex(
								( attr ) => attr.id === changedAttribute.id
							);

						newAttributesSet.splice(
							changedAttributeIndex,
							1,
							changedAttribute
						);

						updateAttributes( newAttributesSet );
						setEditingAttributeId( null );
					} }
					attribute={
						hydratedAttributes.find(
							( attr ) =>
								fetchAttributeId( attr ) === editingAttributeId
						) as HydratedAttributeType
					}
				/>
			) }
		</div>
	);
};
