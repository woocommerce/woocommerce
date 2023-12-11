/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { RangeControl, ToggleControl } from '@wordpress/components';

interface ClampProps {
	( number: number, boundOne: number, boundTwo?: number ): number;
}

const clamp: ClampProps = ( number, boundOne, boundTwo ) => {
	if ( ! boundTwo ) {
		return Math.max( number, boundOne ) === boundOne ? number : boundOne;
	} else if ( Math.min( number, boundOne ) === number ) {
		return boundOne;
	} else if ( Math.max( number, boundTwo ) === number ) {
		return boundTwo;
	}
	return number;
};

interface GridLayoutControlProps {
	columns: number;
	rows: number;
	setAttributes: ( attributes: Record< string, unknown > ) => void;
	alignButtons: boolean;
	minColumns?: number;
	maxColumns?: number;
	minRows?: number;
	maxRows?: number;
}

/**
 * A combination of range controls for product grid layout settings.
 *
 * @param {Object}            props               Incoming props for the component.
 * @param {number}            props.columns
 * @param {number}            props.rows
 * @param {function(any):any} props.setAttributes Setter for block attributes.
 * @param {boolean}           props.alignButtons
 * @param {number}            props.minColumns
 * @param {number}            props.maxColumns
 * @param {number}            props.minRows
 * @param {number}            props.maxRows
 */
const GridLayoutControl = ( {
	columns,
	rows,
	setAttributes,
	alignButtons,
	minColumns = 1,
	maxColumns = 6,
	minRows = 1,
	maxRows = 6,
}: GridLayoutControlProps ) => {
	return (
		<>
			<RangeControl
				label={ __( 'Columns', 'woo-gutenberg-products-block' ) }
				value={ columns }
				onChange={ ( value: number ) => {
					const newValue = clamp( value, minColumns, maxColumns );
					setAttributes( {
						columns: Number.isNaN( newValue ) ? '' : newValue,
					} );
				} }
				min={ minColumns }
				max={ maxColumns }
			/>
			<RangeControl
				label={ __( 'Rows', 'woo-gutenberg-products-block' ) }
				value={ rows }
				onChange={ ( value: number ) => {
					const newValue = clamp( value, minRows, maxRows );
					setAttributes( {
						rows: Number.isNaN( newValue ) ? '' : newValue,
					} );
				} }
				min={ minRows }
				max={ maxRows }
			/>
			<ToggleControl
				label={ __(
					'Align the last block to the bottom',
					'woo-gutenberg-products-block'
				) }
				help={
					alignButtons
						? __(
								'Align the last block to the bottom.',
								'woo-gutenberg-products-block'
						  )
						: __(
								'The last inner block will follow other content.',
								'woo-gutenberg-products-block'
						  )
				}
				checked={ alignButtons }
				onChange={ () =>
					setAttributes( { alignButtons: ! alignButtons } )
				}
			/>
		</>
	);
};

export default GridLayoutControl;
