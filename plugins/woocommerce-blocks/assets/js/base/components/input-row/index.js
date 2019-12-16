/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './style.scss';

const InputRow = ( { className, children } ) => {
	return (
		<div className={ classnames( 'wc-blocks-input-row', className ) }>
			{ children }
		</div>
	);
};

export default InputRow;
