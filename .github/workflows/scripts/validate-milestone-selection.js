/**
 * Validates that a PR has either a milestone set or the auto-assign checkbox selected.
 *
 * @param {Object} params - The parameters object
 * @param {Object} params.github - GitHub API client
 * @param {Object} params.context - GitHub Actions context
 * @param {Object} params.core - GitHub Actions core utilities
 */
module.exports = async ({ github, context, core }) => {
    const prNumber = context.payload.pull_request.number;
    const { data: pr } = await github.rest.pulls.get({
        owner: context.repo.owner,
        repo: context.repo.repo,
        pull_number: prNumber,
    });

    if (pr.milestone) {
        core.info(`Milestone "${pr.milestone.title}" is set.`);
        return;
    }

    const body = pr.body || '';

    if (!body.includes('<!-- milestone-target-selection -->')) {
        core.setFailed('Milestone selection section not found in PR description. Please use the PR template and select the milestone option, or manually assign a milestone.');
        return;
    }

    const nextVersionMatch = body.match(/- \[([ xX])\].*\*\*.*next WooCommerce version.*\*\*/);

    if (!nextVersionMatch) {
        core.setFailed('Milestone selection checkbox not found or modified. Please restore the original checkbox format from the PR template.');
        return;
    }

    const nextVersionChecked = nextVersionMatch[1].toLowerCase() === 'x';

    if (!nextVersionChecked) {
        core.setFailed('No milestone option selected. Please check the auto-assign checkbox or manually assign a milestone.');
        return;
    }

    core.info('Auto-assign milestone checkbox selected. Milestone will be assigned on merge.');
};
