/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Fragment, useEffect, useRef } from '@wordpress/element';
import PropTypes from 'prop-types';
import { PLUGINS_STORE_NAME } from '@woocommerce/data';

export function Connect( {
	setIsPending = () => {},
	onError,
	onConnect,
	hasErrors,
	onSkip,
	skipText,
	onAbort,
	abortText,
} ) {
	const { createNotice } = useDispatch( 'core/notices' );
	const prevIsRequesting = useRef( null );
	const { error, isRequesting, jetpackConnectUrl } = useSelect( () => {
		const { getJetpackConnectUrl, isPluginsRequesting, getPluginsError } =
			select( PLUGINS_STORE_NAME );

		const queryArgs = {
			redirect_url: props.redirectUrl || window.location.href,
		};

		return {
			error: getPluginsError( 'getJetpackConnectUrl' ) || '',
			isRequesting: isPluginsRequesting( 'getJetpackConnectUrl' ),
			jetpackConnectUrl: getJetpackConnectUrl( queryArgs ),
		};
	} );
	const [ isConnecting, setIsConnecting ] = useState( false );

	useEffect( () => {
		setIsPending( true );
	}, [] );

	useEffect( () => {
		if ( prevIsRequesting.current && ! isRequesting ) {
			setIsPending( false );
		}
		prevIsRequesting.current = isRequesting;

		if ( error && error !== prevProps.error ) {
			if ( onError ) {
				onError();
			}
			createNotice( 'error', error );
		}
	}, [ isRequesting, error ] );

	async function connectJetpack() {
		setIsConnecting( true, () => {
			if ( onConnect ) {
				onConnect();
			}
			window.location = jetpackConnectUrl;
		} );
	}

	return (
		<Fragment>
			{ hasErrors ? (
				<Button isPrimary onClick={ () => window.location.reload() }>
					{ __( 'Retry', 'woocommerce' ) }
				</Button>
			) : (
				<Button
					disabled={ isRequesting }
					isBusy={ isConnecting }
					isPrimary
					onClick={ connectJetpack }
				>
					{ __( 'Connect', 'woocommerce' ) }
				</Button>
			) }
			{ onSkip && (
				<Button onClick={ onSkip }>
					{ skipText || __( 'No thanks', 'woocommerce' ) }
				</Button>
			) }
			{ onAbort && (
				<Button onClick={ onAbort }>
					{ abortText || __( 'Abort', 'woocommerce' ) }
				</Button>
			) }
		</Fragment>
	);
}

Connect.propTypes = {
	/**
	 * Method to create a displayed notice.
	 */
	createNotice: PropTypes.func.isRequired,
	/**
	 * Human readable error message.
	 */
	error: PropTypes.string,
	/**
	 * Bool to determine if the "Retry" button should be displayed.
	 */
	hasErrors: PropTypes.bool,
	/**
	 * Bool to check if the connection URL is still being requested.
	 */
	isRequesting: PropTypes.bool,
	/**
	 * Generated Jetpack connection URL.
	 */
	jetpackConnectUrl: PropTypes.string,
	/**
	 * Called before the redirect to Jetpack.
	 */
	onConnect: PropTypes.func,
	/**
	 * Called when the plugin has an error retrieving the jetpackConnectUrl.
	 */
	onError: PropTypes.func,
	/**
	 * Called when the plugin connection is skipped.
	 */
	onSkip: PropTypes.func,
	/**
	 * Redirect URL to encode as a URL param for the connection path.
	 */
	redirectUrl: PropTypes.string,
	/**
	 * Text used for the skip connection button.
	 */
	skipText: PropTypes.string,
	/**
	 * Control the `isPending` logic of the parent containing the Stepper.
	 */
	setIsPending: PropTypes.func,
	/**
	 * Called when the plugin connection is aborted.
	 */
	onAbort: PropTypes.func,
	/**
	 * Text used for the abort connection button.
	 */
	abortText: PropTypes.string,
};

Connect.defaultProps = {
	setIsPending: () => {},
};

export default Connect;
