/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import PriceSlider from '../';

export default {
	title: 'WooCommerce Blocks/@base-components/PriceSlider',
	component: PriceSlider,
};

export const Default = () => {
	// PriceSlider expects client to update min & max price, i.e. is a controlled component
	const [ min, setMin ] = useState( 1000 );
	const [ max, setMax ] = useState( 5000 );
	return (
		<PriceSlider
			minPrice={ min }
			maxPrice={ max }
			onChange={ ( values ) => {
				setMin( values[ 0 ] );
				setMax( values[ 1 ] );
			} }
			minConstraint={ 1000 }
			maxConstraint={ 5000 }
			step={ 250 }
			currency={ {
				code: 'nzd',
				symbol: '$',
				thousandSeparator: ' ',
				decimalSeparator: '.',
				minorUnit: 2,
				prefix: '$',
				suffix: '',
			} }
		/>
	);
};
