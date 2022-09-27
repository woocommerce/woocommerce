/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Modal, Notice, Spinner } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import {
	EXPERIMENTAL_PRODUCT_ATTRIBUTES_STORE_NAME,
	ProductAttribute,
} from '@woocommerce/data';
import {
	__experimentalSelectControl as SelectControl,
	__experimentalSelectControlMenuItem as MenuItem,
	__experimentalSelectControlMenu as Menu,
	Form,
} from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './add-attribute-modal.scss';

type CreateCategoryModalProps = {
	onCancel: () => void;
	onCreated: ( newCategory: ProductAttribute ) => void;
};

export const AddAttributeModal: React.FC< CreateCategoryModalProps > = ( {
	onCancel,
	onCreated,
} ) => {
	const { createNotice } = useDispatch( 'core/notices' );
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
			<Form< Partial< ProductAttribute >[] > initialValues={ [ {} ] }>
				{ ( { values }: { values: Partial< ProductAttribute >[] } ) => {
					return (
						<>
							<table className="woocommerce-add-attribute-modal__table">
								<tr className="woocommerce-add-attribute-modal__table-header">
									<th>Attribute</th>
									<th>Values</th>
								</tr>
								{ values.map( ( attribute, index ) => (
									<tr
										key={ index }
										className="woocommerce-add-attribute-modal__table-row"
									>
										<td>
											{ hasResolvedProductAttributes ? (
												<SelectControl<
													Partial< ProductAttribute >
												>
													items={ productAttributes }
													label=""
													placeholder={ __(
														'Search or create attribute',
														'woocommerce'
													) }
													getItemLabel={ ( item ) =>
														item?.name || ''
													}
													getItemValue={ ( item ) =>
														item?.id || ''
													}
													selected={ attribute }
													onSelect={ ( item ) =>
														item
													}
													onRemove={ () => {} }
												/>
											) : (
												<Spinner />
											) }
										</td>
										<td></td>
									</tr>
								) ) }
							</table>
							<Button variant="tertiary">
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
									<Button isPrimary onClick={ () => {} }>
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
