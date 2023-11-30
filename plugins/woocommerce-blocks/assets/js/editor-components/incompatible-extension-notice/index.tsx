/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import {
	Notice,
	ExternalLink,
	Button,
	TabbableContainer,
	Modal,
} from '@wordpress/components';
import {
	createInterpolateElement,
	useEffect,
	useState,
} from '@wordpress/element';
import { Alert } from '@woocommerce/icons';
import { Icon, chevronDown } from '@wordpress/icons';
import { useDispatch, useSelect } from '@wordpress/data';
import { createBlock, BlockInstance } from '@wordpress/blocks';
import { store as noticesStore } from '@wordpress/notices';
import { store as coreStore } from '@wordpress/core-data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { recordEvent } from '@woocommerce/tracks';
import { findBlock } from '@woocommerce/utils';

/**
 * Internal dependencies
 */
import { useCombinedIncompatibilityNotice } from './use-combined-incompatibility-notice';
import { ModalContent } from './modal';
import './editor.scss';

interface ExtensionNoticeProps {
	toggleDismissedStatus: ( status: boolean ) => void;
	block: 'woocommerce/cart' | 'woocommerce/checkout';
	clientId: string;
}

/**
 * Shows a notice when there are incompatible extensions.
 *
 * Tracks events:
 * - switch_to_classic_shortcode_click
 * - switch_to_classic_shortcode_confirm
 * - switch_to_classic_shortcode_cancel
 * - switch_to_classic_shortcode_undo
 */
