/**
 * External dependencies
 */
import classnames from 'classnames';
import { useInstanceId } from '@wordpress/compose';
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import RadioControlOption from './option';
import type { RadioControlProps } from './types';
import './style.scss';

const RadioControl = ( {
	className = '',
	id,
	selected = '',
	onChange,
	options = [],
	disabled = false,
	highlightChecked = false,
}: RadioControlProps ): JSX.Element | null => {
	const instanceId = useInstanceId( RadioControl );
	const radioControlId = id || instanceId;

	const selectedOptionNumber = useMemo( () => {
		return options.findIndex( ( option ) => option.value === selected );
	}, [ options, selected ] );

	if ( ! options.length ) {
		return null;
	}

	return (
		<div
			className={ classnames(
				'wc-block-components-radio-control',
				{
					'wc-block-components-radio-control--highlight-checked--first-selected':
						highlightChecked && selectedOptionNumber === 0,
					'wc-block-components-radio-control--highlight-checked--last-selected':
						highlightChecked &&
						selectedOptionNumber === options.length - 1,
					'wc-block-components-radio-control--highlight-checked':
						highlightChecked,
				},
				className
			) }
		>
			{ options.map( ( option ) => (
				<RadioControlOption
					highlightChecked={ highlightChecked }
					key={ `${ radioControlId }-${ option.value }` }
					name={ `radio-control-${ radioControlId }` }
					checked={ option.value === selected }
					option={ option }
					onChange={ ( value: string ) => {
						onChange( value );
						if ( typeof option.onChange === 'function' ) {
							option.onChange( value );
						}
					} }
					disabled={ disabled }
				/>
			) ) }
		</div>
	);
};

export default RadioControl;
export { default as RadioControlOption } from './option';
export { default as RadioControlOptionLayout } from './option-layout';
