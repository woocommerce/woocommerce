/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { Component, Fragment } from 'react';

/**
 * Internal dependencies
 */
import BlockError from './block-error';
import './style.scss';

class BlockErrorBoundary extends Component {
	state = { errorMessage: '', hasError: false };

	static getDerivedStateFromError( error ) {
		if (
			typeof error.statusText !== 'undefined' &&
			typeof error.status !== 'undefined'
		) {
			return {
				errorMessage: (
					<Fragment>
						<strong>{ error.status }</strong>:&nbsp;
						{ error.statusText }
					</Fragment>
				),
				hasError: true,
			};
		}

		return { errorMessage: error.message, hasError: true };
	}

	render() {
		const {
			header,
			imageUrl,
			showErrorMessage,
			text,
			errorMessagePrefix,
		} = this.props;
		const { errorMessage, hasError } = this.state;

		if ( hasError ) {
			return (
				<BlockError
					errorMessage={ showErrorMessage ? errorMessage : null }
					header={ header }
					imageUrl={ imageUrl }
					text={ text }
					errorMessagePrefix={ errorMessagePrefix }
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
	text: PropTypes.node,
	/**
	 * Text preceeding the error message.
	 */
	errorMessagePrefix: PropTypes.string,
};

BlockErrorBoundary.defaultProps = {
	showErrorMessage: true,
};

export default BlockErrorBoundary;
