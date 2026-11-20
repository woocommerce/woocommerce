/**
 * External dependencies
 */
import type { ReactElement } from 'react';

/**
 * Match core Button selectors without adding a layout box around flex or grid items.
 */
const withGlobalStyles = ( button: ReactElement ) => (
	<div className="wp-block-button" style={ { display: 'contents' } }>
		{ button }
	</div>
);

export default withGlobalStyles;
