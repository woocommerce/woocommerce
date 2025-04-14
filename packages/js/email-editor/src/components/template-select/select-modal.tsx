/**
 * External dependencies
 */
import { useState, useEffect, memo } from '@wordpress/element';
import { store as editorStore } from '@wordpress/editor';
import { dispatch } from '@wordpress/data';
import { Modal, Button, Flex, FlexItem } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { usePreviewTemplates } from '../../hooks';
import {
	EmailEditorPostType,
	storeName,
	TemplateCategory,
	TemplatePreview,
	editorCurrentPostType,
} from '../../store';
import { TemplateList } from './template-list';
import { TemplateCategoriesListSidebar } from './template-categories-list-sidebar';
import { recordEvent, recordEventOnce } from '../../events';

const TemplateCategories: Array< { name: TemplateCategory; label: string } > = [
	{
		name: 'recent',
		label: 'Recent',
	},
	{
		name: 'basic',
		label: 'Basic',
	},
];

function SelectTemplateBody( {
	hasEmailPosts,
	templates,
	handleTemplateSelection,
	templateSelectMode,
} ) {
	const [ selectedCategory, setSelectedCategory ] = useState(
		TemplateCategories[ 1 ].name // Show the “Basic” category by default
	);

	const hideRecentCategory = templateSelectMode === 'swap';

	const displayCategories = TemplateCategories.filter(
		( { name } ) => name !== 'recent' || ! hideRecentCategory
	);

	const handleCategorySelection = ( category: TemplateCategory ) => {
		recordEvent( 'template_select_modal_category_change', { category } );
		setSelectedCategory( category );
	};

	useEffect( () => {
		setTimeout( () => {
			if ( hasEmailPosts && ! hideRecentCategory ) {
				setSelectedCategory( TemplateCategories[ 0 ].name );
			}
		}, 1000 ); // using setTimeout to ensure the template styles are available before block preview
	}, [ hasEmailPosts, hideRecentCategory ] );

	return (
		<div className="block-editor-block-patterns-explorer">
			<TemplateCategoriesListSidebar
				templateCategories={ displayCategories }
				selectedCategory={ selectedCategory }
				onClickCategory={ handleCategorySelection }
			/>

			<TemplateList
				templates={ templates }
				onTemplateSelection={ handleTemplateSelection }
				selectedCategory={ selectedCategory }
			/>
		</div>
	);
}

const MemorizedSelectTemplateBody = memo( SelectTemplateBody );

export function SelectTemplateModal( {
	onSelectCallback,
	closeCallback = null,
	previewContent = '',
} ) {
	const templateSelectMode = previewContent ? 'swap' : 'new';
	recordEventOnce( 'template_select_modal_opened', { templateSelectMode } );

	const [ templates, emailPosts, hasEmailPosts ] =
		usePreviewTemplates( previewContent );

	const hasTemplates = templates?.length > 0;

	const handleTemplateSelection = ( template: TemplatePreview ) => {
		const templateIsPostContent = template.type === editorCurrentPostType;

		const postContent = template.template as unknown as EmailEditorPostType;

		recordEvent( 'template_select_modal_template_selected', {
			templateSlug: template.slug,
			templateSelectMode,
			templateType: template.type,
		} );

		// When we provide previewContent, we don't want to reset the blocks
		if ( ! previewContent ) {
			void dispatch( editorStore ).resetEditorBlocks(
				template.emailParsed
			);
		}

		void dispatch( storeName ).setTemplateToPost(
			templateIsPostContent ? postContent.template : template.slug
		);
		onSelectCallback();
	};

	const handleCloseWithoutSelection = () => {
		const template = templates[ 0 ] ?? null;
		if ( ! template ) {
			return;
		} // Prevent closing when templates are not loaded
		recordEvent(
			'template_select_modal_handle_close_without_template_selected'
		);
		handleTemplateSelection( template );
	};

	return (
		<Modal
			title={
				templateSelectMode === 'new'
					? __( 'Start with an email preset', 'woocommerce' )
					: __( 'Select a template', 'woocommerce' )
			}
			onRequestClose={ () => {
				recordEvent( 'template_select_modal_closed', {
					templateSelectMode,
				} );
				return closeCallback
					? closeCallback()
					: handleCloseWithoutSelection();
			} }
			isFullScreen
		>
			<MemorizedSelectTemplateBody
				hasEmailPosts={ hasEmailPosts }
				templates={ [ ...templates, ...emailPosts ] }
				handleTemplateSelection={ handleTemplateSelection }
				templateSelectMode={ templateSelectMode }
			/>

			<Flex className="email-editor-modal-footer" justify="flex-end">
				<FlexItem>
					<Button
						variant="tertiary"
						className="email-editor-start_from_scratch_button"
						onClick={ () => {
							recordEvent(
								'template_select_modal_start_from_scratch_clicked'
							);
							return handleCloseWithoutSelection();
						} }
						isBusy={ ! hasTemplates }
					>
						{ __( 'Start from scratch', 'woocommerce' ) }
					</Button>
				</FlexItem>
			</Flex>
		</Modal>
	);
}
