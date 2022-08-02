/**
 * External dependencies
 */
import { TextControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './product-field-layout.scss';

type ProductFieldLayoutProps = {
	label: string;
	key: string;
	autoComplete: string;
	value: string;
	onChange: () => void;
};

export const ProductFieldLayout: React.FC< ProductFieldLayoutProps > = ( {
	label,
	key,
	autoComplete,
	value,
	onChange,
} ) => {
	return (
		<TextControl
			key={ key }
			label={ label }
			autoComplete={ autoComplete }
			value={ value }
			onChange={ onChange }
		/>
	);
};
