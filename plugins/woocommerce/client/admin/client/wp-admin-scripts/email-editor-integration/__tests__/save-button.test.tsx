/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';
import type { ReactNode } from 'react';
import { store as editorStore } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import { SaveButton, registerWooEmailSaveButton } from '../save-button';

// ---- @wordpress/data mock -------------------------------------------------
//
// Drive the component's selectors from a mutable `state` object; each test
// sets the fields it cares about before rendering. Dispatchers are plain
// jest.fn()s asserted on directly.
const state = {
	isSaving: false,
	postStatus: 'draft',
	isDirty: false,
	hasNonPostEntityChanges: false,
	dirtyEntityRecords: [] as {
		kind: string;
		name: string;
		key: string | number;
	}[],
	currentPostId: 5,
	// Status of the post entity record as the wrap-editor filter sees it
	// (null = record not loaded yet).
	entityRecordStatus: null as string | null,
	// Key combination registered for core/editor/save (null = core's
	// EditorKeyboardShortcutsRegister has not run yet).
	coreSaveKeyCombination: null as {
		modifier: string;
		character: string;
	} | null,
};

const mockEditPost = jest.fn();
const mockSavePost = jest.fn();
const mockSaveEditedEntityRecord = jest.fn();
const mockRegisterShortcut = jest.fn();
const mockUnregisterShortcut = jest.fn();

// The useShortcut callbacks registered during render, keyed by shortcut name.
type SaveShortcutEvent = { preventDefault: jest.Mock };
const shortcutHandlers: Record< string, ( event: SaveShortcutEvent ) => void > =
	{};

const mockStoreSelect = ( store: unknown ) => {
	if ( store === editorStore ) {
		return {
			isSavingPost: () => state.isSaving,
			getEditedPostAttribute: ( attribute: string ) =>
				attribute === 'status' ? state.postStatus : undefined,
			isEditedPostDirty: () => state.isDirty,
			hasNonPostEntityChanges: () => state.hasNonPostEntityChanges,
			getCurrentPostId: () => state.currentPostId,
			getCurrentPostType: () => 'woo_email',
		};
	}
	if ( store === jest.requireMock( '@wordpress/keyboard-shortcuts' ).store ) {
		return {
			getShortcutKeyCombination: ( name: string ) =>
				name === 'core/editor/save'
					? state.coreSaveKeyCombination
					: null,
			getShortcutDescription: ( name: string ) =>
				name === 'core/editor/save' ? 'Save your changes.' : undefined,
		};
	}
	return {
		__experimentalGetDirtyEntityRecords: () => state.dirtyEntityRecords,
		// Keyed off the arguments so a wrong kind/name/key lookup in the
		// component surfaces as a missing record instead of passing silently.
		getEntityRecord: ( kind: string, name: string, key: number ) =>
			kind === 'postType' &&
			name === 'woo_email' &&
			key === state.currentPostId &&
			state.entityRecordStatus
				? { status: state.entityRecordStatus }
				: undefined,
	};
};

jest.mock( '@wordpress/data', () => ( {
	useSelect: ( callback: ( select: unknown ) => unknown ) =>
		callback( mockStoreSelect ),
	useDispatch: ( store: unknown ) => {
		if ( store === jest.requireMock( '@wordpress/editor' ).store ) {
			return { editPost: mockEditPost, savePost: mockSavePost };
		}
		if (
			store === jest.requireMock( '@wordpress/keyboard-shortcuts' ).store
		) {
			return {
				registerShortcut: mockRegisterShortcut,
				unregisterShortcut: mockUnregisterShortcut,
			};
		}
		return { saveEditedEntityRecord: mockSaveEditedEntityRecord };
	},
	select: ( store: unknown ) => mockStoreSelect( store ),
} ) );

jest.mock( '@wordpress/editor', () => ( {
	store: { name: 'core/editor' },
} ) );

jest.mock( '@wordpress/keyboard-shortcuts', () => ( {
	store: { name: 'core/keyboard-shortcuts' },
	useShortcut: (
		name: string,
		callback: ( event: { preventDefault: () => void } ) => void
	) => {
		shortcutHandlers[ name ] = callback;
	},
} ) );

jest.mock( '@wordpress/components', () => ( {
	Button: ( {
		children,
		onClick,
		disabled,
		className,
		'aria-disabled': ariaDisabled,
	}: {
		children: ReactNode;
		onClick?: () => void;
		disabled?: boolean;
		className?: string;
		'aria-disabled'?: boolean;
	} ) => (
		<button
			onClick={ onClick }
			disabled={ disabled }
			className={ className }
			aria-disabled={ ariaDisabled }
		>
			{ children }
		</button>
	),
} ) );

