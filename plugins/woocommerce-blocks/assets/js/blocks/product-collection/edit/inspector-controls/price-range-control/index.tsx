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
import { QueryControlProps } from '../../types';
import PriceTextField from './PriceTextField';

const PriceRangeControl = ( props: QueryControlProps ) => {
	const { query, setQueryAttribute } = props;

	const value = query.priceRange;

	return (
		<ToolsPanelItem
			label={ __( 'Price Range', 'woo-gutenberg-products-block' ) }
			hasValue={ () => {
				return value?.min !== undefined || value?.max !== undefined;
			} }
			onDeselect={ () => {
				setQueryAttribute( { priceRange: undefined } );
			} }
			className="wc-block-product-price-range-control"
		>
			<BaseControl.VisualLabel>
				{ __( 'PRICE RANGE', 'woo-gutenberg-products-block' ) }
			</BaseControl.VisualLabel>

			<HStack spacing="2">
				<PriceTextField
					label={ __( 'MIN', 'woo-gutenberg-products-block' ) }
					value={ value?.min as number }
					onChange={ ( min?: number ) => {
						setQueryAttribute( {
							priceRange: {
								min: min === 0 ? undefined : min,
								max: value?.max as number,
							},
						} );
					} }
				/>

				<PriceTextField
					label={ __( 'MAX', 'woo-gutenberg-products-block' ) }
					value={ value?.max as number }
					onChange={ ( max?: number ) => {
						setQueryAttribute( {
							priceRange: {
								min: value?.min as number,
								max: max === 0 ? undefined : max,
							},
						} );
					} }
				/>
			</HStack>
		</ToolsPanelItem>
	);
};

export default PriceRangeControl;
