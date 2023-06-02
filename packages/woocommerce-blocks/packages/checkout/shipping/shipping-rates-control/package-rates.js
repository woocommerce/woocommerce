/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import RadioControl, {
	RadioControlOptionLayout,
} from '@woocommerce/base-components/radio-control';

/**
 * Internal dependencies
 */
import { renderPackageRateOption } from './render-package-rate-option';

const PackageRates = ( {
	className,
	noResultsMessage,
	onSelectRate,
	rates,
	renderOption = renderPackageRateOption,
	selected,
} ) => {
	if ( rates.length === 0 ) {
		return noResultsMessage;
	}

	if ( rates.length > 1 ) {
		return (
			<RadioControl
				className={ className }
				onChange={ ( selectedRateId ) => {
					onSelectRate( selectedRateId );
				} }
				selected={ selected }
				options={ rates.map( renderOption ) }
			/>
		);
	}

	const {
		label,
		secondaryLabel,
		description,
		secondaryDescription,
	} = renderOption( rates[ 0 ] );

	return (
		<RadioControlOptionLayout
			label={ label }
			secondaryLabel={ secondaryLabel }
			description={ description }
			secondaryDescription={ secondaryDescription }
		/>
	);
};

PackageRates.propTypes = {
	onSelectRate: PropTypes.func.isRequired,
	rates: PropTypes.arrayOf( PropTypes.object ).isRequired,
	renderOption: PropTypes.func,
	className: PropTypes.string,
	noResultsMessage: PropTypes.node,
	selected: PropTypes.string,
};

export default PackageRates;
