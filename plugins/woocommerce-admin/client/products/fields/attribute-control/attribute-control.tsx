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
	onRemoveCancel?: ( attribute: ProductAttribute ) => void;
	onNewModalCancel?: () => void;
	onNewModalClose?: () => void;
	onNewModalOpen?: () => void;
	onEditModalCancel?: ( attribute?: ProductAttribute ) => void;
	onEditModalClose?: ( attribute?: ProductAttribute ) => void;
	onEditModalOpen?: ( attribute?: ProductAttribute ) => void;
	uiStrings?: {
		emptyStateSubtitle?: string;
		newAttributeListItemLabel?: string;
		newAttributeModalTitle?: string;
		globalAttributeHelperMessage: string;
	};
};

export const AttributeControl: React.FC< AttributeControlProps > = ( {
	value,
	onAdd = () => {},
	onChange,
	onEdit = () => {},
	onNewModalCancel = () => {},
	onNewModalClose = () => {},
	onNewModalOpen = () => {},
	onEditModalCancel = () => {},
	onEditModalClose = () => {},
	onEditModalOpen = () => {},
	onRemove = () => {},
	onRemoveCancel = () => {},
	uiStrings = {
		newAttributeModalTitle: undefined,
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
			handleChange(
				value.filter(
					( attr ) =>
						getAttributeId( attr ) !== getAttributeId( attribute )
				)
			);
			onRemove( attribute );
			return;
		}
		onRemoveCancel( attribute );
	};

	const openNewModal = () => {
		setIsNewModalVisible( true );
		onNewModalOpen();
	};

	const closeNewModal = () => {
		setIsNewModalVisible( false );
		onNewModalClose();
	};

	const openEditModal = ( attribute: ProductAttribute ) => {
		setCurrentAttributeId( getAttributeId( attribute ) );
		onEditModalOpen( attribute );
	};

	const closeEditModal = ( attribute: ProductAttribute ) => {
		setCurrentAttributeId( null );
		onEditModalClose( attribute );
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
		onAdd( newAttributes );
		closeNewModal();
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

		onEdit( updatedAttribute );
		handleChange( updatedAttributes );
		closeEditModal( updatedAttribute );
	};

	if ( ! value.length ) {
		return (
			<>
				<AttributeEmptyState
					addNewLabel={ uiStrings.newAttributeModalTitle }
					onNewClick={ () => openNewModal() }
					subtitle={ uiStrings.emptyStateSubtitle }
				/>
				{ isNewModalVisible && (
					<NewAttributeModal
						onCancel={ () => {
							closeNewModal();
							onNewModalCancel();
						} }
						onAdd={ handleAdd }
						selectedAttributeIds={ [] }
						title={ uiStrings.newAttributeModalTitle }
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
						onEditClick={ () => openEditModal( attr ) }
						onRemoveClick={ () => handleRemove( attr ) }
					/>
				) ) }
			</Sortable>
			<NewAttributeListItem
				label={ uiStrings.newAttributeListItemLabel }
				onClick={ () => openNewModal() }
			/>
			{ isNewModalVisible && (
				<NewAttributeModal
					title={ uiStrings.newAttributeModalTitle }
					onCancel={ () => {
						closeNewModal();
						onNewModalCancel();
					} }
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
						mixedString: uiStrings.globalAttributeHelperMessage,
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
					onCancel={ () => {
						closeEditModal( currentAttribute );
						onEditModalCancel( currentAttribute );
					} }
					onEdit={ ( updatedAttribute ) => {
						handleEdit( updatedAttribute );
					} }
					attribute={ currentAttribute }
				/>
			) }
		</div>
	);
};
