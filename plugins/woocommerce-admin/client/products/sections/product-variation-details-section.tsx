/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { BlockInstance, serialize, rawHandler } from '@wordpress/blocks';
import {
	CheckboxControl,
	Card,
	CardBody,
	BaseControl,
} from '@wordpress/components';
import { MediaItem } from '@wordpress/media-utils';
import {
	useFormContext,
	__experimentalRichTextEditor as RichTextEditor,
	__experimentalTooltip as Tooltip,
} from '@woocommerce/components';
import { ProductVariation, ProductVariationImage } from '@woocommerce/data';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getCheckboxTracks } from './utils';
import { ProductSectionLayout } from '../layout/product-section-layout';
import { SingleImageField } from '../fields/single-image-field';

function parseVariationImage(
	media?: MediaItem
): ProductVariationImage | undefined {
	if ( ! media ) return undefined;
	return {
		id: media.id,
		src: media.url,
		alt: media.alt,
		name: media.title,
	} as ProductVariationImage;
}

function formatVariationImage(
	image?: ProductVariationImage
): MediaItem | undefined {
	if ( ! image ) return undefined;
	return {
		id: image.id,
		url: image.src,
		alt: image.alt,
		title: image.name,
	} as MediaItem;
}

export const ProductVariationDetailsSection: React.FC = () => {
	const { getCheckboxControlProps, getInputProps, values, setValue } =
		useFormContext< ProductVariation >();

	const [ descriptionBlocks, setDescriptionBlocks ] = useState<
		BlockInstance[]
	>( rawHandler( { HTML: values.description } ) );

	const imageFieldProps = getInputProps( 'image' );

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
							getCheckboxTracks< ProductVariation >( 'status' )
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

					<BaseControl id="product-variation-image">
						<SingleImageField
							label={ __( 'Image', 'woocommerce' ) }
							value={ formatVariationImage(
								imageFieldProps.value as ProductVariationImage
							) }
							onChange={ ( media ) =>
								setValue(
									'image',
									parseVariationImage( media )
								)
							}
						/>
					</BaseControl>
				</CardBody>
			</Card>
		</ProductSectionLayout>
	);
};
