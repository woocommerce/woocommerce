/**
 * External dependencies
 */
import { Page } from '@wordpress/admin-ui';
import { Button, Modal, Notice } from '@wordpress/components';
import {
	Component,
	createElement,
	RawHTML,
	useCallback,
	useEffect,
	useMemo,
	useRef,
	useState,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Card } from '@wordpress/ui';
import type { ErrorInfo, ReactNode } from 'react';

/**
 * Internal dependencies
 */
import { HiddenInputs } from './hidden-inputs';
import { error, warn } from './diagnostics';
import { sanitizeSettingsHtml } from './html';
import { NativeSettingsField } from './native-fields';
import {
	resolveFieldComponent,
	resolveFieldVisibilityPredicate,
	resolveGroupVisibilityPredicate,
	resolveRegionComponent,
	resolveSaveHandler,
} from './registry';
import type {
	SettingsUIField,
	SettingsUIGroup,
	SettingsUISaveStrategy,
	SettingsUISchema,
	SettingsFieldContext,
	SettingsValue,
	SettingsValues,
} from './types';

type SaveNotice = {
	status: 'success' | 'error';
	message: string;
};

type PendingNavigation = {
	href: string;
};

const FORM_POST_REDIRECT_INPUT_NAME = 'wc_settings_ui_redirect_to';

const normalizeSection = ( section?: string ) =>
	section === 'default' ? '' : section;

const getInitialValues = ( schema: SettingsUISchema ): SettingsValues => {
	const values: SettingsValues = {};

	Object.values( schema.groups ).forEach( ( group ) => {
		group.fields.forEach( ( field ) => {
			values[ field.id ] =
				typeof field.value === 'undefined' ? '' : field.value;
		} );
	} );

	return values;
};

const areValuesEqual = ( a: SettingsValue, b: SettingsValue ) => {
	if ( Array.isArray( a ) || Array.isArray( b ) ) {
		return (
			Array.isArray( a ) &&
			Array.isArray( b ) &&
			a.length === b.length &&
			a.every( ( value, index ) => value === b[ index ] )
		);
	}

	return a === b;
};

const getChangedValues = (
	values: SettingsValues,
	initialValues: SettingsValues
) => {
	const changedValues: Partial< SettingsValues > = {};

	Object.keys( values ).forEach( ( key ) => {
		if ( ! areValuesEqual( values[ key ], initialValues[ key ] ) ) {
			changedValues[ key ] = values[ key ];
		}
	} );

	return changedValues;
};

const getFieldTypeClassName = ( type: string ) =>
	`wc-settings-ui__field--${ type.replace( /[^a-z0-9_-]/gi, '-' ) }`;

const getActionVariant = ( variant?: string ) =>
	( [ 'primary', 'secondary', 'tertiary', 'link' ].includes( variant || '' )
		? variant
		: 'secondary' ) as 'primary' | 'secondary' | 'tertiary' | 'link';

// TS unions erase at runtime, so guard the className interpolation against unexpected
// strings from PHP-supplied schemas.
const getBadgeIntent = ( intent?: string ) =>
	[ 'default', 'info', 'success', 'warning', 'error' ].includes(
		intent || ''
	)
		? intent
		: 'default';

const getSaveStrategy = ( schema: SettingsUISchema ): SettingsUISaveStrategy =>
	schema.save || { adapter: 'form_post' };

const clearLegacyFormPrompt = () => {
	window.onbeforeunload = null;
};

const setFormPostRedirectInput = ( form: HTMLFormElement, href: string ) => {
	let redirectInput = form.querySelector< HTMLInputElement >(
		`input[name="${ FORM_POST_REDIRECT_INPUT_NAME }"]`
	);

	if ( ! redirectInput ) {
		redirectInput = document.createElement( 'input' );
		redirectInput.type = 'hidden';
		redirectInput.name = FORM_POST_REDIRECT_INPUT_NAME;
		form.appendChild( redirectInput );
	}

	redirectInput.value = href;
};

