/**
 * External dependencies
 */
import { Fragment } from '@wordpress/element';
import { InspectorControls } from '@wordpress/block-editor';
import { createHigherOrderComponent } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import FeedbackPrompt from './feedback-prompt.js';

/**
 * Adds a feedback prompt with custom text to the editor sidebar.
 *
 * @param {string} content Text to display in the feedback prompt.
 *
 * @return {Function} Function that returns the wrapped component.
 */
const withFeedbackPrompt = ( content ) =>
	/**
	 * Adds a feedback prompt to the provided component.
	 *
	 * @param {WPComponent} BlockEdit Original component.
	 *
	 * @return {WPComponent} Wrapped component.
	 */
	createHigherOrderComponent( ( BlockEdit ) => {
		return ( props ) => (
			<Fragment>
				<BlockEdit { ...props } />
				<InspectorControls>
					<FeedbackPrompt text={ content } />
				</InspectorControls>
			</Fragment>
		);
	}, 'withFeedbackPrompt' );

export default withFeedbackPrompt;
