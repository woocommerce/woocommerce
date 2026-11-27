/**
 * External dependencies
 */
import clsx from 'clsx';
import { BlockControls } from '@wordpress/block-editor';
import { ToolbarButton } from '@wordpress/components';
import { createHigherOrderComponent } from '@wordpress/compose';
import { select, useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { addFilterForEmail } from '../../config-tools/filters';
import {
	getTemplateAreaClientIds,
	isTemplateAreaEditingEnabled,
	useIsTemplateArea,
} from '../../components/template-areas';
import { EditTemplateModal } from '../../components/sidebar/edit-template-modal';
import { recordEvent } from '../../events';
import { storeName } from '../../store';

type NavigateToEntityRecord = ( params: {
	postId: number | string;
	postType: string;
} ) => void;

function TemplateAreaToolbar() {
	const { template, onNavigateToEntityRecord } = useSelect(
		( select ) => ( {
			template: select( storeName ).getCurrentTemplate(),
			onNavigateToEntityRecord: (
				select( editorStore ).getEditorSettings() as {
					onNavigateToEntityRecord?: NavigateToEntityRecord;
				}
			 ).onNavigateToEntityRecord,
		} ),
		[]
	);

	return (
		<BlockControls group="other">
			<span className="woocommerce-email-editor-template-area__label">
				{ template?.title }
			</span>
			<ToolbarButton
				onClick={ () => {
					recordEvent( 'template_area_edit_template_clicked', {
						templateId: template?.id,
					} );
					onNavigateToEntityRecord?.( {
						postId: template?.id,
						postType: 'wp_template',
					} );
				} }
			>
				{ __( 'Edit template', __i18n_text_domain__ ) }
			</ToolbarButton>
		</BlockControls>
	);
}

/**
 * Adds the template name and an "Edit template" button to the toolbar of the
 * template blocks around the email content.
 */
export const withTemplateAreaToolbar = createHigherOrderComponent(
	( BlockEdit ) =>
		function TemplateAreaBlockEdit( props ) {
			const isTemplateArea = useIsTemplateArea( props.clientId );
			return (
				<>
					{ isTemplateArea && <TemplateAreaToolbar /> }
					<BlockEdit { ...props } />
				</>
			);
		},
	'withTemplateAreaToolbar'
);

/**
 * Marks the template blocks for the outline styles and asks before opening the
 * template on double-click, as core does when you double-click a template.
 */
export const withTemplateAreaFrame = createHigherOrderComponent(
	( BlockListBlock ) =>
		function TemplateAreaBlockListBlock( props ) {
			const isTemplateArea = useIsTemplateArea( props.clientId );
			const [ isModalOpen, setIsModalOpen ] = useState( false );

			if ( ! isTemplateArea ) {
				return <BlockListBlock { ...props } />;
			}

			return (
				<>
					<BlockListBlock
						{ ...props }
						className={ clsx(
							props.className,
							'is-email-template-area'
						) }
						wrapperProps={ {
							...props.wrapperProps,
							onDoubleClick: () => setIsModalOpen( true ),
						} }
					/>
					{ isModalOpen && (
						<EditTemplateModal
							close={ () => setIsModalOpen( false ) }
						/>
					) }
				</>
			);
		},
	'withTemplateAreaFrame'
);

/**
 * Template areas can be selected, but their content belongs to the template, so
 * no blocks can be added to them from the email.
 */
export function preventInsertingIntoTemplateAreas(
	canInsert: boolean,
	_blockType: unknown,
	rootClientId: string | null
): boolean {
	if ( ! canInsert || ! rootClientId ) {
		return canInsert;
	}
	return ! (
		isTemplateAreaEditingEnabled( select ) &&
		getTemplateAreaClientIds( select ).includes( rootClientId )
	);
}

export function enableTemplateAreaTools(): void {
	addFilterForEmail(
		'editor.BlockEdit',
		'woocommerce-email-editor/template-area-toolbar',
		withTemplateAreaToolbar
	);
	addFilterForEmail(
		'editor.BlockListBlock',
		'woocommerce-email-editor/template-area-frame',
		withTemplateAreaFrame
	);
	addFilterForEmail(
		'blockEditor.__unstableCanInsertBlockType',
		'woocommerce-email-editor/template-area-insert',
		preventInsertingIntoTemplateAreas
	);
}
