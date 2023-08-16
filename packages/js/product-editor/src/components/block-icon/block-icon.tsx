/**
 * External dependencies
 */
import { BlockIcon as BaseBlockIcon } from '@wordpress/block-editor';
import { Block } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import { createElement, RawHTML } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { BlockIconProps } from './types';

export function BlockIcon( { clientId }: BlockIconProps ) {
	const icon = useSelect(
		( select ) => {
			// Try to get the icon from the block's attributes
			const {
				getBlockAttributes,
				getBlockName,
			}: {
				getBlockName: ( name: string ) => string;
				getBlockAttributes: ( name: string ) => {
					icon?: string;
				};
			} = select( 'core/block-editor' );
			const attributes = getBlockAttributes( clientId );
			if ( attributes?.icon ) {
				return attributes.icon;
			}

			// If there is no icon defined in attributes
			// Then try to get icon from block's metadata
			const {
				getBlockType,
			}: Record< string, ( name: string ) => unknown > =
				select( 'core/blocks' );
			const blockName = getBlockName( clientId );
			const block = getBlockType( blockName ) as Block;
			return block?.icon;
		},
		[ clientId ]
	);

	if ( ! icon ) {
		return null;
	}

	if ( typeof icon === 'object' ) {
		const { src, ...iconProps } = icon;

		if ( /^<(.)+>$/.test( src as string ) ) {
			const iconComponent = (
				<RawHTML aria-hidden="true" { ...iconProps }>
					{ src as string }
				</RawHTML>
			);
			return <BaseBlockIcon icon={ iconComponent } showColors />;
		}

		if ( /^https?:\/\/(.)+/.test( src as string ) ) {
			const iconImage = (
				<img
					src={ src as string }
					alt=""
					aria-hidden="true"
					{ ...iconProps }
					height={ 24 }
					width={ 24 }
				/>
			);
			return <BaseBlockIcon icon={ iconImage } showColors />;
		}
	}

	return <BaseBlockIcon icon={ icon } showColors />;
}
