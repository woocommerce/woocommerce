/**
 * External dependencies
 */
import {
	CheckboxControl,
	Button,
	TextControl,
	Card,
	CardBody,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { cleanForSlug } from '@wordpress/url';
import {
	Link,
	useFormContext,
	__experimentalRichTextEditor as RichTextEditor,
	__experimentalTooltip as Tooltip,
} from '@woocommerce/components';
import interpolateComponents from '@automattic/interpolate-components';
import {
	Product,
	ProductCategory,
	PRODUCTS_STORE_NAME,
	WCDataSelector,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { BlockInstance, serialize, parse } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import './product-details-section.scss';
import { CategoryField } from '../fields/category-field';
import { EditProductLinkModal } from '../shared/edit-product-link-modal';
import { getCheckboxTracks } from './utils';
import { ProductSectionLayout } from '../layout/product-section-layout';

const PRODUCT_DETAILS_SLUG = 'product-details';

export const ProductDetailsSection: React.FC = () => {
	const {
		getCheckboxControlProps,
		getInputProps,
		values,
		touched,
		errors,
		setValue,
	} = useFormContext< Product >();
	const [ showProductLinkEditModal, setShowProductLinkEditModal ] =
		useState( false );
	const [ descriptionBlocks, setDescriptionBlocks ] = useState<
		BlockInstance[]
	>( parse( values.description || '' ) );
	const [ summaryBlocks, setSummaryBlocks ] = useState< BlockInstance[] >(
		parse( values.short_description || '' )
	);
	const { permalinkPrefix, permalinkSuffix } = useSelect(
		( select: WCDataSelector ) => {
			const { getPermalinkParts } = select( PRODUCTS_STORE_NAME );
			if ( values.id ) {
				const parts = getPermalinkParts( values.id );
				return {
					permalinkPrefix: parts?.prefix,
					permalinkSuffix: parts?.suffix,
				};
			}
			return {};
		}
	);

	const hasNameError = () => {
		return Boolean( touched.name ) && Boolean( errors.name );
	};

	const setSkuIfEmpty = () => {
		if ( values.sku || ! values.name?.length ) {
			return;
		}
		setValue( 'sku', cleanForSlug( values.name ) );
	};

	return (
		<ProductSectionLayout
			title={ __( 'Product details', 'woocommerce' ) }
			description={ __(
				'This info will be displayed on the product page, category pages, social media, and search results.',
				'woocommerce'
			) }
		>
			<Card>
				<CardBody>
					<div>
						<TextControl
							label={ interpolateComponents( {
								mixedString: __(
									'Name {{required/}}',
									'woocommerce'
								),
								components: {
									required: (
										<span className="woocommerce-product-form__optional-input">
											{ __(
												'(required)',
												'woocommerce'
											) }
										</span>
									),
								},
							} ) }
							name={ `${ PRODUCT_DETAILS_SLUG }-name` }
							placeholder={ __(
								'e.g. 12 oz Coffee Mug',
								'woocommerce'
							) }
							{ ...getInputProps( 'name', {
								onBlur: setSkuIfEmpty,
							} ) }
						/>
						{ values.id && ! hasNameError() && permalinkPrefix && (
							<span className="woocommerce-product-form__secondary-text product-details-section__product-link">
								{ __( 'Product link', 'woocommerce' ) }
								:&nbsp;
								<a
									href={ values.permalink }
									target="_blank"
									rel="noreferrer"
								>
									{ permalinkPrefix }
									{ values.slug ||
										cleanForSlug( values.name ) }
									{ permalinkSuffix }
								</a>
								<Button
									variant="link"
									onClick={ () =>
										setShowProductLinkEditModal( true )
									}
								>
									{ __( 'Edit', 'woocommerce' ) }
								</Button>
							</span>
						) }
					</div>
					<CategoryField
						label={ __( 'Categories', 'woocommerce' ) }
						placeholder={ __(
							'Search or create category…',
							'woocommerce'
						) }
						{ ...getInputProps<
							Pick< ProductCategory, 'id' | 'name' >[]
						>( 'categories' ) }
					/>
					<CheckboxControl
						label={
							<>
								{ __( 'Feature this product', 'woocommerce' ) }
								<Tooltip
									text={ interpolateComponents( {
										mixedString: __(
											'Include this product in a featured section on your website with a widget or shortcode. {{moreLink/}}',
											'woocommerce'
										),
										components: {
											moreLink: (
												<Link
													href="https://woocommerce.com/document/woocommerce-shortcodes/#products"
													target="_blank"
													type="external"
													onClick={ () =>
														recordEvent(
															'add_product_learn_more',
															{
																category:
																	PRODUCT_DETAILS_SLUG,
															}
														)
													}
												>
													{ __(
														'Learn more',
														'woocommerce'
													) }
												</Link>
											),
										},
									} ) }
								/>
							</>
						}
						{ ...getCheckboxControlProps(
							'featured',
							getCheckboxTracks( 'featured' )
						) }
					/>
					{ showProductLinkEditModal && (
						<EditProductLinkModal
							permalinkPrefix={ permalinkPrefix || '' }
							permalinkSuffix={ permalinkSuffix || '' }
							product={ values }
							onCancel={ () =>
								setShowProductLinkEditModal( false )
							}
							onSaved={ () =>
								setShowProductLinkEditModal( false )
							}
						/>
					) }
					<RichTextEditor
						label={ __( 'Summary', 'woocommerce' ) }
						blocks={ summaryBlocks }
						onChange={ ( blocks ) => {
							setSummaryBlocks( blocks );
							setValue(
								'short_description',
								serialize( blocks )
							);
						} }
					/>
					<RichTextEditor
						label={ __( 'Description', 'woocommerce' ) }
						blocks={ descriptionBlocks }
						onChange={ ( blocks ) => {
							setDescriptionBlocks( blocks );
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
