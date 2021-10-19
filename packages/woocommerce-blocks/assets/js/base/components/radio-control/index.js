/**
 * External dependencies
 */
import classnames from 'classnames';
import { withInstanceId } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import RadioControlOption from './option';
import './style.scss';

const RadioControl = ( {
	className,
	instanceId,
	id,
	selected,
	onChange,
	options = [],
} ) => {
	const radioControlId = id || instanceId;

	return (
		options.length && (
			<div
				className={ classnames(
					'wc-block-components-radio-control',
					className
				) }
			>
				{ options.map( ( option ) => (
					<RadioControlOption
						key={ `${ radioControlId }-${ option.value }` }
						name={ `radio-control-${ radioControlId }` }
						checked={ option.value === selected }
						option={ option }
						onChange={ ( value ) => {
							onChange( value );
							if ( typeof option.onChange === 'function' ) {
								option.onChange( value );
							}
						} }
					/>
				) ) }
			</div>
		)
	);
};

export default withInstanceId( RadioControl );
export { RadioControlOption };
export { default as RadioControlOptionLayout } from './option-layout';
