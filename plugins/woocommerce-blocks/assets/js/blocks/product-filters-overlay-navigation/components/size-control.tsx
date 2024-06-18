/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import {
	RangeControl,
	BaseControl,
	useBaseControlProps,
	__experimentalUnitControl as UnitControl,
	__experimentalGrid as Grid,
} from '@wordpress/components';

type SizeControlProps = {
	onChange: ( numbericSize: number, unit: string ) => void;
	value: string;
	units: { value: string; label: string; default: number }[];
	label?: string;
};

export default function SizeControl( {
	onChange,
	value,
	units,
	label,
	...baseProps
}: SizeControlProps ) {
	const { baseControlProps } = useBaseControlProps( baseProps );

	const [ numericSize, setNumericSize ] = useState< number >(
		parseInt( value || '16px', 10 )
	);

	const [ unit, setUnit ] = useState< string >(
		value ? value.replace( /[^a-zA-Z]/g, '' ) : 'px'
	);

	useEffect( () => {
		onChange( numericSize, unit );
	}, [ numericSize, unit, onChange ] );

	return (
		<BaseControl { ...baseControlProps }>
			{ label && (
				<BaseControl.VisualLabel>{ label }</BaseControl.VisualLabel>
			) }
			<Grid columns={ 2 }>
				<UnitControl
					value={ `${ numericSize }${ unit }` }
					units={ units }
					onChange={ ( newSize: string ) => {
						setNumericSize( parseInt( newSize, 10 ) );
						if ( ! newSize ) {
							setUnit( 'px' );
							return;
						}
						if ( ! newSize.includes( unit ) ) {
							setUnit( newSize.replace( /[^a-zA-Z]/g, '' ) );
						}
					} }
				/>
				<RangeControl
					value={ numericSize }
					onChange={ ( newSize: number ) =>
						setNumericSize( newSize )
					}
					min={ 0 }
					max={ 300 }
					withInputField={ false }
				/>
			</Grid>
		</BaseControl>
	);
}
