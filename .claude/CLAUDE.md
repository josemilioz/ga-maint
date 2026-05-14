# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a **WordPress site hosted on Pantheon** (`jose-playground`). There is no build step — deployment is Git-based, pushing code directly to Pantheon's Git remote via GitHub Actions.

- **PHP**: 8.2
- **Database**: MariaDB 10.4
- **Local dev**: Lando (recipe: `pantheon`)
- **Platform**: Pantheon (manages Varnish caching, Redis, New Relic)

## Local Development

```bash
# Start the local environment
lando start

# Run WP-CLI commands
lando wp <command>

# Pull database/files from Pantheon
lando pull

# Push database/files to Pantheon
lando push
```

## Deployment & Maintenance Workflow

Deployments to Pantheon happen in two steps:

1. **Monthly maintenance PR** (`maintenance-pull.yml`) — runs on the 25th at 6:00 AM UTC:
   - Updates WordPress core (from Pantheon upstream)
   - Updates all plugins and themes via WP-CLI
   - Opens a PR with the changelog

2. **Deploy on merge** (`maintenance-deploy.yml`) — triggers when the maintenance PR is approved and merged into `main` or `master` (whichever is the principal branch of the project), then pushes to Pantheon.

There is no direct push to Pantheon outside of this flow.

## Architecture

```
wp-content/
  mu-plugins/     # Always-loaded plugins (cannot be disabled via admin)
    loader.php                  # Registers MU plugins
    pantheon-mu-plugin/         # Pantheon platform integration (caching, health, updates)
  plugins/        # Optional plugins (Akismet, Hello Dolly)
  themes/         # Default WordPress themes; no custom theme active
```

**`wp-config.php`** is environment-aware:
- On Pantheon: loads `wp-config-pantheon.php`
- Locally: loads `wp-config-local.php` (not committed; copy from `wp-config-local-sample.php`)
- Fallback: placeholder credentials

**`pantheon.yml`** controls platform settings: PHP version, HTTPS enforcement, protected paths (`/private/`, `/wp-content/uploads/private/`, `/xmlrpc.php`).

## Key Constraints

- `wp-admin/` and `wp-includes/` are WordPress core — do not modify these files; apply updates through the maintenance workflow instead.
- The `mu-plugins/pantheon-mu-plugin/` directory is a vendor package — treat as read-only.
- Media uploads (`wp-content/uploads/`) are git-ignored; they live on Pantheon's filesystem.
