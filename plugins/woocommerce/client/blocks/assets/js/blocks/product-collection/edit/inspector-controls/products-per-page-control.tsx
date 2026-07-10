/**
 * External dependencies
 */
import { useIsEmailEditor } from '@woocommerce/email-editor';
import { __ } from '@wordpress/i18n';
import {
	Notice,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { CoreFilterNames, QueryControlProps } from '../../types';
import { DEFAULT_QUERY } from '../../constants';
import ProductsPerPageRangeControl from './products-per-page-range-control';

const CAROUSEL_PERFORMANCE_WARNING_THRESHOLD = 30;

const defaultLabel = __( 'Products per page', 'woocommerce' );
const carouselLabel = __( 'Products in carousel', 'woocommerce' );
const emailLabel = __( 'Number of products', 'woocommerce' );

const getLabel = ( carouselVariant: boolean, isEmailEditor: boolean ) => {
	if ( isEmailEditor ) {
		return emailLabel;
	}
	return carouselVariant ? carouselLabel : defaultLabel;
};

const ProductsPerPageControl = ( {
	query,
	setQueryAttribute,
	trackInteraction,
	carouselVariant,
}: QueryControlProps & { carouselVariant: boolean } ) => {
	const isEmailEditor = useIsEmailEditor();
	const deselectCallback = () => {
		setQueryAttribute( { perPage: DEFAULT_QUERY.perPage } );
		trackInteraction( CoreFilterNames.PRODUCTS_PER_PAGE );
	};

	const label = getLabel( carouselVariant, isEmailEditor );
	const perPage = query.perPage || DEFAULT_QUERY.perPage;
	const showPerformanceWarning =
		carouselVariant && perPage > CAROUSEL_PERFORMANCE_WARNING_THRESHOLD;

	return (
		<ToolsPanelItem
			label={ label }
			isShownByDefault
			hasValue={ () => query.perPage !== DEFAULT_QUERY.perPage }
			onDeselect={ deselectCallback }
			resetAllFilter={ deselectCallback }
		>
			{ showPerformanceWarning && (
				<div>
					<Notice
						status="warning"
						isDismissible={ false }
						className="wc-block-editor-product-collection__carousel-warning"
					>
						{ __(
							'High product counts in carousel may impact performance. Consider reducing the number of products for better user experience.',
							'woocommerce'
						) }
					</Notice>
				</div>
			) }
			<ProductsPerPageRangeControl
				label={ label }
				value={ perPage }
				onChange={ ( newPerPage: number ) => {
					setQueryAttribute( { perPage: newPerPage } );
					trackInteraction( CoreFilterNames.PRODUCTS_PER_PAGE );
				} }
			/>
		</ToolsPanelItem>
	);
};

export default ProductsPerPageControl;
