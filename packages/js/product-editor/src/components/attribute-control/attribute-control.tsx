/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	useState,
	createElement,
	Fragment,
	createInterpolateElement,
} from '@wordpress/element';
import { Button, Notice } from '@wordpress/components';
import { ProductAttribute } from '@woocommerce/data';
import {
	Sortable,
	__experimentalSelectControlMenuSlot as SelectControlMenuSlot,
	Link,
} from '@woocommerce/components';
import { getAdminLink } from '@woocommerce/settings';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { EditAttributeModal } from './edit-attribute-modal';
import { EnhancedProductAttribute } from '../../hooks/use-product-attributes';
import {
	getAttributeId,
	getAttributeKey,
	reorderSortableProductAttributePositions,
} from './utils';
import { AttributeListItem } from '../attribute-list-item';
import { NewAttributeModal } from './new-attribute-modal';
import { RemoveConfirmationModal } from './remove-confirmation-modal';

type AttributeControlProps = {
	value: EnhancedProductAttribute[];
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
	onNoticeDismiss?: () => void;
	createNewAttributesAsGlobal?: boolean;
	useRemoveConfirmationModal?: boolean;
	uiStrings?: {
		notice?: string | React.ReactElement;
		emptyStateSubtitle?: string;
		newAttributeListItemLabel?: string;
		newAttributeModalTitle?: string;
		newAttributeModalDescription?: string | React.ReactElement;
		newAttributeModalNotice?: string;
		customAttributeHelperMessage?: string;
		attributeRemoveLabel?: string;
		attributeRemoveConfirmationMessage?: string;
		attributeRemoveConfirmationModalMessage?: string;
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
	onNoticeDismiss = () => {},
	uiStrings,
	createNewAttributesAsGlobal = false,
	useRemoveConfirmationModal = false,
} ) => {
	uiStrings = {
		newAttributeListItemLabel: __( 'Add new', 'woocommerce' ),
		globalAttributeHelperMessage: __(
			`You can change the attribute's name in <link>Attributes</link>.`,
			'woocommerce'
		),
		newAttributeModalNotice: __(
			'By default, attributes are filterable and visible on the product page. You can change these settings for each attribute separately later.',
			'woocommerce'
		),
		attributeRemoveConfirmationMessage: __(
			'Remove this attribute?',
			'woocommerce'
		),
		...uiStrings,
	};
	const [ isNewModalVisible, setIsNewModalVisible ] = useState( false );
	const [ removingAttribute, setRemovingAttribute ] =
		useState< null | ProductAttribute >();
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
		handleChange(
			value.filter(
				( attr ) =>
					getAttributeId( attr ) !== getAttributeId( attribute )
			)
		);
		onRemove( attribute );
		setRemovingAttribute( null );
	};

	const showRemoveConfirmation = ( attribute: ProductAttribute ) => {
		if ( useRemoveConfirmationModal ) {
			setRemovingAttribute( attribute );
			return;
		}
		// eslint-disable-next-line no-alert
		if ( window.confirm( uiStrings?.attributeRemoveConfirmationMessage ) ) {
			handleRemove( attribute );
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
	);

	return (
		<div className="woocommerce-attribute-field">
			<Button
				variant="secondary"
				className="woocommerce-add-attribute-list-item__add-button"
				onClick={ () => {
					openNewModal();
					recordEvent( 'product_add_attributes_click' );
				} }
			>
				{ uiStrings.newAttributeListItemLabel }
			</Button>
			{ uiStrings.notice && (
				<Notice
					isDismissible={ true }
					status="warning"
					className="woocommerce-attribute-field__notice"
					onRemove={ onNoticeDismiss }
				>
					<p>{ uiStrings.notice }</p>
				</Notice>
			) }
			{ Boolean( value.length ) && (
				<Sortable
					onOrderChange={ ( items ) => {
						const itemPositions = items.reduce(
							( positions, { props }, index ) => {
								positions[
									getAttributeKey( props.attribute )
								] = index;
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
							removeLabel={ uiStrings?.attributeRemoveLabel }
							key={ getAttributeId( attr ) }
							onEditClick={ () => openEditModal( attr ) }
							onRemoveClick={ () =>
								showRemoveConfirmation( attr )
							}
						/>
					) ) }
				</Sortable>
			) }

			{ isNewModalVisible && (
				<NewAttributeModal
					title={ uiStrings.newAttributeModalTitle }
					description={ uiStrings.newAttributeModalDescription }
					notice={ uiStrings.newAttributeModalNotice }
					onCancel={ () => {
						closeNewModal();
						onNewModalCancel();
					} }
					onAdd={ handleAdd }
					selectedAttributeIds={ value.map( ( attr ) => attr.id ) }
					createNewAttributesAsGlobal={ createNewAttributesAsGlobal }
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
					customAttributeHelperMessage={
						uiStrings.customAttributeHelperMessage
					}
					globalAttributeHelperMessage={ createInterpolateElement(
						uiStrings.globalAttributeHelperMessage,
						{
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
						}
					) }
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
			{ removingAttribute && (
				<RemoveConfirmationModal
					title={ sprintf(
						__( 'Delete %(attributeName)s', 'woocommerce' ),
						{ attributeName: removingAttribute.name }
					) }
					description={
						uiStrings.attributeRemoveConfirmationModalMessage
					}
					onRemove={ () => handleRemove( removingAttribute ) }
					onCancel={ () => {
						onRemoveCancel( removingAttribute );
						setRemovingAttribute( null );
					} }
				/>
			) }
		</div>
	);
};
