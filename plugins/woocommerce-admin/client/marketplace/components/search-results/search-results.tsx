/**
 * External dependencies
 */
import { useQuery } from '@woocommerce/navigation';
import { useContext } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './search-results.scss';
import { Product, ProductType, SearchResultType } from '../product-list/types';
import Products from '../products/products';
import NoResults from '../product-list-content/no-results';
import { MarketplaceContext } from '../../contexts/marketplace-context';
import {
	MARKETPLACE_ITEMS_PER_PAGE,
	MARKETPLACE_SEARCH_RESULTS_PER_PAGE,
} from '../../../marketplace/components/constants';

export interface SearchResultProps {
	products: Product[];
	type: SearchResultType;
}

export default function SearchResults( props: SearchResultProps ): JSX.Element {
	const extensionList = props.products.filter(
		( product ) => product.type === ProductType.extension
	);
	const themeList = props.products.filter(
		( product ) => product.type === ProductType.theme
	);
	const businessServiceList = props.products.filter(
		( product ) => product.type === ProductType.businessService
	);

	const hasExtensions = extensionList.length > 0;
	const hasThemes = themeList.length > 0;
	const hasBusinessServices = businessServiceList.length > 0;
	const hasOnlyExtensions =
		hasExtensions && ! hasThemes && ! hasBusinessServices;
	const hasOnlyThemes = hasThemes && ! hasExtensions && ! hasBusinessServices;
	const hasOnlyBusinessServices =
		hasBusinessServices && ! hasExtensions && ! hasThemes;

	const marketplaceContextValue = useContext( MarketplaceContext );
	const { isLoading, hasBusinessServices: canShowBusinessServices } =
		marketplaceContextValue;

	const query = useQuery();
	const showCategorySelector = query.section ? true : false;
	const searchTerm = query.term ? query.term : '';

	type Overrides = {
		categorySelector?: boolean;
		showAllButton?: boolean;
		perPage?: number;
	};

	function productsComponent(
		products: Product[],
		type: ProductType,
		overrides: Overrides = {}
	) {
		return (
			<Products
				products={ products }
				type={ type }
				categorySelector={
					overrides.categorySelector ?? showCategorySelector
				}
				searchTerm={ searchTerm }
				showAllButton={ overrides.showAllButton ?? true }
				perPage={ overrides.perPage ?? MARKETPLACE_ITEMS_PER_PAGE }
			/>
		);
	}

	function extensionsComponent( overrides: Overrides = {} ) {
		return productsComponent(
			extensionList,
			ProductType.extension,
			overrides
		);
	}

	function themesComponent( overrides: Overrides = {} ) {
		return productsComponent( themeList, ProductType.theme, overrides );
	}

	function businessServicesComponent( overrides: Overrides = {} ) {
		return productsComponent(
			businessServiceList,
			ProductType.businessService,
			overrides
		);
	}

	const content = () => {
		if ( query?.section === SearchResultType.extension ) {
			return extensionsComponent( { showAllButton: false } );
		}

		if ( query?.section === SearchResultType.theme ) {
			return themesComponent( { showAllButton: false } );
		}

		if ( query?.section === SearchResultType.businessService ) {
			return businessServicesComponent( { showAllButton: false } );
		}

		// Components can handle their isLoading state. So we can put all three on the page.
		if ( isLoading ) {
			return (
				<>
					{ extensionsComponent() }
					{ themesComponent() }
					{ businessServicesComponent() }
				</>
			);
		}

		// If we did finish loading items, and there are no results, show the no results component.
		if (
			! isLoading &&
			! hasExtensions &&
			! hasThemes &&
			! hasBusinessServices
		) {
			return (
				<NoResults
					type={ SearchResultType.all }
					showHeading={ true }
					heading={
						canShowBusinessServices
							? __(
									'No extensions, themes or business services found…',
									'woocommerce'
							  )
							: __(
									'No extensions or themes found…',
									'woocommerce'
							  )
					}
				/>
			);
		}

		// If we're done loading, we can put these components on the page.
		return (
			<>
				{ hasExtensions
					? extensionsComponent( {
							categorySelector: hasOnlyExtensions || undefined,
							showAllButton: hasOnlyExtensions
								? false
								: undefined,
							perPage: hasOnlyExtensions
								? MARKETPLACE_ITEMS_PER_PAGE
								: MARKETPLACE_SEARCH_RESULTS_PER_PAGE,
					  } )
					: null }
				{ hasThemes
					? themesComponent( {
							categorySelector: hasOnlyThemes || undefined,
							showAllButton: hasOnlyThemes ? false : undefined,
							perPage: hasOnlyThemes
								? MARKETPLACE_ITEMS_PER_PAGE
								: MARKETPLACE_SEARCH_RESULTS_PER_PAGE,
					  } )
					: null }
				{ hasBusinessServices
					? businessServicesComponent( {
							categorySelector:
								hasOnlyBusinessServices || undefined,
							showAllButton: hasOnlyBusinessServices
								? false
								: undefined,
							perPage: hasOnlyBusinessServices
								? MARKETPLACE_ITEMS_PER_PAGE
								: MARKETPLACE_SEARCH_RESULTS_PER_PAGE,
					  } )
					: null }
			</>
		);
	};

	return (
		<div className="woocommerce-marketplace__search-results">
			{ content() }
		</div>
	);
}
