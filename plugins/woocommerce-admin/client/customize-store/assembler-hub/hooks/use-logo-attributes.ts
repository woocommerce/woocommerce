/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { useContext } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import {
	store as blockEditorStore,
	// @ts-ignore No types for this exist yet.
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { LogoBlockContext } from '../logo-block-context';

export type LogoAttributes = Partial< {
	align: string;
	width: number;
	height: number;
	isLink: boolean;
	linkTarget: string;
	shouldSyncIcon: boolean;
} >;

export const useLogoAttributes = () => {
	// Get the current logo block client ID and attributes. These are used for the logo settings.
	const { logoBlockIds } = useContext( LogoBlockContext );
	const {
		attributes,
		isAttributesLoading,
	}: {
		attributes: LogoAttributes;
		isAttributesLoading: boolean;
	} = useSelect(
		( select ) => {
			const logoBlocks =
				// @ts-ignore No types for this exist yet.
				select( blockEditorStore ).getBlocksByClientId( logoBlockIds );
			const _isAttributesLoading =
				! logoBlocks.length || logoBlocks[ 0 ] === null;

			if ( _isAttributesLoading ) {
				return {
					attributes: {},
					isAttributesLoading: _isAttributesLoading,
				};
			}

			return {
				attributes: logoBlocks[ 0 ].attributes,
				isAttributesLoading: _isAttributesLoading,
			};
		},
		[ logoBlockIds ]
	);

	return {
		attributes,
		isAttributesLoading,
	};
};
