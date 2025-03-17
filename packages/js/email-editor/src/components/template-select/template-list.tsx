/**
 * External dependencies
 */
import { useMemo, memo } from '@wordpress/element';
import { store as editorStore } from '@wordpress/editor';
import { useSelect } from '@wordpress/data';
import { Icon, info, blockDefault } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import {
	__experimentalHStack as HStack, // eslint-disable-line
} from '@wordpress/components';
// @ts-expect-error No types available for this component
// eslint-disable-next-line
import { BlockPreview } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { Async } from './async';
import { TemplateCategory, TemplatePreview } from '../../store';
import { useEmailCss } from '../../hooks';

type Props = {
	templates: TemplatePreview[];
	onTemplateSelection: ( template: TemplatePreview ) => void;
	selectedCategory?: TemplateCategory;
};

function TemplateNoResults() {
	return (
		<div className="block-editor-inserter__no-results">
			<Icon
				className="block-editor-inserter__no-results-icon"
				icon={ blockDefault }
			/>
			<p>{ __( 'No recent templates.', 'woocommerce' ) }</p>
			<p>
				{ __(
					'Your recent creations will appear here as soon as you begin.',
					'woocommerce'
				) }
			</p>
		</div>
	);
}

function TemplateListBox( {
	templates,
	onTemplateSelection,
	selectedCategory,
}: Props ) {
	const { layout } = useSelect( ( select ) => {
		const { getEditorSettings } = select( editorStore );
		const editorSettings = getEditorSettings();
		return {
			// @ts-expect-error There are no types for the experimental features settings.
			// eslint-disable-next-line no-underscore-dangle
			layout: editorSettings.__experimentalFeatures.layout,
		};
	} );

	const [ styles ] = useEmailCss();
	const css =
		styles.reduce( ( acc, style ) => {
			return acc + ( style.css ?? '' );
		}, '' ) +
		`.is-root-container { width: ${ layout.contentSize }; margin: 0 auto; }`;

	if ( selectedCategory === 'recent' && templates.length === 0 ) {
		return <TemplateNoResults />;
	}

	return (
		<div className="block-editor-block-patterns-list" role="listbox">
			{ templates.map( ( template ) => (
				<div
					key={ `${ template.slug }_${ template.displayName }_${ template.id }` }
					className="block-editor-block-patterns-list__list-item email-editor-pattern__list-item"
				>
					<div
						className="block-editor-block-patterns-list__item"
						role="button"
						tabIndex={ 0 }
						onClick={ () => {
							onTemplateSelection( template );
						} }
						onKeyPress={ ( event ) => {
							if ( event.key === 'Enter' || event.key === ' ' ) {
								onTemplateSelection( template );
							}
						} }
					>
						<Async
							placeholder={
								<p>
									{ __(
										'rendering template',
										'woocommerce'
									) }
								</p>
							}
						>
							<BlockPreview
								blocks={ template.previewContentParsed }
								viewportWidth={ 900 }
								minHeight={ 300 }
								additionalStyles={ [
									{
										css,
									},
								] }
							/>

							<HStack className="block-editor-patterns__pattern-details">
								<h4 className="block-editor-block-patterns-list__item-title">
									{ template.displayName }
								</h4>
							</HStack>
						</Async>
					</div>
				</div>
			) ) }
		</div>
	);
}

const compareProps = ( prev, next ) =>
	prev.templates.length === next.templates.length &&
	prev.selectedCategory === next.selectedCategory;

const MemorizedTemplateListBox = memo( TemplateListBox, compareProps );

export function TemplateList( {
	templates,
	onTemplateSelection,
	selectedCategory,
}: Props ) {
	const filteredTemplates = useMemo(
		() =>
			templates.filter(
				( template ) => template.category === selectedCategory
			),
		[ selectedCategory, templates ]
	);

	return (
		<div className="block-editor-block-patterns-explorer__list">
			{ selectedCategory === 'recent' && (
				<div className="email-editor-recent-templates-info">
					<HStack spacing={ 1 } expanded={ false } justify="start">
						<Icon icon={ info } />
						<p>
							{ __(
								'Templates created on the legacy editor will not appear here.',
								'woocommerce'
							) }
						</p>
					</HStack>
				</div>
			) }

			<MemorizedTemplateListBox
				templates={ filteredTemplates }
				onTemplateSelection={ onTemplateSelection }
				selectedCategory={ selectedCategory }
			/>
		</div>
	);
}
