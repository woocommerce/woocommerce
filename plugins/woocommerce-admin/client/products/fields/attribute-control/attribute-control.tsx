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
	getAttributeId,
	getAttributeKey,
	reorderSortableProductAttributePositions,
} from './utils';
import { AttributeEmptyState } from '../attribute-empty-state';
import {
	AttributeListItem,
	NewAttributeListItem,
} from '../attribute-list-item';

type AttributeControlProps = {
	value: ProductAttribute[];
	onChange: ( value: ProductAttribute[] ) => void;
	onEdit?: ( attribute: ProductAttribute ) => void;
	onModalClose?: ( attribute?: ProductAttribute ) => void;
	onModalOpen?: ( attribute?: ProductAttribute ) => void;
	text?: {
		addAttributeModalTitle?: string;
		emptyStateSubtitle?: string;
		newAttributeListItemLabel?: string;
		globalAttributeHelperMessage: string;
	};
};

export const AttributeControl: React.FC< AttributeControlProps > = ( {
	value,
	onChange,
	onEdit,
	onModalClose,
	onModalOpen,
	text = {
		addAttributeModalTitle: undefined,
		emptyStateSubtitle: undefined,
		newAttributeListItemLabel: undefined,
		globalAttributeHelperMessage: __(
			`You can change the attribute's name in {{link}}Attributes{{/link}}.`,
			'woocommerce'
		),
	},
} ) => {
	const [ isNewModalVisible, setIsNewModalVisible ] = useState( false );
	const [ currentAttributeId, setCurrentAttributeId ] = useState<
		null | string
	>( null );

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
						getAttributeId( attr ) !== getAttributeId( attribute )
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
		setIsNewModalVisible( false );
	};

	const openModal = ( attribute?: ProductAttribute ) => {
		if ( attribute ) {
			setCurrentAttributeId( getAttributeId( attribute ) );
		} else {
			setIsNewModalVisible( true );
		}
		if ( typeof onModalOpen === 'function' ) {
			onModalOpen( attribute );
		}
	};

	const closeModal = ( attribute?: ProductAttribute ) => {
		if ( attribute ) {
			setCurrentAttributeId( null );
		} else {
			setIsNewModalVisible( false );
		}
		if ( typeof onModalClose === 'function' ) {
			onModalClose( attribute );
		}
	};

	const handleEdit = ( updatedAttribute: ProductAttribute ) => {
		const updatedAttributes = value.map( ( attr ) => {
			if (
				getAttributeId( attr ) === getAttributeId( updatedAttribute )
			) {
				return updatedAttribute;
			}

			return attr;
		} );

		if ( typeof onEdit === 'function' ) {
			onEdit( updatedAttribute );
		}
		handleChange( updatedAttributes );
		closeModal( updatedAttribute );
	};

	if ( ! value.length ) {
		return (
			<>
				<AttributeEmptyState
					addNewLabel={ text.addAttributeModalTitle }
					onNewClick={ () => openModal() }
					subtitle={ text.emptyStateSubtitle }
				/>
				{ isNewModalVisible && (
					<AddAttributeModal
						onCancel={ () => {
							closeModal();
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

	const currentAttribute = value.find(
		( attr ) => getAttributeId( attr ) === currentAttributeId
	) as EnhancedProductAttribute;

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
						key={ getAttributeId( attr ) }
						onEditClick={ () => openModal( attr ) }
						onRemoveClick={ () => onRemove( attr ) }
					/>
				) ) }
			</Sortable>
			<NewAttributeListItem
				label={ text.newAttributeListItemLabel }
				onClick={ () => openModal() }
			/>
			{ isNewModalVisible && (
				<AddAttributeModal
					title={ text.addAttributeModalTitle }
					onCancel={ () => closeModal() }
					onAdd={ onAddNewAttributes }
					selectedAttributeIds={ value.map( ( attr ) => attr.id ) }
				/>
			) }
			<SelectControlMenuSlot />
			{ currentAttribute && (
				<EditAttributeModal
					title={ sprintf(
						/* translators: %s is the attribute name */
						__( 'Edit %s', 'woocommerce' ),
						currentAttribute.name
					) }
					globalAttributeHelperMessage={ interpolateComponents( {
						mixedString: text.globalAttributeHelperMessage,
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
					onCancel={ () => closeModal() }
					onEdit={ ( updatedAttribute ) => {
						handleEdit( updatedAttribute );
					} }
					attribute={ currentAttribute }
				/>
			) }
		</div>
	);
};
