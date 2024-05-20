/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement, Fragment, useRef, useEffect } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';
import { useEntityProp } from '@wordpress/core-data';
import { closeSmall } from '@wordpress/icons';
import classnames from 'classnames';
import type { Product } from '@woocommerce/data';
import { isInTheFuture } from '@wordpress/date';

/**
 * Internal dependencies
 */
import { PublishButton } from '../header/publish-button';
import { PrepublishPanelProps } from './types';
import { store as productEditorUiStore } from '../../store/product-editor-ui';
import { TRACKS_SOURCE } from '../../constants';
import { VisibilitySection } from './visibility-section';
import { ScheduleSection } from './schedule-section';
import { PostPublishSection, PostPublishTitle } from './post-publish';

export function PrepublishPanel( {
	productType = 'product',
	title = __( 'Are you ready to publish this product?', 'woocommerce' ),
	description = __(
		'Double-check your settings before sharing this product with customers.',
		'woocommerce'
	),
}: PrepublishPanelProps ) {
	const [ editedDate ] = useEntityProp< string >(
		'postType',
		productType,
		'date_created_gmt'
	);

	const [ productStatus, , prevStatus ] = useEntityProp<
		Product[ 'status' ]
	>( 'postType', productType, 'status' );

	const { closePrepublishPanel } = useDispatch( productEditorUiStore );

	const isPublishedOrScheduled =
		productType === 'product' && prevStatus !== 'future'
			? productStatus === 'publish'
			: true;

	if ( isInTheFuture( editedDate ) ) {
		title = __( 'Are you ready to schedule this product?', 'woocommerce' );
		description = __(
			'Your product will be published at the specified date and time.',
			'woocommerce'
		);
	}
	const panelRef = useRef< HTMLDivElement >( null );

	function handleClickOutside( event: MouseEvent ) {
		if (
			panelRef.current &&
			! panelRef.current.contains( event.target as Node )
		) {
			closePrepublishPanel();
		}
	}

	useEffect( () => {
		if ( ! isPublishedOrScheduled ) {
			return;
		}
		document.addEventListener( 'mouseup', handleClickOutside );
		return () => {
			document.removeEventListener( 'mouseup', handleClickOutside );
		};
	}, [ isPublishedOrScheduled ] );

	function getHeaderActions() {
		if ( isPublishedOrScheduled ) {
			return (
				<Button
					className="woocommerce-publish-panel-close"
					icon={ closeSmall }
					label={ __( 'Close panel', 'woocommerce' ) }
					onClick={ () => {
						recordEvent( 'product_prepublish_panel', {
							source: TRACKS_SOURCE,
							action: 'close',
						} );
						closePrepublishPanel();
					} }
				/>
			);
		}
		return (
			<>
				<PublishButton productType={ productType } />
				<Button
					variant={ 'secondary' }
					onClick={ () => {
						recordEvent( 'product_prepublish_panel', {
							source: TRACKS_SOURCE,
							action: 'cancel',
						} );
						closePrepublishPanel();
					} }
				>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
			</>
		);
	}

	function getPanelTitle() {
		if ( isPublishedOrScheduled ) {
			return <PostPublishTitle productType={ productType } />;
		}
		return (
			<>
				<h4>{ title }</h4>
				<span>{ description }</span>
			</>
		);
	}

	function getPanelSections() {
		if ( isPublishedOrScheduled ) {
			return <PostPublishSection postType={ productType } />;
		}
		return (
			<>
				<VisibilitySection productType={ productType } />
				<ScheduleSection postType={ productType } />
			</>
		);
	}

	return (
		<div
			ref={ panelRef }
			className={ classnames( 'woocommerce-product-publish-panel', {
				'is-published': isPublishedOrScheduled,
			} ) }
		>
			<div className="woocommerce-product-publish-panel__header">
				{ getHeaderActions() }
			</div>
			<div className="woocommerce-product-publish-panel__title">
				{ getPanelTitle() }
			</div>
			<div className="woocommerce-product-publish-panel__content">
				{ getPanelSections() }
			</div>
			<div className="woocommerce-product-publish-panel__footer" />
		</div>
	);
}
