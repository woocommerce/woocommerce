/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { getSettingWithCoercion } from '@woocommerce/settings';
import { isNumber } from '@woocommerce/types';
import {
	RangeControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { CoreFilterNames, QueryControlProps } from '../../types';

const MIN_PRODUCTS_PER_PAGE = 1;
const MAX_PRODUCTS_PER_PAGE = 100;

const label = __( 'Products per page', 'woocommerce' );

/**
 * "Products per page" for a Product Collection that inherits the archive
 * query. The value is stored on the block and read on the server when the
 * archive's main query is built, so pagination, the results count and the
 * filter blocks follow the same page size as the collection. Until it is set,
 * the control shows the store default that applies today.
 */
const ArchiveProductsPerPageControl = ( {
	query,
	setQueryAttribute,
	trackInteraction,
}: QueryControlProps ) => {
	// Same fallback the Product Template preview uses when the setting is absent.
	const storeDefault = getSettingWithCoercion(
		'loopShopPerPage',
		12,
		isNumber
	);
	const hasValue = query.archivePerPage !== undefined;
	const value = hasValue ? query.archivePerPage : storeDefault;

	const deselectCallback = () => {
		setQueryAttribute( { archivePerPage: undefined } );
		trackInteraction( CoreFilterNames.PRODUCTS_PER_PAGE );
	};

	return (
		<ToolsPanelItem
			label={ label }
			isShownByDefault
			hasValue={ () => hasValue }
			onDeselect={ deselectCallback }
			resetAllFilter={ deselectCallback }
		>
			<RangeControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ label }
				help={ sprintf(
					/* translators: %d: the store's default number of products per page. */
					__(
						'Sets how many products each page of this archive shows. Pagination and the results count follow it. The store default is %d.',
						'woocommerce'
					),
					storeDefault
				) }
				min={ MIN_PRODUCTS_PER_PAGE }
				max={ MAX_PRODUCTS_PER_PAGE }
				onChange={ ( newValue?: number ) => {
					if (
						newValue === undefined ||
						newValue < MIN_PRODUCTS_PER_PAGE ||
						newValue > MAX_PRODUCTS_PER_PAGE
					) {
						return;
					}
					setQueryAttribute( { archivePerPage: newValue } );
					trackInteraction( CoreFilterNames.PRODUCTS_PER_PAGE );
				} }
				value={ value }
			/>
		</ToolsPanelItem>
	);
};

export default ArchiveProductsPerPageControl;
