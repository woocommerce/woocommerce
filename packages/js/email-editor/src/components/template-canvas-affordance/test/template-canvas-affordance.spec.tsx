import '../../test/__mocks__/setup-shared-mocks';

/**
 * External dependencies
 */
import { fireEvent, render, waitFor } from '@testing-library/react';
import '@testing-library/jest-dom';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import { TemplateCanvasAffordance } from '../template-canvas-affordance';
import { storeName } from '../../../store';
import { recordEvent } from '../../../events';

jest.mock( '@wordpress/components', () => {
	const { forwardRef: forwardRefImpl } =
		jest.requireActual( '@wordpress/element' );

	return {
		Button: forwardRefImpl(
			( { children, className, onClick, variant }, ref ) => (
				<button
					ref={ ref }
					className={ `${ className } components-button is-${ variant }` }
					onClick={ onClick }
					type="button"
				>
					{ children }
				</button>
			)
		),
	};
} );

jest.mock( '@wordpress/icons', () => ( {
	Icon: () => <span data-testid="template-area-icon" />,
	layout: 'layout',
} ) );

jest.mock( '../../../events', () => ( {
	recordEvent: jest.fn(),
} ) );

const useSelectMock = useSelect as jest.Mock;
const recordEventMock = recordEvent as jest.Mock;

const template = {
	id: 'twentytwentyfive//wooemailtemplate',
	title: 'Woo email template',
};

const setupUseSelectMock = ( {
	canEditTemplates = true,
	currentPostType = 'woo_email',
	onNavigateToEntityRecord = jest.fn(),
} = {} ) => {
	useSelectMock.mockImplementation( ( selector ) =>
		selector( ( store ) => {
			if ( store === storeName ) {
				return {
					canUserEditTemplates: () => canEditTemplates,
					getCurrentTemplate: () => template,
				};
			}

			if ( store === editorStore ) {
				return {
					getCurrentPostType: () => currentPostType,
					getEditorSettings: () => ( {
						onNavigateToEntityRecord,
					} ),
				};
			}

			return {};
		} )
	);

	return { onNavigateToEntityRecord };
};

const addEditorCanvas = ( {
	headerAttributes = '',
	headerContent = '<h1 class="wp-block-site-title">testingbun</h1>',
} = {} ) => {
	const iframe = document.createElement( 'iframe' );
	document.body.appendChild( iframe );
	iframe.contentDocument?.body.insertAdjacentHTML(
		'beforeend',
		[
			'<div>',
			'<div class="block-editor-block-list__layout is-root-container">',
			'<div class="block-editor-block-list__block" data-block="outer-template">',
			`<div class="block-editor-block-list__block" data-block="template-header" ${ headerAttributes }>`,
			headerContent,
			'</div>',
			'<div class="block-editor-block-list__block" data-block="email-content-wrapper">',
			'<div class="block-editor-block-list__block" data-block="email-content">',
			'<div class="wp-block-post-content">New order</div>',
			'</div>',
			'</div>',
			'<div class="block-editor-block-list__block" data-block="template-footer">',
			'<p>Footer</p>',
			'</div>',
			'</div>',
			'</div>',
			'</div>',
		].join( '' )
	);

	const templateHeader = iframe.contentDocument?.querySelector(
		'[data-block="template-header"]'
	);
	const outerTemplate = iframe.contentDocument?.querySelector(
		'[data-block="outer-template"]'
	);

	if ( ! outerTemplate || ! templateHeader ) {
		throw new Error(
			'Editor canvas fixture is missing expected template elements.'
		);
	}

	Object.defineProperty( outerTemplate, 'getBoundingClientRect', {
		value: () => ( {
			bottom: 900,
			height: 900,
			left: 100,
			right: 760,
			top: 0,
			width: 660,
			x: 100,
			y: 0,
		} ),
	} );

	Object.defineProperty( templateHeader, 'getBoundingClientRect', {
		value: () => ( {
			bottom: 148,
			height: 64,
			left: 120,
			right: 720,
			top: 84,
			width: 600,
			x: 120,
			y: 84,
		} ),
	} );

	return iframe;
};

