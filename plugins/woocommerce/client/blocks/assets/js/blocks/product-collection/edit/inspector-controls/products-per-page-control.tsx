/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	RangeControl,
	// @ts-expect-error Using experimental features
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

	return (
		<ToolsPanelItem
			label={ label }
			isShownByDefault
			hasValue={ () => query.perPage !== DEFAULT_QUERY.perPage }
			onDeselect={ deselectCallback }
			resetAllFilter={ deselectCallback }
		>
			<RangeControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ label }
				min={ MIN_PRODUCTS_PER_PAGE }
				max={ MAX_PRODUCTS_PER_PAGE }
				onChange={ ( newPerPage: number ) => {
					if (
						isNaN( newPerPage ) ||
						newPerPage < MIN_PRODUCTS_PER_PAGE ||
						newPerPage > MAX_PRODUCTS_PER_PAGE
					) {
						return;
					}
					setQueryAttribute( { perPage: newPerPage } );
					trackInteraction( CoreFilterNames.PRODUCTS_PER_PAGE );
				} }
				value={ query.perPage || DEFAULT_QUERY.perPage }
			/>
		</ToolsPanelItem>
	);
};

export default ProductsPerPageControl;
