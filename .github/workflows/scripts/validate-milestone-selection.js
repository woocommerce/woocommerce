const { getAutoAssignMilestoneSelection } = require('./milestone-selection');

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

    const nextVersionSelection = getAutoAssignMilestoneSelection(body);

    if (!nextVersionSelection.found) {
        core.setFailed('Auto-assign milestone checkbox not found. Please add the milestone checkbox from the PR template, or manually assign a milestone.');
        return;
    }

    if (!nextVersionSelection.checked) {
        core.setFailed('No milestone option selected. Please check the auto-assign checkbox or manually assign a milestone.');
        return;
    }

    core.info('Auto-assign milestone checkbox selected. Milestone will be assigned on merge.');
};
