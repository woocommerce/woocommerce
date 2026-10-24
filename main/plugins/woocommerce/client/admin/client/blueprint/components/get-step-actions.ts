/**
 * External dependencies
 */
import { _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { BlueprintStep } from './types';

/**
 * Steps whose effects are already described to the user by the list of
 * WooCommerce Settings sections the import will overwrite, so describing them
 * again here would only repeat what the user has been told.
 */
const SETTINGS_STEPS = [ 'setSiteOptions' ];

/**
 * Human descriptions for the steps a Blueprint can contain, in the order they
 * should be listed — most consequential first.
 *
 * A Blueprint step runs with the importing administrator's access, so the point
 * of these descriptions is to let that administrator see what a file will do
 * before they confirm it, rather than only seeing its name.
 */
const STEP_ACTIONS: Record< string, ( count: number ) => string > = {
	runSql: ( count ) =>
		sprintf(
			/* translators: %d: number of database queries a Blueprint will run. */
			_n(
				'Run %d database query',
				'Run %d database queries',
				count,
				'woocommerce'
			),
			count
		),
	installPlugin: ( count ) =>
		sprintf(
			/* translators: %d: number of plugins a Blueprint will install. */
			_n(
				'Install %d plugin',
				'Install %d plugins',
				count,
				'woocommerce'
			),
			count
		),
	activatePlugin: ( count ) =>
		sprintf(
			/* translators: %d: number of plugins a Blueprint will activate. */
			_n(
				'Activate %d plugin',
				'Activate %d plugins',
				count,
				'woocommerce'
			),
			count
		),
	installTheme: ( count ) =>
		sprintf(
			/* translators: %d: number of themes a Blueprint will install. */
			_n( 'Install %d theme', 'Install %d themes', count, 'woocommerce' ),
			count
		),
	activateTheme: ( count ) =>
		sprintf(
			/* translators: %d: number of themes a Blueprint will activate. */
			_n(
				'Activate %d theme',
				'Activate %d themes',
				count,
				'woocommerce'
			),
			count
		),
};

/**
 * Describe what a Blueprint will do beyond writing WooCommerce settings.
 *
 * Steps this function does not recognise are still counted and named rather
 * than dropped, so a Blueprint cannot carry an action past the confirmation
 * screen simply by using a step this list has not been taught about.
 *
 * @param steps a list of Blueprint steps
 * @return string[] a list of descriptions, ready to show as a list
 */
export const getStepActions = ( steps: BlueprintStep[] ): string[] => {
	const counts = steps.reduce< Map< string, number > >( ( acc, step ) => {
		const name = step?.step;
		if ( name && ! SETTINGS_STEPS.includes( name ) ) {
			acc.set( name, ( acc.get( name ) || 0 ) + 1 );
		}
		return acc;
	}, new Map() );

	const actions = Object.keys( STEP_ACTIONS )
		.filter( ( name ) => counts.has( name ) )
		.map( ( name ) => STEP_ACTIONS[ name ]( counts.get( name ) || 0 ) );

	const unrecognized = Array.from( counts.keys() )
		.filter( ( name ) => ! Object.hasOwn( STEP_ACTIONS, name ) )
		.sort();

	if ( unrecognized.length ) {
		const total = unrecognized.reduce(
			( sum, name ) => sum + ( counts.get( name ) || 0 ),
			0
		);

		actions.push(
			sprintf(
				/* translators: 1: number of steps a Blueprint will run. 2: comma separated list of step names. */
				_n(
					'Run %1$d other step (%2$s)',
					'Run %1$d other steps (%2$s)',
					total,
					'woocommerce'
				),
				total,
				unrecognized.join( ', ' )
			)
		);
	}

	return actions;
};
