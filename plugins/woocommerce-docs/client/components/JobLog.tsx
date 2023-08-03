/**
 * External dependencies
 */
import {
	Card,
	CardBody,
	CardHeader,
	__experimentalItem as Item,
	__experimentalItemGroup as ItemGroup,
	__experimentalScrollable as Scrollable,
	Spinner,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useJobLog } from '../data/useJobs';

export const JobLog = () => {
	const { jobLogs, isLoading, error } = useJobLog();

	if ( isLoading ) {
		return (
			<Card elevation={ 3 }>
				<CardHeader>
					<h2>Job Log</h2>
				</CardHeader>
				<CardBody>
					<Spinner />
				</CardBody>
			</Card>
		);
	}

	if ( error ) {
		return (
			<Card elevation={ 3 }>
				<CardHeader>
					<h2>Job Log</h2>
				</CardHeader>
				<CardBody>
					<p>{ error }</p>
				</CardBody>
			</Card>
		);
	}

	if ( ! jobLogs.length && ! isLoading ) {
		return (
			<Card elevation={ 3 }>
				<CardHeader>
					<h2>Job Log</h2>
				</CardHeader>
				<CardBody>
					<p>No job logs found.</p>
				</CardBody>
			</Card>
		);
	}

	return (
		<Card elevation={ 3 }>
			<CardHeader>
				<h2>Job Log</h2>
			</CardHeader>
			<CardBody>
				<Scrollable style={ { maxHeight: 300 } }>
					<ItemGroup>
						{ jobLogs.map( ( jobLog, i ) => (
							<div key={ `${ jobLog.date }:${ i }` }>
								<Item>
									Message: { jobLog.message }
									<br></br>
									Logged at: { jobLog.date }
								</Item>
								<hr />
							</div>
						) ) }
					</ItemGroup>
				</Scrollable>
			</CardBody>
		</Card>
	);
};
