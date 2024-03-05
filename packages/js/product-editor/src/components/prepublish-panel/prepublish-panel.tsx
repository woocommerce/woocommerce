/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement, Fragment } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';
import { useEntityProp } from '@wordpress/core-data';
import { closeSmall } from '@wordpress/icons';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import { PublishButton } from '../header/publish-button';
import { PrepublishPanelProps } from './types';
import { store as productEditorUiStore } from '../../store/product-editor-ui';
import { TRACKS_SOURCE } from '../../constants';
import { VisibilitySection } from './visibility-section';
import { ScheduleSection } from './schedule-section';
import { ShowPrepublishChecksSection } from './show-prepublish-checks-section';

export function PrepublishPanel( {
	productType = 'product',
	title = __( 'Are you ready to publish this product?', 'woocommerce' ),
	description = __(
		'Double-check your settings before sharing this product with customers.',
		'woocommerce'
	),
}: PrepublishPanelProps ) {
	const [ editedDate, , date ] = useEntityProp< string >(
		'postType',
		productType,
		'date_created'
	);
	const [ editedProductName ] = useEntityProp< string >(
		'postType',
		productType,
		'name'
	);
	const [ productStatus ] = useEntityProp< string >(
		'postType',
		productType,
		'status'
	);
	const [ permalink ] = useEntityProp< string >(
		'postType',
		productType,
		'permalink'
	);
	const { closePrepublishPanel } = useDispatch( productEditorUiStore );

	const isPublished =
		productType === 'product' ? productStatus === 'publish' : true;

	if ( editedDate !== date ) {
		title = __( 'Are you ready to schedule this product?', 'woocommerce' );
		description = __(
			'Your product will be published at the specified date and time.',
			'woocommerce'
		);
	}

	function getHeaderActions() {
		if ( isPublished ) {
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

	let productURL: URL | undefined;
	if ( typeof permalink === 'string' ) {
		productURL = new URL( permalink );
	}

	function getPanelTitle() {
		if ( isPublished ) {
			return (
				<div className="woocommerce-product-publish-panel__published">
					<a
						className="woocommerce-product-list__product-name"
						href={ productURL?.toString() }
						target="_blank"
						rel="noreferrer"
					>
						{ editedProductName }
					</a>
					&nbsp;
					{ __( 'is now live.', 'woocommerce' ) }
				</div>
			);
		}
		return (
			<>
				<h4>{ title }</h4>
				<span>{ description }</span>
			</>
		);
	}

	function getPanelSections() {
		if ( isPublished ) {
			return null;
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
			className={ classnames( 'woocommerce-product-publish-panel', {
				'is-published': isPublished,
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
			<div className="woocommerce-product-publish-panel__footer">
				<ShowPrepublishChecksSection />
			</div>
		</div>
	);
}
