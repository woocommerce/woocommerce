/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';
import { createElement } from '@wordpress/element';
import { ToolbarButton } from '@wordpress/components';
// import { ContextToolbarButton } from '../components/ContextToolbarButton';
// import { FixedToolbarButton } from '../components/FixedToolbarButton';
// import { headingOne } from '../icons/headingOne';
// import { headingThree } from '../icons/headingThree';
// import { headingTwo } from '../icons/headingTwo';
// import { useReplaceSelectedBlocks } from '../utils/replace-selected-blocks';

type HeadingLevel = 1 | 2 | 3;

type HeadingTransformProps = {
	headingLevel: HeadingLevel;
	isContextMenu: boolean;
};

// const icons = [ headingOne, headingTwo, headingThree ];

const allBlocksHaveHeading = (
	headingLevel: HeadingLevel,
	blocks: BlockInstance[]
) => {
	// This logic is reversed from `every()` because that way
	// it can stop as soon as it's invalidated
	return ! blocks.some( ( block ) => {
		return ! (
			block.name === 'core/heading' &&
			block?.attributes?.level === headingLevel
		);
	} );
};

export const HeadingTransform: React.VFC< HeadingTransformProps > = ( {
	headingLevel,
	isContextMenu,
} ) => {
	// const { replaceSelectedBlocks, blocks, swapBlocksFn } =
	// 	useReplaceSelectedBlocks();

	// const Button = isContextMenu ? ContextToolbarButton : FixedToolbarButton;

	return (
		<ToolbarButton
			icon={ 'test' }
			title={ 'Heading' }
			onClick={ () => console.log( 'clicked' ) }
			isActive={ false }
		/>
	);
};
