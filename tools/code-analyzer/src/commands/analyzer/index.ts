/**
 * External dependencies
 */
import { CliUx, Command, Flags } from '@oclif/core';
import { tmpdir } from 'os';
import { join } from 'path';
import { readFileSync } from 'fs';

/**
 * Internal dependencies
 */
import { MONOREPO_ROOT } from '../../const';
import { download } from '../../node-async';

/**
 * Analyzer class
 */
export default class Analyzer extends Command {
	/**
	 * CLI description
	 */
	static description = 'Analyze code changes in WooCommerce Monorepo.';

	/**
	 * CLI arguments
	 */
	static args = [
		{
			name: 'compare',
			description:
				'GitHub branch or commit hash to compare against the base branch/commit.',
			required: true,
		},
	];

	/**
	 * CLI flags
	 */
	static flags = {
		base: Flags.string({
			char: 'b',
			description: 'GitHub base branch or commit hash.',
			default: 'trunk',
		}),
		output: Flags.string({
			char: 'o',
			description: 'Output styling.',
			options: ['console', 'github'],
			default: 'console',
		}),
		source: Flags.string({
			char: 's',
			description: 'GitHub organization/repository.',
			default: 'woocommerce/woocommerce',
		}),
	};

	/**
	 * This method is called to execute the command
	 */
	async run(): Promise<void> {
		const { args, flags } = await this.parse(Analyzer);

		await this.validateArgs(flags.source);

		const patchContent = await this.getChanges(
			flags.source,
			args.compare,
			flags.base
		);

		const version = await this.getPluginVersion();
		this.log(`WooCommerce Version: ${version}`);

		await this.scanChanges(patchContent, version, flags.output);
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
	 * Get current plugin version
	 *
	 * @return {Promise<string>}
	 */
	private async getPluginVersion(): Promise<string> {
		CliUx.ux.action.start('Getting current WooCommerce version');

		const filepath = join(
			MONOREPO_ROOT,
			'plugins',
			'woocommerce',
			'woocommerce.php'
		);

		const content = readFileSync(filepath).toString();
		const data = content.match(/^\s+\*\s+Version:\s+(.*)/m);

		if (!data) {
			this.error('Failed to find plugin version!');
		}

		CliUx.ux.action.stop();
		return data![1].replace(/\-.*/, '');
	}

	/**
	 * Generate a patch file into the temp directory and return its contents
	 *
	 * @param {string} source The GitHub repository.
	 * @param {string} compare Branch/commit hash to compare against the base.
	 * @param {string} base Base branch/commit hash.
	 * @return {Promise<string>}
	 */
	private async getChanges(
		source: string,
		compare: string,
		base: string
	): Promise<string> {
		CliUx.ux.action.start('Generating patch for ' + compare);

		const url = `https://github.com/${source}/compare/${base}...${compare}.patch`;
		const filename = `${source}-${base}-${compare}.patch`.replace(
			/\//g,
			'-'
		);
		const filepath = join(tmpdir(), filename);

		// Download patch.
		const success = await this.downloadPatch(url, filepath);
		if (!success) {
			this.error('Failed to download patch!');
		}

		const content = readFileSync(filepath).toString();

		CliUx.ux.action.stop();
		return content;
	}

	/**
	 * Download patch
	 *
	 * @param {string} url GitHub patch URL.
	 * @param {string} path File path.
	 * @return {Promise<boolean>}
	 */
	private async downloadPatch(url: string, path: string): Promise<boolean> {
		return await download(url, path);
	}

	/**
	 * Get patches
	 *
	 * @param {string} content Patch content.
	 * @param {RegExp} regex Regex to find specific patches.
	 * @return {Promise<string[]>}
	 */
	private async getPatches(
		content: string,
		regex: RegExp
	): Promise<string[]> {
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
	 * Get filename from patch
	 *
	 * @param {string} str String to extract filename from.
	 * @return {Promise<string>}
	 */
	private async getFilename(str: string): Promise<string> {
		return str.replace(/a(.*)\s.*/g, '$1');
	}

	/**
	 * Scan patches for changes in templates, hooks and database schema
	 *
	 * @param {string} content Patch content.
	 * @param {string} version Current product version.
	 * @param {string} output Output style.
	 */
	private async scanChanges(
		content: string,
		version: string,
		output: string
	): Promise<void> {
		const templates = await this.scanTemplates(content, version);
		// const hooks = await this.scanHooks(content, version);
		// @todo: Scan for changes to database schema.

		if (templates) {
			await this.printTemplateResults(
				templates,
				output,
				'TEMPLATE CHANGES'
			);
		} else {
			this.log('No template changes found');
		}

		// if (hooks) {
		// 	await this.printHookResults(hooks, output, 'HOOKS');
		// 	this.log(
		// 		JSON.stringify(hooks, (key, value) =>
		// 			value instanceof Map ? [...value] : value
		// 		)
		// 	);
		// } else {
		// 	this.log('No template changes found');
		// }
	}

	/**
	 * Print template results
	 *
	 * @param {Map<string, string[]>} data Raw data.
	 * @param {string} output Output style.
	 * @param {string} title Section title.
	 */
	private async printTemplateResults(
		data: Map<string, string[]>,
		output: string,
		title: string
	): Promise<void> {
		if ('github' === output) {
			for (const [key, value] of data) {
				this.log(
					`::${value[1]} file=${key},line=${value[0]},title=${value[2]}::${value[3]}`
				);
			}
		} else {
			this.log(`\n## ${title}:`);
			for (const [key, value] of data) {
				this.log('FILE: ' + key);
				this.log('---------------------------------------------------');
				this.log(
					` ${value[0]} | ${value[1].toUpperCase()} | ${value[2]} | ${
						value[3]
					}`
				);
				this.log('---------------------------------------------------');
			}
		}
	}

	/**
	 * Print hook results
	 *
	 * @param {Map<string, Map<string, string[]>} data Raw data.
	 * @param {string} output Output style.
	 * @param {string} title Section title.
	 */
	private async printHookResults(
		data: Map<string, Map<string, string[]>>,
		output: string,
		title: string
	): Promise<void> {
		if ('github' === output) {
			for (const [key, value] of data) {
				for (const [k, v] of value) {
					this.log(
						`::${v[1]} file=${key},line=${v[0]},title=${v[2]} - ${k}::${v[3]}`
					);
				}
			}
		} else {
			this.log(`\n## ${title}:`);
			for (const [key, value] of data) {
				this.log('FILE: ' + key);
				this.log('---------------------------------------------------');
				for (const [k, v] of value) {
					this.log('HOOK: ' + k);
					this.log(
						'---------------------------------------------------'
					);
					this.log(
						` ${v[0]} | ${v[1].toUpperCase()} | ${v[2]} | ${v[3]}`
					);
					this.log(
						'---------------------------------------------------'
					);
				}
			}
		}
	}

	/**
	 * Scan patches for changes in templates
	 *
	 * @param {string} content Patch content.
	 * @param {string} version Current product version.
	 * @return {Promise<Map<string, string[]>>}
	 */
	private async scanTemplates(
		content: string,
		version: string
	): Promise<Map<string, string[]>> {
		CliUx.ux.action.start('Scanning template changes');

		let report: Map<string, string[]> = new Map<string, string[]>();

		if (!content.match(/diff --git a\/(.+)\/templates\/(.+)/g)) {
			CliUx.ux.action.stop();
			return report;
		}

		const matchPatches = /^a\/(.+)\/templates\/(.+)/g;
		const title = 'Template change detected';
		const patches = await this.getPatches(content, matchPatches);

		for (let p in patches) {
			const patch = patches[p];
			const lines = patch.split('\n');
			const filepath = await this.getFilename(lines[0]);
			let code = 'warning';
			let message = 'This template may require a version bump!';

			for (let l in lines) {
				const line = lines[l];

				if (line.match(/^(\+.+\*.+)(@version)/g)) {
					// @todo: Compare with current product version.
					code = 'notice';
					message = 'Version bump found';
				}
			}

			if ('notice' === code && report.get(filepath)) {
				report.set(filepath, ['1', code, title, message]);
			} else if (!report.get(filepath)) {
				report.set(filepath, ['1', code, title, message]);
			}
		}

		CliUx.ux.action.stop();
		return report;
	}
}
