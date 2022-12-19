/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { BlockInstance, serialize, parse } from '@wordpress/blocks';
import { CheckboxControl, Card, CardBody } from '@wordpress/components';
import {
	useFormContext,
	__experimentalRichTextEditor as RichTextEditor,
	__experimentalTooltip as Tooltip,
} from '@woocommerce/components';
import { ProductVariation } from '@woocommerce/data';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getCheckboxTracks } from './utils';
import { ProductSectionLayout } from '../layout/product-section-layout';

export const ProductVariationDetailsSection: React.FC = () => {
	const { getCheckboxControlProps, values, setValue } =
		useFormContext< ProductVariation >();

	const [ descriptionBlocks, setDescriptionBlocks ] = useState<
		BlockInstance[]
	>( parse( values.description || '' ) );

	return (
		<ProductSectionLayout
			title={ __( 'Variant details', 'woocommerce' ) }
			description={ __(
				'This info will be displayed on the product page, category pages, social media, and search results.',
				'woocommerce'
			) }
		>
			<Card>
				<CardBody>
					<CheckboxControl
						label={
							<>
								{ __( 'Visible to customers', 'woocommerce' ) }
								<Tooltip
									text={ __(
										'When enabled, customers will be able to select and purchase this variation from the product page.',
										'woocommerce'
									) }
								/>
							</>
						}
						{ ...getCheckboxControlProps(
							'status',
							getCheckboxTracks( 'status' )
						) }
						checked={ values.status === 'publish' }
						onChange={ () =>
							setValue(
								'status',
								values.status !== 'publish'
									? 'publish'
									: 'private'
							)
						}
					/>
					<RichTextEditor
						label={ __( 'Description', 'woocommerce' ) }
						blocks={ descriptionBlocks }
						onChange={ ( blocks ) => {
							setDescriptionBlocks( blocks );
							if ( ! descriptionBlocks.length ) {
								return;
							}
							setValue( 'description', serialize( blocks ) );
						} }
						placeholder={ __(
							'Describe this product. What makes it unique? What are its most important features?',
							'woocommerce'
						) }
					/>
				</CardBody>
			</Card>
		</ProductSectionLayout>
	);
};
