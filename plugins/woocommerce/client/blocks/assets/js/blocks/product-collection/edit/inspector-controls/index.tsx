/**
 * External dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';
import { EditorBlock } from '@woocommerce/types';
import { addFilter } from '@wordpress/hooks';
import { useIsEmailEditor } from '@woocommerce/email-editor';
import { recordEvent } from '@woocommerce/tracks';
import { CesFeedbackButton } from '@woocommerce/editor-components/ces-feedback-button';
import {
	PanelBody,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanel as ToolsPanel,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import metadata from '../../block.json';
import { useTracksLocation } from '../../tracks-utils';
import {
	ProductCollectionEditComponentProps,
	ProductCollectionContentProps,
	CoreFilterNames,
	FilterName,
	LayoutOptions,
	CoreCollectionNames,
} from '../../types';
import { setQueryAttribute, getDefaultSettings } from '../../utils';
import ColumnsControl from './columns-control';
import {
	InheritQueryControl,
	FilterableControl,
} from './use-page-context-control';
import useCarouselLayoutAdjustments from './use-carousel-layout-adjustments';
import useEmailPaginationAdjustments from './use-email-pagination-adjustments';
import useEmailColumnAdjustments from './use-email-column-adjustments';
import DefaultQueryOrderByControl from './order-by-control/default-query-order-by-control';
import CustomQueryOrderByControl from './order-by-control/custom-query-order-by-control';
import OnSaleControl from './on-sale-control';
import StockStatusControl from './stock-status-control';
import KeywordControl from './keyword-control';
import AttributesControl from './attributes-control';
import TaxonomyControls from './taxonomy-controls';
import HandPickedProductsControl, {
	HandPickedProductsControlField,
} from './hand-picked-products-control';
import LayoutOptionsControl from './layout-options-control';
import FeaturedProductsControl from './featured-products-control';
import CreatedControl from './created-control';
import PriceRangeControl from './price-range-control';
import LinkedProductControl from './linked-product-control';
import WidthOptionsControl from './width-options-control';
import RelatedByControl from './related-by-control';
import ProductsPerPageControl from './products-per-page-control';
import OffsetControl from './offset-control';
import MaxPagesToShowControl from './max-pages-to-show-control';

const prepareShouldShowFilter =
	( hideControls: FilterName[] ) => ( filter: FilterName ) => {
		return ! hideControls.includes( filter );
	};

const ProductCollectionInspectorControls = (
	props: ProductCollectionContentProps
) => {
	const { attributes, context, setAttributes, clientId } = props;
	const { query, hideControls, dimensions, displayLayout, collection } =
		attributes;

	const tracksLocation = useTracksLocation( context.templateSlug );
	const trackInteraction = ( filter: FilterName ) =>
		recordEvent( 'blocks_product_collection_inspector_control_clicked', {
			collection: attributes.collection,
			location: tracksLocation,
			filter,
		} );

	const inherit = query?.inherit || false;

	const shouldShowFilter = prepareShouldShowFilter( hideControls );

	const isArchiveTemplate =
		tracksLocation === 'product-catalog' ||
		tracksLocation === 'product-archive';

	// Carousel layout influences the visibility and behavior of some controls.
	const isCarouselLayout = displayLayout?.type === LayoutOptions.CAROUSEL;
	const isEmailEditor = useIsEmailEditor();
	useCarouselLayoutAdjustments( clientId, attributes );
	useEmailPaginationAdjustments( clientId, attributes );
	useEmailColumnAdjustments( attributes, setAttributes );

	const showCustomQueryControls = inherit === false;
	const showInheritQueryControl =
		isArchiveTemplate && shouldShowFilter( CoreFilterNames.INHERIT );
	const showFilterableControl =
		! isArchiveTemplate && shouldShowFilter( CoreFilterNames.FILTERABLE );
	const showCustomOrderControl =
		showCustomQueryControls && shouldShowFilter( CoreFilterNames.ORDER );
	const showDefaultOrderControl = ! showCustomQueryControls;
	const showOffsetControl =
		showCustomQueryControls && shouldShowFilter( CoreFilterNames.OFFSET );
	const showColumnsControl = ! isCarouselLayout;
	const showMaxPagesToShowControl =
		showCustomQueryControls &&
		! isCarouselLayout &&
		shouldShowFilter( CoreFilterNames.MAX_PAGES_TO_SHOW );
	const showProductsPerPageControl =
		showCustomQueryControls &&
		shouldShowFilter( CoreFilterNames.PRODUCTS_PER_PAGE );
	const showOnSaleControl = shouldShowFilter( CoreFilterNames.ON_SALE );
	const showStockStatusControl = shouldShowFilter(
		CoreFilterNames.STOCK_STATUS
	);
	const showHandPickedProductsControl = shouldShowFilter(
		CoreFilterNames.HAND_PICKED
	);
	const showKeywordControl = shouldShowFilter( CoreFilterNames.KEYWORD );
	const showAttributesControl = shouldShowFilter(
		CoreFilterNames.ATTRIBUTES
	);
	const showTaxonomyControls = shouldShowFilter( CoreFilterNames.TAXONOMY );
	const showFeaturedControl = shouldShowFilter( CoreFilterNames.FEATURED );
	const showCreatedControl = shouldShowFilter( CoreFilterNames.CREATED );
	const showPriceRangeControl = shouldShowFilter(
		CoreFilterNames.PRICE_RANGE
	);

	const setQueryAttributeBind = useMemo(
		() => setQueryAttribute.bind( null, props ),
		[ props ]
	);

	const displayControlProps = {
		setAttributes,
		displayLayout,
	};

	const dimensionsControlProps = {
		setAttributes,
		dimensions,
	};

	const queryControlProps = {
		setQueryAttribute: setQueryAttributeBind,
		trackInteraction,
		query,
	};

	return (
		<InspectorControls>
			<LinkedProductControl
				query={ props.attributes.query }
				setAttributes={ props.setAttributes }
				usesReference={ props.usesReference }
				location={ props.location }
			/>

			<ToolsPanel
				label={ __( 'Settings', 'woocommerce' ) }
				resetAll={ () => {
					const defaultSettings = getDefaultSettings(
						props.attributes
					);
					props.setAttributes( defaultSettings );
				} }
				className="wc-block-editor-product-collection__settings_panel"
			>
				{ showInheritQueryControl && (
					<InheritQueryControl { ...queryControlProps } />
				) }
				{ showFilterableControl && (
					<FilterableControl { ...queryControlProps } />
				) }
				{ showCustomOrderControl && (
					<CustomQueryOrderByControl { ...queryControlProps } />
				) }
				{ showDefaultOrderControl && (
					<DefaultQueryOrderByControl
						trackInteraction={ trackInteraction }
					/>
				) }
				{ ! isEmailEditor && (
					<LayoutOptionsControl { ...displayControlProps } />
				) }
				{ ! isEmailEditor && (
					<WidthOptionsControl { ...dimensionsControlProps } />
				) }
				{ showProductsPerPageControl && (
					<ProductsPerPageControl
						{ ...queryControlProps }
						carouselVariant={ isCarouselLayout }
					/>
				) }
				{ ! isEmailEditor && showColumnsControl && (
					<ColumnsControl { ...displayControlProps } />
				) }
				{ ! isEmailEditor && showOffsetControl && (
					<OffsetControl { ...queryControlProps } />
				) }
				{ showMaxPagesToShowControl && ! isEmailEditor && (
					<MaxPagesToShowControl { ...queryControlProps } />
				) }
			</ToolsPanel>

			{ showCustomQueryControls ? (
				<ToolsPanel
					label={ __( 'Filters', 'woocommerce' ) }
					resetAll={ ( resetAllFilters: ( () => void )[] ) => {
						resetAllFilters.forEach( ( resetFilter ) => {
							resetFilter();
						} );
					} }
					className="wc-block-editor-product-collection-inspector-toolspanel__filters"
				>
					{ showOnSaleControl && (
						<OnSaleControl { ...queryControlProps } />
					) }
					{ showStockStatusControl && (
						<StockStatusControl { ...queryControlProps } />
					) }
					{ showHandPickedProductsControl && (
						<HandPickedProductsControl { ...queryControlProps } />
					) }
					{ showKeywordControl && (
						<KeywordControl { ...queryControlProps } />
					) }
					{ showAttributesControl && (
						<AttributesControl { ...queryControlProps } />
					) }
					{ showTaxonomyControls && (
						<TaxonomyControls
							{ ...queryControlProps }
							collection={ collection }
							renderMode="panel"
						/>
					) }
					{ showFeaturedControl && (
						<FeaturedProductsControl { ...queryControlProps } />
					) }
					{ showCreatedControl && (
						<CreatedControl { ...queryControlProps } />
					) }
					{ showPriceRangeControl && (
						<PriceRangeControl { ...queryControlProps } />
					) }
				</ToolsPanel>
			) : null }
			<CesFeedbackButton
				blockName={ `${ metadata.title } block` }
				wrapper={ PanelBody }
			/>
		</InspectorControls>
	);
};

export default ProductCollectionInspectorControls;

const isProductCollection = ( blockName: string ) =>
	blockName === metadata.name;

const CollectionSpecificControls = (
	props: ProductCollectionEditComponentProps
) => {
	const { collection } = props.attributes;
	const setQueryAttributeBind = useMemo(
		() => setQueryAttribute.bind( null, props ),
		[ props ]
	);
	const tracksLocation = useTracksLocation( props.context.templateSlug );
	const trackInteraction = ( filter: FilterName ) => {
		return recordEvent(
			'blocks_product_collection_inspector_control_clicked',
			{
				collection,
				location: tracksLocation,
				filter,
			}
		);
	};
	const queryControlProps = {
		setQueryAttribute: setQueryAttributeBind,
		trackInteraction,
		query: props.attributes.query,
	};

	const isByCategoryOrTag =
		collection === CoreCollectionNames.BY_CATEGORY ||
		collection === CoreCollectionNames.BY_TAG;

	return (
		<InspectorControls>
			{
				/**
				 * "Hand-Picked" collection-specific controls.
				 */
				collection === CoreCollectionNames.HAND_PICKED && (
					<PanelBody>
						<HandPickedProductsControlField
							{ ...queryControlProps }
						/>
					</PanelBody>
				)
			}
			{
				/**
				 * "Related Products" collection-specific controls.
				 */
				collection === CoreCollectionNames.RELATED && (
					<RelatedByControl { ...queryControlProps } />
				)
			}
			{
				/**
				 * "Category and Tag" collection-specific controls.
				 */
				isByCategoryOrTag && (
					<PanelBody>
						<TaxonomyControls
							{ ...queryControlProps }
							collection={ collection }
							renderMode="standalone"
						/>
					</PanelBody>
				)
			}
		</InspectorControls>
	);
};

const withCollectionSpecificControls =
	< T extends EditorBlock< T > >( BlockEdit: ElementType ) =>
	( props: ProductCollectionEditComponentProps ) => {
		if ( ! isProductCollection( props.name ) ) {
			return <BlockEdit { ...props } />;
		}

		return (
			<>
				<CollectionSpecificControls { ...props } />
				<BlockEdit { ...props } />
			</>
		);
	};

addFilter( 'editor.BlockEdit', metadata.name, withCollectionSpecificControls );
