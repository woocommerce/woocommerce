/**
 * External dependencies
 */
import clsx from 'clsx';
import PropTypes from 'prop-types';

const ActivityPanelToggleBubble = ( {
	height = 24,
	width = 24,
	hasUnread = false,
} ) => {
	const classes = clsx( 'woocommerce-layout__activity-panel-toggle-bubble', {
		'has-unread': hasUnread,
	} );

	/* eslint-disable max-len */
	return (
		<div className={ classes }>
			<svg height={ height } width={ width } viewBox="0 0 24 24">
				<path d="M18.9 2H5.1C3.4 2 2 3.4 2 5.1v10.7C2 17.6 3.4 19 5.1 19H9l6 3-1-3h4.9c1.7 0 3.1-1.4 3.1-3.1V5.1C22 3.4 20.6 2 18.9 2zm-1.5 4.5c-.4.8-.8 2.1-1 3.9-.3 1.8-.4 3.1-.3 4.1 0 .3 0 .5-.1.7-.1.2-.3.4-.6.4s-.6-.1-.9-.4c-1-1-1.8-2.6-2.4-4.6-.7 1.4-1.2 2.4-1.6 3.1-.6 1.2-1.2 1.8-1.6 1.9-.3 0-.5-.2-.8-.7-.5-1.4-1.1-4.2-1.7-8.2 0-.3 0-.5.2-.7.1-.2.4-.3.7-.4.5 0 .9.2.9.8.3 2.3.7 4.2 1.1 5.7l2.4-4.5c.2-.4.4-.6.8-.6.5 0 .8.3.9.9.3 1.4.6 2.6 1 3.7.3-2.7.8-4.7 1.4-5.9.2-.3.4-.5.7-.5.2 0 .5.1.7.2.2.2.3.4.3.6 0 .2 0 .4-.1.5z" />
			</svg>
		</div>
	);
	/* eslint-enable max-len */
};

ActivityPanelToggleBubble.propTypes = {
	height: PropTypes.number,
	width: PropTypes.number,
	hasUnread: PropTypes.bool,
};

export default ActivityPanelToggleBubble;
