/**
 * External dependencies
 */
import { useEffect, useRef, useState } from '@wordpress/element';
/* eslint-disable @wordpress/use-recommended-components -- The design asks for these @wordpress/ui components; this chunk bundles the package. */
import { Button, InputControl, Link } from '@wordpress/ui';
/* eslint-enable @wordpress/use-recommended-components */

/**
 * Internal dependencies
 */
import { safeHref } from '../safe-href';
import { trackReadiness } from '../tracks';
import type { ShoppingView } from '../types';

/**
 * The connection URL, read only, with Copy, and a link to test it in UCP Playground.
 */
export const ConnectionUrl = ( {
	connection,
}: {
	connection: ShoppingView[ 'connection' ];
} ) => {
	const [ copied, setCopied ] = useState( false );
	const timer = useRef< ReturnType< typeof setTimeout > >();
	useEffect( () => () => clearTimeout( timer.current ), [] );

	const copy = async () => {
		trackReadiness( 'link_clicked', {
			surface: 'shopping',
			area: 'connection_url_copy',
		} );
		if ( ! navigator.clipboard ) {
			return;
		}
		try {
			await navigator.clipboard.writeText( connection.url );
		} catch {
			return;
		}
		setCopied( true );
		clearTimeout( timer.current );
		timer.current = setTimeout( () => setCopied( false ), 2000 );
	};

	return (
		<div className="wc-ai-shopping-connection">
			<div className="wc-ai-shopping-connection__row">
				<InputControl
					className="wc-ai-shopping-connection__input"
					label={ connection.title }
					hideLabelFromVision
					readOnly
					value={ connection.url }
				/>
				<Button
					variant="outline"
					className="wc-sales-readiness-ui-button"
					onClick={ copy }
				>
					{ copied ? connection.copied : connection.copy }
				</Button>
			</div>
			<Link
				href={ safeHref( connection.test.href ) }
				openInNewTab
				onClick={ () =>
					trackReadiness( 'link_clicked', {
						surface: 'shopping',
						area: 'connection_url_test',
					} )
				}
			>
				{ connection.test.text }
			</Link>
		</div>
	);
};
