/* global BeforeUnloadEvent, Element, HTMLAnchorElement, HTMLButtonElement, HTMLFormElement, HTMLInputElement, MouseEvent */

/**
 * External dependencies
 */
import { NavigableRegion } from '@wordpress/admin-ui';
import { Button } from '@wordpress/components';
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
import { __, sprintf } from '@wordpress/i18n';
import { DataForm, useFormValidity } from '@wordpress/dataviews';
import type {
	FieldValidity,
	Form,
	FormField,
	FormValidity,
} from '@wordpress/dataviews';
import {
	Badge,
	Button as UIButton,
	Card,
	Dialog,
	Notice,
	Stack,
	Text,
} from '@wordpress/ui';
import type { ComponentProps, ErrorInfo, ReactNode } from 'react';

/**
 * Internal dependencies
 */
import { createDataFormAdapter } from './dataform-adapter';
import { HiddenInputs } from './hidden-inputs';
import { error } from './diagnostics';
import { sanitizeSettingsHtml, toSanitizedHtmlNode } from './html';
import { resolveFieldValidator, resolveSaveHandler } from './registry';
import { areValuesEqual } from './values';
import { SettingsUIPageContext } from './settings-ui-context';
import type {
	SettingsUIField,
	SettingsUIGroup,
	SettingsUISaveStrategy,
	SettingsUISchema,
	SettingsUIShellBadgeIntent,
	SettingsFieldContext,
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

const getActionVariant = ( variant?: string ) =>
	( [ 'primary', 'secondary', 'tertiary', 'link' ].includes( variant || '' )
		? variant
		: 'secondary' ) as 'primary' | 'secondary' | 'tertiary' | 'link';

const BADGE_INTENTS: Record<
	SettingsUIShellBadgeIntent,
	ComponentProps< typeof Badge >[ 'intent' ]
> = {
	default: 'draft',
	info: 'informational',
	success: 'stable',
	warning: 'medium',
	error: 'high',
};

// TS unions erase at runtime, so guard against unexpected strings from
// PHP-supplied schemas. Own-property check: `in` would accept
// Object.prototype keys such as "constructor".
const getBadgeIntent = (
	intent?: string
): ComponentProps< typeof Badge >[ 'intent' ] =>
	intent && Object.prototype.hasOwnProperty.call( BADGE_INTENTS, intent )
		? BADGE_INTENTS[ intent as SettingsUIShellBadgeIntent ]
		: BADGE_INTENTS.default;

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

const getNavigationHref = ( event: MouseEvent ) => {
	if (
		event.defaultPrevented ||
		event.button !== 0 ||
		event.metaKey ||
		event.ctrlKey ||
		event.shiftKey ||
		event.altKey
	) {
		return undefined;
	}

	const target = event.target;
	if (
		! ( target instanceof Element ) ||
		! target.closest( '.wc-settings-ui-shell, #mainform .subsubsub' )
	) {
		return undefined;
	}

	const link = target.closest( 'a[href]' );
	if (
		! ( link instanceof HTMLAnchorElement ) ||
		( link.target && link.target !== '_self' ) ||
		! link.href ||
		link.href === window.location.href
	) {
		return undefined;
	}

	return link.href;
};

const UnsavedChangesModal = ( {
	isSaving,
	isValid,
	onClose,
	onDiscard,
	onSave,
}: {
	isSaving: boolean;
	isValid: boolean;
	onClose: () => void;
	onDiscard: () => void;
	onSave: () => void;
} ) => {
	return (
		<Dialog.Root
			open
			onOpenChange={ ( open ) => {
				if ( ! open ) {
					onClose();
				}
			} }
		>
			<Dialog.Popup
				className="wc-settings-ui__unsaved-changes-modal"
				portal={
					<Dialog.Portal className="wc-settings-ui__unsaved-changes-portal" />
				}
				size="small"
			>
				<Dialog.Header>
					<Dialog.Title>
						{ __( 'You have unsaved changes', 'woocommerce' ) }
					</Dialog.Title>
					<Dialog.CloseIcon />
				</Dialog.Header>
				<Dialog.Content>
					<Dialog.Description>
						{ __(
							"If you leave now, your changes won't be saved.",
							'woocommerce'
						) }
					</Dialog.Description>
				</Dialog.Content>
				<Dialog.Footer className="wc-settings-ui__unsaved-changes-actions">
					<UIButton variant="minimal" onClick={ onDiscard }>
						{ __( 'Discard', 'woocommerce' ) }
					</UIButton>
					<UIButton
						loading={ isSaving }
						disabled={ isSaving || ! isValid }
						onClick={ onSave }
					>
						{ __( 'Save', 'woocommerce' ) }
					</UIButton>
				</Dialog.Footer>
			</Dialog.Popup>
		</Dialog.Root>
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
			render={
				<Stack
					render={ <header /> }
					direction="row"
					gap="xl"
					align="flex-start"
					justify="space-between"
				/>
			}
		>
			<div className="wc-settings-ui__section-heading">
				{ group.title ? (
					<Card.Title render={ <h2 /> }>{ group.title }</Card.Title>
				) : null }
				{ group.description ? (
					<Text
						className="wc-settings-ui__section-description"
						variant="body-md"
						render={ <div /> }
					>
						<RawHTML>
							{ sanitizeSettingsHtml( group.description ) }
						</RawHTML>
					</Text>
				) : null }
			</div>
			{ group.actions && group.actions.length > 0 ? (
				<Stack
					className="wc-settings-ui__section-actions"
					direction="row"
					gap="sm"
					wrap="wrap"
				>
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
				</Stack>
			) : null }
		</Card.Header>
	);
};

const getGroupForm = ( group: SettingsUIGroup ): Form => {
	if ( group.actions?.length ) {
		return {
			layout: { type: 'regular' },
			fields: group.fields.map( ( field ) => field.id ),
		};
	}

	return {
		layout: { type: 'card' },
		fields: [
			{
				id: group.id,
				label: group.title,
				description: group.description
					? ( toSanitizedHtmlNode(
							group.description
					  ) as unknown as string )
					: undefined,
				layout: {
					type: 'card',
					withHeader: Boolean( group.title ),
				},
				children: group.fields.map( ( field ) => field.id ),
			},
		],
	};
};

const getAllFields = ( schema: SettingsUISchema ): SettingsUIField[] =>
	Object.values( schema.groups ).flatMap( ( group ) => group.fields );

const validationRuleNames = [ 'required', 'elements', 'custom' ] as const;

const getFormFieldId = ( field: FormField | string ) =>
	typeof field === 'string' ? field : field.id;

const isCombinedFormField = ( field: FormField | string ) =>
	typeof field === 'object' && 'children' in field;

const getFormFieldChildren = ( field: FormField | string ) =>
	isCombinedFormField( field ) && typeof field !== 'string'
		? field.children
		: [];

const compactValidity = (
	validity: Record< string, FieldValidity >
): FormValidity =>
	Object.keys( validity ).length > 0 ? validity : undefined;

/** Remove stale DataForm validity for fields no longer in the validation form. */
export const filterDataFormValidity = (
	validity: FormValidity,
	form: Form
): FormValidity => {
	if ( ! validity ) {
		return undefined;
	}

	const filtered: Record< string, FieldValidity > = {};
	( form.fields || [] ).forEach( ( formField ) => {
		const fieldId = getFormFieldId( formField );
		const source = validity[ fieldId ];
		if ( ! source ) {
			return;
		}

		if ( ! isCombinedFormField( formField ) ) {
			filtered[ fieldId ] = source;
			return;
		}

		const formChildren = getFormFieldChildren( formField );
		const children: Record< string, FieldValidity > = {};
		formChildren.forEach( ( child ) => {
			const childId = getFormFieldId( child );
			if ( source.children?.[ childId ] ) {
				children[ childId ] = source.children[ childId ];
			}
		} );

		const { children: ignoredChildren, ...ownValidity } = source;
		if (
			Object.keys( ownValidity ).length > 0 ||
			Object.keys( children ).length > 0
		) {
			filtered[ fieldId ] = {
				...ownValidity,
				...( Object.keys( children ).length > 0 ? { children } : {} ),
			};
		}
	} );

	return compactValidity( filtered );
};

const addFieldValidity = (
	validity: Record< string, FieldValidity >,
	fieldId: string,
	parentId: string | undefined,
	fieldValidity: FieldValidity
) => {
	if ( ! parentId ) {
		validity[ fieldId ] = fieldValidity;
		return;
	}

	validity[ parentId ] = {
		...validity[ parentId ],
		children: {
			...validity[ parentId ]?.children,
			[ fieldId ]: fieldValidity,
		},
	};
};

/** Evaluate Woo-owned validation for every current visible, enabled field. */
export const evaluateWooValidity = (
	fields: SettingsUIField[],
	values: SettingsValues,
	context: SettingsFieldContext,
	form: Form
): FormValidity => {
	const fieldToParent = new Map< string, string >();
	( form.fields || [] ).forEach( ( formField ) => {
		const parentId = getFormFieldId( formField );
		getFormFieldChildren( formField ).forEach( ( child ) => {
			fieldToParent.set( getFormFieldId( child ), parentId );
		} );
	} );

	const validity: Record< string, FieldValidity > = {};
	fields.forEach( ( field ) => {
		const value = values[ field.id ];
		let rangeMessage: string | undefined;
		if ( typeof value === 'number' && Number.isFinite( value ) ) {
			if (
				typeof field.validation?.min === 'number' &&
				value < field.validation.min
			) {
				rangeMessage = sprintf(
					/* translators: %s: Minimum allowed numeric value. */
					__( 'Value must be at least %s.', 'woocommerce' ),
					String( field.validation.min )
				);
			} else if (
				typeof field.validation?.max === 'number' &&
				value > field.validation.max
			) {
				rangeMessage = sprintf(
					/* translators: %s: Maximum allowed numeric value. */
					__( 'Value must be at most %s.', 'woocommerce' ),
					String( field.validation.max )
				);
			}
		}

		let validatorMessage: string | undefined;
		const validator = resolveFieldValidator( field, context );
		if ( validator ) {
			try {
				validatorMessage =
					validator( { value, values, field, context } ) || undefined;
			} catch ( validatorError ) {
				validatorMessage =
					validatorError instanceof Error && validatorError.message
						? validatorError.message
						: __( 'Unable to validate this field.', 'woocommerce' );
			}
		}

		const message = rangeMessage || validatorMessage;
		if ( message ) {
			addFieldValidity(
				validity,
				field.id,
				fieldToParent.get( field.id ),
				{ custom: { type: 'invalid', message } }
			);
		}
	} );

	return compactValidity( validity );
};

const hasBlockingOwnRule = ( validity?: FieldValidity ) =>
	validationRuleNames.some( ( rule ) => {
		const result = validity?.[ rule ];
		return result && result.type !== 'valid';
	} );

const mergeFieldValidity = (
	packageValidity?: FieldValidity,
	wooValidity?: FieldValidity
): FieldValidity | undefined => {
	const result: FieldValidity = { ...packageValidity };
	if ( ! hasBlockingOwnRule( packageValidity ) && wooValidity?.custom ) {
		result.custom = wooValidity.custom;
	}

	const childIds = new Set( [
		...Object.keys( packageValidity?.children || {} ),
		...Object.keys( wooValidity?.children || {} ),
	] );
	if ( childIds.size > 0 ) {
		const children: Record< string, FieldValidity > = {};
		childIds.forEach( ( childId ) => {
			const child = mergeFieldValidity(
				packageValidity?.children?.[ childId ],
				wooValidity?.children?.[ childId ]
			);
			if ( child ) {
				children[ childId ] = child;
			}
		} );
		if ( Object.keys( children ).length > 0 ) {
			result.children = children;
		}
	}

	return Object.keys( result ).length > 0 ? result : undefined;
};

/** Merge package validity first, followed by Woo range and extension rules. */
export const mergeFormValidity = (
	packageValidity: FormValidity,
	wooValidity: FormValidity
): FormValidity => {
	const fieldIds = new Set( [
		...Object.keys( packageValidity || {} ),
		...Object.keys( wooValidity || {} ),
	] );
	const merged: Record< string, FieldValidity > = {};
	fieldIds.forEach( ( fieldId ) => {
		const validity = mergeFieldValidity(
			packageValidity?.[ fieldId ],
			wooValidity?.[ fieldId ]
		);
		if ( validity ) {
			merged[ fieldId ] = validity;
		}
	} );

	return compactValidity( merged );
};

export const isFormValidityValid = ( validity: FormValidity ): boolean => {
	if ( ! validity ) {
		return true;
	}

	return Object.values( validity ).every( ( fieldValidity ) => {
		const ownRulesValid = validationRuleNames.every( ( rule ) => {
			const result = fieldValidity[ rule ];
			return ! result || result.type === 'valid';
		} );
		return ownRulesValid && isFormValidityValid( fieldValidity.children );
	} );
};

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
				<Notice.Root intent="error">
					<Notice.Description>
						{ __(
							'This settings page could not be displayed. Reload the page and try again.',
							'woocommerce'
						) }
					</Notice.Description>
					<Button variant="secondary" href={ window.location.href }>
						{ __( 'Reload page', 'woocommerce' ) }
					</Button>
				</Notice.Root>
			);
		}

		return this.props.children;
	}
}

