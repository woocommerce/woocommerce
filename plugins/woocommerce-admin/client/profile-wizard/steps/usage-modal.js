/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import interpolateComponents from '@automattic/interpolate-components';
import { Button, Modal } from '@wordpress/components';
import { Link } from '@woocommerce/components';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { initializeExPlat } from '@woocommerce/explat';
import { useDispatch } from '@wordpress/data';

function UsageModal( {
	onClose,
	onContinue,
	isDismissible = () => {},
	title = __( 'Build a better WooCommerce', 'woocommerce' ),
	message = interpolateComponents( {
		mixedString: __(
			'Get improved features and faster fixes by sharing non-sensitive data via {{link}}usage tracking{{/link}} ' +
				'that shows us how WooCommerce is used. No personal data is tracked or stored.',
			'woocommerce'
		),
		components: {
			link: (
				<Link
					href="https://woocommerce.com/usage-tracking?utm_medium=product"
					target="_blank"
					type="external"
				/>
			),
		},
	} ),
	dismissActionText = __( 'No thanks', 'woocommerce' ),
	acceptActionText = __( 'Yes, count me in!', 'woocommerce' ),
} ) {
	const { createNotice } = useDispatch( 'core/notices' );
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const { allowTracking, isResolving } = useSelect( ( select ) => {
		const { getOption, hasFinishedResolution } =
			select( OPTIONS_STORE_NAME );

		return {
			allowTracking: getOption( 'woocommerce_allow_tracking' ) === 'yes',
			isResolving:
				! hasFinishedResolution( 'getOption', [
					'woocommerce_allow_tracking',
				] ) ||
				typeof getOption( 'woocommerce_allow_tracking' ) ===
					'undefined',
		};
	} );
	const setEnableFunction = useRef( false );
	const [ isLoadingScripts, setIsLoadingScripts ] = useState( false );
	const [ isRequestStarted, setIsRequestStarted ] = useState( false );
	const [ selectedAction, setSelectedAction ] = useState( null );

	useEffect( () => {
		return () => {
			if (
				allowTracking &&
				setEnableFunction.current &&
				typeof window.wcTracks.enable === 'function'
			) {
				// remove overwrite onmount.
				window.wcTracks.enable( () => {} );
			}
		};
	}, [] );

	function updateTracking( { allowTracking } ) {
		if ( allowTracking && typeof window.wcTracks.enable === 'function' ) {
			setIsLoadingScripts( true );
			setEnableFunction.current = true;
			window.wcTracks.enable( () => {
				initializeExPlat();

				setIsLoadingScripts( false );
			} );
		} else if ( ! allowTracking ) {
			window.wcTracks.isEnabled = false;
		}

		const trackingValue = allowTracking ? 'yes' : 'no';
		setIsRequestStarted( true );
		updateOptions( {
			woocommerce_allow_tracking: trackingValue,
		} )
			.then( () => {
				onClose();
				onContinue();
				setIsRequestStarted( false );
			} )
			.then( () => {
				createNotice(
					'error',
					__(
						'There was a problem updating your preferences',
						'woocommerce'
					)
				);
				onClose();
				setIsRequestStarted( false );
			} );
	}

	if ( isResolving ) {
		return null;
	}
	if ( allowTracking ) {
		onClose();
		onContinue();
		return null;
	}

	const isBusy = isRequestStarted;

	return (
		<Modal
			title={ title }
			isDismissible={ isDismissible }
			onRequestClose={ () => onClose() }
			className="woocommerce-usage-modal"
		>
			<div className="woocommerce-usage-modal__wrapper">
				<div className="woocommerce-usage-modal__message">
					{ message }
				</div>
				<div className="woocommerce-usage-modal__actions">
					<Button
						isSecondary
						isBusy={ isBusy && selectedAction === 'dismiss' }
						disabled={ isBusy && selectedAction === 'accept' }
						onClick={ () => {
							setSelectedAction( 'dismiss' );
							updateTracking( { allowTracking: false } );
						} }
					>
						{ dismissActionText }
					</Button>
					<Button
						isPrimary
						isBusy={ isBusy && selectedAction === 'accept' }
						disabled={ isBusy && selectedAction === 'dismiss' }
						onClick={ () => {
							setSelectedAction( 'accept' );
							updateTracking( { allowTracking: true } );
						} }
					>
						{ acceptActionText }
					</Button>
				</div>
			</div>
		</Modal>
	);
}

export default UsageModal;
