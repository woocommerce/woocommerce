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
const STYLE_ID = 'woocommerce-email-editor-template-area-affordance-style';
const BLOCK_SELECTOR = '[data-block], .block-editor-block-list__block';

// The block-editor style.scss is bundled into the outer editor document, but
// the affordance lives inside the canvas iframe. The iframe is a separate
// document, so we inject the positional/visual rules into its <head> directly.
const CANVAS_STYLES = `
.woocommerce-email-editor-template-area-affordance-slot {
	box-sizing: border-box;
	inset: 0 auto auto 0;
	pointer-events: none;
	position: absolute;
	z-index: 29;
}
.woocommerce-email-editor-template-area-affordance {
	align-items: stretch;
	background: #fff;
	border: 1px solid #1e1e1e;
	border-radius: 2px;
	box-shadow: 0 2px 4px rgba(0, 0, 0, 0.12);
	display: inline-flex;
	font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
	font-size: 13px;
	line-height: 1;
	pointer-events: auto;
	position: absolute;
	z-index: 30;
}
.woocommerce-email-editor-template-area-affordance__frame {
	appearance: none;
	background: transparent;
	border: 1.5px solid transparent;
	box-sizing: border-box;
	cursor: pointer;
	font: inherit;
	margin: 0;
	padding: 0;
	pointer-events: auto;
	position: absolute;
	z-index: 29;
}
.woocommerce-email-editor-template-area-affordance__frame:hover,
.woocommerce-email-editor-template-area-affordance__frame:focus-visible,
.woocommerce-email-editor-template-area-affordance__frame.is-active {
	border-color: var(--wp-components-color-accent, #3858e9);
	outline: none;
}
.woocommerce-email-editor-template-area-affordance__label {
	align-items: center;
	border-right: 1px solid #ddd;
	display: inline-flex;
	font-weight: 500;
	gap: 8px;
	height: 48px;
	padding: 0 16px;
	white-space: nowrap;
}
.woocommerce-email-editor-template-area-affordance__label svg {
	fill: currentColor;
}
.woocommerce-email-editor-template-area-affordance__button.components-button {
	border-radius: 0;
	box-shadow: none;
	color: var(--wp-components-color-accent, #3858e9);
	height: 48px;
	padding: 0 18px;
	white-space: nowrap;
}
.woocommerce-email-editor-template-area-affordance__button.components-button:hover:not(:disabled) {
	color: var(--wp-components-color-accent, #3858e9);
}
.woocommerce-email-editor-template-area-affordance__button.components-button:focus-visible {
	box-shadow: inset 0 0 0 1.5px var(--wp-components-color-accent, #3858e9);
}
`;

function ensureCanvasStyles( canvasDocument: Document ): void {
	if ( canvasDocument.getElementById( STYLE_ID ) ) {
		return;
	}

	const style = canvasDocument.createElement( 'style' );
	style.id = STYLE_ID;
	style.textContent = CANVAS_STYLES;
	canvasDocument.head.appendChild( style );
}

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
		} catch ( error ) {
			// Accessing contentDocument throws SecurityError for cross-origin
			// iframes; those frames cannot host the editor canvas, so skip
			// them silently.
		}
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

