// Reference: https://github.com/WordPress/gutenberg/blob/94ff2ba55379d9ad7f6bed743b20b85ff26cf56d/packages/block-editor/src/components/block-patterns-list/index.js#L1
/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import {
	VisuallyHidden,
	__unstableComposite as Composite,
	__unstableUseCompositeState as useCompositeState,
	__unstableCompositeItem as CompositeItem,
	Tooltip,
} from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { useMemo } from '@wordpress/element';
import { store as blockEditorStore } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import BlockPreview from './block-preview';
import Iframe from './iframe';

const WithToolTip = ( { showTooltip, title, children } ) => {
	if ( showTooltip ) {
		return <Tooltip text={ title }>{ children }</Tooltip>;
	}
	return <>{ children }</>;
};

function BlockPattern( { pattern, onClick, onHover, composite, showTooltip } ) {
	const { blocks, viewportWidth } = pattern;
	const instanceId = useInstanceId( BlockPattern );
	const descriptionId = `block-editor-block-patterns-list__item-description-${ instanceId }`;
	const originalSettings = useSelect(
		( select ) => select( blockEditorStore ).getSettings(),
		[]
	);
	const settings = useMemo(
		() => ( { ...originalSettings, __unstableIsPreviewMode: true } ),
		[ originalSettings ]
	);
	return (
		<div>
			<div className="block-editor-block-patterns-list__list-item">
				<WithToolTip
					showTooltip={ showTooltip }
					title={ pattern.title }
				>
					<CompositeItem
						role="option"
						as="div"
						{ ...composite }
						className="block-editor-block-patterns-list__item"
						onClick={ () => {
							onClick( pattern, blocks );
							onHover?.( null );
						} }
						onMouseEnter={ () => {
							onHover?.( pattern );
						} }
						onMouseLeave={ () => onHover?.( null ) }
						aria-label={ pattern.title }
						aria-describedby={
							pattern.description ? descriptionId : undefined
						}
					>
						<BlockPreview
							blocks={ blocks }
							viewportWidth={ viewportWidth || 1200 }
							additionalStyles=""
							useSubRegistry={ true }
							settings={ settings }
							isScrollable={ false }
							autoScale={ true }
							CustomIframeComponent={ Iframe }
						/>
						{ ! showTooltip && (
							<div className="block-editor-block-patterns-list__item-title">
								{ pattern.title }
							</div>
						) }
						{ !! pattern.description && (
							<VisuallyHidden id={ descriptionId }>
								{ pattern.description }
							</VisuallyHidden>
						) }
					</CompositeItem>
				</WithToolTip>
			</div>
		</div>
	);
}

function BlockPatternPlaceholder() {
	return (
		<div className="block-editor-block-patterns-list__item is-placeholder" />
	);
}

function BlockPatternList( {
	isDraggable,
	blockPatterns,
	shownPatterns,
	onHover,
	onClickPattern,
	orientation,
	label = __( 'Block Patterns', 'woocommerce' ),
	showTitlesAsTooltip,
} ) {
	const composite = useCompositeState( { orientation } );
	return (
		<Composite
			{ ...composite }
			role="listbox"
			className="block-editor-block-patterns-list"
			aria-label={ label }
		>
			{ blockPatterns.map( ( pattern ) => {
				const isShown = shownPatterns.includes( pattern );
				return isShown ? (
					<BlockPattern
						key={ pattern.name }
						pattern={ pattern }
						onClick={ onClickPattern }
						onHover={ onHover }
						isDraggable={ isDraggable }
						composite={ composite }
						showTooltip={ showTitlesAsTooltip }
					/>
				) : (
					<BlockPatternPlaceholder key={ pattern.name } />
				);
			} ) }
		</Composite>
	);
}

export default BlockPatternList;