export function IncompatibleExtensionsNotice( {
	toggleDismissedStatus,
	block,
	clientId,
}: ExtensionNoticeProps ) {
	const [
		isVisible,
		dismissNotice,
		incompatibleExtensions,
		incompatibleExtensionsCount,
	] = useCombinedIncompatibilityNotice( block );
	const [ isOpen, setOpen ] = useState( false );
	const openModal = () => setOpen( true );
	const closeModal = () => setOpen( false );
	const { createInfoNotice } = useDispatch( noticesStore );
	const { replaceBlock, selectBlock } = useDispatch( blockEditorStore );
	const { undo } = useDispatch( coreStore );
	const { getBlocks } = useSelect( ( select ) => {
		return {
			getBlocks: select( blockEditorStore ).getBlocks,
		};
	}, [] );

	useEffect( () => {
		toggleDismissedStatus( ! isVisible );
	}, [ isVisible, toggleDismissedStatus ] );

	if ( ! isVisible ) {
		return null;
	}

	const switchButtonLabel =
		block === 'woocommerce/cart'
			? __( 'Switch to classic cart', 'woo-gutenberg-products-block' )
			: __(
					'Switch to classic checkout',
					'woo-gutenberg-products-block'
			  );

	const snackbarLabel =
		block === 'woocommerce/cart'
			? __( 'Switched to classic cart.', 'woo-gutenberg-products-block' )
			: __(
					'Switched to classic checkout.',
					'woo-gutenberg-products-block'
			  );

	const noticeContent = (
		<>
			{ incompatibleExtensionsCount > 1
				? createInterpolateElement(
						__(
							'Some active extensions do not yet support this block. This may impact the shopper experience. <a>Learn more</a>',
							'woo-gutenberg-products-block'
						),
						{
							a: (
								<ExternalLink href="https://woocommerce.com/document/cart-checkout-blocks-support-status/" />
							),
						}
				  )
				: createInterpolateElement(
						sprintf(
							// translators: %s is the name of the extension.
							__(
								'<strong>%s</strong> does not yet support this block. This may impact the shopper experience. <a>Learn more</a>',
								'woo-gutenberg-products-block'
							),
							Object.values( incompatibleExtensions )[ 0 ]
						),
						{
							strong: <strong />,
							a: (
								<ExternalLink href="https://woocommerce.com/document/cart-checkout-blocks-support-status/" />
							),
						}
				  ) }
		</>
	);

	const selectClassicShortcodeBlock = () => {
		const classicShortcodeBlock = findBlock( {
			blocks: getBlocks(),
			findCondition: ( foundBlock: BlockInstance ) =>
				foundBlock.name === 'woocommerce/classic-shortcode',
		} );

		if ( classicShortcodeBlock ) {
			selectBlock( classicShortcodeBlock.clientId );
		}
	};

	const entries = Object.entries( incompatibleExtensions );
	const remainingEntries = entries.length - 2;

	return (
		<Notice
			className="wc-blocks-incompatible-extensions-notice"
			status={ 'warning' }
			onRemove={ dismissNotice }
			spokenMessage={ noticeContent }
		>
			<div className="wc-blocks-incompatible-extensions-notice__content">
				<Icon
					className="wc-blocks-incompatible-extensions-notice__warning-icon"
					icon={ <Alert /> }
				/>
				<div>
					<p>{ noticeContent }</p>
					{ incompatibleExtensionsCount > 1 && (
						<ul>
							{ entries.slice( 0, 2 ).map( ( [ id, title ] ) => (
								<li
									key={ id }
									className="wc-blocks-incompatible-extensions-notice__element"
								>
									{ title }
								</li>
							) ) }
						</ul>
					) }

					{ entries.length > 2 && (
						<details>
							<summary>
								<span>
									{ sprintf(
										// translators: %s is the number of incompatible extensions.
										_n(
											'%s more incompatibility',
											'%s more incompatibilites',
											remainingEntries,
											'woo-gutenberg-products-block'
										),
										remainingEntries
									) }
								</span>
								<Icon icon={ chevronDown } />
							</summary>
							<ul>
								{ entries.slice( 2 ).map( ( [ id, title ] ) => (
									<li
										key={ id }
										className="wc-blocks-incompatible-extensions-notice__element"
									>
										{ title }
									</li>
								) ) }
							</ul>
						</details>
					) }

					<Button
						variant={ 'secondary' }
						onClick={ () => {
							recordEvent( 'switch_to_classic_shortcode_click', {
								shortcode:
									block === 'woocommerce/checkout'
										? 'checkout'
										: 'cart',
							} );
							openModal();
						} }
					>
						{ switchButtonLabel }
					</Button>
					{ isOpen && (
						<Modal
							size="medium"
							title={ switchButtonLabel }
							onRequestClose={ closeModal }
							className="wc-blocks-incompatible-extensions-notice-modal-content"
						>
							<ModalContent blockType={ block } />
							<TabbableContainer className="wc-blocks-incompatible-extensions-notice-modal-actions">
								<Button
									variant="primary"
									isDestructive={ true }
									onClick={ () => {
										replaceBlock(
											clientId,
											createBlock(
												'woocommerce/classic-shortcode',
												{
													shortcode:
														block ===
														'woocommerce/checkout'
															? 'checkout'
															: 'cart',
												}
											)
										);
										recordEvent(
											'switch_to_classic_shortcode_confirm',
											{
												shortcode:
													block ===
													'woocommerce/checkout'
														? 'checkout'
														: 'cart',
											}
										);
										selectClassicShortcodeBlock();
										createInfoNotice( snackbarLabel, {
											actions: [
												{
													label: __(
														'Undo',
														'woo-gutenberg-products-block'
													),
													onClick: () => {
														undo();
														recordEvent(
															'switch_to_classic_shortcode_undo',
															{
																shortcode:
																	block ===
																	'woocommerce/checkout'
																		? 'checkout'
																		: 'cart',
															}
														);
													},
												},
											],
											type: 'snackbar',
										} );
										closeModal();
									} }
								>
									{ __(
										'Switch',
										'woo-gutenberg-products-block'
									) }
								</Button>{ ' ' }
								<Button
									variant="secondary"
									onClick={ () => {
										recordEvent(
											'switch_to_classic_shortcode_cancel',
											{
												shortcode:
													block ===
													'woocommerce/checkout'
														? 'checkout'
														: 'cart',
											}
										);
										closeModal();
									} }
								>
									{ __(
										'Cancel',
										'woo-gutenberg-products-block'
									) }
								</Button>
							</TabbableContainer>
						</Modal>
					) }
				</div>
			</div>
		</Notice>
	);
}
