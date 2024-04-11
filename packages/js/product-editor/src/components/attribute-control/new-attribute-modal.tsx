/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement, Fragment, useEffect } from '@wordpress/element';
import { resolveSelect } from '@wordpress/data';
import { closeSmall } from '@wordpress/icons';
import {
	Form,
	__experimentalSelectControlMenuSlot as SelectControlMenuSlot,
} from '@woocommerce/components';
import {
	EXPERIMENTAL_PRODUCT_ATTRIBUTE_TERMS_STORE_NAME,
	ProductAttribute,
	ProductAttributeTerm,
} from '@woocommerce/data';
import { Button, Modal, Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { AttributeInputField } from '../attribute-input-field';
import {
	AttributeTermInputField,
	CustomAttributeTermInputField,
} from '../attribute-term-input-field';
import { EnhancedProductAttribute } from '../../hooks/use-product-attributes';
import { getProductAttributeObject } from './utils';

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

const MAX_N_TERMS_AUTO_SELECTION = 10;

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

	const hasTermsOrOptions = ( attribute: EnhancedProductAttribute ) => {
		return (
			( attribute.terms && attribute.terms.length > 0 ) ||
			( attribute.options && attribute.options.length > 0 )
		);
	};

	const isGlobalAttribute = ( attribute: EnhancedProductAttribute ) => {
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

	const isAttributeFilledOut = (
		attribute: EnhancedProductAttribute | null
	): attribute is EnhancedProductAttribute => {
		return (
			attribute !== null &&
			attribute.name.length > 0 &&
			hasTermsOrOptions( attribute )
		);
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
			value: AttributeForm[ keyof AttributeForm ]
		) => void
	) => {
		onRemoveItem();
		if ( values.attributes.length > 1 ) {
			setValue(
				'attributes',
				values.attributes.filter( ( val, i ) => i !== index )
			);
		} else {
			setValue( `attributes[${ index }]`, [ null ] );
		}
	};

	const focusValueField = ( index: number ) => {
		setTimeout( () => {
			const valueInputField: HTMLInputElement | null =
				document.querySelector(
					'.woocommerce-new-attribute-modal__table-row-' +
						index +
						' .woocommerce-new-attribute-modal__table-attribute-value-column .woocommerce-experimental-select-control__input'
				);
			if ( valueInputField ) {
				valueInputField.focus();
			}
		}, 0 );
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
					function getAttributeOnChange( index: number ) {
						return function handleAttributeChange(
							value?:
								| Omit<
										ProductAttribute,
										'position' | 'visible' | 'variation'
								  >
								| string
						) {
							if (
								termsAutoSelection &&
								value &&
								! ( typeof value === 'string' )
							) {
								resolveSelect(
									EXPERIMENTAL_PRODUCT_ATTRIBUTE_TERMS_STORE_NAME
								)
									.getProductAttributeTerms<
										ProductAttributeTerm[]
									>( {
										// Send search parameter as empty to avoid a second
										// request when focusing the attribute-term-input-field
										// which perform the same request to get all the terms
										search: '',
										attribute_id: value.id,
									} )
									.then( ( terms ) => {
										const selectedAttribute =
											getProductAttributeObject(
												value
											) as EnhancedProductAttribute;
										if (
											termsAutoSelection === 'all' &&
											terms.length <=
												MAX_N_TERMS_AUTO_SELECTION
										) {
											selectedAttribute.terms = terms;
										} else if ( terms.length > 0 ) {
											selectedAttribute.terms = [
												terms[ 0 ],
											];
										}
										setValue( 'attributes[' + index + ']', {
											...selectedAttribute,
										} );
										focusValueField( index );
									} );
							} else {
								setValue(
									'attributes[' + index + ']',
									value && getProductAttributeObject( value )
								);
								if ( value ) {
									focusValueField( index );
								}
							}
						};
					}

					return (
						<Modal
							title={ title }
							onRequestClose={ (
								event:
									| React.KeyboardEvent< Element >
									| React.MouseEvent< Element >
									| React.FocusEvent< Element >
							) => {
								if ( ! event.isPropagationStopped() ) {
									onCancel();
								}
							} }
							className="woocommerce-new-attribute-modal"
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
												<tr
													key={ index }
													className={ `woocommerce-new-attribute-modal__table-row woocommerce-new-attribute-modal__table-row-${ index }` }
												>
													<td className="woocommerce-new-attribute-modal__table-attribute-column">
														<AttributeInputField
															placeholder={
																attributePlaceholder
															}
															value={ attribute }
															label={
																attributeLabel
															}
															onChange={ getAttributeOnChange(
																index
															) }
															ignoredAttributeIds={ [
																...selectedAttributeIds,
																...values.attributes
																	.map(
																		(
																			attr
																		) =>
																			attr?.id
																	)
																	.filter(
																		(
																			attrId
																		): attrId is number =>
																			attrId !==
																			undefined
																	),
															] }
															createNewAttributesAsGlobal={
																createNewAttributesAsGlobal
															}
															disabledAttributeIds={
																disabledAttributeIds
															}
															disabledAttributeMessage={
																disabledAttributeMessage
															}
														/>
													</td>
													<td className="woocommerce-new-attribute-modal__table-attribute-value-column">
														{ ! attribute ||
														attribute.id !== 0 ? (
															<AttributeTermInputField
																placeholder={
																	attribute?.terms &&
																	attribute
																		?.terms
																		.length >
																		0
																		? ''
																		: termPlaceholder
																}
																disabled={
																	attribute
																		? ! attribute.id
																		: true
																}
																attributeId={
																	attribute
																		? attribute.id
																		: undefined
																}
																value={
																	attribute ===
																		null ||
																	attribute ===
																		undefined
																		? []
																		: attribute.terms
																}
																label={
																	valueLabel
																}
																onChange={ (
																	val
																) =>
																	setValue(
																		'attributes[' +
																			index +
																			'].terms',
																		val
																	)
																}
															/>
														) : (
															<CustomAttributeTermInputField
																placeholder={
																	attribute?.options &&
																	attribute
																		?.options
																		.length >
																		0
																		? ''
																		: termPlaceholder
																}
																disabled={
																	! attribute.name
																}
																value={
																	attribute.options
																}
																label={
																	valueLabel
																}
																onChange={ (
																	val
																) =>
																	setValue(
																		'attributes[' +
																			index +
																			'].options',
																		val
																	)
																}
															/>
														) }
													</td>
													<td className="woocommerce-new-attribute-modal__table-attribute-trash-column">
														<Button
															icon={ closeSmall }
															disabled={
																values
																	.attributes
																	.length ===
																	1 &&
																values
																	.attributes[ 0 ] ===
																	null
															}
															label={
																removeLabel
															}
															onClick={ () =>
																onRemove(
																	index,
																	values,
																	setValue
																)
															}
														></Button>
													</td>
												</tr>
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
								<Button
									isPrimary
									label={ addAccessibleLabel }
									disabled={
										values.attributes.length === 1 &&
										( values.attributes[ 0 ] === null ||
											values.attributes[ 0 ] ===
												undefined )
									}
									onClick={ () =>
										onAddingAttributes( values )
									}
								>
									{ addLabel }
								</Button>
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
