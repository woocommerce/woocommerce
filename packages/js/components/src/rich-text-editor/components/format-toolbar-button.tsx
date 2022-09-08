/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Fill, ToolbarButton } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { FORMAT_TOOLBAR_SLOT_NAME } from './fixed-formatting-toolbar';

export const FormatToolbarButton: React.VFC< ToolbarButton.Props > = (
	props
) => {
	// Set up a Fill for each formatting toolbar (top bar and contextual bar)
	return (
		<Fill name={ FORMAT_TOOLBAR_SLOT_NAME }>
			<ToolbarButton { ...props } />
		</Fill>
	);
};
