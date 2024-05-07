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
	const marketplaceContextValue = useContext( MarketplaceContext );
	const { isLoading } = marketplaceContextValue;

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
					heading={ __(
						'No extensions, themes or business services found…',
						'woocommerce'
					) }
				/>
			);
		}

		if ( hasExtensions && ! hasThemes && ! hasBusinessServices ) {
			return extensionsComponent( {
				categorySelector: true,
				showAllButton: false,
				perPage: MARKETPLACE_ITEMS_PER_PAGE,
			} );
		}

		if ( hasThemes && ! hasBusinessServices && ! hasExtensions ) {
			return themesComponent( {
				categorySelector: true,
				showAllButton: false,
				perPage: MARKETPLACE_ITEMS_PER_PAGE,
			} );
		}

		if ( hasBusinessServices && ! hasThemes && ! hasExtensions ) {
			return businessServicesComponent( {
				categorySelector: true,
				showAllButton: false,
				perPage: MARKETPLACE_ITEMS_PER_PAGE,
			} );
		}

		if ( hasExtensions && hasThemes && ! hasBusinessServices ) {
			return (
				<>
					{ extensionsComponent( {
						perPage: MARKETPLACE_SEARCH_RESULTS_PER_PAGE,
					} ) }
					{ themesComponent( {
						perPage: MARKETPLACE_SEARCH_RESULTS_PER_PAGE,
					} ) }
				</>
			);
		}

		if ( hasExtensions && hasBusinessServices && ! hasThemes ) {
			return (
				<>
					{ extensionsComponent( {
						perPage: MARKETPLACE_SEARCH_RESULTS_PER_PAGE,
					} ) }
					{ businessServicesComponent( {
						perPage: MARKETPLACE_SEARCH_RESULTS_PER_PAGE,
					} ) }
				</>
			);
		}

		if ( hasThemes && hasBusinessServices && ! hasExtensions ) {
			return (
				<>
					{ themesComponent( {
						perPage: MARKETPLACE_SEARCH_RESULTS_PER_PAGE,
					} ) }
					{ businessServicesComponent( {
						perPage: MARKETPLACE_SEARCH_RESULTS_PER_PAGE,
					} ) }
				</>
			);
		}

		// If we're done loading, we can put these components on the page.
		return (
			<>
				{ extensionsComponent( {
					perPage: MARKETPLACE_SEARCH_RESULTS_PER_PAGE,
				} ) }
				{ themesComponent( {
					perPage: MARKETPLACE_SEARCH_RESULTS_PER_PAGE,
				} ) }
				{ businessServicesComponent( {
					perPage: MARKETPLACE_SEARCH_RESULTS_PER_PAGE,
				} ) }
			</>
		);
	};

	return (
		<div className="woocommerce-marketplace__search-results">
			{ content() }
		</div>
	);
}
