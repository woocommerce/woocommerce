/**
 * External dependencies
 */
import classNames from 'classnames';
import PropTypes from 'prop-types';

const Main = ( { children, className } ) => {
	return (
		<div className={ classNames( 'wc-block-main', className ) }>
			{ children }
		</div>
	);
};

Main.propTypes = {
	className: PropTypes.string,
};

export default Main;
