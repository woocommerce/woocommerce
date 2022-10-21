/**
 * External dependencies
 */
import { sprintf, __ } from '@wordpress/i18n';
import { Button, Card, CardBody, Popover } from '@wordpress/components';
import { useState, useCallback, useEffect } from '@wordpress/element';
import {
	ProductAttribute,
	EXPERIMENTAL_PRODUCT_ATTRIBUTE_TERMS_STORE_NAME,
	ProductAttributeTerm,
} from '@woocommerce/data';
import { resolveSelect } from '@wordpress/data';
import { Text } from '@woocommerce/experimental';
import { Sortable, ListItem } from '@woocommerce/components';
import { closeSmall } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import './attribute-field.scss';
import AttributeEmptyStateLogo from './attribute-empty-state-logo.svg';
import { AddAttributeModal } from './add-attribute-modal';
import { EditAttributeModal } from './edit-attribute-modal';
import { reorderSortableProductAttributePositions } from './utils';

type AttributeFieldProps = {
	value: ProductAttribute[];
	onChange: ( value: ProductAttribute[] ) => void;
	productId: number;
};

export type HydratedAttributeType = Omit< ProductAttribute, 'options' > & {
	terms: ProductAttributeTerm[];
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
		null | number
	>( null );

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
		Promise.all( value.map( ( attr ) => fetchTerms( attr.id ) ) ).then(
			( allResults ) => {
				setHydratedAttributes(
					value.map( ( attr, index ) => {
						const newAttr = {
							...attr,
							terms: allResults[ index ],
							options: undefined,
						};

						return newAttr;
					} )
				);
				setHydrationComplete( true );
			}
		);
	}, [ productId, value, hydrationComplete ] );

	useEffect( () => {
		if ( ! hydrationComplete ) {
			return;
		}
		onChange(
			hydratedAttributes.map( ( attr ) => {
				return {
					...attr,
					options: attr.terms.map( ( term ) => term.name ),
					terms: undefined,
				};
			} )
		);
	}, [ hydratedAttributes, hydrationComplete ] );

	const onRemove = ( attribute: ProductAttribute ) => {
		// eslint-disable-next-line no-alert
		if ( window.confirm( __( 'Remove this attribute?', 'woocommerce' ) ) ) {
			setHydratedAttributes(
				hydratedAttributes.filter(
					( attr ) => attr.id !== attribute.id
				)
			);
		}
	};

	const onAddNewAttributes = ( newAttributes: HydratedAttributeType[] ) => {
		setHydratedAttributes( [
			...( hydratedAttributes || [] ),
			...newAttributes
				.filter(
					( newAttr ) =>
						! ( value || [] ).find(
							( attr ) => attr.id === newAttr.id
						)
				)
				.map( ( newAttr, index ) => {
					newAttr.position = ( value || [] ).length + index;
					return newAttr;
				} ),
		] );
		setShowAddAttributeModal( false );
	};

	if ( ! value || value.length === 0 ) {
		return (
			<Card>
				<CardBody>
					<div className="woocommerce-attribute-field">
						<div className="woocommerce-attribute-field__empty-container">
							<img
								src={ AttributeEmptyStateLogo }
								alt="Completed"
								className="woocommerce-attribute-field__empty-logo"
							/>
							<Text
								variant="subtitle.small"
								weight="600"
								size="14"
								lineHeight="20px"
								className="woocommerce-attribute-field__empty-subtitle"
							>
								{ __( 'No attributes yet', 'woocommerce' ) }
							</Text>
							<Button
								variant="secondary"
								className="woocommerce-attribute-field__add-new"
								onClick={ () =>
									setShowAddAttributeModal( true )
								}
							>
								{ __( 'Add first attribute', 'woocommerce' ) }
							</Button>
						</div>
						{ showAddAttributeModal && (
							<AddAttributeModal
								onCancel={ () =>
									setShowAddAttributeModal( false )
								}
								onAdd={ onAddNewAttributes }
								selectedAttributeIds={ ( value || [] ).map(
									( attr ) => attr.id
								) }
							/>
						) }
					</div>
				</CardBody>
			</Card>
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
					<ListItem key={ attribute.id }>
						<div>{ attribute.name }</div>
						<div className="woocommerce-attribute-field__attribute-options">
							{ attribute.options
								.slice( 0, 2 )
								.map( ( option, index ) => (
									<div
										className="woocommerce-attribute-field__attribute-option-chip"
										key={ index }
									>
										{ option }
									</div>
								) ) }
							{ attribute.options.length > 2 && (
								<div className="woocommerce-attribute-field__attribute-option-chip">
									{ sprintf(
										__( '+ %i more', 'woocommerce' ),
										attribute.options.length - 2
									) }
								</div>
							) }
						</div>
						<div className="woocommerce-attribute-field__attribute-actions">
							<Button
								variant="tertiary"
								onClick={ () =>
									setEditingAttributeId( attribute.id )
								}
							>
								{ __( 'edit', 'woocommerce' ) }
							</Button>
							<Button
								icon={ closeSmall }
								label={ __(
									'Remove attribute',
									'woocommerce'
								) }
								onClick={ () => onRemove( attribute ) }
							></Button>
						</div>
					</ListItem>
				) ) }
			</Sortable>
			<ListItem>
				<Button
					variant="secondary"
					className="woocommerce-attribute-field__add-attribute"
					onClick={ () => setShowAddAttributeModal( true ) }
				>
					{ __( 'Add attribute', 'woocommerce' ) }
				</Button>
			</ListItem>
			{ showAddAttributeModal && (
				<AddAttributeModal
					onCancel={ () => setShowAddAttributeModal( false ) }
					onAdd={ onAddNewAttributes }
					selectedAttributeIds={ value.map( ( attr ) => attr.id ) }
				/>
			) }

			{ editingAttributeId && (
				<EditAttributeModal
					onCancel={ () => setEditingAttributeId( null ) }
					onEdit={ () => {} }
					clickedAttributeId={ editingAttributeId }
					allAttributes={ hydratedAttributes }
				/>
			) }
			<Popover.Slot />
		</div>
	);
};