const shouldPromptForNavigation = ( event: MouseEvent ) => {
	if (
		event.defaultPrevented ||
		event.button !== 0 ||
		event.metaKey ||
		event.ctrlKey ||
		event.shiftKey ||
		event.altKey
	) {
		return false;
	}

	const target = event.target;

	if ( ! ( target instanceof Element ) ) {
		return false;
	}

	const link = target.closest( 'a[href]' );

	if ( ! ( link instanceof HTMLAnchorElement ) ) {
		return false;
	}

	if ( link.target && link.target !== '_self' ) {
		return false;
	}

	return Boolean( link.href ) && link.href !== window.location.href;
};

const getNavigationHref = ( event: MouseEvent ) => {
	const target = event.target;

	if ( ! ( target instanceof Element ) ) {
		return undefined;
	}

	const link = target.closest( 'a[href]' );

	return link instanceof HTMLAnchorElement ? link.href : undefined;
};

const UnsavedChangesModal = ( {
	isSaving,
	onClose,
	onDiscard,
	onSave,
}: {
	isSaving: boolean;
	onClose: () => void;
	onDiscard: () => void;
	onSave: () => void;
} ) => {
	return (
		<Modal
			className="wc-settings-ui__unsaved-changes-modal"
			title={ __( 'You have unsaved changes', 'woocommerce' ) }
			onRequestClose={ onClose }
		>
			<p>
				{ __(
					"If you leave now, your changes won't be saved.",
					'woocommerce'
				) }
			</p>
			<div className="wc-settings-ui__unsaved-changes-actions">
				<Button variant="tertiary" onClick={ onDiscard }>
					{ __( 'Discard', 'woocommerce' ) }
				</Button>
				<Button
					variant="primary"
					type="button"
					name="save"
					value={ __( 'Save', 'woocommerce' ) }
					isBusy={ isSaving }
					disabled={ isSaving }
					onClick={ onSave }
				>
					{ __( 'Save', 'woocommerce' ) }
				</Button>
			</div>
		</Modal>
	);
};

const GroupHeader = ( { group }: { group: SettingsUIGroup } ) => {
	const hasHeaderContent =
		group.title || group.description || ( group.actions || [] ).length > 0;

	if ( ! hasHeaderContent ) {
		return null;
	}

	return (
		<Card.Header
			className="wc-settings-ui__section-header"
			render={ <header /> }
		>
			<div className="wc-settings-ui__section-heading">
				{ group.title ? (
					<Card.Title
						// eslint-disable-next-line jsx-a11y/heading-has-content -- Card.Title injects its children into the render element.
						render={ <h2 /> }
					>
						{ group.title }
					</Card.Title>
				) : null }
				{ group.description ? (
					<div className="wc-settings-ui__section-description">
						<RawHTML>
							{ sanitizeSettingsHtml( group.description ) }
						</RawHTML>
					</div>
				) : null }
			</div>
			{ group.actions && group.actions.length > 0 ? (
				<div className="wc-settings-ui__section-actions">
					{ group.actions.map( ( action ) => (
						<Button
							key={ action.id }
							variant={ getActionVariant( action.variant ) }
							href={ action.href }
							target={ action.target }
							rel={ action.rel }
						>
							{ action.label }
						</Button>
					) ) }
				</div>
			) : null }
		</Card.Header>
	);
};

const valueMatchesVisibilityRule = (
	value: SettingsValue,
	expected: SettingsValue | SettingsValue[] | undefined
) => {
	const expectedValues = Array.isArray( expected )
		? expected
		: [ expected ?? true ];

	return expectedValues.some( ( expectedValue ) =>
		areValuesEqual( value, expectedValue )
	);
};

