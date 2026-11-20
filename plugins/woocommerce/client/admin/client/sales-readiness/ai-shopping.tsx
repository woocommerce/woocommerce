/**
 * External dependencies
 */
import {
	useCallback,
	useEffect,
	useMemo,
	useRef,
	useState,
} from '@wordpress/element';
import type { RefObject } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { speak } from '@wordpress/a11y';
import { __ } from '@wordpress/i18n';
import { Page } from '@wordpress/admin-ui';
import { DataForm } from '@wordpress/dataviews';
import type { Field, Form, FormField } from '@wordpress/dataviews';
/* eslint-disable @wordpress/use-recommended-components -- The design asks for these @wordpress/ui components; this chunk bundles the package. */
import { Button, Link, Notice, Stack, Text } from '@wordpress/ui';
/* eslint-enable @wordpress/use-recommended-components */

/**
 * Internal dependencies
 */
import './sales-readiness.scss';
import './ai-shopping.scss';
import type { ShoppingView } from './types';
import { asLink, ShoppingChecklist } from './components/shopping-checklist';
import { ProductVisibility } from './components/product-visibility';
import { ConnectionUrl } from './components/connection-url';

type Values = Record< string, string | boolean >;

declare global {
	interface Window {
		woocommerce_settings_params?: { i18n_nav_warning?: string };
	}
}

/**
 * The trail back to AI tools, as the admin-ui Breadcrumbs draws it. Breadcrumbs itself links
 * through the admin router, which settings pages don't have.
 */
export const SettingsBreadcrumbs = ( {
	parent,
	title,
}: {
	parent: ShoppingView[ 'parent' ];
	title: string;
} ) => (
	<nav aria-label={ __( 'Breadcrumbs', 'woocommerce' ) }>
		<Stack
			render={ <ul /> }
			direction="row"
			align="center"
			className="wc-ai-shopping-breadcrumbs"
		>
			<li>
				<Text
					variant="body-lg"
					render={ <Link tone="neutral" href={ parent.href } /> }
				>
					{ parent.text }
				</Text>
				<Text
					variant="body-lg"
					aria-hidden="true"
					className="wc-ai-shopping-breadcrumbs__separator"
				>
					/
				</Text>
			</li>
			<li>
				<Text variant="heading-lg" render={ <h1 /> }>
					{ title }
				</Text>
			</li>
		</Stack>
	</nav>
);

const plain = { type: 'regular', labelPosition: 'none' } as const;
const card = ( extra = {} ) =>
	( {
		type: 'card',
		withHeader: true,
		isCollapsible: false,
		...extra,
	} ) as const;

/**
 * A link from the Setup checklist or Sales readiness opens AI shopping on a card or a policy
 * field. The page draws after the browser looked for the anchor, and the policy fields have no
 * element with that id, so find the target here: on load, and when a link on this page changes the hash.
 */
