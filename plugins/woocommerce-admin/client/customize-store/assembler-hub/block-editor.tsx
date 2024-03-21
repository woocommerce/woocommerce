/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';
import { memo } from 'react';

/**
 * Internal dependencies
 */

import BlockPreview from './block-preview';
import Iframe from './iframe';
import { ChangeHandler } from './hooks/use-editor-blocks';

export const BlockEditor = memo(
	( {
		renderedBlocks,
		settings,
		additionalStyles,
		isScrollable,
		onClickNavigationItem,
		onChange,
	}: {
		renderedBlocks: BlockInstance[];
		settings: Record< string, unknown > & {
			styles: string[];
		};
		additionalStyles: string;
		isScrollable: boolean;
		onClickNavigationItem: ( event: MouseEvent ) => void;
		onChange: ChangeHandler;
	} ) => {
		return (
			<div className="woocommerce-customize-store__block-editor">
				<div className={ 'woocommerce-block-preview-container' }>
					<BlockPreview
						blocks={ renderedBlocks }
						onChange={ onChange }
						settings={ settings }
						additionalStyles={ additionalStyles }
						isNavigable={ false }
						isScrollable={ isScrollable }
						onClickNavigationItem={ onClickNavigationItem }
						// Don't use sub registry so that we can get the logo block from the main registry on the logo sidebar navigation screen component.
						useSubRegistry={ false }
						autoScale={ false }
						setLogoBlockContext={ true }
						CustomIframeComponent={ Iframe }
					/>
				</div>
			</div>
		);
	}
);
