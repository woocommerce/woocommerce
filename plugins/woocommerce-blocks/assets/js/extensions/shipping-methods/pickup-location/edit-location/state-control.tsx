/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { SelectControl, TextControl } from '@wordpress/components';

const StateControl = ( {
	states,
	currentCountry,
	...props
}: {
	states: Record< string, Record< string, string > >;
	currentCountry: string;
} ): JSX.Element | null => {
	const filteredStates = states[ currentCountry ] || [];

	if ( filteredStates.length === 0 ) {
		return (
			<TextControl
				{ ...props }
				disabled={ ! currentCountry || props.disabled }
			/>
		);
	}
	return (
		<SelectControl
			{ ...props }
			options={ [
				{
					value: '',
					disabled: true,
					label: __( 'State', 'woo-gutenberg-products-block' ),
				},
				...Object.entries( filteredStates ).map(
					( [ code, state ] ) => ( {
						value: code,
						label: state,
					} )
				),
			] }
		/>
	);
};

export default StateControl;
