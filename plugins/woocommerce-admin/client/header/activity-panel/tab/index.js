/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import classnames from 'classnames';

export const Tab = ( {
	icon,
	title,
	name,
	unread,
	selected,
	isPanelOpen,
	onTabClick,
	index,
} ) => {
	const className = classnames( 'woocommerce-layout__activity-panel-tab', {
		'is-active': isPanelOpen && selected,
		'has-unread': unread,
	} );

	let tabIndex = -1;

	// Only make this item tabbable if it is the currently selected item, or the panel is closed and the item is the first item.
	if ( selected || ( ! isPanelOpen && index === 0 ) ) {
		tabIndex = null;
	}

	const tabKey = `activity-panel-tab-${ name }`;

	return (
		<Button
			role="tab"
			className={ className }
			tabIndex={ tabIndex }
			aria-selected={ selected }
			aria-controls={ `activity-panel-${ name }` }
			key={ tabKey }
			id={ tabKey }
			onClick={ () => {
				onTabClick( name );
			} }
		>
			{ icon }
			{ title }{ ' ' }
			{ unread && (
				<span className="screen-reader-text">
					{ __( 'unread activity', 'woocommerce-admin' ) }
				</span>
			) }
		</Button>
	);
};
