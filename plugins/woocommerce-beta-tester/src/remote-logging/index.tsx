/**
 * External dependencies
 */

import { useState, useEffect } from '@wordpress/element';
import { Button, ToggleControl, Notice, Spinner } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { log, init as initRemoteLogging } from '@woocommerce/remote-logging';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore no types
// eslint-disable-next-line @woocommerce/dependency-group
import { dispatch } from '@wordpress/data';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore no types
// eslint-disable-next-line @woocommerce/dependency-group
import { STORE_KEY as OPTIONS_STORE_NAME } from '../options/data/constants';

export const API_NAMESPACE = '/wc-admin-test-helper';

interface RemoteLoggingStatus {
	isEnabled: boolean;
	wpEnvironment: string;
}

interface NoticeState {
	status: 'success' | 'error' | 'warning' | 'info';
	message: string;
}

function RemoteLogging() {
	const [ isRemoteLoggingEnabled, setIsRemoteLoggingEnabled ] = useState<
		boolean | null
	>( null );
	const [ wpEnvironment, setWpEnvironment ] = useState< string >( '' );
	const [ notice, setNotice ] = useState< NoticeState | null >( null );

	useEffect( () => {
		const fetchRemoteLoggingStatus = async () => {
			try {
				const response: RemoteLoggingStatus = await apiFetch( {
					path: `${ API_NAMESPACE }/remote-logging/status`,
				} );
				setIsRemoteLoggingEnabled( response.isEnabled );
				setWpEnvironment( response.wpEnvironment );
			} catch ( error ) {
				setNotice( {
					status: 'error',
					message: 'Failed to fetch remote logging status.',
				} );
			}
		};

		fetchRemoteLoggingStatus();
	}, [] );

	const toggleRemoteLogging = async () => {
		try {
			const response: RemoteLoggingStatus = await apiFetch( {
				path: `${ API_NAMESPACE }/remote-logging/toggle`,
				method: 'POST',
				data: { enable: ! isRemoteLoggingEnabled },
			} );
			setIsRemoteLoggingEnabled( response.isEnabled );

			window.wcSettings.isRemoteLoggingEnabled = response.isEnabled;
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message: `Failed to update remote logging status. ${ JSON.stringify(
					error
				) }`,
			} );
		}

		if ( window.wcSettings.isRemoteLoggingEnabled ) {
			initRemoteLogging( {
				errorRateLimitMs: 60000, // 1 minute
			} );
		}
	};

	const simulatePhpException = async ( context: 'core' | 'beta-tester' ) => {
		try {
			await dispatch( OPTIONS_STORE_NAME ).saveOption(
				'wc_beta_tester_simulate_woocommerce_php_error',
				context
			);
			setNotice( {
				status: 'success',
				message: `Please refresh your browser to trigger the PHP exception in ${ context } context.`,
			} );
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message: `Failed to trigger PHP exception test in ${ context } context. ${ JSON.stringify(
					error
				) }`,
			} );
		}
	};

	const logPhpEvent = async () => {
		try {
			await apiFetch( {
				path: `${ API_NAMESPACE }/remote-logging/log-event`,
				method: 'POST',
			} );
			setNotice( {
				status: 'success',
				message: 'Remote event logged successfully.',
			} );
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message: `Failed to log remote event.`,
			} );
		}
	};

	const resetPhpRateLimit = async () => {
		try {
			await apiFetch( {
				path: `${ API_NAMESPACE }/remote-logging/reset-rate-limit`,
				method: 'POST',
			} );
			setNotice( {
				status: 'success',
				message: 'PHP rate limit reset successfully.',
			} );
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message: `Failed to reset PHP rate limit. ${ JSON.stringify(
					error
				) }`,
			} );
		}
	};

	const simulateException = async ( context: 'core' | 'beta-tester' ) => {
		try {
			await dispatch( OPTIONS_STORE_NAME ).saveOption(
				'wc_beta_tester_simulate_woocommerce_js_error',
				context
			);

			if ( context === 'core' ) {
				setNotice( {
					status: 'success',
					message: `Please go to WooCommerce pages to trigger the JS exception in woocommerce context.`,
				} );
			} else {
				setNotice( {
					status: 'success',
					message:
						'Please refresh your browser to trigger the JS exception in woocommerce beta tester context.',
				} );
			}
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message: `Failed to set up JS exception test`,
			} );
		}
	};

	const logJsEvent = async () => {
		try {
			const result = await log(
				'info',
				'Test JS event from WooCommerce Beta Tester',
				{
					extra: {
						source: 'wc-beta-tester',
					},
				}
			);

			if ( ! result ) {
				throw new Error();
			}

			setNotice( {
				status: 'success',
				message: 'JS event logged successfully.',
			} );
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message:
					'Failed to log JS event. Try enabling debug mode `window.localStorage.setItem( "debug", "wc:remote-logging" )` to see the details.',
			} );
		}
	};

	const resetJsRateLimit = () => {
		window.localStorage.removeItem(
			'wc_remote_logging_last_error_sent_time'
		);
		setNotice( {
			status: 'success',
			message: 'JS rate limit reset successfully.',
		} );
	};

	if ( isRemoteLoggingEnabled === null ) {
		return <Spinner />;
	}

	return (
		<div id="wc-admin-test-helper-remote-logging">
			<h2>Remote Logging</h2>
			{ notice && (
				<div style={ { marginBottom: '12px' } }>
					<Notice
						status={ notice.status }
						onRemove={ () => setNotice( null ) }
					>
						{ notice.message }
					</Notice>
				</div>
			) }

			{ ! isRemoteLoggingEnabled && (
				<p className="helper-text" style={ { marginBottom: '12px' } }>
					Enable remote logging to test log event functionality.
				</p>
			) }

			{ ( wpEnvironment === 'local' ||
				wpEnvironment === 'development' ) && (
				<div style={ { marginBottom: '12px' } }>
					<Notice status="warning" isDismissible={ false }>
						Warning: You are in a { wpEnvironment } environment.
						Remote logging may not work as expected. Please set
						<code>WP_ENVIRONMENT_TYPE</code> to{ ' ' }
						<code>production</code>
						in your wp-config.php file to test remote logging.
					</Notice>
				</div>
			) }

			<ToggleControl
				label="Enable Remote Logging"
				checked={ isRemoteLoggingEnabled }
				onChange={ toggleRemoteLogging }
			/>

			<hr />
			<h3>PHP Integration</h3>
			<p>Test PHP remote logging functionality:</p>
			<div className="button-group" style={ { marginBottom: '20px' } }>
				<Button
					variant="secondary"
					onClick={ () => simulatePhpException( 'core' ) }
					style={ { marginRight: '10px' } }
				>
					Simulate Core Exception
				</Button>
				<Button
					variant="secondary"
					onClick={ () => simulatePhpException( 'beta-tester' ) }
					style={ { marginRight: '10px' } }
				>
					Simulate Beta Tester Exception
				</Button>
				<Button
					variant="secondary"
					onClick={ logPhpEvent }
					disabled={ ! isRemoteLoggingEnabled }
					style={ { marginRight: '10px' } }
				>
					Log PHP Event
				</Button>
				<Button
					variant="secondary"
					onClick={ resetPhpRateLimit }
					disabled={ ! isRemoteLoggingEnabled }
				>
					Reset Rate Limit
				</Button>
			</div>

			<hr className="section-divider" style={ { margin: '20px 0' } } />

			<h3>JavaScript Integration</h3>
			<p>Test JavaScript remote logging functionality:</p>
			<div className="button-group" style={ { marginBottom: '20px' } }>
				<Button
					variant="secondary"
					onClick={ () => simulateException( 'core' ) }
					style={ { marginRight: '10px' } }
				>
					Simulate Core Exception
				</Button>
				<Button
					variant="secondary"
					onClick={ () => simulateException( 'beta-tester' ) }
					style={ { marginRight: '10px' } }
				>
					Simulate Beta Tester Exception
				</Button>
				<Button
					variant="secondary"
					onClick={ logJsEvent }
					disabled={ ! isRemoteLoggingEnabled }
					style={ { marginRight: '10px' } }
				>
					Log Event
				</Button>
				<Button
					variant="secondary"
					onClick={ resetJsRateLimit }
					disabled={ ! isRemoteLoggingEnabled }
				>
					Reset Rate Limit
				</Button>
			</div>
		</div>
	);
}

export default RemoteLogging;