function getTemplateTarget( canvasDocument: Document ): Element | null {
	// We can only confidently identify a template area when the template
	// renders one of the well-known site-identity blocks. Falling back to
	// arbitrary nearby blocks produced visually misleading highlights — see
	// https://github.com/woocommerce/woocommerce/pull/64703#discussion (manual
	// QA on the default Woo email template).
	const templateBlock = canvasDocument.querySelector(
		'[data-type="core/site-logo"], [data-type="core/site-title"], .wp-block-site-logo, .wp-block-site-title'
	);

	if ( ! templateBlock ) {
		return null;
	}

	return getClosestBlock( templateBlock );
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
		// Bail out after roughly two seconds at 60fps. If the canvas hasn't
		// rendered an identifiable template area by then, this email almost
		// certainly doesn't have one — keep the loop bounded instead of
		// retrying forever for the lifetime of the editor.
		let retriesRemaining = 120;

		const mount = () => {
			const canvasDocument = getCanvasDocument();
			const templateTarget =
				canvasDocument && getTemplateTarget( canvasDocument );

			if ( ! canvasDocument || ! templateTarget ) {
				if ( retriesRemaining > 0 ) {
					retriesRemaining--;
					animationFrame = window.requestAnimationFrame( mount );
				}
				return;
			}

			ensureCanvasStyles( canvasDocument );
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

			const ownerDocument = mountedSlot?.ownerDocument;
			mountedSlot?.remove();
			ownerDocument?.getElementById( STYLE_ID )?.remove();
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

	// React’s synthetic event system is attached to the outer document where
	// the editor is mounted. The portal target lives inside the canvas iframe
	// (a separate document), so onClick/onKeyDown handlers wired through React
	// never receive real user-generated events on these nodes. We attach
	// native listeners via refs to bypass the cross-document delegation gap.
	const frameRef = useRef< HTMLButtonElement | null >( null );
	const buttonRef = useRef< HTMLButtonElement | null >( null );

	const activate = useCallback( () => setIsActive( true ), [] );

	const handleEditTemplate = useCallback( () => {
		if ( ! template?.id || ! onNavigateToEntityRecord ) {
			return;
		}

		recordEvent( 'template_canvas_affordance_edit_template_clicked', {
			templateId: template.id,
		} );
		onNavigateToEntityRecord( {
			postId: template.id,
			postType: 'wp_template',
		} );
	}, [ onNavigateToEntityRecord, template?.id ] );

	// Gutenberg’s block list installs capture-phase pointerdown/mousedown
	// handlers on the canvas to start block selection and drag gestures. If it
	// captures the pointer here, the browser never dispatches a `click` event
	// on the affordance, so we stop propagation on the pointer-start events
	// to keep the click sequence intact.
	const swallowPointerStart = useCallback( ( event: Event ) => {
		event.stopPropagation();
	}, [] );

	useEffect( () => {
		const node = frameRef.current;

		if ( ! node ) {
			return undefined;
		}

		// Native <button> elements handle Enter/Space activation themselves,
		// so we only need to wire up the click and pointer-start listeners.
		node.addEventListener( 'mousedown', swallowPointerStart );
		node.addEventListener( 'pointerdown', swallowPointerStart );
		node.addEventListener( 'click', activate );

		return () => {
			node.removeEventListener( 'mousedown', swallowPointerStart );
			node.removeEventListener( 'pointerdown', swallowPointerStart );
			node.removeEventListener( 'click', activate );
		};
	}, [ portalSlot, activate, swallowPointerStart ] );

	useEffect( () => {
		const node = buttonRef.current;

		if ( ! node ) {
			return undefined;
		}

		node.addEventListener( 'mousedown', swallowPointerStart );
		node.addEventListener( 'pointerdown', swallowPointerStart );
		node.addEventListener( 'click', handleEditTemplate );

		return () => {
			node.removeEventListener( 'mousedown', swallowPointerStart );
			node.removeEventListener( 'pointerdown', swallowPointerStart );
			node.removeEventListener( 'click', handleEditTemplate );
		};
	}, [ isActive, handleEditTemplate, swallowPointerStart ] );

	if ( ! canShowAffordance || ! portalSlot || ! position ) {
		return null;
	}

	const toolbarId = `${ SLOT_ID }-toolbar`;

	return createPortal(
		<>
			<button
				ref={ frameRef }
				type="button"
				className={
					isActive
						? 'woocommerce-email-editor-template-area-affordance__frame is-active'
						: 'woocommerce-email-editor-template-area-affordance__frame'
				}
				aria-label={ __( 'Template area', 'woocommerce' ) }
				aria-expanded={ isActive }
				aria-controls={ isActive ? toolbarId : undefined }
				style={ position.frame }
			/>
			{ isActive && (
				<div
					id={ toolbarId }
					className="woocommerce-email-editor-template-area-affordance"
					style={ position.toolbar }
				>
					<span className="woocommerce-email-editor-template-area-affordance__label">
						<Icon icon={ layout } size={ 24 } />
						<span>{ __( 'Template', 'woocommerce' ) }</span>
					</span>
					<Button
						ref={ buttonRef }
						className="woocommerce-email-editor-template-area-affordance__button"
						variant="tertiary"
					>
						{ __( 'Edit template', 'woocommerce' ) }
					</Button>
				</div>
			) }
		</>,
		portalSlot
	);
}
