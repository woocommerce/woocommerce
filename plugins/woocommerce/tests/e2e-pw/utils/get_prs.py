import requests

def get_prs_for_milestone(repo_owner, repo_name, milestone_number, token):
    """
    Get all PRs for a specific milestone from a GitHub repo.

    :param repo_owner: str, the owner of the repository.
    :param repo_name: str, the name of the repository.
    :param milestone_number: int, the number of the milestone.
    :param token: str, GitHub API token for authentication.
    :return: list, PRs related to the milestone.
    """
    # GitHub API URL for pull requests
    prs_url = f"https://api.github.com/repos/{repo_owner}/{repo_name}/pulls"
    
    # Headers for authentication
    headers = {
        "Authorization": f"token {token}",
        "Accept": "application/vnd.github.v3+json",
    }

    # Parameters for the API request, to filter PRs by milestone
    params = {
        "milestone": "8.2.0",
        "per_page": 100, 
        "page": 2,
    }

    # Making the API request
    response = requests.get(prs_url, headers=headers, params=params)
    
    # Handling the response
    if response.status_code == 200:
        return response.json()  # Returning the list of PRs
    else:
        print(f"Failed to fetch PRs: {response.status_code}")
        return None

# Example usage:
# Replace 'YOUR_GITHUB_TOKEN' with your actual GitHub API token.
# prs = get_prs_for_milestone("woocommerce", "woocommerce", 8, "YOUR_GITHUB_TOKEN")
# Note: Ensure to handle and store tokens securely.
if __name__ == "__main__":
    # Replace 'YOUR_GITHUB_TOKEN' with the token you generated.
    prs = get_prs_for_milestone("woocommerce", "woocommerce", "8.2.0", "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")
    
    if prs:
        for pr in prs:
            print(f"PR #{pr['number']} - {pr['title']} - {pr['html_url']}")
