# Pantheon Maintenance Actions — Setup Guide

This document describes the steps required to wire up the automated maintenance workflows in a new Pantheon + GitHub project.

---

## Prerequisites

- A Pantheon site already created and running
- A GitHub repository for the project
- [Terminus](https://docs.pantheon.io/terminus) installed locally
- A Slack app with a bot token and a target channel

---

## 1. Sync the two repositories

The GitHub repo and the Pantheon Git repo must start from the same commit history. Do this once during initial setup.

```bash
# Add the Pantheon remote locally
git remote add pantheon <pantheon-git-url>

# Push the Pantheon repo into GitHub (or vice versa, depending on where the code lives)
git push origin master
```

Get the Pantheon Git URL from the Pantheon dashboard under **Connection Info**, or via Terminus:

```bash
terminus connection:info <site-name>.dev --field=git_url
```

> **Important:** Both repositories must use the same branch name. These workflows assume `master`. If your project uses `main`, update the `PANTHEON_BRANCH` env var and all branch references in both workflow files.

---

## 2. Set Pantheon to Git mode

The dev environment must be in Git connection mode (not SFTP) for the workflows to push code.

```bash
terminus connection:set <site-name>.dev git
```

---

## 3. Generate a Pantheon machine token

1. Log in to the Pantheon dashboard
2. Go to **Account → Machine Tokens → Generate Token**
3. Copy the token — you will not see it again

---

## 4. Configure GitHub Secrets

Go to the GitHub repository → **Settings → Secrets and variables → Actions → Secrets** and add:

| Secret | Value |
|--------|-------|
| `PANTHEON_MACHINE_TOKEN` | The machine token generated in step 3 |
| `SLACK_BOT_TOKEN` | The Slack bot OAuth token (`xoxb-...`) |
| `TEAMWORK_API_TOKEN` | A Teamwork API token with permission to comment on tasks |

To generate a Teamwork API token: log in to Teamwork → **Your avatar → Edit my details → API & Mobile → Show your token**.

---

## 5. Configure GitHub Variables

Same location, under the **Variables** tab:

| Variable | Value |
|----------|-------|
| `PANTHEON_PROJECT_NAME` | The Pantheon site machine name (e.g. `jose-playground`) |
| `SLACK_CHANNEL_ID` | The Slack channel ID where notifications should be posted |
| `TEAMWORK_SITE_URL` | Your Teamwork hostname, e.g. `yoursite.teamwork.com` |
| `TEAMWORK_TASK_SEARCH_TERM` | Title (or unique fragment) of the recurring maintenance task |
| `TEAMWORK_PROJECT_ID` | (Optional) Teamwork project ID — scopes the search to avoid false matches |

The Pantheon site machine name is the slug shown in the dashboard URL and in Terminus commands.

To find a Slack channel ID: open Slack, right-click the channel → **View channel details** → the ID is at the bottom.

To find a Teamwork project ID: open the project in Teamwork — the ID is in the URL (`/projects/<id>/`).

---

## 6. Add the workflow files

Copy both workflow files into `.github/workflows/` in the repository:

- `pantheon-maintenance-pull.yml` — runs on the 25th of each month; pulls upstream WordPress core updates, updates plugins and themes, and opens a maintenance PR
- `pantheon-maintenance-deploy.yml` — triggers automatically when a `maintenance-*` branch PR is merged into `master`; pushes to Pantheon, deploys to test, runs DB updates, and notifies Slack

---

## 7. WordPress local config sample

The pull workflow writes a temporary `wp-config-local.php` during its run. It expects a `wp-config-local-sample.php` file in the project root as a template. Make sure this file exists and contains the placeholder values the workflow looks for:

```php
define( 'DB_NAME', 'database_name' );
define( 'DB_USER', 'database_username' );
define( 'DB_PASSWORD', 'database_password' );
define( 'DB_HOST', 'database_host' );
// ...
define( 'WP_HOME', 'http://<YOUR LOCAL DOMAIN>' );
```

---

## 8. Adjust the cron schedule (optional)

The pull workflow runs at **6:00 AM UTC on the 25th of every month** by default. To change it, edit the `cron` value in `pantheon-maintenance-pull.yml`:

```yaml
schedule:
  - cron: '0 6 25 * *'
```

---

## 9. First run

Both workflows support manual triggering via `workflow_dispatch`. Use this to verify everything is connected before the first scheduled run:

1. Go to **Actions** in the GitHub repository
2. Select **Pantheon Monthly Maintenance PR** → **Run workflow**
3. Once the PR is created and reviewed, merge it
4. The deploy workflow will trigger automatically on merge
