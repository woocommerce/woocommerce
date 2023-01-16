/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
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
import {
	getAttributeKey,
	reorderSortableProductAttributePositions,
} from './utils';
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
	// TODO: should we support an 'any' option to show all attributes?
	attributeType?: 'regular' | 'for-variations';
};

export type HydratedAttributeType = Omit< ProductAttribute, 'options' > & {
	options?: string[];
	terms?: ProductAttributeTerm[];
	visible?: boolean;
};

export const AttributeField: React.FC< AttributeFieldProps > = ( {
	value,
	onChange,
	productId,
	attributeType = 'regular',
} ) => {
	const [ showAddAttributeModal, setShowAddAttributeModal ] =
		useState( false );
	const [ hydratedAttributes, setHydratedAttributes ] = useState<
		HydratedAttributeType[]
	>( [] );
	const [ editingAttributeId, setEditingAttributeId ] = useState<
		null | string
	>( null );

	const isOnlyForVariations = attributeType === 'for-variations';

	const newAttributeProps = { variation: isOnlyForVariations };

	const CANCEL_BUTTON_EVENT_NAME = isOnlyForVariations
		? 'product_add_options_modal_cancel_button_click'
		: 'product_add_attributes_modal_cancel_button_click';

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
		// I think we'll need to move the hydration out of the individual component
		// instance. To where, I do not yet know... maybe in the form context
		// somewhere so that a single hydration source can be shared between multiple
		// instances? Something like a simple key-value store in the form context
		// would be handy.
		if ( ! value || hydratedAttributes.length !== 0 ) {
			return;
		}

		const [ customAttributes, globalAttributes ]: ProductAttribute[][] =
			sift( value, ( attr: ProductAttribute ) => attr.id === 0 );

		Promise.all(
			globalAttributes.map( ( attr ) => fetchTerms( attr.id ) )
		).then( ( allResults ) => {
			setHydratedAttributes( [
				...globalAttributes.map( ( attr, index ) => {
					const fetchedTerms = allResults[ index ];

					const newAttr = {
						...attr,
						// I'm not sure this is quite right for handling unpersisted terms,
						// but this gets things kinda working for now
						terms:
							fetchedTerms.length > 0 ? fetchedTerms : undefined,
						options:
							fetchedTerms.length === 0
								? attr.options
								: undefined,
					};

					return newAttr;
				} ),
				...customAttributes,
			] );
		} );
	}, [ fetchTerms, hydratedAttributes, value ] );

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
					return {
						...newAttributeProps,
						...newAttr,
						position: ( value || [] ).length + index,
					};
				} ),
		] );
		recordEvent( 'product_add_attributes_modal_add_button_click' );
		setShowAddAttributeModal( false );
	};

	const filteredAttributes = value
		? value.filter(
				( attribute: ProductAttribute ) =>
					attribute.variation === isOnlyForVariations
		  )
		: false;

	if (
		! filteredAttributes ||
		filteredAttributes.length === 0 ||
		hydratedAttributes.length === 0
	) {
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
						selectedAttributeIds={ ( filteredAttributes || [] ).map(
							( attr ) => attr.id
						) }
					/>
				) }
				<SelectControlMenuSlot />
			</>
		);
	}

	const sortedAttributes = filteredAttributes.sort(
		( a, b ) => a.position - b.position
	);
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

	const attribute = hydratedAttributes.find(
		( attr ) => fetchAttributeId( attr ) === editingAttributeId
	) as HydratedAttributeType;

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
			{ editingAttributeId && (
				<EditAttributeModal
					title={
						/* translators: %s is the attribute name */
						sprintf(
							__( 'Edit %s', 'woocommerce' ),
							attribute.name
						)
					}
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
						const newAttributesSet = [ ...hydratedAttributes ];
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

						updateAttributes( newAttributesSet );
						setEditingAttributeId( null );
					} }
					attribute={ attribute }
				/>
			) }
		</div>
	);
};