const getVisible = ( {
	id,
	kind,
	field,
	values,
	initialValues,
	context,
	schema,
}: {
	id: string;
	kind: 'field' | 'group';
	field?: SettingsUIField;
	values: SettingsValues;
	initialValues: SettingsValues;
	context: SettingsFieldContext;
	schema: SettingsUISchema;
} ) => {
	const predicate =
		kind === 'field'
			? resolveFieldVisibilityPredicate( id, context )
			: resolveGroupVisibilityPredicate( id, context );

	if ( predicate ) {
		try {
			return predicate( { values, initialValues, context, schema } );
		} catch ( predicateError ) {
			warn(
				`Visibility predicate for ${ kind } "${ id }" failed. Rendering it visible.`,
				{ error: predicateError, context }
			);
			return true;
		}
	}

	if ( field?.visibility ) {
		return valueMatchesVisibilityRule(
			values[ field.visibility.controller ],
			field.visibility.value
		);
	}

	return true;
};

const getAllFields = ( schema: SettingsUISchema ): SettingsUIField[] =>
	Object.values( schema.groups ).flatMap( ( group ) => group.fields );

type ErrorBoundaryProps = {
	children: ReactNode;
};

type ErrorBoundaryState = {
	hasError: boolean;
};

export class SettingsUIErrorBoundary extends Component<
	ErrorBoundaryProps,
	ErrorBoundaryState
> {
	state: ErrorBoundaryState = { hasError: false };

	static getDerivedStateFromError(): ErrorBoundaryState {
		return { hasError: true };
	}

	componentDidCatch( caughtError: Error, errorInfo: ErrorInfo ) {
		error( 'Settings UI render failed.', {
			error: caughtError,
			errorInfo,
		} );
	}

	render() {
		if ( this.state.hasError ) {
			return (
				<Notice status="error" isDismissible={ false }>
					{ __(
						'Something went wrong while rendering this settings page. Reload the page with the settings UI feature disabled to use the classic settings screen.',
						'woocommerce'
					) }
				</Notice>
			);
		}

		return this.props.children;
	}
}

