/**
 * External dependencies
 */
import { hasBlockSupport, getBlockSupport } from '@wordpress/blocks';
import { Fill, Notice } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

export const hasBackgroundImageSupport = ( nameOrType: string ) => {
	const backgroundSupport = getBlockSupport(
		nameOrType, // @ts-expect-error not yet supported in the types
		'background'
	) as Record< string, boolean >;

	return backgroundSupport && backgroundSupport?.backgroundImage !== false;
};

export function BlockCompatibilityWarnings(): JSX.Element {
	const { selectedBlock, canChangeStyles } = useSelect( ( sel ) => {
		const blockEditor = sel( 'core/block-editor' );
		// getBlockEditingMode is missing from the block editor store types.
		const { getBlockEditingMode } = blockEditor as unknown as {
			getBlockEditingMode: ( clientId: string ) => string;
		};
		const clientId = blockEditor.getSelectedBlockClientId();
		return {
			selectedBlock: blockEditor.getSelectedBlock(),
			// Template areas are locked, so there are no style controls to warn about.
			canChangeStyles:
				!! clientId && getBlockEditingMode( clientId ) === 'default',
		};
	}, [] );

	if ( ! canChangeStyles ) {
		return null;
	}

	// Check if the selected block has enabled border configuration
	const hasBorderSupport =
		hasBlockSupport(
			selectedBlock?.name,
			// @ts-expect-error Border is not yet supported in the types
			'border',
			false
		) ||
		// Core blocks and core's border support still use the __experimentalBorder key.
		hasBlockSupport(
			selectedBlock?.name,
			// @ts-expect-error Border is not yet supported in the types
			'__experimentalBorder',
			false
		);

	return (
		<>
			{ hasBorderSupport && (
				<Fill name="InspectorControlsBorder">
					<Notice
						className="woocommerce-grid-full-width"
						status="warning"
						isDismissible={ false }
					>
						{ __(
							'Border display may vary or be unsupported in some email clients.',
							__i18n_text_domain__
						) }
						<br />
						{ __(
							'Units other than pixels (px) lack support in old email clients.',
							__i18n_text_domain__
						) }
					</Notice>
				</Fill>
			) }
			{ hasBackgroundImageSupport( selectedBlock?.name ) && (
				<Fill name="InspectorControlsBackground">
					<Notice
						className="woocommerce-grid-full-width"
						status="warning"
						isDismissible={ false }
					>
						{ __(
							'Select a background color for email clients that do not support background images.',
							__i18n_text_domain__
						) }
					</Notice>
				</Fill>
			) }
		</>
	);
}
