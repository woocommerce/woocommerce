/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement, Fragment, useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	Form,
	__experimentalSelectControlMenuSlot as SelectControlMenuSlot,
} from '@woocommerce/components';
import {
	EXPERIMENTAL_PRODUCT_ATTRIBUTES_STORE_NAME,
	type ProductAttributesActions,
	type WPDataActions,
	type ProductAttributeTerm,
	type ProductAttribute,
} from '@woocommerce/data';
import { Button, Modal, Notice, Tooltip } from '@wordpress/components';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { TRACKS_SOURCE } from '../../constants';
import { AttributeTableRow } from './attribute-table-row';
import type { EnhancedProductAttribute } from '../../hooks/use-product-attributes';
import type { AttributesComboboxControlItem } from '../attribute-combobox-field/types';
import { isAttributeFilledOut } from './utils';

type NewAttributeModalProps = {
	title?: string;
	description?: string | React.ReactElement;
	notice?: string;
	attributeLabel?: string;
	valueLabel?: string;
	attributePlaceholder?: string;
	termPlaceholder?: string;
	removeLabel?: string;
	addAnotherAccessibleLabel?: string;
	addAnotherLabel?: string;
	cancelLabel?: string;
	addAccessibleLabel?: string;
	addLabel?: string;
	onCancel: () => void;
	onAdd: ( newCategories: EnhancedProductAttribute[] ) => void;
	onAddAnother?: () => void;
	onRemoveItem?: () => void;
	selectedAttributeIds?: number[];
	createNewAttributesAsGlobal?: boolean;
	disabledAttributeIds?: number[];
	disabledAttributeMessage?: string;
	termsAutoSelection?: 'first' | 'all';
	defaultVisibility?: boolean;
	defaultSearch?: string;
};

type AttributeForm = {
	attributes: Array< EnhancedProductAttribute | null >;
};

/*
 * Sort criteria for the attributes.
 */
const attributeSortCriteria = { order_by: 'name' };

