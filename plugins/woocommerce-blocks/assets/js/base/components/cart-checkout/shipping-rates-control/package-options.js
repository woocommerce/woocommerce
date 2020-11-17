/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import RadioControl, {
	RadioControlOptionLayout,
} from '@woocommerce/base-components/radio-control';

const PackageOptions = ( {
	className,
	noResultsMessage,
	onChange,
	options,
	renderOption,
	selected,
} ) => {
	if ( options.length === 0 ) {
		return noResultsMessage;
	}

	if ( options.length > 1 ) {
		return (
			<RadioControl
				className={ className }
				onChange={ onChange }
				selected={ selected }
				options={ options.map( renderOption ) }
			/>
		);
	}

	const {
		label,
		secondaryLabel,
		description,
		secondaryDescription,
	} = renderOption( options[ 0 ] );

	return (
		<RadioControlOptionLayout
			label={ label }
			secondaryLabel={ secondaryLabel }
			description={ description }
			secondaryDescription={ secondaryDescription }
		/>
	);
};

PackageOptions.propTypes = {
	onChange: PropTypes.func.isRequired,
	options: PropTypes.arrayOf( PropTypes.object ).isRequired,
	renderOption: PropTypes.func.isRequired,
	className: PropTypes.string,
	noResultsMessage: PropTypes.node,
	selected: PropTypes.string,
};

export default PackageOptions;
