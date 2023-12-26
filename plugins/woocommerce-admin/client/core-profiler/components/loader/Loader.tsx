/**
 * External dependencies
 */
import React from 'react';
import { Loader } from '@woocommerce/onboarding';
/**
 * Internal dependencies
 */
import { CoreProfilerStateMachineContext } from '../..';
import { getLoaderStageMeta } from '../../utils/get-loader-stage-meta';

import './loader.scss';

export type Stage = {
	title: string;
	image?: string | JSX.Element;
	paragraphs: Array< {
		label: string;
		text: string;
		duration?: number;
		element?: JSX.Element;
	} >;
};

export type Stages = Array< Stage >;
export type LoaderContextProps = Pick<
	CoreProfilerStateMachineContext,
	'loader'
>;

export const CoreProfilerLoader = ( {
	context,
}: {
	context: LoaderContextProps;
} ) => {
	const stages = getLoaderStageMeta( context.loader.useStages ?? 'default' );
	const currentStage = stages[ context.loader.stageIndex ?? 0 ];

	return (
		<Loader className={ context.loader.className }>
			<Loader.Layout>
				<Loader.Illustration>
					{ currentStage.image }
				</Loader.Illustration>

				<Loader.Title>{ currentStage.title }</Loader.Title>
				<Loader.ProgressBar
					progress={ context.loader?.progress ?? 0 }
				/>
				<Loader.Sequence interval={ 3000 }>
					{ currentStage.paragraphs.map( ( paragraph, index ) => (
						<Loader.Subtext key={ index }>
							<b>{ paragraph?.label }</b>
							{ paragraph?.text }
							{ paragraph?.element }
						</Loader.Subtext>
					) ) }
				</Loader.Sequence>
			</Loader.Layout>
		</Loader>
	);
};