export const NewAttributeModal: React.FC< NewAttributeModalProps > = ( {
	title = __( 'Add attributes', 'woocommerce' ),
	description = '',
	notice,
	attributeLabel = __( 'Attribute', 'woocommerce' ),
	valueLabel = __( 'Values', 'woocommerce' ),
	attributePlaceholder = __( 'Search or create attribute', 'woocommerce' ),
	termPlaceholder = __( 'Search or create value', 'woocommerce' ),
	removeLabel = __( 'Remove attribute', 'woocommerce' ),
	addAnotherAccessibleLabel = __( 'Add another attribute', 'woocommerce' ),
	addAnotherLabel = __( '+ Add another', 'woocommerce' ),
	cancelLabel = __( 'Cancel', 'woocommerce' ),
	addAccessibleLabel = __( 'Add attributes', 'woocommerce' ),
	addLabel = __( 'Add', 'woocommerce' ),
	onCancel,
	onAdd,
	onAddAnother = () => {},
	onRemoveItem = () => {},
	selectedAttributeIds = [],
	createNewAttributesAsGlobal = false,
	disabledAttributeIds = [],
	disabledAttributeMessage = __(
		'Already used in Attributes',
		'woocommerce'
	),
	termsAutoSelection,
	defaultVisibility = false,
	defaultSearch,
} ) => {
	const scrollAttributeIntoView = ( index: number ) => {
		setTimeout( () => {
			const attributeRow = document.querySelector(
				`.woocommerce-new-attribute-modal__table-row-${ index }`
			);
			attributeRow?.scrollIntoView( { behavior: 'smooth' } );
		}, 0 );
	};
	const addAnother = (
		values: AttributeForm,
		setValue: (
			name: string,
			value: AttributeForm[ keyof AttributeForm ]
		) => void
	) => {
		setValue( 'attributes', [ ...values.attributes, null ] );
		scrollAttributeIntoView( values.attributes.length );
		onAddAnother();
	};

	const isGlobalAttribute = (
		attribute: EnhancedProductAttribute
	): boolean => {
		return attribute.id !== 0;
	};

	const mapTermsToOptions = ( terms: ProductAttributeTerm[] | undefined ) => {
		if ( ! terms ) {
			return [];
		}

		return terms.map( ( term ) => term.name );
	};

	const getOptions = ( attribute: EnhancedProductAttribute ) => {
		return isGlobalAttribute( attribute )
			? mapTermsToOptions( attribute.terms )
			: attribute.options;
	};

	const getVisibleOrTrue = ( attribute: EnhancedProductAttribute ) =>
		attribute.visible !== undefined ? attribute.visible : defaultVisibility;

	const onAddingAttributes = ( values: AttributeForm ) => {
		const newAttributesToAdd: EnhancedProductAttribute[] = [];
		values.attributes.forEach( ( attr ) => {
			if ( isAttributeFilledOut( attr ) ) {
				newAttributesToAdd.push( {
					...attr,
					visible: getVisibleOrTrue( attr ),
					options: getOptions( attr ),
				} );
			}
		} );
		onAdd( newAttributesToAdd );
	};

	const onRemove = (
		index: number,
		values: AttributeForm,
		setValue: (
			name: string,
			value: AttributeForm[ keyof AttributeForm ] | null
		) => void
	) => {
		onRemoveItem();
		if ( values.attributes.length > 1 ) {
			setValue(
				'attributes',
				values.attributes.filter( ( val, i ) => i !== index )
			);
		} else {
			setValue( `attributes[${ index }]`, null );
		}
	};

	useEffect( function focusFirstAttributeField() {
		const firstAttributeFieldLabel =
			document.querySelector< HTMLLabelElement >(
				'.woocommerce-new-attribute-modal__table-row .woocommerce-attribute-input-field label'
			);
		const timeoutId = setTimeout( () => {
			firstAttributeFieldLabel?.focus();
		}, 100 );

		return () => clearTimeout( timeoutId );
	}, [] );

	const initialAttribute = {
		name: defaultSearch,
	} as EnhancedProductAttribute;

	const { attributes, isLoadingAttributes } = useSelect(
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore
		( select: WCDataSelector ) => {
			const {
				getProductAttributes: getAttributes,
				hasFinishedResolution: hasLoadedAttributes,
			} = select( EXPERIMENTAL_PRODUCT_ATTRIBUTES_STORE_NAME );

			return {
				isLoadingAttributes: ! hasLoadedAttributes(
					'getProductAttributes',
					[ attributeSortCriteria ]
				),
				attributes: getAttributes( attributeSortCriteria ),
			};
		},
		[]
	);

	const { createErrorNotice } = useDispatch( 'core/notices' );
	const { createProductAttribute } = useDispatch(
		EXPERIMENTAL_PRODUCT_ATTRIBUTES_STORE_NAME
	) as unknown as ProductAttributesActions & WPDataActions;

	return (
		<>
			<Form< AttributeForm >
				initialValues={ {
					attributes: [ defaultSearch ? initialAttribute : null ],
				} }
			>
				{ ( {
					values,
					setValue,
				}: {
					values: AttributeForm;
					// eslint-disable-next-line @typescript-eslint/no-explicit-any
					setValue: ( name: string, value: any ) => void;
				} ) => {
					const isAddButtonDisabled = ! values.attributes.every(
						( attr ) => isAttributeFilledOut( attr )
					);

					/**
					 * Select the attribute in the form field.
					 * If the attribute does not exist, create it.
					 * ToDo: Improve Id. Adding a attribute with id -99
					 * does not seem a good idea.
					 *
					 * @param {AttributesComboboxControlItem} nextAttribute - The attribute to select.
					 * @param { number }                      index         - The index of the attribute in the form field.
					 * @return { void }
					 */
					function selectAttribute(
						nextAttribute: AttributesComboboxControlItem,
						index: number
					): void {
						recordEvent( 'product_attribute_add_custom_attribute', {
							source: TRACKS_SOURCE,
						} );

						return setValue(
							`attributes[${ index }]`,
							nextAttribute
						);
					}

					/**
					 * Create a new attribute and fill the form field with it.
					 * If the attribute is not global, create it locally.
					 *
					 * @param {string} newAttributeName - The name of the new attribute.
					 * @param {number} index            - The index of the attribute in the form field.
					 * @return {void}
					 */
					function addNewAttribute(
						newAttributeName: string,
						index: number
					): void {
						if ( ! createNewAttributesAsGlobal ) {
							return setValue( `attributes[${ index }]`, {
								id: 0,
								name: newAttributeName,
								slug: newAttributeName,
							} );
						}

						createProductAttribute(
							{
								name: newAttributeName,
								generate_slug: true,
							},
							{
								optimisticQueryUpdate: attributeSortCriteria,
							}
						)
							.then( ( newAttribute ) => {
								setValue(
									`attributes[${ index }]`,
									newAttribute
								);
							} )
							.catch( ( error ) => {
								let message = __(
									'Failed to create new attribute.',
									'woocommerce'
								);
								if (
									error.code ===
									'woocommerce_rest_cannot_create'
								) {
									message = error.message;
								}

								createErrorNotice( message, {
									explicitDismiss: true,
								} );
							} );
					}

					/**
					 * Set the attribute terms in the form field.
					 *
					 * @param {ProductAttributeTerm[] | string[]} terms     - The terms to set.
					 * @param {number}                            index     - The index of the attribute in the form field.
					 * @param {EnhancedProductAttribute}          attribute - The attribute to set the terms.
					 */
					function selectTerms(
						terms: ProductAttributeTerm[] | string[],
						index: number,
						attribute?: EnhancedProductAttribute
					) {
						const attributeTermPropName =
							attribute && isGlobalAttribute( attribute )
								? 'terms'
								: 'options';

						const fieldName = `attributes[${ index }].${ attributeTermPropName }`;

						setValue( fieldName, terms );
					}

					/*
					 * Get the attribute ids that are already selected
					 * by other form fields.
					 */
					const attributeBelongTo = values.attributes
						.map( ( attr ) => ( attr ? attr.id : null ) )
						.filter( ( id ) => typeof id === 'number' );

					/*
					 * Compute the available attributes to show in the attribute input field,
					 * filtering out the ignored attributes,
					 * marking the disabled ones,
					 * and setting the `takenBy` property.
					 */
					const availableAttributes = attributes
						?.filter(
							( attribute: ProductAttribute ) =>
								! selectedAttributeIds.includes( attribute.id )
						)
						?.map( ( attribute: ProductAttribute ) => ( {
							...attribute,
							isDisabled: disabledAttributeIds.includes(
								attribute.id
							),
							takenBy: attributeBelongTo.indexOf( attribute.id ),
						} ) ) as AttributesComboboxControlItem[];

					return (
						<Modal
							title={ title }
							onRequestClose={ (
								event:
									| React.KeyboardEvent< Element >
									| React.MouseEvent< Element >
									| React.FocusEvent< Element >
							) => {
								if ( ! event?.isPropagationStopped() ) {
									onCancel();
								}
							} }
							className="woocommerce-new-attribute-modal"
							// eslint-disable-next-line @typescript-eslint/ban-ts-comment
							// @ts-ignore
							size="medium"
						>
							{ notice && (
								<Notice isDismissible={ false }>
									<p>{ notice }</p>
								</Notice>
							) }

							{ description && <p>{ description }</p> }

							<div className="woocommerce-new-attribute-modal__body">
								<table className="woocommerce-new-attribute-modal__table">
									<thead>
										<tr className="woocommerce-new-attribute-modal__table-header">
											<th>{ attributeLabel }</th>
											<th>{ valueLabel }</th>
										</tr>
									</thead>
									<tbody>
										{ values.attributes.map(
											( attribute, index ) => (
												<AttributeTableRow
													key={ index }
													index={ index }
													attribute={ attribute }
													attributePlaceholder={
														attributePlaceholder
													}
													disabledAttributeMessage={
														disabledAttributeMessage
													}
													isLoadingAttributes={
														isLoadingAttributes
													}
													attributes={
														availableAttributes
													}
													onNewAttributeAdd={
														addNewAttribute
													}
													onAttributeSelect={
														selectAttribute
													}
													termPlaceholder={
														termPlaceholder
													}
													removeLabel={ removeLabel }
													onTermsSelect={
														selectTerms
													}
													onRemove={ (
														removedIndex
													) =>
														onRemove(
															removedIndex,
															values,
															setValue
														)
													}
													termsAutoSelection={
														termsAutoSelection
													}
												/>
											)
										) }
									</tbody>
								</table>
							</div>
							<div>
								<Button
									className="woocommerce-new-attribute-modal__add-attribute"
									variant="tertiary"
									label={ addAnotherAccessibleLabel }
									onClick={ () => {
										addAnother( values, setValue );
									} }
								>
									{ addAnotherLabel }
								</Button>
							</div>
							<div className="woocommerce-new-attribute-modal__buttons">
								<Button
									isSecondary
									label={ cancelLabel }
									onClick={ () => onCancel() }
								>
									{ cancelLabel }
								</Button>
								<Tooltip
									text={
										isAddButtonDisabled
											? __(
													'Add at least one attribute and one value. Press Enter to select.',
													'woocommerce'
											  )
											: ''
									}
								>
									{ /*
									 * we need to wrap the button in a div to make the tooltip work,
									 * since when the button is disabled, the tooltip is not shown.
									 */ }
									<div>
										<Button
											variant="primary"
											label={ addAccessibleLabel }
											showTooltip={ true }
											disabled={ isAddButtonDisabled }
											onClick={ () =>
												onAddingAttributes( values )
											}
										>
											{ addLabel }
										</Button>
									</div>
								</Tooltip>
							</div>
						</Modal>
					);
				} }
			</Form>
			{ /* Add slot so select control menu renders correctly within Modal */ }
			<SelectControlMenuSlot />
		</>
	);
};
