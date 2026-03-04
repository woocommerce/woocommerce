/**
 * External dependencies
 */
import {
	useBlockProps,
	useInnerBlocksProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { Spinner } from '@wordpress/components';
import { store as coreStore, useEntityRecords } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { createBlock } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import './editor.scss';

interface PrivacyLinkRecord {
	id: number;
	label: string;
	url: string;
	page_id?: number;
	status?: string;
	edit_url?: string;
}

const ENTITY_CONFIG = {
	name: 'privacy-link',
	kind: 'wc/v3',
	baseURL: '/wc/v3/privacy-links',
	label: __( 'Privacy Link', 'woocommerce' ),
	plural: __( 'Privacy Links', 'woocommerce' ),
	key: 'id',
};

const CHILD_BLOCK_NAME = 'woocommerce/privacy-link';

interface EditProps {
	clientId: string;
}

export default function Edit( { clientId }: EditProps ) {
	const blockProps = useBlockProps();
	const registered = useRef( false );
	const synced = useRef( false );
	const { addEntities } = useDispatch( coreStore );
	const { replaceInnerBlocks } = useDispatch( blockEditorStore );

	const savedOrder = useSelect(
		( select ) => {
			const store = select( blockEditorStore ) as unknown as {
				getBlocks: ( id: string ) => Array< {
					attributes: Record< string, unknown >;
				} >;
			};
			return store
				.getBlocks( clientId )
				.map( ( b ) => b.attributes.pageId as number );
		},
		[ clientId ]
	);

	useEffect( () => {
		if ( ! registered.current ) {
			registered.current = true;
			addEntities( [ ENTITY_CONFIG ] );
		}
	}, [ addEntities ] );

	const { records: links, isResolving } =
		useEntityRecords< PrivacyLinkRecord >(
			ENTITY_CONFIG.kind,
			ENTITY_CONFIG.name,
			{}
		);

	// Reconcile inner blocks with REST data on mount.
	// Preserves saved order for known links, appends new ones, removes stale ones.
	useEffect( () => {
		if ( isResolving || ! links || synced.current ) {
			return;
		}
		synced.current = true;

		const linksByPageId = new Map(
			links.map( ( link ) => [ link.page_id ?? 0, link ] )
		);

		const makeBlock = ( link: PrivacyLinkRecord ) =>
			createBlock( CHILD_BLOCK_NAME, {
				label: link.label,
				url: link.url,
				pageId: link.page_id ?? 0,
				status: link.status ?? 'publish',
				editUrl: link.edit_url ?? '',
			} );

		// Start with saved order — keep only links still in REST data.
		const ordered: PrivacyLinkRecord[] = [];
		const used = new Set< number >();
		for ( const pageId of savedOrder ) {
			const link = linksByPageId.get( pageId );
			if ( link ) {
				ordered.push( link );
				used.add( pageId );
			}
		}

		// Append any new links not in saved order.
		for ( const link of links ) {
			if ( ! used.has( link.page_id ?? 0 ) ) {
				ordered.push( link );
			}
		}

		replaceInnerBlocks(
			clientId,
			ordered.map( makeBlock )
		);
	}, [ links, isResolving, clientId, replaceInnerBlocks, savedOrder ] );

	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		allowedBlocks: [ CHILD_BLOCK_NAME ],
		templateLock: false,
		renderAppender: false,
	} );

	if ( isResolving ) {
		return (
			<div { ...blockProps }>
				<Spinner />
			</div>
		);
	}

	if ( ! links?.length ) {
		return (
			<ul { ...blockProps }>
				<li className="wp-block-woocommerce-privacy-links__item wp-block-woocommerce-privacy-links__empty">
					{ __( 'No policy pages configured.', 'woocommerce' ) }
				</li>
			</ul>
		);
	}

	return <ul { ...innerBlocksProps } />;
}
