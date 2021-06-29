/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { Component } from 'react';

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
					<>
						<strong>{ error.status }</strong>:&nbsp;
						{ error.statusText }
					</>
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
			renderError,
			button,
		} = this.props;
		const { errorMessage, hasError } = this.state;

		if ( hasError ) {
			if ( typeof renderError === 'function' ) {
				return renderError( { errorMessage } );
			}
			return (
				<BlockError
					errorMessage={ showErrorMessage ? errorMessage : null }
					header={ header }
					imageUrl={ imageUrl }
					text={ text }
					errorMessagePrefix={ errorMessagePrefix }
					button={ button }
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
	/**
	 * Render function to show a custom error component.
	 */
	renderError: PropTypes.func,
};

BlockErrorBoundary.defaultProps = {
	showErrorMessage: true,
};

export default BlockErrorBoundary;