describe( 'TemplateCanvasAffordance', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		document.body.innerHTML = '';
	} );

	it( 'renders a selectable frame over the template area without showing the toolbar by default', async () => {
		const iframe = addEditorCanvas();
		setupUseSelectMock();

		render( <TemplateCanvasAffordance /> );

		await waitFor( () => {
			expect(
				iframe.contentDocument?.getElementById(
					'woocommerce-email-editor-template-area-affordance-slot'
				)
			).toBeInTheDocument();
		} );

		expect(
			iframe.contentDocument?.querySelector(
				'.woocommerce-email-editor-template-area-affordance__frame'
			)
		).toBeInTheDocument();
		expect( iframe.contentDocument?.body ).not.toHaveTextContent(
			'Edit template'
		);
	} );

	it( 'shows the toolbar after the template area is selected', async () => {
		const iframe = addEditorCanvas();
		setupUseSelectMock();

		render( <TemplateCanvasAffordance /> );

		await waitFor( () => {
			expect(
				iframe.contentDocument?.querySelector(
					'.woocommerce-email-editor-template-area-affordance__frame'
				)
			).toBeInTheDocument();
		} );

		fireEvent.click(
			iframe.contentDocument?.querySelector(
				'.woocommerce-email-editor-template-area-affordance__frame'
			) as HTMLButtonElement
		);

		expect( iframe.contentDocument?.body ).toHaveTextContent( 'Template' );
		expect( iframe.contentDocument?.body ).toHaveTextContent(
			'Edit template'
		);
	} );

	it( 'anchors the affordance to editor block metadata when site title classes are not rendered', async () => {
		const iframe = addEditorCanvas( {
			headerAttributes: 'data-type="core/site-title"',
			headerContent: '<h1>testingbun</h1>',
		} );
		setupUseSelectMock();

		render( <TemplateCanvasAffordance /> );

		await waitFor( () => {
			expect(
				iframe.contentDocument?.querySelector(
					'.woocommerce-email-editor-template-area-affordance__frame'
				)
			).toBeInTheDocument();
		} );

		fireEvent.click(
			iframe.contentDocument?.querySelector(
				'.woocommerce-email-editor-template-area-affordance__frame'
			) as HTMLButtonElement
		);

		expect(
			iframe.contentDocument?.querySelector(
				'.woocommerce-email-editor-template-area-affordance__frame'
			)
		).toHaveStyle( { top: '83px' } );
		expect(
			iframe.contentDocument?.querySelector(
				'.woocommerce-email-editor-template-area-affordance'
			)
		).toHaveStyle( { top: '30px' } );
	} );

	it( 'does not render when no site-identity block is available to anchor to', async () => {
		const iframe = addEditorCanvas( {
			headerContent: '<h1>testingbun</h1>',
		} );
		setupUseSelectMock();

		render( <TemplateCanvasAffordance /> );

		// Give the rAF-driven mount loop a chance to run and bail out.
		await new Promise( ( resolve ) => setTimeout( resolve, 50 ) );

		expect(
			iframe.contentDocument?.getElementById(
				'woocommerce-email-editor-template-area-affordance-slot'
			)
		).not.toBeInTheDocument();
	} );

	it( 'does not render when the user cannot edit templates', async () => {
		const iframe = addEditorCanvas();
		setupUseSelectMock( { canEditTemplates: false } );

		render( <TemplateCanvasAffordance /> );

		await new Promise( ( resolve ) => setTimeout( resolve, 50 ) );

		expect(
			iframe.contentDocument?.getElementById(
				'woocommerce-email-editor-template-area-affordance-slot'
			)
		).not.toBeInTheDocument();
	} );

	it( 'navigates to the current template when edit template is clicked', async () => {
		const iframe = addEditorCanvas();
		const { onNavigateToEntityRecord } = setupUseSelectMock();

		render( <TemplateCanvasAffordance /> );

		await waitFor( () => {
			expect(
				iframe.contentDocument?.querySelector(
					'.woocommerce-email-editor-template-area-affordance__frame'
				)
			).toBeInTheDocument();
		} );

		fireEvent.click(
			iframe.contentDocument?.querySelector(
				'.woocommerce-email-editor-template-area-affordance__frame'
			) as HTMLButtonElement
		);

		fireEvent.click(
			iframe.contentDocument?.querySelector(
				'.woocommerce-email-editor-template-area-affordance__button'
			) as HTMLButtonElement
		);

		expect( recordEventMock ).toHaveBeenCalledWith(
			'template_canvas_affordance_edit_template_clicked',
			{ templateId: template.id }
		);
		expect( onNavigateToEntityRecord ).toHaveBeenCalledWith( {
			postId: template.id,
			postType: 'wp_template',
		} );
	} );

	it( 'does not render while editing a template', async () => {
		const iframe = addEditorCanvas();
		setupUseSelectMock( { currentPostType: 'wp_template' } );

		render( <TemplateCanvasAffordance /> );

		await waitFor( () => {
			expect(
				iframe.contentDocument?.getElementById(
					'woocommerce-email-editor-template-area-affordance-slot'
				)
			).not.toBeInTheDocument();
		} );
	} );
} );
