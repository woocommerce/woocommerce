/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { Spinner } from 'wordpress-components';

/**
 * Internal dependencies
 */
import './style.scss';

const LoadingMask = ( {
	children,
	className,
	screenReaderLabel,
	showSpinner = false,
	isLoading = true,
} ) => {
	// If nothing is loading, just pass through the children.
	if ( ! isLoading ) {
		return children;
	}

	return (
		<div
			className={ classNames(
				className,
				'wc-block-components-loading-mask'
			) }
		>
			{ showSpinner && <Spinner /> }
			<div
				className="wc-block-components-loading-mask__children"
				aria-hidden={ true }
			>
				{ children }
			</div>
			<span className="screen-reader-text">
				{ screenReaderLabel ||
					__( 'Loading…', 'woocommerce' ) }
			</span>
		</div>
	);
};

LoadingMask.propTypes = {
	className: PropTypes.string,
	screenReaderLabel: PropTypes.string,
	showSpinner: PropTypes.bool,
	isLoading: PropTypes.bool,
};

export default LoadingMask;