jest.mock( '@wordpress/core-data', () => ( {
	store: { name: 'core' },
} ) );

describe( 'SaveButton', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		state.isSaving = false;
		state.postStatus = 'draft';
		state.isDirty = false;
		state.hasNonPostEntityChanges = false;
		state.dirtyEntityRecords = [];
		state.currentPostId = 5;
		state.entityRecordStatus = null;
		state.coreSaveKeyCombination = { modifier: 'primary', character: 's' };
		Object.keys( shortcutHandlers ).forEach(
			( key ) => delete shortcutHandlers[ key ]
		);
	} );

	it( 'renders a button labeled "Save"', () => {
		render( <SaveButton /> );

		expect(
			screen.getByRole( 'button', { name: 'Save' } )
		).toBeInTheDocument();
	} );

	// The email editor package's DOM tracking records save clicks via the
	// `.editor-post-publish-button` selector and checks `aria-disabled`;
	// dropping either silently kills the `header_save_button_clicked` event.
	it( 'carries the telemetry contract: core publish-button class and aria-disabled', () => {
		render( <SaveButton /> );

		const button = screen.getByRole( 'button', { name: 'Save' } );
		expect( button ).toHaveClass( 'editor-post-publish-button' );
		expect( button ).toHaveAttribute( 'aria-disabled', 'false' );
	} );

	// auto-draft covers legacy stray posts created outside the lazy flow.
	it.each( [ [ 'draft' ], [ 'auto-draft' ] ] )(
		'is enabled for an unpublished (%s) post even when not dirty',
		( status ) => {
			state.postStatus = status;
			state.isDirty = false;

			render( <SaveButton /> );

			expect(
				screen.getByRole( 'button', { name: 'Save' } )
			).toBeEnabled();
		}
	);

	it( 'is disabled while a save is already in flight', () => {
		state.postStatus = 'draft';
		state.isSaving = true;

		render( <SaveButton /> );

		expect( screen.getByRole( 'button', { name: 'Save' } ) ).toBeDisabled();
	} );

	it( 'is disabled when the post is published and not dirty', () => {
		state.postStatus = 'publish';
		state.isDirty = false;

		render( <SaveButton /> );

		expect( screen.getByRole( 'button', { name: 'Save' } ) ).toBeDisabled();
	} );

	it( 'publishes and saves an unpublished post on click', () => {
		state.postStatus = 'draft';

		render( <SaveButton /> );
		fireEvent.click( screen.getByRole( 'button', { name: 'Save' } ) );

		expect( mockEditPost ).toHaveBeenCalledWith( { status: 'publish' } );
		expect( mockSavePost ).toHaveBeenCalled();
	} );

	it( 'saves a published post on click without re-publishing it', () => {
		state.postStatus = 'publish';
		state.isDirty = true;

		render( <SaveButton /> );
		fireEvent.click( screen.getByRole( 'button', { name: 'Save' } ) );

		expect( mockEditPost ).not.toHaveBeenCalled();
		expect( mockSavePost ).toHaveBeenCalled();
	} );

	it( 'is enabled for a published, non-dirty post when non-post entities have changes', () => {
		state.postStatus = 'publish';
		state.isDirty = false;
		state.hasNonPostEntityChanges = true;

		render( <SaveButton /> );

		expect( screen.getByRole( 'button', { name: 'Save' } ) ).toBeEnabled();
	} );

	describe( 'save shortcut takeover', () => {
		const makeKeydownEvent = (): SaveShortcutEvent => ( {
			preventDefault: jest.fn(),
		} );

		it( 'replaces the core save shortcut with its own registration', () => {
			render( <SaveButton /> );

			expect( mockUnregisterShortcut ).toHaveBeenCalledWith(
				'core/editor/save'
			);
			expect( mockRegisterShortcut ).toHaveBeenCalledWith(
				expect.objectContaining( {
					name: 'woocommerce/email-editor/save',
					keyCombination: { modifier: 'primary', character: 's' },
				} )
			);
		} );

		it( 'does not unregister anything before core registered its shortcut', () => {
			// EditorKeyboardShortcutsRegister registers core/editor/save in its
			// own mount effect — the takeover must wait for the store, not
			// race the mount order.
			state.coreSaveKeyCombination = null;

			render( <SaveButton /> );

			expect( mockUnregisterShortcut ).not.toHaveBeenCalled();
			expect( mockRegisterShortcut ).not.toHaveBeenCalled();
		} );

		it( 'takes over again when core re-registers while mounted', () => {
			const { rerender } = render( <SaveButton /> );
			mockRegisterShortcut.mockClear();
			mockUnregisterShortcut.mockClear();

			// The store reports core/editor/save as registered again (e.g. a
			// remounted EditorKeyboardShortcutsRegister); a rerender must
			// repeat the takeover.
			rerender( <SaveButton /> );

			expect( mockUnregisterShortcut ).toHaveBeenCalledWith(
				'core/editor/save'
			);
			expect( mockRegisterShortcut ).toHaveBeenCalledWith(
				expect.objectContaining( {
					name: 'woocommerce/email-editor/save',
				} )
			);
		} );

		it( 'publishes and saves on the shortcut like a button click', () => {
			state.postStatus = 'draft';

			render( <SaveButton /> );
			const event = makeKeydownEvent();
			shortcutHandlers[ 'woocommerce/email-editor/save' ]( event );

			expect( event.preventDefault ).toHaveBeenCalled();
			expect( mockEditPost ).toHaveBeenCalledWith( {
				status: 'publish',
			} );
			expect( mockSavePost ).toHaveBeenCalled();
		} );

		it( 'still prevents the browser save dialog but does not save while disabled', () => {
			state.isSaving = true;

			render( <SaveButton /> );
			const event = makeKeydownEvent();
			shortcutHandlers[ 'woocommerce/email-editor/save' ]( event );

			expect( event.preventDefault ).toHaveBeenCalled();
			expect( mockSavePost ).not.toHaveBeenCalled();
		} );

		it( 'restores the core save shortcut on unmount', () => {
			const { unmount } = render( <SaveButton /> );
			mockRegisterShortcut.mockClear();
			mockUnregisterShortcut.mockClear();

			unmount();

			expect( mockUnregisterShortcut ).toHaveBeenCalledWith(
				'woocommerce/email-editor/save'
			);
			expect( mockRegisterShortcut ).toHaveBeenCalledWith(
				expect.objectContaining( {
					name: 'core/editor/save',
					keyCombination: { modifier: 'primary', character: 's' },
					description: 'Save your changes.',
				} )
			);
		} );
	} );

	it( 'saves dirty non-post entities on click but not the post entity record', () => {
		state.postStatus = 'publish';
		state.isDirty = true;
		state.currentPostId = 5;
		state.dirtyEntityRecords = [
			{ kind: 'postType', name: 'woo_email', key: 5 },
			{ kind: 'root', name: 'globalStyles', key: 1 },
		];

		render( <SaveButton /> );
		fireEvent.click( screen.getByRole( 'button', { name: 'Save' } ) );

		expect( mockSaveEditedEntityRecord ).toHaveBeenCalledTimes( 1 );
		expect( mockSaveEditedEntityRecord ).toHaveBeenCalledWith(
			'root',
			'globalStyles',
			1,
			{}
		);
		expect( mockSaveEditedEntityRecord ).not.toHaveBeenCalledWith(
			'postType',
			'woo_email',
			5,
			{}
		);
	} );
} );

