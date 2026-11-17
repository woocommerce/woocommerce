/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';
import { useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { WooHeaderItem, WooHeaderPageTitle } from '@woocommerce/admin-layout';
import { Link } from '@wordpress/ui';

/**
 * Internal dependencies
 */
import './ai-shopping-header.scss';

interface AiShoppingHeaderProps {
	parentLabel: string;
	parentUrl: string;
	title: string;
	description: string;
}

/**
 * Save in the header, as in the design: the settings form's own Save changes button, moved here and
 * renamed, so wp-admin's settings script still turns it on when a field changes and the form saves
 * as before. It goes back under the form if the header unmounts.
 */
export const HeaderSave = () => {
	const ref = useRef< HTMLSpanElement >( null );
	useEffect( () => {
		const button = document.querySelector< HTMLButtonElement >(
			'#mainform .woocommerce-save-button'
		);
		const home = button?.parentElement;
		if ( ! button || ! home || ! ref.current ) {
			return;
		}
		const text = button.textContent;
		// Outside the form, the button still submits it.
		button.setAttribute( 'form', 'mainform' );
		button.textContent = __( 'Save', 'woocommerce' );
		// The settings script lets the page unload without the unsaved changes prompt only for
		// buttons it found under the form when it loaded.
		const allowUnload = () => {
			window.onbeforeunload = null;
		};
		button.addEventListener( 'click', allowUnload );
		ref.current.appendChild( button );
		return () => {
			button.removeEventListener( 'click', allowUnload );
			button.textContent = text;
			home.prepend( button );
		};
	}, [] );
	return <span ref={ ref } className="wc-ai-shopping-header__save" />;
};

/**
 * WooCommerce > Settings > AI tools > AI shopping: the breadcrumb becomes the WooCommerce page
 * header's title, with the description under it, as the offline payment methods pages do. The
 * PHP section hides the settings tabs.
 */
export const registerAiShoppingHeader = ( {
	parentLabel,
	parentUrl,
	title,
	description,
}: AiShoppingHeaderProps ): void => {
	registerPlugin( 'wc-ai-shopping-header', {
		scope: 'woocommerce-admin',
		render: () => (
			<>
				<WooHeaderPageTitle>
					<span className="wc-ai-shopping-header__trail">
						<Link tone="neutral" href={ parentUrl }>
							{ parentLabel }
						</Link>
						<span
							aria-hidden="true"
							className="wc-ai-shopping-header__separator"
						>
							/
						</span>
						<span aria-current="page">{ title }</span>
					</span>
				</WooHeaderPageTitle>
				<WooHeaderItem order={ 0 }>
					<HeaderSave />
				</WooHeaderItem>
				<WooHeaderItem>
					<p className="wc-ai-shopping-header__description">
						{ description }
					</p>
				</WooHeaderItem>
			</>
		),
	} );
};
