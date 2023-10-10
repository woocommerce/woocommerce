/**
 * External dependencies
 */
import type { BlockAttributes } from '@wordpress/blocks';
import deprecated from '@wordpress/deprecated';
import { createElement, useMemo } from '@wordpress/element';
import { InnerBlocks } from '@wordpress/block-editor';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { DisplayState } from '@woocommerce/components';
import { evaluate } from '@woocommerce/expression-evaluation';

/**
 * Internal dependencies
 */
import { ProductEditorBlockEditProps } from '../../../types';

export interface ConditionalBlockAttributes extends BlockAttributes {
	mustMatch: Record< string, Array< string > >;
}

export function Edit( {
	attributes,
	context,
}: ProductEditorBlockEditProps< ConditionalBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );
	const { mustMatch, showIf } = attributes;

	if ( mustMatch ) {
		deprecated( '`mustMatch` attribute in woocommerce/conditional block', {
			alternative: '`showIf` attribute in woocommerce/conditional block',
		} );
	}

	const displayBlocks = useMemo( () => {
		if ( mustMatch && context.editedProduct ) {
			for ( const [ prop, values ] of Object.entries( mustMatch ) ) {
				if ( ! values.includes( context.editedProduct[ prop ] ) ) {
					return false;
				}
			}
		}

		return evaluate( showIf, context );
	}, [ mustMatch, showIf, context ] );

	return (
		<div { ...blockProps }>
			<DisplayState
				state={ displayBlocks ? 'visible' : 'visually-hidden' }
			>
				<InnerBlocks templateLock="all" />
			</DisplayState>
		</div>
	);
}
