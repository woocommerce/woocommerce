/**
 * External dependencies
 */
import { Page } from '@wordpress/admin-ui';
import { Button, Notice } from '@wordpress/components';
import {
	createElement,
	RawHTML,
	useCallback,
	useEffect,
	useMemo,
	useState,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import type { ReactNode } from 'react';

/**
 * Internal dependencies
 */
import { HiddenInputs } from './hidden-inputs';
import { warn } from './diagnostics';
import { NativeSettingsField } from './native-fields';
import {
	resolveFieldComponent,
	resolveFieldVisibilityPredicate,
	resolveGroupVisibilityPredicate,
	resolveRegionComponent,
	resolveSaveHandler,
} from './registry';
import type {
	ModernSettingsField,
	ModernSettingsGroup,
	ModernSettingsSaveStrategy,
	ModernSettingsSchema,
	SettingsFieldContext,
	SettingsValue,
	SettingsValues,
} from './types';

type SaveNotice = {
	status: 'success' | 'error';
	message: string;
};

const getInitialValues = ( schema: ModernSettingsSchema ): SettingsValues => {
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
	`wc-modern-settings__field--${ type.replace( /[^a-z0-9_-]/gi, '-' ) }`;

const getActionVariant = ( variant?: string ) =>
	( [ 'primary', 'secondary', 'tertiary', 'link' ].includes( variant || '' )
		? variant
		: 'secondary' ) as 'primary' | 'secondary' | 'tertiary' | 'link';

const getSaveStrategy = (
	schema: ModernSettingsSchema
): ModernSettingsSaveStrategy => schema.save || { adapter: 'form_post' };

const GroupHeader = ( { group }: { group: ModernSettingsGroup } ) => {
	const hasHeaderContent =
		group.title || group.description || ( group.actions || [] ).length > 0;

	if ( ! hasHeaderContent ) {
		return null;
	}

	return (
		<div className="wc-modern-settings__group-header">
			{ group.title ? <h2>{ group.title }</h2> : null }
			{ group.description ? (
				<div className="wc-modern-settings__group-description">
					<RawHTML>{ group.description }</RawHTML>
				</div>
			) : null }
			{ group.actions && group.actions.length > 0 ? (
				<div className="wc-modern-settings__group-actions">
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
		</div>
	);
};

const getVisible = ( {
	id,
	kind,
	values,
	initialValues,
	context,
	schema,
}: {
	id: string;
	kind: 'field' | 'group';
	values: SettingsValues;
	initialValues: SettingsValues;
	context: SettingsFieldContext;
	schema: ModernSettingsSchema;
} ) => {
	const predicate =
		kind === 'field'
			? resolveFieldVisibilityPredicate( id, context )
			: resolveGroupVisibilityPredicate( id, context );

	if ( ! predicate ) {
		return true;
	}

	try {
		return predicate( { values, initialValues, context, schema } );
	} catch ( error ) {
		warn(
			`Visibility predicate for ${ kind } "${ id }" failed. Rendering it visible.`,
			{ error, context }
		);
		return true;
	}
};

const getAllFields = ( schema: ModernSettingsSchema ): ModernSettingsField[] =>
	Object.values( schema.groups ).flatMap( ( group ) => group.fields );

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
	schema: ModernSettingsSchema;
	context: SettingsFieldContext;
	values: SettingsValues;
	initialValues: SettingsValues;
	isDirty: boolean;
	isSaving: boolean;
	saveStrategy: ModernSettingsSaveStrategy;
	onSave: () => void;
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
				className="wc-modern-settings-shell__breadcrumbs"
				aria-label={ __( 'Breadcrumbs', 'woocommerce' ) }
			>
				{ shell.breadcrumbs.map( ( breadcrumb, index ) => (
					<span
						className="wc-modern-settings-shell__breadcrumb"
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

	const actions = showSaveButton ? (
		<Button
			variant="primary"
			type={ saveButtonType }
			name="save"
			value={ __( 'Save changes', 'woocommerce' ) }
			disabled={ ! isDirty || isSaving }
			isBusy={ isSaving }
			onClick={
				saveStrategy.adapter === 'form_post' ? undefined : onSave
			}
		>
			{ __( 'Save changes', 'woocommerce' ) }
		</Button>
	) : undefined;

	return (
		<Page
			className="wc-modern-settings-shell"
			headingLevel={ 1 }
			title={ title }
			breadcrumbs={ breadcrumbs }
			actions={ actions }
			showSidebarToggle={ false }
		>
			{ hasNavigation ? (
				<div className="wc-modern-settings-shell__navigation">
					{ shell.navigation && shell.navigation.length > 0 ? (
						<nav
							className="wc-modern-settings-shell__tabs wc-modern-settings-shell__tabs--primary"
							aria-label={ __( 'Settings pages', 'woocommerce' ) }
						>
							{ shell.navigation.map( ( item ) => (
								<a
									className={
										item.active
											? 'wc-modern-settings-shell__tab is-active'
											: 'wc-modern-settings-shell__tab'
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
							className="wc-modern-settings-shell__tabs wc-modern-settings-shell__tabs--secondary"
							aria-label={ __(
								'Settings sections',
								'woocommerce'
							) }
						>
							{ shell.sectionNavigation.map( ( item ) => (
								<a
									className={
										item.active
											? 'wc-modern-settings-shell__tab is-active'
											: 'wc-modern-settings-shell__tab'
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

export const ModernSettingsPage = ( {
	schema,
	page,
	section,
}: {
	schema: ModernSettingsSchema;
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
	const context: SettingsFieldContext = useMemo(
		() => ( {
			page: page || schema.id,
			section: section || schema.section,
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
			return;
		}

		const handler = saveStrategy.handler
			? resolveSaveHandler( saveStrategy.handler, context )
			: undefined;
		if ( ! handler ) {
			setSaveNotice( {
				status: 'error',
				message: __( 'Unable to save settings.', 'woocommerce' ),
			} );
			return;
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
		} catch ( error ) {
			const message =
				error instanceof Error && error.message
					? error.message
					: __( 'Unable to save settings.', 'woocommerce' );
			setSaveNotice( { status: 'error', message } );
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
			onSave={ handleCustomSave }
		>
			{ saveNotice ? (
				<Notice
					className="wc-modern-settings-shell__notice"
					status={ saveNotice.status }
					isDismissible
					onRemove={ () => setSaveNotice( null ) }
				>
					{ saveNotice.message }
				</Notice>
			) : null }
			<div className="wc-modern-settings">
				{ visibleGroups.map( ( group ) => (
					<section
						className="wc-modern-settings__group"
						key={ group.id }
					>
						<GroupHeader group={ group } />
						<div className="wc-modern-settings__group-panel">
							{ group.fields.map( ( field ) => {
								const FieldComponent =
									resolveFieldComponent( field, context ) ||
									NativeSettingsField;
								const value = values[ field.id ];

								return (
									<div
										className={ [
											'wc-modern-settings__field',
											getFieldTypeClassName( field.type ),
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
												setValue( field.id, nextValue )
											}
										/>
									</div>
								);
							} ) }
						</div>
					</section>
				) ) }
			</div>
			{ formPostFields.length > 0 ? (
				<div className="wc-modern-settings__hidden-inputs">
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
