/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import { WC_BLOCKS_IMAGE_URL } from '@woocommerce/block-settings';

const BlockError = ( {
	imageUrl = `${ WC_BLOCKS_IMAGE_URL }/block-error.svg`,
	header = __( 'Oops!', 'woocommerce' ),
	text = __(
		'There was an error loading the content.',
		'woocommerce'
	),
	errorMessage,
	errorMessagePrefix = __( 'Error:', 'woocommerce' ),
	button,
} ) => {
	return (
		<div className="wc-block-error wc-block-components-error">
			{ imageUrl && (
				<img
					className="wc-block-error__image wc-block-components-error__image"
					src={ imageUrl }
					alt=""
				/>
			) }
			<div className="wc-block-error__content wc-block-components-error__content">
				{ header && (
					<p className="wc-block-error__header wc-block-components-error__header">
						{ header }
					</p>
				) }
				{ text && (
					<p className="wc-block-error__text wc-block-components-error__text">
						{ text }
					</p>
				) }
				{ errorMessage && (
					<p className="wc-block-error__message wc-block-components-error__message">
						{ errorMessagePrefix ? errorMessagePrefix + ' ' : '' }
						{ errorMessage }
					</p>
				) }
				{ button && (
					<p className="wc-block-error__button wc-block-components-error__button">
						{ button }
					</p>
				) }
			</div>
		</div>
	);
};

BlockError.propTypes = {
	/**
	 * Error message to display below the content.
	 */
	errorMessage: PropTypes.node,
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
	 * Button cta.
	 */
	button: PropTypes.node,
};

export default BlockError;
