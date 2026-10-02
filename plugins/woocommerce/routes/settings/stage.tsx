/**
 * External dependencies
 */
import { Breadcrumbs, Page } from '@wordpress/admin-ui';
import { Button, Notice, Spinner } from '@wordpress/components';
import { DataForm, useFormValidity } from '@wordpress/dataviews';
import type { Field } from '@wordpress/dataviews';
import { __ } from '@wordpress/i18n';
import { useNavigate, useParams } from '@wordpress/route';
import { useEffect, useMemo, useRef } from 'react';
import type { MouseEvent } from 'react';

/**
 * Internal dependencies
 */
import { resolveLocation, subPageFieldId } from './location';
import { useSettingsEntity } from './save';
import { getScreen, useScreenDefinition } from './screen';
import type {
	PaymentSettingsScreen,
	ScreenDefinition,
	SettingsRecord,
} from './screen';
import './style.scss';

/**
 * Router path to a screen, or to a sub-page within it.
 */
const screenPath = ( id: string, ...segments: string[] ) =>
	[ '/settings', id, ...segments ].join( '/' );

/**
 * Get the path of a link to one of this screen's own pages, such as a breadcrumb or an extension's link to a sub-page.
 */
function getScreenLinkPath( event: MouseEvent, screenId: string ) {
	const anchor = ( event.target as Element ).closest( 'a[href]' );
	if (
		! anchor ||
		event.button !== 0 ||
		event.metaKey ||
		event.ctrlKey ||
		event.shiftKey ||
		event.altKey
	) {
		return undefined;
	}
	const url = new URL( ( anchor as HTMLAnchorElement ).href );
	const current = new URL( window.location.href );
	const [ path = '' ] = ( url.searchParams.get( 'p' ) ?? '' ).split( '?' );
	const isScreenPath =
		path === screenPath( screenId ) ||
		path.startsWith( `${ screenPath( screenId ) }/` );
	return url.origin === current.origin &&
		url.pathname === current.pathname &&
		url.searchParams.get( 'page' ) === current.searchParams.get( 'page' ) &&
		isScreenPath
		? path
		: undefined;
}