const ShellHeader = ( {
	schema,
	context,
	values,
	initialValues,
	isDirty,
	isSaving,
	saveStrategy,
	onSave,
	children,
}: {
	schema: SettingsUISchema;
	context: SettingsFieldContext;
	values: SettingsValues;
	initialValues: SettingsValues;
	isDirty: boolean;
	isSaving: boolean;
	saveStrategy: SettingsUISaveStrategy;
	onSave: () => void | Promise< boolean >;
	children: ReactNode;
} ) => {
	const shell = schema.shell || {};
	const title = shell.title || schema.title;
	const NavigationComponent = shell.navigationComponent
		? resolveRegionComponent( shell.navigationComponent, context )
		: undefined;
	const hasNavigation = Boolean(
		( shell.navigation && shell.navigation.length > 0 ) ||
			( shell.sectionNavigation && shell.sectionNavigation.length > 0 ) ||
			NavigationComponent
	);
	const showSaveButton = saveStrategy.adapter !== 'none';
	const saveButtonType =
		saveStrategy.adapter === 'form_post' ? 'submit' : 'button';

	const breadcrumbs =
		shell.breadcrumbs && shell.breadcrumbs.length > 0 ? (
			<nav
				className="wc-settings-ui-shell__breadcrumbs"
				aria-label={ __( 'Breadcrumbs', 'woocommerce' ) }
			>
				{ shell.breadcrumbs.map( ( breadcrumb, index ) => (
					<span
						className="wc-settings-ui-shell__breadcrumb"
						key={ `${ breadcrumb.label }-${ index }` }
					>
						{ breadcrumb.href ? (
							<a href={ breadcrumb.href }>{ breadcrumb.label }</a>
						) : (
							<span>{ breadcrumb.label }</span>
						) }
					</span>
				) ) }
			</nav>
		) : undefined;

	const badges = shell.badges?.length
		? shell.badges.map( ( badge, index ) => (
				<span
					className={ `wc-settings-ui-shell__badge wc-settings-ui-shell__badge--${ getBadgeIntent(
						badge.intent
					) }` }
					key={ `${ badge.label }-${ index }` }
				>
					{ badge.label }
				</span>
		  ) )
		: undefined;

	const saveButtonLabel = __( 'Save', 'woocommerce' );

	const actions = showSaveButton ? (
		<Button
			className="woocommerce-save-button"
			variant="primary"
			type={ saveButtonType }
			name="save"
			value={ saveButtonLabel }
			disabled={ ! isDirty || isSaving }
			isBusy={ isSaving }
			onClick={ onSave }
		>
			{ saveButtonLabel }
		</Button>
	) : undefined;

	return (
		<Page
			className="wc-settings-ui-shell"
			title={ title }
			subTitle={ shell.subtitle }
			breadcrumbs={ breadcrumbs }
			badges={ badges }
			actions={ actions }
		>
			{ hasNavigation ? (
				<div className="wc-settings-ui-shell__navigation">
					{ shell.navigation && shell.navigation.length > 0 ? (
						<nav
							className="wc-settings-ui-shell__tabs wc-settings-ui-shell__tabs--primary"
							aria-label={ __( 'Settings pages', 'woocommerce' ) }
						>
							{ shell.navigation.map( ( item ) => (
								<a
									className={
										item.active
											? 'wc-settings-ui-shell__tab is-active'
											: 'wc-settings-ui-shell__tab'
									}
									href={ item.href }
									key={ item.id }
								>
									{ item.label }
								</a>
							) ) }
						</nav>
					) : null }
					{ shell.sectionNavigation &&
					shell.sectionNavigation.length > 0 ? (
						<nav
							className="wc-settings-ui-shell__tabs wc-settings-ui-shell__tabs--secondary"
							aria-label={ __(
								'Settings sections',
								'woocommerce'
							) }
						>
							{ shell.sectionNavigation.map( ( item ) => (
								<a
									className={
										item.active
											? 'wc-settings-ui-shell__tab is-active'
											: 'wc-settings-ui-shell__tab'
									}
									href={ item.href }
									key={ item.id }
								>
									{ item.label }
								</a>
							) ) }
						</nav>
					) : null }
					{ NavigationComponent ? (
						<NavigationComponent
							values={ values }
							initialValues={ initialValues }
							context={ context }
							schema={ schema }
						/>
					) : null }
				</div>
			) : null }
			{ children }
		</Page>
	);
};

