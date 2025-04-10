/**
 * External dependencies
 */
import { useRefEffect } from '@wordpress/compose';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { unlockGetEnabledClientIdsTree } from '../../../private-apis';

const DISTANCE_THRESHOLD = 500;

function clamp( value: number, min: number, max: number ) {
	return Math.min( Math.max( value, min ), max );
}

function distanceFromRect( x: number, y: number, rect ) {
	const dx = x - clamp( x, Number( rect.left ), Number( rect.right ) );
	const dy = y - clamp( y, Number( rect.top ), Number( rect.bottom ) );
	return Math.sqrt( dx * dx + dy * dy );
}

export default function useSelectNearestEditableBlock( {
	isEnabled = true,
} = {} ) {
	const getEnabledClientIdsTree = unlockGetEnabledClientIdsTree(
		useSelect( blockEditorStore )
	);
	const { getBlockName, getBlockOrder } = useSelect( blockEditorStore );

	const { selectBlock } = useDispatch( blockEditorStore );

	return useRefEffect(
		( element: Element ) => {
			if ( ! isEnabled ) {
				return null;
			}

			const selectNearestEditableBlock = ( x, y ) => {
				const editableBlockClientIds =
					getEnabledClientIdsTree().flatMap( ( { clientId } ) => {
						// @ts-expect-error getBlockName is not typed
						const blockName = getBlockName( clientId );
						if ( blockName === 'core/template-part' ) {
							return [];
						}
						if ( blockName === 'core/post-content' ) {
							// @ts-expect-error getBlockOrder is not typed
							const innerBlocks = getBlockOrder( clientId );
							if ( innerBlocks.length ) {
								return innerBlocks as string[];
							}
						}
						return [ clientId as string ];
					} );

				// Extract the nearest client ID
				const nearestClientIdData = editableBlockClientIds.reduce(
					(
						acc: { clientId: string; distance: number },
						clientId: string
					) => {
						const block = element.querySelector(
							`[data-block="${ clientId }"]`
						);
						if ( ! block ) {
							return acc;
						}
						const rect = block.getBoundingClientRect();
						const distance = distanceFromRect(
							Number( x ),
							Number( y ),
							rect
						);

						if (
							distance < acc.distance &&
							distance < DISTANCE_THRESHOLD
						) {
							return { clientId, distance };
						}
						return acc;
					},
					{ clientId: null, distance: Number.POSITIVE_INFINITY }
				);

				const nearestClientId = nearestClientIdData?.clientId || '';
				if ( nearestClientId ) {
					void selectBlock( nearestClientId as string );
				}
			};

			const handleClick = ( event ) => {
				const shouldSelect =
					event.target === element ||
					event.target.classList.contains( 'is-root-container' );
				if ( shouldSelect ) {
					selectNearestEditableBlock( event.clientX, event.clientY );
				}
			};

			element.addEventListener( 'click', handleClick );
			return () => element.removeEventListener( 'click', handleClick );
		},
		[ isEnabled ]
	);
}
