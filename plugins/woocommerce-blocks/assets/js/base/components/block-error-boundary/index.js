/**
 * External dependencies
 */
import { Component } from 'react';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import BlockError from './block-error';
import './style.scss';

class BlockErrorBoundary extends Component {
	state = { hasError: false };

	static getDerivedStateFromError( error ) {
		return { errorMessage: error.message, hasError: true };
	}

	render() {
		const { header, imageUrl, showErrorMessage, text } = this.props;
		const { errorMessage, hasError } = this.state;

		if ( hasError ) {
			return (
				<BlockError
					errorMessage={ showErrorMessage ? errorMessage : null }
					header={ header }
					imageUrl={ imageUrl }
					text={ text }
				/>
			);
		}

		return this.props.children;
	}
}

BlockErrorBoundary.propTypes = {
	/**
	 * Text to display as the heading of the error block.
	 * If it's `null` or an empty string, no header will be displayed.
	 * If it's not defined, the default header will be used.
	 */
	header: PropTypes.string,
	/**
	 * URL of the image to display.
	 * If it's `null` or an empty string, no image will be displayed.
	 * If it's not defined, the default image will be used.
	 */
	imageUrl: PropTypes.string,
	/**
	 * Whether to display the JS error message.
	 */
	showErrorMessage: PropTypes.bool,
	/**
	 * Text to display in the error block below the header.
	 * If it's `null` or an empty string, nothing will be displayed.
	 * If it's not defined, the default text will be used.
	 */
	text: PropTypes.string,
};

BlockErrorBoundary.defaultProps = {
	showErrorMessage: false,
};

export default BlockErrorBoundary;