const ShellHeader = ( {
	schema,
	actions,
	children,
}: {
	schema: SettingsUISchema;
	actions?: ReactNode;
	children: ReactNode;
} ) => {
	const shell = schema.shell || {};
	const showHeader = shell.header === 'visible';
	const title = shell.title || schema.title;
	const hasNavigation = Boolean(
		( shell.navigation && shell.navigation.length > 0 ) ||
			( shell.sectionNavigation && shell.sectionNavigation.length > 0 )
	);

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
				<Badge
					className="wc-settings-ui-shell__badge"
					intent={ getBadgeIntent( badge.intent ) }
					key={ `${ badge.label }-${ index }` }
				>
					{ badge.label }
				</Badge>
		  ) )
		: undefined;

	return (
		<NavigableRegion
			className="wc-settings-ui-shell"
			ariaLabel={ title || __( 'Settings', 'woocommerce' ) }
		>
			{ showHeader ? (
				<Stack
					className="wc-settings-ui-shell__header"
					direction="column"
					gap="sm"
					render={ <header /> }
				>
					<Stack
						className="wc-settings-ui-shell__header-row"
						direction="row"
						align="center"
					>
						{ breadcrumbs }
						<h2 className="wc-settings-ui-shell__title">
							{ title }
						</h2>
						{ badges }
						{ actions ? (
							<Stack
								className="wc-settings-ui-shell__header-actions"
								direction="row"
								gap="sm"
							>
								{ actions }
							</Stack>
						) : null }
					</Stack>
					{ shell.subtitle ? (
						<Text
							className="wc-settings-ui-shell__subtitle"
							variant="body-md"
							render={ <p /> }
						>
							{ shell.subtitle }
						</Text>
					) : null }
				</Stack>
			) : null }
			{ hasNavigation ? (
				<div className="wc-settings-ui-shell__navigation">
					{ shell.navigation && shell.navigation.length > 0 ? (
						<nav
							className="wc-settings-ui-shell__tabs"
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
							className="wc-settings-ui-shell__tabs"
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
				</div>
			) : null }
			{ children }
		</NavigableRegion>
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

	const pageContextValue = useMemo(
		() => ( { schema, context, initialValues } ),
		[ context, initialValues, schema ]
	);
	const dataFormAdapter = useMemo(
		() => createDataFormAdapter( pageContextValue ),
		[ pageContextValue ]
	);
	const visibleGroups = useMemo(
		() => dataFormAdapter.getVisibleGroups( values ),
		[ dataFormAdapter, values ]
	);
	const groupForms = useMemo(
		() =>
			new Map(
				Object.values( schema.groups ).map( ( group ) => [
					group.id,
					getGroupForm( group ),
				] )
			),
		[ schema.groups ]
	);
	const validationFields = useMemo(
		() => dataFormAdapter.getValidationFields( values ),
		[ dataFormAdapter, values ]
	);
	const validationForm = useMemo(
		() => dataFormAdapter.getValidationForm( values ),
		[ dataFormAdapter, values ]
	);
	const { validity: packageValidity } = useFormValidity(
		values,
		dataFormAdapter.fields,
		validationForm
	);
	const filteredPackageValidity = useMemo(
		() => filterDataFormValidity( packageValidity, validationForm ),
		[ packageValidity, validationForm ]
	);
	const wooValidity = useMemo(
		() =>
			evaluateWooValidity(
				validationFields,
				values,
				context,
				validationForm
			),
		[ context, validationFields, validationForm, values ]
	);
	const validity = useMemo(
		() => mergeFormValidity( filteredPackageValidity, wooValidity ),
		[ filteredPackageValidity, wooValidity ]
	);
	const isValid = useMemo(
		() => isFormValidityValid( validity ),
		[ validity ]
	);

	useEffect( () => {
		const nextValues = getInitialValues( schema );
		setInitialValues( nextValues );
		setValuesState( nextValues );
		setSaveNotice( null );
		setPendingNavigation( null );
	}, [ schema ] );

	const allowNavigation = useCallback( () => {
		allowNavigationRef.current = true;
		clearLegacyFormPrompt();
	}, [] );

	const submitSettingsForm = useCallback(
		( redirectTo?: string ) => {
			if ( ! isValid ) {
				return;
			}

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
		[ allowNavigation, isValid ]
	);

	const setValues = useCallback(
		( nextValues: Partial< SettingsValues > ) => {
			setValuesState( ( currentValues ) => {
				const mergedValues: SettingsValues = { ...currentValues };

				Object.entries( nextValues ).forEach(
					( [ fieldId, value ] ) => {
						mergedValues[ fieldId ] =
							typeof value === 'undefined' ? null : value;
					}
				);

				return mergedValues;
			} );
		},
		[]
	);

	const handleCustomSave = useCallback( async () => {
		if ( saveStrategy.adapter !== 'custom' || ! isValid ) {
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
		isValid,
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

	const formPostFields =
		saveStrategy.adapter === 'form_post' ? getAllFields( schema ) : [];

	const showHeader = schema.shell?.header === 'visible';
	const saveButtonLabel = __( 'Save', 'woocommerce' );
	const saveButton =
		saveStrategy.adapter !== 'none' ? (
			<Button
				className="woocommerce-save-button"
				variant="primary"
				type={
					saveStrategy.adapter === 'form_post' ? 'submit' : 'button'
				}
				name="save"
				value={ saveButtonLabel }
				disabled={ ! isDirty || isSaving || ! isValid }
				isBusy={ isSaving }
				onClick={
					saveStrategy.adapter === 'form_post'
						? allowNavigation
						: handleCustomSave
				}
			>
				{ saveButtonLabel }
			</Button>
		) : undefined;

	return (
		<SettingsUIPageContext.Provider value={ pageContextValue }>
			<ShellHeader schema={ schema } actions={ saveButton }>
				{ pendingNavigation ? (
					<UnsavedChangesModal
						isSaving={ isSaving }
						isValid={ isValid }
						onClose={ () => setPendingNavigation( null ) }
						onDiscard={ handleDiscardNavigation }
						onSave={ handleSavePendingNavigation }
					/>
				) : null }
				{ saveNotice ? (
					<Notice.Root
						className="wc-settings-ui-shell__notice"
						intent={ saveNotice.status }
					>
						<Notice.Description>
							{ saveNotice.message }
						</Notice.Description>
						<Notice.CloseIcon
							onClick={ () => setSaveNotice( null ) }
						/>
					</Notice.Root>
				) : null }
				<Stack className="wc-settings-ui" direction="column" gap="xl">
					{ visibleGroups.map( ( group ) => {
						const form = groupForms.get( group.id );
						if ( ! form ) {
							return null;
						}

						if ( ! group.actions?.length ) {
							return (
								<DataForm
									data={ values }
									fields={ dataFormAdapter.fields }
									form={ form }
									key={ group.id }
									onChange={ setValues }
									validity={ validity }
								/>
							);
						}

						return (
							<section
								className="wc-settings-ui__section"
								key={ group.id }
							>
								<Card.Root className="wc-settings-ui__section-card">
									<GroupHeader group={ group } />
									<Card.Content className="wc-settings-ui__section-fields">
										<DataForm
											data={ values }
											fields={ dataFormAdapter.fields }
											form={ form }
											onChange={ setValues }
											validity={
												validity?.[ group.id ]?.children
											}
										/>
									</Card.Content>
								</Card.Root>
							</section>
						);
					} ) }
					{ ! showHeader && saveButton ? (
						<div className="wc-settings-ui__footer-actions">
							{ saveButton }
						</div>
					) : null }
				</Stack>
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
		</SettingsUIPageContext.Provider>
	);
};
