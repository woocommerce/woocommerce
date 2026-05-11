/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { PREVIEW_ITEMS } from '../../preview-items';

// Keep the editor's count badge in sync with what the parent shows in
// its preview grid — both derive from the same array so they can't drift.
const PREVIEW_COUNT = PREVIEW_ITEMS.length;

// Default child for the header: one core/heading the merchant can edit
// like any other heading. Locked from move/remove so the heading can't
// be deleted or rearranged out of the header — only its text and
// per-heading supports are editable. Item-count suffix is rendered
// alongside as a non-editable span (PHP + iAPI).
const HEADER_TEMPLATE: [
	string,
	Record< string, unknown >
][] = [
	[
		'core/heading',
		{
			level: 2,
			content: __( 'Saved for later', 'woocommerce' ),
			lock: { remove: true, move: true },
		},
	],
];

const Edit = (): JSX.Element => {
	const blockProps = useBlockProps( {
		className: 'wc-block-shopper-collection-header',
	} );
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'wc-block-shopper-collection-header__heading-slot' },
		{
			template: HEADER_TEMPLATE,
			templateLock: 'all',
		}
	);

	return (
		<div { ...blockProps }>
			<div { ...innerBlocksProps } />
			<span className="wc-block-shopper-collection-header__count">
				{ sprintf(
					/* translators: %d: number of saved items. */
					__( '(%d items)', 'woocommerce' ),
					PREVIEW_COUNT
				) }
			</span>
		</div>
	);
};

export default Edit;
