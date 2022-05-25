/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { createElement, useState } from '@wordpress/element';

type InboxNoteActionProps = {
	onClick: () => void;
	label: string;
	href?: string;
	preventBusyState?: boolean;
	variant: 'link' | 'secondary';
};

/**
 * Renders a secondary button that can also be a link. If href is provided it will
 * automatically open it in a new tab/window.
 */
export const InboxNoteActionButton: React.FC< InboxNoteActionProps > = ( {
	label,
	onClick,
	href,
	preventBusyState,
	variant = 'link',
} ) => {
	const [ inAction, setInAction ] = useState( false );

	const handleActionClick: React.MouseEventHandler< HTMLAnchorElement > = (
		event
	) => {
		const targetHref = event.currentTarget.href || '';
		let isActionable = true;

		let adminUrl = '';
		if ( window.wcSettings ) {
			adminUrl = window.wcSettings.adminUrl;
		}

		if (
			targetHref.length &&
			( ! adminUrl || ! targetHref.startsWith( adminUrl ) )
		) {
			event.preventDefault();
			isActionable = false; // link buttons shouldn't be "busy".
			window.open( targetHref, '_blank' );
		}

		if ( preventBusyState ) {
			isActionable = false;
		}

		setInAction( isActionable );
		onClick();
	};

	return (
		<Button
			isSecondary={ variant === 'secondary' }
			isLink={ variant === 'link' }
			isBusy={ inAction }
			disabled={ inAction }
			href={ href }
			onClick={ handleActionClick }
		>
			{ label }
		</Button>
	);
};
