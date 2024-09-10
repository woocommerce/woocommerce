/**
 * External dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { type ElementType, useMemo } from '@wordpress/element';
import { EditorBlock } from '@woocommerce/types';
import { addFilter } from '@wordpress/hooks';
import { ProductCollectionFeedbackPrompt } from '@woocommerce/editor-components/feedback-prompt';
import {
	revertMigration,
	getUpgradeStatus,
	HOURS_TO_DISPLAY_UPGRADE_NOTICE,
	UPGRADE_NOTICE_DISPLAY_COUNT_THRESHOLD,
} from '@woocommerce/blocks/migration-products-to-product-collection';
import { recordEvent } from '@woocommerce/tracks';
import {
	PanelBody,
	// @ts-expect-error Using experimental features
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
	CoreFilterNames,
	FilterName,
} from '../../types';
import { setQueryAttribute, getDefaultSettings } from '../../utils';
import UpgradeNotice from './upgrade-notice';
import ColumnsControl from './columns-control';
import {
	InheritQueryControl,
	FilterableControl,
} from './use-page-context-control';
import OrderByControl from './order-by-control';
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

const prepareShouldShowFilter =
	( hideControls: FilterName[] ) => ( filter: FilterName ) => {
		return ! hideControls.includes( filter );
	};

const ProductCollectionInspectorControls = (
	props: ProductCollectionEditComponentProps
) => {
	const { attributes, context, setAttributes } = props;
	const { query, hideControls, displayLayout } = attributes;

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

	const showQueryControls = inherit === false;
	const showInheritQueryControl =
		isArchiveTemplate && shouldShowFilter( CoreFilterNames.INHERIT );
	const showFilterableControl =
		! isArchiveTemplate && shouldShowFilter( CoreFilterNames.FILTERABLE );
	const showOrderControl =
		showQueryControls &&
		shouldShowFilter( CoreFilterNames.ORDER ) &&
		// See https://github.com/woocommerce/woocommerce/issues/50862
		attributes.collection !== 'woocommerce/product-collection/hand-picked';
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
			>
				{ showInheritQueryControl && (
					<InheritQueryControl { ...queryControlProps } />
				) }
				{ showFilterableControl && (
					<FilterableControl { ...queryControlProps } />
				) }
				<LayoutOptionsControl { ...displayControlProps } />
				<ColumnsControl { ...displayControlProps } />
				{ showOrderControl && (
					<OrderByControl { ...queryControlProps } />
				) }
			</ToolsPanel>

			{ showQueryControls ? (
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
						<TaxonomyControls { ...queryControlProps } />
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
			<ProductCollectionFeedbackPrompt />
		</InspectorControls>
	);
};

export default ProductCollectionInspectorControls;

const isProductCollection = ( blockName: string ) =>
	blockName === metadata.name;

const lessThanThresholdSinceUpdate = ( t: number ) => {
	// Xh * 60m * 60s * 1000ms
	const xHoursFromT = t + HOURS_TO_DISPLAY_UPGRADE_NOTICE * 60 * 60 * 1000;
	return Date.now() < xHoursFromT;
};

const displayedLessThanThreshold = ( displayCount = 0 ) => {
	return displayCount <= UPGRADE_NOTICE_DISPLAY_COUNT_THRESHOLD;
};

// Upgrade Notice should be displayed only if:
// - block is converted from Products
// - user haven't acknowledged seeing the notice
// - less than X hours since the notice was first displayed
// - notice was displayed less than X times
const shouldDisplayUpgradeNotice = (
	props: ProductCollectionEditComponentProps
) => {
	const { attributes } = props;
	const { convertedFromProducts } = attributes;
	const { status, time, displayCount } = getUpgradeStatus();

	return (
		convertedFromProducts &&
		status === 'notseen' &&
		lessThanThresholdSinceUpdate( time ) &&
		displayedLessThanThreshold( displayCount )
	);
};

// Block should be unmarked as converted from Products if:
// block is converted from Products and either:
// - user acknowledged seeing the notice
// - it's more than X hours since the notice was first displayed
// - notice was displayed more than X times
// We do that to prevent showing the notice again after Products on
// other page were updated or local storage was cleared or user
// switched to another machine/browser etc.
const shouldBeUnmarkedAsConverted = (
	props: ProductCollectionEditComponentProps
) => {
	const { attributes } = props;
	const { convertedFromProducts } = attributes;
	const { status, time, displayCount } = getUpgradeStatus();

	return (
		convertedFromProducts &&
		( status === 'seen' ||
			! lessThanThresholdSinceUpdate( time ) ||
			! displayedLessThanThreshold( displayCount ) )
	);
};

const CollectionSpecificControls = (
	props: ProductCollectionEditComponentProps
) => {
	const setQueryAttributeBind = useMemo(
		() => setQueryAttribute.bind( null, props ),
		[ props ]
	);
	const tracksLocation = useTracksLocation( props.context.templateSlug );
	const trackInteraction = ( filter: FilterName ) => {
		return recordEvent(
			'blocks_product_collection_inspector_control_clicked',
			{
				collection: props.attributes.collection,
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

	return (
		<InspectorControls>
			<PanelBody>
				{
					/**
					 * Hand-Picked collection-specific controls.
					 */
					props.attributes.collection ===
						'woocommerce/product-collection/hand-picked' && (
						<HandPickedProductsControlField
							{ ...queryControlProps }
						/>
					)
				}
			</PanelBody>
		</InspectorControls>
	);
};

const withCollectionSpecificControls =
	< T extends EditorBlock< T > >( BlockEdit: ElementType ) =>
	( props: ProductCollectionEditComponentProps ) => {
		if ( ! isProductCollection( props.name ) || ! props.isSelected ) {
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

export const withUpgradeNoticeControls =
	< T extends EditorBlock< T > >( BlockEdit: ElementType ) =>
	( props: ProductCollectionEditComponentProps ) => {
		if ( ! isProductCollection( props.name ) ) {
			return <BlockEdit { ...props } />;
		}

		const displayUpgradeNotice = shouldDisplayUpgradeNotice( props );
		const unmarkAsConverted = shouldBeUnmarkedAsConverted( props );

		if ( unmarkAsConverted ) {
			props.setAttributes( { convertedFromProducts: false } );
		}

		return (
			<>
				{ displayUpgradeNotice && (
					<InspectorControls>
						{
							<UpgradeNotice
								revertMigration={ revertMigration }
							/>
						}
					</InspectorControls>
				) }
				<BlockEdit { ...props } />
			</>
		);
	};

addFilter( 'editor.BlockEdit', metadata.name, withUpgradeNoticeControls );
