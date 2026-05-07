/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { createPortal, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Icon, layout } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { storeName } from '../../store';
import { recordEvent } from '../../events';

const SLOT_ID = 'woocommerce-email-editor-template-area-affordance-slot';

type NavigateToEntityRecord = ( params: {
	postId: number | string;
	postType: string;
} ) => void;

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

function getCanvasRoot( canvasDocument: Document ): Element | null {
	return canvasDocument.querySelector(
		'.block-editor-block-list__layout.is-root-container, .is-root-container'
	);
}

function createSlot( canvasDocument: Document ): HTMLDivElement | null {
	const canvasRoot = getCanvasRoot( canvasDocument );

	if ( ! canvasRoot?.parentElement ) {
		return null;
	}

	let slot = canvasDocument.getElementById(
		SLOT_ID
	) as HTMLDivElement | null;

	if ( ! slot ) {
		slot = canvasDocument.createElement( 'div' );
		slot.id = SLOT_ID;
		slot.className =
			'woocommerce-email-editor-template-area-affordance-slot';
		canvasRoot.parentElement.insertBefore( slot, canvasRoot );
	}

	return slot;
}

export function TemplateCanvasAffordance() {
	const [ portalSlot, setPortalSlot ] = useState< HTMLDivElement | null >(
		null
	);

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

	useEffect( () => {
		if ( ! canShowAffordance ) {
			setPortalSlot( null );
			return undefined;
		}

		let animationFrame = 0;
		let mountedSlot: HTMLDivElement | null = null;

		const mount = () => {
			const canvasDocument = getCanvasDocument();

			if ( ! canvasDocument ) {
				animationFrame = window.requestAnimationFrame( mount );
				return;
			}

			mountedSlot = createSlot( canvasDocument );

			if ( ! mountedSlot ) {
				animationFrame = window.requestAnimationFrame( mount );
				return;
			}

			setPortalSlot( mountedSlot );
		};

		mount();

		return () => {
			if ( animationFrame ) {
				window.cancelAnimationFrame( animationFrame );
			}

			mountedSlot?.remove();
			setPortalSlot( null );
		};
	}, [ canShowAffordance ] );

	if ( ! canShowAffordance || ! portalSlot ) {
		return null;
	}

	return createPortal(
		<div className="woocommerce-email-editor-template-area-affordance">
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
		</div>,
		portalSlot
	);
}