describe( 'registerWooEmailSaveButton', () => {
	// The wrap-editor filter must only inject the custom button while the
	// post is unpublished; published posts keep core's stock save flow
	// (including the multi-entity save panel).
	const getWrappedEditor = () => {
		registerWooEmailSaveButton();
		const MockEditor = ( {
			customSaveButton,
		}: {
			customSaveButton?: ReactNode;
		} ) => <div>{ customSaveButton ?? <span>core save flow</span> }</div>;

		const { applyFilters } = require( '@wordpress/hooks' );
		return applyFilters(
			'woocommerce_email_editor_wrap_editor_component',
			MockEditor
		) as React.ComponentType< Record< string, unknown > >;
	};

	beforeEach( () => {
		state.entityRecordStatus = null;
	} );

	it.each( [ [ 'auto-draft' ], [ 'draft' ] ] )(
		'injects the custom save button for an unpublished (%s) post',
		( status ) => {
			state.entityRecordStatus = status;
			const Wrapped = getWrappedEditor();

			render( <Wrapped postId={ 5 } postType="woo_email" /> );

			expect(
				screen.getByRole( 'button', { name: 'Save' } )
			).toBeInTheDocument();
		}
	);

	it( 'keeps the core save flow for a published post', () => {
		state.entityRecordStatus = 'publish';
		const Wrapped = getWrappedEditor();

		render( <Wrapped postId={ 5 } postType="woo_email" /> );

		expect( screen.getByText( 'core save flow' ) ).toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Save' } )
		).not.toBeInTheDocument();
	} );

	it( 'keeps the core save flow while the post record is not loaded yet', () => {
		state.entityRecordStatus = null;
		const Wrapped = getWrappedEditor();

		render( <Wrapped postId={ 5 } postType="woo_email" /> );

		expect( screen.getByText( 'core save flow' ) ).toBeInTheDocument();
	} );
} );
