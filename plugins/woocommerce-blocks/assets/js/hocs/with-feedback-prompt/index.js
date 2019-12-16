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
 * Adds a feedback prompt to the editor sidebar.
 *
 * @param {WPComponent} BlockEdit Original component.
 *
 * @return {WPComponent} Wrapped component.
 */
const withFeedbackPrompt = ( content ) =>
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
