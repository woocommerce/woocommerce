/* eslint-disable @wordpress/no-unsafe-wp-apis */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	BaseControl,
	// @ts-expect-error Using experimental features
	__experimentalHStack as HStack,
	// @ts-expect-error Using experimental features
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { QueryControlProps } from '../../../types';
import { DEFAULT_FILTERS } from '../../../constants';
import PriceTextField from './PriceTextField';

const PriceRangeControl = ( props: QueryControlProps ) => {
	const { query, setQueryAttribute } = props;

	const value = query.priceRange;

	const deselectCallback = () => {
		setQueryAttribute( { priceRange: DEFAULT_FILTERS.priceRange } );
	};

	return (
		<ToolsPanelItem
			label={ __( 'Price Range', 'woocommerce' ) }
			hasValue={ () => {
				return value?.min !== undefined || value?.max !== undefined;
			} }
			onDeselect={ deselectCallback }
			resetAllFilter={ deselectCallback }
			className="wc-block-product-price-range-control"
		>
			<BaseControl.VisualLabel>
				{ __( 'PRICE RANGE', 'woocommerce' ) }
			</BaseControl.VisualLabel>

			<HStack spacing="2">
				<PriceTextField
					label={ __( 'MIN', 'woocommerce' ) }
					value={ value?.min as number }
					onChange={ ( val?: number ) => {
						const min = val === 0 ? undefined : val;

						setQueryAttribute( {
							priceRange: {
								min,
								max: value?.max as number,
							},
						} );
					} }
				/>

				<PriceTextField
					label={ __( 'MAX', 'woocommerce' ) }
					value={ value?.max as number }
					onChange={ ( val?: number ) => {
						const max = val === 0 ? undefined : val;

						setQueryAttribute( {
							priceRange: {
								min: value?.min as number,
								max,
							},
						} );
					} }
				/>
			</HStack>
		</ToolsPanelItem>
	);
};

export default PriceRangeControl;
