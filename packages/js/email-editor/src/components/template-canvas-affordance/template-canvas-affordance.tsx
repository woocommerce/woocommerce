/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import {
	createPortal,
	useCallback,
	useEffect,
	useRef,
	useState,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Icon, layout } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { storeName } from '../../store';
import { recordEvent } from '../../events';

const SLOT_ID = 'woocommerce-email-editor-template-area-affordance-slot';
const BLOCK_SELECTOR = '[data-block], .block-editor-block-list__block';

type NavigateToEntityRecord = ( params: {
	postId: number | string;
	postType: string;
} ) => void;

type AffordancePosition = {
	frame: {
		height: number;
		left: number;
		top: number;
		width: number;
	};
	toolbar: {
		left: number;
		top: number;
	};
};

function getCanvasDocument(): Document | null {
	const frames = Array.from( document.querySelectorAll( 'iframe' ) );

	for ( const frame of frames ) {
		try {
			const frameDocument = frame.contentDocument;

			if (
				frameDocument?.querySelector(
					'.block-editor-block-list__layout.is-root-container, .is-root-container'
				)
			) {
				return frameDocument;
			}
		} catch ( error ) {}
	}

	return null;
}

function getClosestBlock( element: Element ): Element {
	return (
		element.closest( BLOCK_SELECTOR ) ||
		element.closest( '[data-type]' ) ||
		element
	);
}

function getPreviousBlock( element: Element ): Element | null {
	let current: Element | null = getClosestBlock( element );

	while ( current ) {
		let sibling = current.previousElementSibling;

		while ( sibling ) {
			if ( sibling.matches( BLOCK_SELECTOR ) ) {
				return sibling;
			}

			const descendantBlocks = sibling.querySelectorAll( BLOCK_SELECTOR );

			if ( descendantBlocks.length ) {
				return descendantBlocks.item( descendantBlocks.length - 1 );
			}

			sibling = sibling.previousElementSibling;
		}

		current = current.parentElement?.closest( BLOCK_SELECTOR ) || null;
	}

	return null;
}

function getTemplateTarget( canvasDocument: Document ): Element | null {
	const templateBlock = canvasDocument.querySelector(
		'[data-type="core/site-logo"], [data-type="core/site-title"], .wp-block-site-logo, .wp-block-site-title'
	);

	if ( templateBlock ) {
		return getClosestBlock( templateBlock );
	}

	const postContentBlock = canvasDocument.querySelector(
		'[data-type="core/post-content"], .wp-block-post-content'
	);

	if ( postContentBlock ) {
		return getPreviousBlock( postContentBlock );
	}

	return null;
}

function createSlot( canvasDocument: Document ): HTMLDivElement {
	let slot = canvasDocument.getElementById(
		SLOT_ID
	) as HTMLDivElement | null;

	if ( ! slot ) {
		slot = canvasDocument.createElement( 'div' );
		slot.id = SLOT_ID;
		slot.className =
			'woocommerce-email-editor-template-area-affordance-slot';
		canvasDocument.body.appendChild( slot );
	}

	return slot;
}

