/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import clsx from 'clsx';

export const Tab = ( {
	icon,
	title,
	name,
	unread,
	selected,
	isPanelOpen,
	onTabClick,
} ) => {
	const className = clsx( 'woocommerce-layout__activity-panel-tab', {
		'is-active': isPanelOpen && selected,
		'has-unread': unread,
	} );

	const tabKey = `activity-panel-tab-${ name }`;

	return (
		<Button
			role="tab"
			className={ className }
			aria-selected={ selected }
			aria-controls={ `activity-panel-${ name }` }
			key={ tabKey }
			id={ tabKey }
			data-testid={ tabKey }
			onClick={ () => {
				onTabClick( name );
			} }
		>
			{ icon }
			{ title }{ ' ' }
			{ unread && (
				<span className="screen-reader-text">
					{ __( 'unread activity', 'woocommerce' ) }
				</span>
			) }
		</Button>
	);
};
