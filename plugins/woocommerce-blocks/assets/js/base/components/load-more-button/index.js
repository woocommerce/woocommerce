/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment } from 'react';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';

export const LoadMoreButton = ( { onClick, label, screenReaderLabel } ) => {
	const labelNode = ( screenReaderLabel && label !== screenReaderLabel ) ? (
		<Fragment>
			<span aria-hidden>
				{ label }
			</span>
			<span className="screen-reader-text">
				{ screenReaderLabel }
			</span>
		</Fragment>
	) : label;

	return (
		<button
			className="wc-block-load-more"
			onClick={ onClick }
		>
			{ labelNode }
		</button>
	);
};

LoadMoreButton.propTypes = {
	label: PropTypes.string,
	onClick: PropTypes.func,
	screenReaderLabel: PropTypes.string,
};

LoadMoreButton.defaultProps = {
	label: __( 'Load more', 'woo-gutenberg-products-block' ),
};

export default LoadMoreButton;