export function TemplateCanvasAffordance() {
	const [ isActive, setIsActive ] = useState( false );
	const [ portalSlot, setPortalSlot ] = useState< HTMLDivElement | null >(
		null
	);
	const [ position, setPosition ] = useState< AffordancePosition | null >(
		null
	);
	const targetRef = useRef< Element | null >( null );

	const {
		canEditTemplates,
		currentPostType,
		onNavigateToEntityRecord,
		template,
	} = useSelect( ( select ) => {
		const editorSettings = select( editorStore ).getEditorSettings();

		return {
			canEditTemplates: select( storeName ).canUserEditTemplates(),
			currentPostType: select( editorStore ).getCurrentPostType(),
			onNavigateToEntityRecord:
				// @ts-expect-error onNavigateToEntityRecord is provided through email editor settings.
				editorSettings?.onNavigateToEntityRecord as
					| NavigateToEntityRecord
					| undefined,
			template: select( storeName ).getCurrentTemplate(),
		};
	}, [] );

	const canShowAffordance =
		currentPostType !== 'wp_template' &&
		canEditTemplates &&
		!! template?.id &&
		!! onNavigateToEntityRecord;

	const updatePosition = useCallback( () => {
		const target = targetRef.current;
		const canvasDocument = target?.ownerDocument;
		const canvasWindow = canvasDocument?.defaultView;

		if ( ! target || ! canvasDocument || ! canvasWindow ) {
			setPosition( null );
			return;
		}

		const rect = target.getBoundingClientRect();

		if ( ! rect.width || ! rect.height ) {
			setPosition( null );
			return;
		}

		const scrollX = canvasWindow.scrollX;
		const scrollY = canvasWindow.scrollY;
		const toolbarHeight = 50;
		const toolbarGap = 4;
		const toolbarTop =
			rect.top > toolbarHeight + toolbarGap
				? rect.top + scrollY - toolbarHeight - toolbarGap
				: rect.bottom + scrollY + toolbarGap;

		setPosition( {
			frame: {
				height: rect.height + 2,
				left: rect.left + scrollX - 1,
				top: rect.top + scrollY - 1,
				width: rect.width + 2,
			},
			toolbar: {
				left: rect.left + scrollX - 1,
				top: toolbarTop,
			},
		} );
	}, [] );

	useEffect( () => {
		if ( ! canShowAffordance ) {
			setIsActive( false );
			setPortalSlot( null );
			setPosition( null );
			targetRef.current = null;
			return undefined;
		}

		let animationFrame = 0;
		let mountedSlot: HTMLDivElement | null = null;

		const mount = () => {
			const canvasDocument = getCanvasDocument();
			const templateTarget =
				canvasDocument && getTemplateTarget( canvasDocument );

			if ( ! canvasDocument || ! templateTarget ) {
				animationFrame = window.requestAnimationFrame( mount );
				return;
			}

			mountedSlot = createSlot( canvasDocument );
			targetRef.current = templateTarget;
			setPortalSlot( mountedSlot );
			updatePosition();
		};

		mount();

		return () => {
			if ( animationFrame ) {
				window.cancelAnimationFrame( animationFrame );
			}

			mountedSlot?.remove();
			setIsActive( false );
			setPortalSlot( null );
			setPosition( null );
			targetRef.current = null;
		};
	}, [ canShowAffordance, updatePosition ] );

	useEffect( () => {
		const canvasDocument = portalSlot?.ownerDocument;
		const canvasWindow = canvasDocument?.defaultView;

		if ( ! portalSlot || ! canvasDocument || ! canvasWindow ) {
			return undefined;
		}

		const closeOnOutsidePointerDown = ( event: MouseEvent ) => {
			if (
				event.target instanceof Node &&
				portalSlot.contains( event.target )
			) {
				return;
			}

			setIsActive( false );
		};

		canvasDocument.addEventListener(
			'mousedown',
			closeOnOutsidePointerDown
		);
		canvasDocument.addEventListener( 'scroll', updatePosition, true );
		canvasWindow.addEventListener( 'resize', updatePosition );
		window.addEventListener( 'resize', updatePosition );
		updatePosition();

		return () => {
			canvasDocument.removeEventListener(
				'mousedown',
				closeOnOutsidePointerDown
			);
			canvasDocument.removeEventListener(
				'scroll',
				updatePosition,
				true
			);
			canvasWindow.removeEventListener( 'resize', updatePosition );
			window.removeEventListener( 'resize', updatePosition );
		};
	}, [ portalSlot, updatePosition ] );

	if ( ! canShowAffordance || ! portalSlot || ! position ) {
		return null;
	}

	return createPortal(
		<>
			<div
				className={
					isActive
						? 'woocommerce-email-editor-template-area-affordance__frame is-active'
						: 'woocommerce-email-editor-template-area-affordance__frame'
				}
				role="button"
				tabIndex={ 0 }
				aria-label={ __( 'Template area', 'woocommerce' ) }
				style={ position.frame }
				onClick={ () => setIsActive( true ) }
				onKeyDown={ ( event ) => {
					if ( event.key !== 'Enter' && event.key !== ' ' ) {
						return;
					}

					event.preventDefault();
					setIsActive( true );
				} }
			/>
			{ isActive && (
				<div
					className="woocommerce-email-editor-template-area-affordance"
					style={ position.toolbar }
				>
					<span
						className="woocommerce-email-editor-template-area-affordance__label"
						aria-label={ __( 'Template area', 'woocommerce' ) }
						title={ __( 'Template area', 'woocommerce' ) }
					>
						<Icon icon={ layout } size={ 24 } />
						<span>{ __( 'Template', 'woocommerce' ) }</span>
					</span>
					<Button
						className="woocommerce-email-editor-template-area-affordance__button"
						variant="tertiary"
						onClick={ () => {
							recordEvent(
								'template_canvas_affordance_edit_template_clicked',
								{ templateId: template.id }
							);
							onNavigateToEntityRecord( {
								postId: template.id,
								postType: 'wp_template',
							} );
						} }
					>
						{ __( 'Edit template', 'woocommerce' ) }
					</Button>
				</div>
			) }
		</>,
		portalSlot
	);
}
