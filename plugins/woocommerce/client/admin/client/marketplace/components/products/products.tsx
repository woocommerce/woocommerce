/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	createInterpolateElement,
	useContext,
	useState,
} from '@wordpress/element';
import { getNewPath, navigateTo, useQuery } from '@woocommerce/navigation';
import { Button } from '@wordpress/components';
import clsx from 'clsx';
import { addQueryArgs } from '@wordpress/url';
import { useSelect } from '@wordpress/data';
import { ONBOARDING_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import './products.scss';
import { MarketplaceContext } from '../../contexts/marketplace-context';
import CategorySelector from '../category-selector/category-selector';
import ProductListContent from '../product-list-content/product-list-content';
import ProductLoader from '../product-loader/product-loader';
import NoResults from '../product-list-content/no-results';
import { Product, ProductType, SearchResultType } from '../product-list/types';
import { ADMIN_URL } from '~/utils/admin-settings';
import { ThemeSwitchWarningModal } from '~/customize-store/intro/warning-modals';

interface ProductsProps {
	categorySelector?: boolean;
	products?: Product[];
	perPage?: number;
	type: ProductType;
	searchTerm?: string;
	showAllButton?: boolean;
}

const LABELS = {
	[ ProductType.extension ]: {
		label: __( 'extensions', 'woocommerce' ),
		singularLabel: __( 'extension', 'woocommerce' ),
	},
	[ ProductType.theme ]: {
		label: __( 'themes', 'woocommerce' ),
		singularLabel: __( 'theme', 'woocommerce' ),
	},
	[ ProductType.businessService ]: {
		label: __( 'business services', 'woocommerce' ),
		singularLabel: __( 'business service', 'woocommerce' ),
	},
};

export default function Products( props: ProductsProps ) {
	const marketplaceContextValue = useContext( MarketplaceContext );
	const { isLoading } = marketplaceContextValue;
	const label = LABELS[ props.type ].label;
	const query = useQuery();
	const category = query?.category;
	interface Theme {
		stylesheet?: string;
	}

	const currentTheme = useSelect( ( select ) => {
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore
		return select( 'core' ).getCurrentTheme() as Theme;
	}, [] );
	const isDefaultTheme = currentTheme?.stylesheet === 'twentytwentyfour';
	const [ isModalOpen, setIsModalOpen ] = useState( false );
	const customizeStoreDesignUrl = addQueryArgs( `${ ADMIN_URL }admin.php`, {
		page: 'wc-admin',
		path: '/customize-store/design',
	} );
	const assemblerHubUrl = addQueryArgs( `${ ADMIN_URL }admin.php`, {
		page: 'wc-admin',
		path: '/customize-store/assembler-hub',
	} );

	const customizeStoreTask = useSelect( ( select ) => {
		return select( ONBOARDING_STORE_NAME ).getTask( 'customize-store' );
	}, [] );

	// Only show the "View all" button when on search but not showing a specific section of results.
	const showAllButton = props.showAllButton ?? false;

	function showSection( section: ProductType ) {
		navigateTo( {
			url: getNewPath( { section } ),
		} );
	}

	// Store the total number of products before we slice it later.
	const products = props.products ?? [];

	const labelForClassName =
		label === 'business services' ? 'business-services' : label;

	const baseContainerClass = 'woocommerce-marketplace__search-';

	const containerClassName = clsx( baseContainerClass + labelForClassName );
	const viewAllButonClassName = clsx(
		'woocommerce-marketplace__view-all-button',
		baseContainerClass + 'button-' + labelForClassName
	);

	if ( isLoading ) {
		return (
			<>
				{ props.categorySelector && (
					<CategorySelector type={ props.type } />
				) }
				<ProductLoader hasTitle={ false } type={ props.type } />
			</>
		);
	}

	if ( products.length === 0 ) {
		let type = SearchResultType.all;

		switch ( props.type ) {
			case ProductType.extension:
				type = SearchResultType.extension;
				break;
			case ProductType.theme:
				type = SearchResultType.theme;
				break;
			case ProductType.businessService:
				type = SearchResultType.businessService;
				break;
		}

		return <NoResults type={ type } showHeading={ false } />;
	}

	const productListClass = clsx(
		showAllButton
			? 'woocommerce-marketplace__product-list-content--collapsed'
			: ''
	);

	return (
		<div className={ containerClassName }>
			<nav className="woocommerce-marketplace__sub-header">
				<div className="woocommerce-marketplace__sub-header__categories">
					{ props.categorySelector && (
						<CategorySelector type={ props.type } />
					) }
				</div>
				{ props.type === 'theme' && (
					<Button
						className="woocommerce-marketplace__customize-your-store-button"
						variant="secondary"
						text={ __( 'Design your own', 'woocommerce' ) }
						onClick={ () => {
							if ( ! isDefaultTheme ) {
								setIsModalOpen( true );
							} else if ( customizeStoreTask?.isComplete ) {
								window.location.href = assemblerHubUrl;
							} else {
								window.location.href = customizeStoreDesignUrl;
							}
						} }
					/>
				) }
			</nav>
			{ isModalOpen && (
				<ThemeSwitchWarningModal
					setIsModalOpen={ setIsModalOpen }
					redirectToCYSFlow={ () => {
						window.location.href = customizeStoreDesignUrl;
					} }
				/>
			) }
			<ProductListContent
				products={ products }
				type={ props.type }
				className={ productListClass }
				searchTerm={ props.searchTerm }
				category={ category }
			/>
			{ props.type === 'theme' && (
				<div
					className={
						'woocommerce-marketplace__browse-wp-theme-directory'
					}
				>
					<b>
						{ __( 'Didn’t find a theme you like?', 'woocommerce' ) }
					</b>
					{ createInterpolateElement(
						__(
							' Browse the <a>WordPress.org theme directory</a> to discover more.',
							'woocommerce'
						),
						{
							a: (
								// eslint-disable-next-line jsx-a11y/anchor-has-content
								<a
									href={
										ADMIN_URL +
										'theme-install.php?search=e-commerce'
									}
								/>
							),
						}
					) }
				</div>
			) }
			{ showAllButton && (
				<Button
					className={ viewAllButonClassName }
					variant="secondary"
					text={ __( 'View all', 'woocommerce' ) }
					onClick={ () => showSection( props.type ) }
				/>
			) }
		</div>
	);
}