export const SettingsUIPage = ( {
	schema,
	page,
	section,
}: {
	schema: SettingsUISchema;
	page?: string;
	section?: string;
} ) => {
	const [ initialValues, setInitialValues ] = useState< SettingsValues >(
		() => getInitialValues( schema )
	);
	const [ values, setValuesState ] = useState< SettingsValues >( () =>
		getInitialValues( schema )
	);
	const [ isSaving, setIsSaving ] = useState( false );
	const [ saveNotice, setSaveNotice ] = useState< SaveNotice | null >( null );
	const [ pendingNavigation, setPendingNavigation ] =
		useState< PendingNavigation | null >( null );
	const allowNavigationRef = useRef( false );
	const context: SettingsFieldContext = useMemo(
		() => ( {
			page: page || schema.id,
			section: normalizeSection(
				typeof section === 'undefined' ? schema.section : section
			),
		} ),
		[ page, schema.id, schema.section, section ]
	);
	const saveStrategy = getSaveStrategy( schema );
	const changedValues = useMemo(
		() => getChangedValues( values, initialValues ),
		[ initialValues, values ]
	);
	const dirtyFields = useMemo(
		() => Object.keys( changedValues ),
		[ changedValues ]
	);
	const isDirty = dirtyFields.length > 0;

	useEffect( () => {
		const nextValues = getInitialValues( schema );
		setInitialValues( nextValues );
		setValuesState( nextValues );
		setSaveNotice( null );
		setPendingNavigation( null );
	}, [ schema ] );

	const setValue = useCallback(
		( fieldId: string, nextValue: SettingsValue ) => {
			setValuesState( ( currentValues ) => ( {
				...currentValues,
				[ fieldId ]: nextValue,
			} ) );
		},
		[]
	);

	const allowNavigation = useCallback( () => {
		allowNavigationRef.current = true;
		clearLegacyFormPrompt();
	}, [] );

	const submitSettingsForm = useCallback(
		( redirectTo?: string ) => {
			const form = document.getElementById( 'mainform' );

			if ( ! ( form instanceof HTMLFormElement ) ) {
				return;
			}

			if ( typeof redirectTo === 'string' && redirectTo ) {
				setFormPostRedirectInput( form, redirectTo );
			}

			allowNavigation();

			const saveButton = form.querySelector( '.woocommerce-save-button' );

			if ( saveButton instanceof HTMLButtonElement ) {
				form.requestSubmit( saveButton );
				return;
			}

			form.requestSubmit();
		},
		[ allowNavigation ]
	);

	const setValues = useCallback(
		( nextValues: Partial< SettingsValues > ) => {
			setValuesState( ( currentValues ) => {
				const mergedValues: SettingsValues = { ...currentValues };

				Object.entries( nextValues ).forEach(
					( [ fieldId, value ] ) => {
						if ( typeof value !== 'undefined' ) {
							mergedValues[ fieldId ] = value;
						}
					}
				);

				return mergedValues;
			} );
		},
		[]
	);

	const handleCustomSave = useCallback( async () => {
		if ( saveStrategy.adapter !== 'custom' ) {
			return false;
		}

		const handlerName =
			'handler' in saveStrategy ? saveStrategy.handler : undefined;
		const handler = handlerName
			? resolveSaveHandler( handlerName, context )
			: undefined;
		if ( ! handler ) {
			setSaveNotice( {
				status: 'error',
				message: __( 'Unable to save settings.', 'woocommerce' ),
			} );
			return false;
		}

		setIsSaving( true );
		setSaveNotice( null );

		try {
			const result = await handler( {
				values,
				initialValues,
				changedValues,
				dirtyFields,
				context,
				schema,
			} );
			const savedValues = result?.values || values;
			setValuesState( savedValues );
			setInitialValues( savedValues );
			setSaveNotice( {
				status: 'success',
				message:
					result?.notice ||
					__( 'Settings saved successfully.', 'woocommerce' ),
			} );
			return true;
		} catch ( saveError ) {
			const message =
				saveError instanceof Error && saveError.message
					? saveError.message
					: __( 'Unable to save settings.', 'woocommerce' );
			setSaveNotice( { status: 'error', message } );
			return false;
		} finally {
			setIsSaving( false );
		}
	}, [
		changedValues,
		context,
		dirtyFields,
		initialValues,
		saveStrategy,
		schema,
		values,
	] );

	useEffect( () => {
		if ( ! isDirty ) {
			return;
		}

		const handleBeforeUnload = ( event: BeforeUnloadEvent ) => {
			if ( allowNavigationRef.current ) {
				return;
			}

			event.preventDefault();
			event.returnValue = '';
		};

		window.addEventListener( 'beforeunload', handleBeforeUnload );

		return () => {
			window.removeEventListener( 'beforeunload', handleBeforeUnload );
		};
	}, [ isDirty ] );

	useEffect( () => {
		if ( ! isDirty ) {
			return;
		}

		const handleNavigationClick = ( event: MouseEvent ) => {
			const target = event.target;

			if (
				! ( target instanceof Element ) ||
				! target.closest( '.wc-settings-ui-shell' ) ||
				! shouldPromptForNavigation( event )
			) {
				return;
			}

			const href = getNavigationHref( event );

			if ( ! href ) {
				return;
			}

			event.preventDefault();
			setPendingNavigation( { href } );
		};

		document.addEventListener( 'click', handleNavigationClick, true );

		return () => {
			document.removeEventListener(
				'click',
				handleNavigationClick,
				true
			);
		};
	}, [ isDirty ] );

	const handleDiscardNavigation = useCallback( () => {
		if ( ! pendingNavigation ) {
			return;
		}

		allowNavigation();
		window.location.assign( pendingNavigation.href );
	}, [ allowNavigation, pendingNavigation ] );

	const handleSavePendingNavigation = useCallback( async () => {
		if ( ! pendingNavigation ) {
			return;
		}

		if ( saveStrategy.adapter === 'form_post' ) {
			submitSettingsForm( pendingNavigation.href );
			return;
		}

		if ( saveStrategy.adapter === 'custom' ) {
			const saved = await handleCustomSave();

			if ( saved ) {
				allowNavigation();
				window.location.assign( pendingNavigation.href );
			}
		}
	}, [
		allowNavigation,
		handleCustomSave,
		pendingNavigation,
		saveStrategy.adapter,
		submitSettingsForm,
	] );

	const visibleGroups = useMemo(
		() =>
			Object.values( schema.groups )
				.filter( ( group ) =>
					getVisible( {
						id: group.id,
						kind: 'group',
						values,
						initialValues,
						context,
						schema,
					} )
				)
				.map( ( group ) => ( {
					...group,
					fields: group.fields.filter( ( field ) =>
						getVisible( {
							id: field.id,
							kind: 'field',
							field,
							values,
							initialValues,
							context,
							schema,
						} )
					),
				} ) )
				.filter( ( group ) => group.fields.length > 0 ),
		[ context, initialValues, schema, values ]
	);

	const formPostFields =
		saveStrategy.adapter === 'form_post' ? getAllFields( schema ) : [];

	return (
		<ShellHeader
			schema={ schema }
			context={ context }
			values={ values }
			initialValues={ initialValues }
			isDirty={ isDirty }
			isSaving={ isSaving }
			saveStrategy={ saveStrategy }
			onSave={
				saveStrategy.adapter === 'form_post'
					? submitSettingsForm
					: handleCustomSave
			}
		>
			{ pendingNavigation ? (
				<UnsavedChangesModal
					isSaving={ isSaving }
					onClose={ () => setPendingNavigation( null ) }
					onDiscard={ handleDiscardNavigation }
					onSave={ handleSavePendingNavigation }
				/>
			) : null }
			{ saveNotice ? (
				<Notice
					className="wc-settings-ui-shell__notice"
					status={ saveNotice.status }
					isDismissible
					onRemove={ () => setSaveNotice( null ) }
				>
					{ saveNotice.message }
				</Notice>
			) : null }
			<div className="wc-settings-ui">
				{ visibleGroups.map( ( group ) => (
					<section
						className="wc-settings-ui__section"
						key={ group.id }
					>
						<Card.Root className="wc-settings-ui__section-card">
							<GroupHeader group={ group } />
							<Card.Content className="wc-settings-ui__section-fields">
								{ group.fields.map( ( field ) => {
									const FieldComponent =
										resolveFieldComponent(
											field,
											context
										) || NativeSettingsField;
									const value = values[ field.id ];

									return (
										<div
											className={ [
												'wc-settings-ui__field',
												getFieldTypeClassName(
													field.type
												),
											].join( ' ' ) }
											key={ field.id }
										>
											<FieldComponent
												field={ field }
												value={ value }
												context={ context }
												values={ values }
												initialValues={ initialValues }
												setValue={ setValue }
												setValues={ setValues }
												onChange={ ( nextValue ) =>
													setValue(
														field.id,
														nextValue
													)
												}
											/>
										</div>
									);
								} ) }
							</Card.Content>
						</Card.Root>
					</section>
				) ) }
			</div>
			{ formPostFields.length > 0 ? (
				<div className="wc-settings-ui__hidden-inputs">
					{ formPostFields.map( ( field ) => (
						<HiddenInputs
							field={ field }
							value={ values[ field.id ] }
							key={ field.id }
						/>
					) ) }
				</div>
			) : null }
		</ShellHeader>
	);
};