function SettingsForm( {
	screen,
	definition,
	path,
}: {
	screen: PaymentSettingsScreen;
	definition: ScreenDefinition;
	path?: string | undefined;
} ) {
	const location = useMemo(
		() => resolveLocation( definition.form, path ),
		[ definition.form, path ]
	);
	const {
		record,
		data,
		isDirty,
		isSaving,
		loadError,
		notice,
		clearNotice,
		edit,
		save,
		discard,
	} = useSettingsEntity( screen, location?.fieldIds ?? [] );
	const navigate = useNavigate();
	// Start each page at the top, rather than where the last one was scrolled to.
	useEffect( () => {
		window.scrollTo( 0, 0 );
	}, [ path ] );
	const trail = location?.subPages ?? [];
	const currentPath = screenPath(
		screen.id,
		...trail.map( ( group ) => group.id )
	);

	// Each page is its own form, so leaving one with unsaved edits discards them once confirmed.
	const goTo = ( to: string ) => {
		if (
			isDirty &&
			// eslint-disable-next-line no-alert -- A blocking prompt keeps the edits until the merchant decides.
			! window.confirm(
				__(
					'You have unsaved changes on this page. If you leave, they will be lost.',
					'woocommerce'
				)
			)
		) {
			return;
		}
		discard();
		void navigate( { to } );
	};
	// The link fields are memoized, so they call the latest `goTo` to see the current unsaved edits.
	const goToRef = useRef( goTo );
	goToRef.current = goTo;

	// Each sub-page is reached from a button where its group sits, unless the extension links to it itself.
	const fields = useMemo( () => {
		const links = ( location?.subPageLinks ?? [] ).map(
			( link ): Field< SettingsRecord > => ( {
				id: subPageFieldId( link.id ),
				label: link.label,
				Edit: () => (
					<div>
						<Button
							variant="secondary"
							size="compact"
							onClick={ () => {
								goToRef.current(
									`${ currentPath }/${ link.id }`
								);
							} }
						>
							{ link.label }
						</Button>
					</div>
				),
			} )
		);
		return [ ...definition.fields, ...links ];
	}, [ definition.fields, location, currentPath ] );
	const { validity, isValid } = useFormValidity(
		data ?? {},
		fields,
		location?.form ?? definition.form
	);

	if ( loadError ) {
		return (
			<Notice status="error" isDismissible={ false }>
				{ loadError.message ||
					__( 'Unable to load settings.', 'woocommerce' ) }
			</Notice>
		);
	}
	if ( ! record || ! data ) {
		return <Spinner />;
	}

	if ( ! location ) {
		return (
			<Notice status="error" isDismissible={ false }>
				{ __( 'This settings page doesn’t exist.', 'woocommerce' ) }
			</Notice>
		);
	}

	const payments = {
		label: __( 'Payments', 'woocommerce' ),
		to: screen.paymentsUrl,
	};
	// Each sub-page crumb links to its own path, so a nested sub-page can go back one level.
	const subPageCrumbs = trail.map( ( group, index ) => {
		const label = group.label ?? group.id;
		if ( index === trail.length - 1 ) {
			return { label };
		}
		const ids = trail.slice( 0, index + 1 ).map( ( item ) => item.id );
		return { label, to: screenPath( screen.id, ...ids ) };
	} );

	return (
		// Links to the screen's own pages, such as breadcrumbs or an extension's link to a sub-page, stay in the page.

		<div
			onClickCapture={ ( event ) => {
				const linkPath = getScreenLinkPath( event, screen.id );
				if ( linkPath ) {
					event.preventDefault();
					event.stopPropagation();
					goTo( linkPath );
				}
			} }
		>
			<Page
				breadcrumbs={
					<Breadcrumbs
						items={
							trail.length
								? [
										payments,
										{
											label: screen.title,
											to: screenPath( screen.id ),
										},
										...subPageCrumbs,
								  ]
								: [ payments, { label: screen.title } ]
						}
					/>
				}
				showSidebarToggle={ false }
				hasPadding
				actions={
					<>
						{ screen.classicUrl && (
							<Button
								variant="tertiary"
								size="compact"
								href={ screen.classicUrl }
							>
								{ __( 'Use classic settings', 'woocommerce' ) }
							</Button>
						) }
						<Button
							variant="secondary"
							size="compact"
							disabled={ isSaving || ! isDirty }
							onClick={ discard }
						>
							{ __( 'Discard changes', 'woocommerce' ) }
						</Button>
						<Button
							variant="primary"
							size="compact"
							isBusy={ isSaving }
							disabled={ isSaving || ! isDirty || ! isValid }
							onClick={ () => void save() }
						>
							{ __( 'Save changes', 'woocommerce' ) }
						</Button>
					</>
				}
			>
				<div className="wc-payment-settings__content">
					{ definition.errors.map( ( error ) => (
						<Notice
							key={ error.message }
							status="warning"
							isDismissible={ false }
						>
							{ error.message }
						</Notice>
					) ) }
					{ notice && (
						<Notice
							status={ notice.status }
							onRemove={ clearNotice }
						>
							{ notice.message }
						</Notice>
					) }
					<DataForm
						data={ data }
						fields={ fields }
						form={ location.form }
						validity={ validity }
						onChange={ edit }
					/>
				</div>
			</Page>
		</div>
	);
}

function ScreenContent( {
	screen,
	path,
}: {
	screen: PaymentSettingsScreen;
	path?: string | undefined;
} ) {
	const { definition, error } = useScreenDefinition( screen );

	if ( error ) {
		return (
			<Notice status="error" isDismissible={ false }>
				{ error.message ||
					__( 'Unable to load settings.', 'woocommerce' ) }
			</Notice>
		);
	}
	if ( ! definition ) {
		return <Spinner />;
	}

	return (
		// Each page is its own form, so it starts with its own record of what it edited.
		<SettingsForm
			key={ path ?? '' }
			screen={ screen }
			definition={ definition }
			path={ path }
		/>
	);
}

// useParams() is untyped without a registered router, so narrow it here.
function getStringParam( params: unknown, key: string ): string | undefined {
	if ( typeof params !== 'object' || params === null ) {
		return undefined;
	}
	const value: unknown = ( params as Record< string, unknown > )[ key ];
	return typeof value === 'string' ? value : undefined;
}

function SettingsStage() {
	const params: unknown = useParams( { strict: false } );
	const screen = getScreen( getStringParam( params, 'page' ) );
	const path = getStringParam( params, '_splat' );

	return screen ? <ScreenContent screen={ screen } path={ path } /> : null;
}

export const stage = SettingsStage;
