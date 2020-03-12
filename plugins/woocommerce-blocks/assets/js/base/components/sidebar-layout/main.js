/**
 * External dependencies
 */
import classNames from 'classnames';

const Main = ( { children, className } ) => {
	return (
		<div className={ classNames( 'wc-block-main', className ) }>
			{ children }
		</div>
	);
};

export default Main;
