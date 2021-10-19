/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import './style.scss';
import Spinner from '../spinner';

// @todo Find a way to block buttons/form components when LoadingMask isLoading
const LoadingMask = ( {
	children,
	className,
	screenReaderLabel,
	showSpinner = false,
	isLoading = true,
} ) => {
	return (
		<div
			className={ classNames( className, {
				'wc-block-components-loading-mask': isLoading,
			} ) }
		>
			{ isLoading && showSpinner && <Spinner /> }
			<div
				className={ classNames( {
					'wc-block-components-loading-mask__children': isLoading,
				} ) }
				aria-hidden={ isLoading }
			>
				{ children }
			</div>
			{ isLoading && (
				<span className="screen-reader-text">
					{ screenReaderLabel ||
						__( 'Loading…', 'woocommerce' ) }
				</span>
			) }
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
