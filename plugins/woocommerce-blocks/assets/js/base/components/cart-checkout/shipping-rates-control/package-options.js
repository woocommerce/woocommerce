/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import RadioControl, {
	RadioControlOptionLayout,
} from '@woocommerce/base-components/radio-control';
import { Notice } from 'wordpress-components';
import classnames from 'classnames';

const PackageOptions = ( {
	className,
	noResultsMessage,
	onChange,
	options,
	renderOption,
	selected,
} ) => {
	if ( options.length === 0 ) {
		return (
			<Notice
				isDismissible={ false }
				className={ classnames(
					'wc-block-components-shipping-rates-control__no-results-notice',
					'woocommerce-message',
					'woocommerce-info'
				) }
			>
				{ noResultsMessage }
			</Notice>
		);
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
	noResultsMessage: PropTypes.string,
	selected: PropTypes.string,
};

export default PackageOptions;
