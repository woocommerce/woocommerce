/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, createElement, Fragment } from '@wordpress/element';
import { trash } from '@wordpress/icons';
import {
	Form,
	__experimentalSelectControlMenuSlot as SelectControlMenuSlot,
} from '@woocommerce/components';
import { ProductAttributeTerm } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import {
	Button,
	Modal,
	Notice,
	// @ts-expect-error ConfirmDialog is not part of the typescript definition yet.
	__experimentalConfirmDialog as ConfirmDialog,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { AttributeInputField } from '../attribute-input-field';
import {
	AttributeTermInputField,
	CustomAttributeTermInputField,
} from '../attribute-term-input-field';
import { EnhancedProductAttribute } from '../../hooks/use-product-attributes';
import { getProductAttributeObject } from '../attribute-control/utils';

type NewAttributeModalProps = {
	title?: string;
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
	confirmMessage?: string;
	confirmCancelLabel?: string;
	confirmConfirmLabel?: string;
	onCancel: () => void;
	onAdd: ( newCategories: EnhancedProductAttribute[] ) => void;
	selectedAttributeIds?: number[];
};

type AttributeForm = {
	attributes: Array< EnhancedProductAttribute | null >;
};

export const NewVariationOptionModal: React.FC< NewAttributeModalProps > = ( {
	title = __( 'Add variation options', 'woocommerce' ),
	notice = '',
	attributeLabel = __( 'Variation option', 'woocommerce' ),
	valueLabel = __( 'Values', 'woocommerce' ),
	attributePlaceholder = __(
		'Search or create variation option',
		'woocommerce'
	),
	termPlaceholder = __( 'Search or create value', 'woocommerce' ),
	removeLabel = __( 'Remove variation option', 'woocommerce' ),
	addAnotherAccessibleLabel = __(
		'Add another variation option',
		'woocommerce'
	),
	addAnotherLabel = __( '+ Add another', 'woocommerce' ),
	cancelLabel = __( 'Cancel', 'woocommerce' ),
	addAccessibleLabel = __( 'Add variation options', 'woocommerce' ),
	addLabel = __( 'Add', 'woocommerce' ),
	confirmMessage = __(
		'You have some variation options added to the list, are you sure you want to cancel?',
		'woocommerce'
	),
	confirmCancelLabel = __( 'No thanks', 'woocommerce' ),
	confirmConfirmLabel = __( 'Yes please!', 'woocommerce' ),
	onCancel,
	onAdd,
	selectedAttributeIds = [],
} ) => {
	const scrollAttributeIntoView = ( index: number ) => {
		setTimeout( () => {
			const attributeRow = document.querySelector(
				`.woocommerce-new-variation-option-modal__table-row-${ index }`
			);
			attributeRow?.scrollIntoView( { behavior: 'smooth' } );
		}, 0 );
	};
	const [ showConfirmClose, setShowConfirmClose ] = useState( false );
	const addAnother = (
		values: AttributeForm,
		setValue: (
			name: string,
			value: AttributeForm[ keyof AttributeForm ]
		) => void
	) => {
		setValue( 'attributes', [ ...values.attributes, null ] );
		scrollAttributeIntoView( values.attributes.length );
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
		attribute.visible !== undefined ? attribute.visible : true;

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
		recordEvent(
			'product_add_variation_options_modal_remove_variation_option_button_click'
		);
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
					'.woocommerce-new-variation-option-modal__table-row-' +
						index +
						' .woocommerce-new-variation-option-modal__table-attribute-value-column .woocommerce-experimental-select-control__input'
				);
			if ( valueInputField ) {
				valueInputField.focus();
			}
		}, 0 );
	};

	const onClose = ( values: AttributeForm ) => {
		const hasValuesSet = values.attributes.some(
			( value ) =>
				value !== null && value?.terms && value?.terms.length > 0
		);
		if ( hasValuesSet ) {
			setShowConfirmClose( true );
		} else {
			onCancel();
		}
	};

	return (
		<>
			<Form< AttributeForm >
				initialValues={ {
					attributes: [ null ],
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
									onClose( values );
								}
							} }
							className="woocommerce-new-variation-option-modal"
						>
							{ notice && (
								<Notice isDismissible={ false }>
									<p>{ notice }</p>
								</Notice>
							) }

							<div className="woocommerce-new-variation-option-modal__body">
								<table className="woocommerce-new-variation-option-modal__table">
									<thead>
										<tr className="woocommerce-new-variation-option-modal__table-header">
											<th>{ attributeLabel }</th>
											<th>{ valueLabel }</th>
										</tr>
									</thead>
									<tbody>
										{ values.attributes.map(
											( attribute, index ) => (
												<tr
													key={ index }
													className={ `woocommerce-new-variation-option-modal__table-row woocommerce-new-variation-option-modal__table-row-${ index }` }
												>
													<td className="woocommerce-new-variation-option-modal__table-attribute-column">
														<AttributeInputField
															placeholder={
																attributePlaceholder
															}
															value={ attribute }
															label={
																attributeLabel
															}
															onChange={ (
																val
															) => {
																setValue(
																	'attributes[' +
																		index +
																		']',
																	val &&
																		getProductAttributeObject(
																			val
																		)
																);
																if ( val ) {
																	focusValueField(
																		index
																	);
																}
															} }
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
														/>
													</td>
													<td className="woocommerce-new-variation-option-modal__table-attribute-value-column">
														{ attribute === null ||
														attribute.id !== 0 ? (
															<AttributeTermInputField
																placeholder={
																	termPlaceholder
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
																	null
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
																	termPlaceholder
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
													<td className="woocommerce-new-variation-option-modal__table-attribute-trash-column">
														<Button
															icon={ trash }
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
									className="woocommerce-new-variation-option-modal__add-variation-option"
									variant="tertiary"
									label={ addAnotherAccessibleLabel }
									onClick={ () => {
										recordEvent(
											'product_add_variation_options_modal_add_another_variation_option_button_click'
										);
										addAnother( values, setValue );
									} }
								>
									{ addAnotherLabel }
								</Button>
							</div>
							<div className="woocommerce-new-variation-option-modal__buttons">
								<Button
									isSecondary
									label={ cancelLabel }
									onClick={ () => onClose( values ) }
								>
									{ cancelLabel }
								</Button>
								<Button
									isPrimary
									label={ addAccessibleLabel }
									disabled={
										values.attributes.length === 1 &&
										values.attributes[ 0 ] === null
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
			{ showConfirmClose && (
				<ConfirmDialog
					cancelButtonText={ confirmCancelLabel }
					confirmButtonText={ confirmConfirmLabel }
					onCancel={ () => setShowConfirmClose( false ) }
					onConfirm={ onCancel }
				>
					{ confirmMessage }
				</ConfirmDialog>
			) }
		</>
	);
};
