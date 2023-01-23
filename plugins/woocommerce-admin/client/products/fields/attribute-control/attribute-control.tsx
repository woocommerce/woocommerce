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
import { NewAttributeModal } from './new-attribute-modal';

type AttributeControlProps = {
	value: ProductAttribute[];
	onAdd?: ( attribute: EnhancedProductAttribute[] ) => void;
	onChange: ( value: ProductAttribute[] ) => void;
	onEdit?: ( attribute: ProductAttribute ) => void;
	onRemove?: ( attribute: ProductAttribute ) => void;
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
	onAdd,
	onChange,
	onEdit,
	onModalClose,
	onModalOpen,
	onRemove,
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

	const handleRemove = ( attribute: ProductAttribute ) => {
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
			if ( typeof onRemove === 'function' ) {
				onRemove( attribute );
			}
		} else {
			recordEvent( 'product_remove_attribute_confirmation_cancel_click' );
		}
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

	const handleAdd = ( newAttributes: EnhancedProductAttribute[] ) => {
		handleChange( [
			...value,
			...newAttributes.filter(
				( newAttr ) =>
					! value.find(
						( attr ) =>
							getAttributeId( newAttr ) === getAttributeId( attr )
					)
			),
		] );
		if ( typeof onAdd === 'function' ) {
			onAdd( newAttributes );
		}
		recordEvent( 'product_add_attributes_modal_add_button_click' );
		closeModal();
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
					<NewAttributeModal
						onCancel={ () => {
							closeModal();
						} }
						onAdd={ handleAdd }
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
						onRemoveClick={ () => handleRemove( attr ) }
					/>
				) ) }
			</Sortable>
			<NewAttributeListItem
				label={ text.newAttributeListItemLabel }
				onClick={ () => openModal() }
			/>
			{ isNewModalVisible && (
				<NewAttributeModal
					title={ text.addAttributeModalTitle }
					onCancel={ () => closeModal() }
					onAdd={ handleAdd }
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