const useHashTarget = (
	root: RefObject< HTMLDivElement >,
	view: ShoppingView
) => {
	useEffect( () => {
		const go = () => {
			let id = '';
			try {
				id = decodeURIComponent( window.location.hash.slice( 1 ) );
			} catch {
				// A malformed hash, such as #%E0%A4%A, names no field.
			}
			if ( ! id || ! root.current ) {
				return;
			}
			const field = view.policies.fields.find( ( f ) => f.id === id );
			let target: HTMLElement | null = null;
			if ( field ) {
				const label = Array.from(
					root.current.querySelectorAll( 'label' )
				).find(
					( element ) => element.textContent?.trim() === field.label
				);
				target = label?.htmlFor
					? document.getElementById( label.htmlFor )
					: null;
			} else {
				target = document.getElementById( id );
			}
			if ( target ) {
				target.scrollIntoView( { block: 'center' } );
				if ( field ) {
					target.focus( { preventScroll: true } );
				}
			}
		};
		go();
		window.addEventListener( 'hashchange', go );
		return () => window.removeEventListener( 'hashchange', go );
		// The fields don't change while the page is open.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );
};

/**
 * WooCommerce > Settings > AI tools > AI shopping. The switch saves on its own; Save posts the
 * settings form around this page, with the store policy fields as hidden inputs, so they save and
 * validate as settings fields do. Everything shown comes from the page, built in PHP.
 */
export default function AiShopping( { view }: { view: ShoppingView } ) {
	const { ucp, policies } = view;
	const [ values, setValues ] = useState< Values >( () => ( {
		enabled: ucp.enabled,
		...Object.fromEntries(
			policies.fields.map( ( field ) => [ field.id, field.value ] )
		),
	} ) );
	const [ dirty, setDirty ] = useState( false );
	const [ busy, setBusy ] = useState( false );
	const [ message, setMessage ] = useState( ucp.message );
	const dirtyRef = useRef( false );
	// Only clear the handler this page installed, so Save does not drop another unload listener.
	const unloadHandler = useRef< ( () => string ) | null >( null );
	const root = useRef< HTMLDivElement >( null );
	useHashTarget( root, view );

	useEffect( () => {
		return () => {
			if ( window.onbeforeunload === unloadHandler.current ) {
				window.onbeforeunload = null;
			}
			unloadHandler.current = null;
		};
	}, [] );

	const allowUnload = () => {
		if ( window.onbeforeunload === unloadHandler.current ) {
			window.onbeforeunload = null;
		}
		unloadHandler.current = null;
	};

	const say = ( text: string ) => {
		setMessage( text );
		if ( text ) {
			speak( text );
		}
	};

	const toggle = useCallback(
		async ( enable: boolean ) => {
			setValues( ( current ) => ( { ...current, enabled: enable } ) );
			setBusy( true );
			try {
				const response = await apiFetch< { enabled: boolean } >( {
					path: ucp.path,
					method: 'POST',
					data: { enabled: enable },
				} );
				setValues( ( current ) => ( {
					...current,
					enabled: response.enabled,
				} ) );
				// The checklist and the other cards depend on the switch, so the page reloads,
				// unless that would lose unsaved fields.
				if ( dirtyRef.current ) {
					say( ucp.unsaved );
					setBusy( false );
					return;
				}
				// Not reload(): after Save, that would post the settings form again.
				window.location.assign(
					window.location.href.split( '#' )[ 0 ]
				);
			} catch ( error ) {
				setValues( ( current ) => ( {
					...current,
					enabled: ! enable,
				} ) );
				// Only the required steps reason helps; offline and server errors get the plain line.
				const failure = error as { code?: string; message?: string };
				const fallback = enable ? ucp.error_on : ucp.error_off;
				say(
					failure?.code ===
						'woocommerce_ucp_required_checks_failing' &&
						failure.message
						? failure.message
						: fallback
				);
				setBusy( false );
			}
		},

		[ ucp ]
	);

	const onChange = ( edits: Values ) => {
		if ( typeof edits.enabled === 'boolean' ) {
			void toggle( edits.enabled );
			return;
		}
		setValues( ( current ) => ( { ...current, ...edits } ) );
		if ( ! dirtyRef.current ) {
			dirtyRef.current = true;
			setDirty( true );
			const warning =
				window.woocommerce_settings_params?.i18n_nav_warning ??
				__(
					'The changes you made will be lost if you navigate away from this page.',
					'woocommerce'
				);
			const handler = () => warning;
			unloadHandler.current = handler;
			window.onbeforeunload = handler;
		}
	};

	const fields = useMemo(
		(): Field< Values >[] => [
			{
				id: 'enabled',
				label: ucp.title,
				description: (
					<>
						{ ucp.description }{ ' ' }
						<Link href={ ucp.learn_more.href } openInNewTab>
							{ ucp.learn_more.text }
						</Link>
					</>
				),
				type: 'boolean',
				Edit: 'toggle',
				isDisabled: () => busy || ucp.blocked,
			},
			{
				id: 'ucp_message',
				label: ucp.title,
				readOnly: true,
				isVisible: () => message !== '',
				render: () => (
					<Text variant="body-sm" className="wc-ai-shopping__message">
						{ message }
					</Text>
				),
			},
			{
				id: 'coming_soon',
				label: ucp.title,
				readOnly: true,
				isVisible: () => view.coming_soon !== null,
				render: () =>
					view.coming_soon && (
						<Notice.Root intent="warning">
							<Notice.Description>
								{ view.coming_soon.text }
							</Notice.Description>
							<Notice.Actions>
								<Notice.ActionButton
									variant="outline"
									className="wc-ai-shopping__notice-action"
									{ ...asLink( view.coming_soon.link.href ) }
								>
									{ view.coming_soon.link.text }
								</Notice.ActionButton>
							</Notice.Actions>
						</Notice.Root>
					),
			},
			{
				id: 'visibility',
				label: view.visibility.title,
				readOnly: true,
				render: () => (
					<ProductVisibility visibility={ view.visibility } />
				),
			},
			{
				id: 'checklist',
				label: view.checklist.title,
				readOnly: true,
				render: () => (
					<ShoppingChecklist checklist={ view.checklist } />
				),
			},
			...policies.fields.map( ( field ): Field< Values > => ( {
				id: field.id,
				label: field.label,
				type: 'text',
				elements: field.options,
			} ) ),
			{
				id: 'connection',
				label: view.connection.title,
				readOnly: true,
				render: () => <ConnectionUrl connection={ view.connection } />,
			},
		],
		[ view, ucp, policies, busy, message ]
	);

	const form = useMemo( (): Form => {
		// Two policy fields to a row, as in the design.
		const rows: FormField[] = [];
		for ( let i = 0; i < policies.fields.length; i += 2 ) {
			rows.push( {
				id: 'policies_row_' + i,
				layout: { type: 'row', alignment: 'start' },
				children: policies.fields
					.slice( i, i + 2 )
					.map( ( field ) => field.id ),
			} );
		}
		return {
			layout: { type: 'card' },
			fields: [
				{
					id: 'switch_card',
					label: ucp.title,
					layout: { type: 'card', withHeader: false },
					children: [
						'enabled',
						{ id: 'ucp_message', layout: plain },
						{ id: 'coming_soon', layout: plain },
					],
				},
				{
					id: 'visibility_card',
					label: view.visibility.title,
					description: view.visibility.description,
					layout: card(),
					children: [ { id: 'visibility', layout: plain } ],
				},
				{
					id: 'checklist_card',
					label: view.checklist.title,
					description: view.checklist.summary,
					layout: card(),
					children: [ { id: 'checklist', layout: plain } ],
				},
				{
					id: 'policies_card',
					label: policies.title,
					description: policies.description,
					layout: card(),
					children: rows,
				},
				{
					id: 'connection_card',
					label: view.connection.title,
					description: view.connection.description,
					layout: card(),
					children: [ { id: 'connection', layout: plain } ],
				},
			],
		};
	}, [ view, ucp, policies ] );

	return (
		<div ref={ root } className="wc-sales-readiness wc-ai-shopping">
			<Page
				breadcrumbs={
					<SettingsBreadcrumbs
						parent={ view.parent }
						title={ view.title }
					/>
				}
				subTitle={ view.description }
				showSidebarToggle={ false }
				actions={
					<Button
						variant="solid"
						type="submit"
						form="mainform"
						name="save"
						value="Save changes"
						disabled={ ! dirty }
						onClick={ allowUnload }
					>
						{ __( 'Save', 'woocommerce' ) }
					</Button>
				}
			>
				<div className="wc-sales-readiness__content wc-ai-shopping__content">
					<DataForm< Values >
						data={ values }
						fields={ fields }
						form={ form }
						onChange={ onChange }
					/>
					{ policies.fields.map( ( field ) => (
						<input
							key={ field.id }
							type="hidden"
							name={ field.name }
							value={ String( values[ field.id ] ?? '' ) }
						/>
					) ) }
				</div>
			</Page>
		</div>
	);
}
