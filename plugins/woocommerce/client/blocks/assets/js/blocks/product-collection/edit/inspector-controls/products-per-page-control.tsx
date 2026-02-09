/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	RangeControl,
	Notice,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { CoreFilterNames, QueryControlProps } from '../../types';
import { DEFAULT_QUERY } from '../../constants';

const MIN_PRODUCTS_PER_PAGE = 1;
const MAX_PRODUCTS_PER_PAGE = 100;
const CAROUSEL_PERFORMANCE_WARNING_THRESHOLD = 30;

const defaultLabel = __( 'Products per page', 'woocommerce' );
const carouselLabel = __( 'Products in carousel', 'woocommerce' );

const getLabel = ( carouselVariant: boolean ) => {
	return carouselVariant ? carouselLabel : defaultLabel;
};

const ProductsPerPageControl = ( {
	query,
	setQueryAttribute,
	trackInteraction,
	carouselVariant,
}: QueryControlProps & { carouselVariant: boolean } ) => {
	const deselectCallback = () => {
		setQueryAttribute( { perPage: DEFAULT_QUERY.perPage } );
		trackInteraction( CoreFilterNames.PRODUCTS_PER_PAGE );
	};

	const label = getLabel( carouselVariant );
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
			<RangeControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ label }
				min={ MIN_PRODUCTS_PER_PAGE }
				max={ MAX_PRODUCTS_PER_PAGE }
				onChange={ ( newPerPage: number ) => {
					if (
						newPerPage < MIN_PRODUCTS_PER_PAGE ||
						newPerPage > MAX_PRODUCTS_PER_PAGE
					) {
						return;
					}
					setQueryAttribute( { perPage: newPerPage } );
					trackInteraction( CoreFilterNames.PRODUCTS_PER_PAGE );
				} }
				value={ perPage }
			/>
		</ToolsPanelItem>
	);
};

export default ProductsPerPageControl;
