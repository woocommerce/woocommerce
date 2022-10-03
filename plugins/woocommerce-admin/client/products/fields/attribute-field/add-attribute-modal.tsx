/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Modal, Notice, Spinner } from '@wordpress/components';
import { resolveSelect, useSelect } from '@wordpress/data';
import { trash } from '@wordpress/icons';
import {
	EXPERIMENTAL_PRODUCT_ATTRIBUTES_STORE_NAME,
	EXPERIMENTAL_PRODUCT_ATTRIBUTE_TERMS_STORE_NAME,
	ProductAttribute,
	ProductAttributeTerm,
} from '@woocommerce/data';
import {
	__experimentalSelectControl as SelectControl,
	Form,
} from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './add-attribute-modal.scss';
import { AsyncSelectControl } from '~/products/shared/async-select-control';

type CreateCategoryModalProps = {
	onCancel: () => void;
	onCreated: ( newCategories: ProductAttribute[] ) => void;
	selectedAttributeIds?: number[];
};

type AttributeForm = {
	attributes: Partial< ProductAttribute >[];
	attributeTerms: Record< number, ProductAttributeTerm[] >;
};

export const AddAttributeModal: React.FC< CreateCategoryModalProps > = ( {
	onCancel,
	onCreated,
	selectedAttributeIds = [],
} ) => {
	const { productAttributes, hasResolvedProductAttributes } = useSelect(
		( select ) => {
			const { getProductAttributes, hasFinishedResolution } = select(
				EXPERIMENTAL_PRODUCT_ATTRIBUTES_STORE_NAME
			);
			return {
				hasResolvedProductAttributes: hasFinishedResolution(
					'getProductAttributes'
				),
				productAttributes: getProductAttributes< ProductAttribute[] >(),
			};
		},
		[]
	);

	const addAnother = (
		values: AttributeForm,
		setValue: (
			name: string,
			value: AttributeForm[ keyof AttributeForm ]
		) => void
	) => {
		setValue( 'attributes', [ ...values.attributes, {} ] );
	};

	const onAdd = ( values: AttributeForm ) => {
		const newAttributesToAdd: ProductAttribute[] = [];
		values.attributes.forEach( ( attr, index ) => {
			if (
				attr &&
				attr.name &&
				values.attributeTerms[ index ] &&
				values.attributeTerms[ index ].length > 0
			) {
				newAttributesToAdd.push( {
					...( attr as ProductAttribute ),
					options: values.attributeTerms[ index ].map(
						( term ) => term.name
					),
				} );
			}
		} );
		onCreated( newAttributesToAdd );
	};

	const getFilteredItems = (
		allItems: Partial< ProductAttribute >[],
		inputValue: string,
		selectedItems: Partial< ProductAttribute >[]
	) => {
		return allItems.filter(
			( item ) =>
				selectedItems.indexOf( item ) < 0 &&
				selectedAttributeIds.indexOf( item.id || -1 ) < 0 &&
				( item.name || '' )
					.toLowerCase()
					.startsWith( inputValue.toLowerCase() )
		);
	};

	return (
		<Modal
			title={ __( 'Add attributes', 'woocommerce' ) }
			onRequestClose={ () => onCancel() }
			className="woocommerce-add-attribute-modal"
		>
			<Notice isDismissible={ false }>
				<p>
					{ __(
						'By default, attributes are filterable and visible on the product page. You can change these settings for each attribute separately later.',
						'woocommerce'
					) }
				</p>
			</Notice>
			<Form< AttributeForm >
				initialValues={ { attributes: [ {} ], attributeTerms: {} } }
			>
				{ ( {
					values,
					setValue,
				}: {
					values: AttributeForm;
					setValue: (
						name: string,
						value: AttributeForm[ keyof AttributeForm ]
					) => void;
				} ) => {
					return (
						<>
							<table className="woocommerce-add-attribute-modal__table">
								<tr className="woocommerce-add-attribute-modal__table-header">
									<th>Attribute</th>
									<th>Values</th>
								</tr>
								{ values.attributes.map(
									( attribute, index ) => (
										<tr
											key={ index }
											className="woocommerce-add-attribute-modal__table-row"
										>
											<td>
												{ hasResolvedProductAttributes ? (
													<SelectControl<
														Partial< ProductAttribute >
													>
														items={
															productAttributes
														}
														label=""
														placeholder={ __(
															'Search or create attribute',
															'woocommerce'
														) }
														getItemLabel={ (
															item
														) => item?.name || '' }
														getItemValue={ (
															item
														) => item?.id || '' }
														getFilteredItems={
															getFilteredItems
														}
														selected={ attribute }
														onSelect={ ( item ) =>
															setValue(
																'attributes[' +
																	index +
																	']',
																item
															)
														}
														onRemove={ () =>
															setValue(
																'attributes[' +
																	index +
																	']',
																{}
															)
														}
													/>
												) : (
													<Spinner />
												) }
											</td>
											<td>
												<AsyncSelectControl< ProductAttributeTerm >
													items={ [] }
													multiple
													disabled={
														! values.attributes[
															index
														]?.id
													}
													label=""
													onSearch={ (
														searchString:
															| string
															| undefined
													) => {
														return resolveSelect(
															EXPERIMENTAL_PRODUCT_ATTRIBUTE_TERMS_STORE_NAME
														).getProductAttributeTerms<
															ProductAttributeTerm[]
														>( {
															search: searchString,
															attribute_id:
																values
																	.attributes[
																	index
																]?.id,
														} );
													} }
													placeholder={ __(
														'Search or create attribute',
														'woocommerce'
													) }
													getItemLabel={ ( item ) =>
														item?.name || ''
													}
													getItemValue={ ( item ) =>
														item?.slug || ''
													}
													selected={
														values.attributeTerms[
															index
														] || []
													}
													onSelect={ ( item ) =>
														setValue(
															'attributeTerms[' +
																index +
																']',
															[
																...( values
																	.attributeTerms[
																	index
																] || [] ),
																item,
															]
														)
													}
													onRemove={ ( item ) =>
														setValue(
															'attributes[' +
																index +
																'].options',
															values.attributeTerms[
																index
															]?.filter(
																( opt ) =>
																	opt.slug !==
																	item.slug
															)
														)
													}
												/>
											</td>
											<td>
												<Button
													icon={ trash }
													label={ __(
														'Remove attribute',
														'woocommerce'
													) }
													onClick={ () => {} }
												></Button>
											</td>
										</tr>
									)
								) }
							</table>
							<Button
								variant="tertiary"
								onClick={ () => addAnother( values, setValue ) }
							>
								+&nbsp;{ __( 'Add another', 'woocommerce' ) }
							</Button>
							<div className="woocommerce-add-attribute-modal__wrapper">
								<div className="woocommerce-add-attribute-modal__buttons">
									<Button
										isSecondary
										onClick={ () => onCancel() }
									>
										{ __( 'Cancel', 'woocommerce' ) }
									</Button>
									<Button
										isPrimary
										onClick={ () => onAdd( values ) }
									>
										{ __( 'Add', 'woocommerce' ) }
									</Button>
								</div>
							</div>
						</>
					);
				} }
			</Form>
		</Modal>
	);
};
