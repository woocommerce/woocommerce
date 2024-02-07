/**
 * External dependencies
 */
import { useCollectionData } from '@woocommerce/base-context/hooks';

/**
 * Internal dependencies
 */
import { EditProps } from '../types';
import { getFormattedPrice } from '../utils';

/**
 * We pass the whole props from Edit component to <PriceSlider/> so we're
 * reusing the EditProps type here.
 */
export const PriceSlider = ( { attributes }: EditProps ) => {
	const { showInputFields } = attributes;

	const { results, isLoading } = useCollectionData( {
		queryPrices: true,
		queryState: {},
		isEditor: true,
	} );

	if ( isLoading ) return null;

	const { minPrice, maxPrice, formattedMinPrice, formattedMaxPrice } =
		getFormattedPrice( results );

	const onChange = () => null;

	const priceMin = showInputFields ? (
		<input
			className="min"
			type="text"
			value={ minPrice }
			onChange={ onChange }
		/>
	) : (
		<span>{ formattedMinPrice }</span>
	);

	const priceMax = showInputFields ? (
		<input
			className="max"
			type="text"
			value={ maxPrice }
			onChange={ onChange }
		/>
	) : (
		<span>{ formattedMaxPrice }</span>
	);

	return (
		<div>
			<div className="range">
				<div className="range-bar"></div>
				<input
					type="range"
					className="min"
					min={ minPrice }
					max={ maxPrice }
					value={ minPrice }
					onChange={ onChange }
				/>
				<input
					type="range"
					className="max"
					min={ minPrice }
					max={ maxPrice }
					value={ maxPrice }
					onChange={ onChange }
				/>
			</div>
			<div className="text">
				{ priceMin }
				{ priceMax }
			</div>
		</div>
	);
};
