/**
 * Internal dependencies
 */
import { download } from '../../node-async';

/**
 * External dependencies
 */
import { CliUx, Command, Flags } from '@oclif/core';
import { tmpdir } from 'os';
import { createHash } from 'crypto';
import { readFileSync } from 'fs';

export default class Analyzer extends Command {
	static description = 'Analyze code changes in WooCommerce Monorepo.';

	static args = [
		{
			name: 'source',
			description: 'GitHub organization/repository.',
			required: true,
		},
		{
			name: 'branch',
			description:
				'A GitHub branch containing changes to compare against a source branch.',
			required: true,
		},
	];

	static flags = {
		destination: Flags.string({
			description: 'GitHub destination branch to compare from.',
			default: 'trunk',
		}),
	};

	/**
	 * This method is called to execute the command.
	 */
	async run(): Promise<void> {
		const { args, flags } = await this.parse(Analyzer);

		await this.validateArgs(args.source);

		const patchContent = await this.getChanges(
			args.source,
			args.branch,
			flags.destination
		);

		await this.scanChanges(patchContent);
	}

	/**
	 * Validates all of the arguments to make sure
	 *
	 * @param {string} source The GitHub repository we are merging.
	 */
	private async validateArgs(source: string): Promise<void> {
		// We only support pulling from GitHub so the format needs to match that.
		if (!source.match(/^[a-z0-9\-]+\/[a-z0-9\-]+$/)) {
			this.error(
				'The "source" argument must be in "organization/repository" format'
			);
		}
	}

	/**
	 * Download a patch file to a temporary directory and return its contents.
	 *
	 * @param {string} source The GitHub repository.
	 * @param {string} branch Git branch.
	 * @param {string} destination Destination branch to compare.
	 */
	private async getChanges(
		source: string,
		branch: string,
		destination: string
	): Promise<string> {
		CliUx.ux.action.start('Downloading patch for ' + branch);

		const url = `https://github.com/${source}/compare/${destination}...${branch}.patch`;
		const filename = createHash('md5').update(url).digest('hex') + '.patch';
		const filepath = tmpdir() + '/' + filename;

		// Download patch.
		const success = await this.downloadPatch(url, filepath);
		if (!success) {
			this.error('Failed to download patch!');
		}

		const content = await this.readPatch(filepath);

		CliUx.ux.action.stop();
		return content;
	}

	/**
	 * Download patch.
	 *
	 * @param url string GitHub patch URL.
	 * @param path string File path.
	 * @returns boolean
	 */
	private async downloadPatch(url: string, path: string): Promise<boolean> {
		return await download(url, path);
	}

	/**
	 * Read patch file
	 *
	 * @param path string File path.
	 * @returns string
	 */
	private async readPatch(path: string): Promise<string> {
		const content = readFileSync(path);

		return content.toString();
	}

	/**
	 * Scan patches for changes in templates, hooks and database schema.
	 *
	 * @param content string Patch content.
	 */
	private async scanChanges(content: string): Promise<void> {
		const templates = await this.scanTemplates(content);

		if (templates) {
			this.log(templates);
		} else {
			this.log('No template changes found');
		}
	}

	/**
	 * Scan patches for changes in templates.
	 *
	 * @param content string Patch content.
	 * @returns
	 */
	private async scanTemplates(content: string): Promise<string> {
		CliUx.ux.action.start('Scanning template changes');

		const matchTemplates = /diff --git a\/(.+)\/templates\/(.+)/g;
		const matchPatches = /^a\/(.+)\/templates\/(.+)/g;

		if (!content.match(matchTemplates)) {
			CliUx.ux.action.stop();
			return '';
		}

		const patches = await this.getPatches(content, matchPatches);
		let report = '';

		for (let p in patches) {
			const patch = patches[p];
			const lines = patch.split('\n');
			let found = false;

			report += 'Template updated: ';
			report += await this.getFilename(lines[0]);

			for (let l in lines) {
				const line = lines[l];

				if (line.match(/^(\+\s*.+)(@version)/g)) {
					found = true;
				}
			}

			if (found) {
				report += ' version bumped succefully';
			} else {
				report += ' missing to update template version';
			}

			report += '\n';
			found = false;
		}

		CliUx.ux.action.stop();
		return report;
	}

	/**
	 *
	 * @param content string Patch content.
	 * @param regex RegExp Regex to find specific patches.
	 * @returns string[]
	 */
	private async getPatches(content: string, regex: RegExp): Promise<string[]> {
		const patches = content.split('diff --git ');
		let changes: string[] = [];

		for (let p in patches) {
			const patch = patches[p];
			const id = patch.match(regex);

			if (id) {
				changes.push(patch);
			}
		}

		return changes;
	}

	/**
	 * Get filename from patch.
	 *
	 * @param str string String to extract filename from.
	 * @returns string
	 */
	private async getFilename(str: string): Promise<string> {
		return str.replace(/a(.*)\s.*/g, '$1');
	}
}
