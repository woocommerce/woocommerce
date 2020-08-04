/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import Label from '@woocommerce/base-components/label';

/**
 * Internal dependencies
 */
import './style.scss';

export const LoadMoreButton = ( { onClick, label, screenReaderLabel } ) => {
	return (
		<div className="wp-block-button wc-block-load-more wc-block-components-load-more">
			<button className="wp-block-button__link" onClick={ onClick }>
				<Label
					label={ label }
					screenReaderLabel={ screenReaderLabel }
				/>
			</button>
		</div>
	);
};

LoadMoreButton.propTypes = {
	label: PropTypes.string,
	onClick: PropTypes.func,
	screenReaderLabel: PropTypes.string,
};

LoadMoreButton.defaultProps = {
	label: __( 'Load more', 'woocommerce' ),
};

export default LoadMoreButton;
