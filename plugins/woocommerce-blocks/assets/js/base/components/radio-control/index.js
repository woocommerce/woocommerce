/**
 * External dependencies
 */
import classnames from 'classnames';
import withComponentId from '@woocommerce/base-hocs/with-component-id';

/**
 * Internal dependencies
 */
import RadioControlOption from './option';
import './style.scss';

const RadioControl = ( {
	className,
	componentId,
	id,
	selected,
	onChange,
	options = [],
} ) => {
	const radioControlId = id || componentId;

	return (
		options.length && (
			<div
				className={ classnames( 'wc-block-radio-control', className ) }
			>
				{ options.map( ( option ) => (
					<RadioControlOption
						key={ option.value }
						name={ `radio-control-${ radioControlId }` }
						checked={ option.value === selected }
						option={ option }
						onChange={ onChange }
					/>
				) ) }
			</div>
		)
	);
};

export default withComponentId( RadioControl );
export { RadioControlOption };
export { default as RadioControlOptionLayout } from './option-layout';
