# Contributing

TLDR: branch from `develop`, open the PR against `develop`, and start every new `.php` file with the header block below. CI and pre-commit reject a PHP file without it.

## Branches

- `develop` is the default branch and the PR target. The `:develop` image is built from it on every merge ([README](README.md#image-tags)).
- `main` is the release branch behind the `:main` image. Do not target it.
- CI (`.github/workflows/`) runs on pushes and PRs to `main`, `develop`, and `nightly`.

## PHP file header

Every `.php` file starts with this block, as the first lines of the file. The date is `MMDDYY` (the day you created the file) and the name is yours:

```php
<?php

/*
----------------------------------
 ------  Created: 110424   ------
 ------  Austin Best	   ------
----------------------------------
*/
```

Take a copy from any existing file, for example `root/app/www/public/classes/Starr.php`. Do not add or change the header in files you did not create.

## Local checks

Install [pre-commit](https://pre-commit.com/) once, then the header check runs on every commit:

```bash
pre-commit install
pre-commit run --all-files
```

The `Checks` workflow runs the same `pre-commit run --all-files`, so a local pass is a CI pass.

JavaScript under `root/app/www/public/js/` is linted with ESLint (`eslint.config.js`):

```bash
npm ci
npm run lint
```

## Build and run

`sh docker/build.sh` builds `ghcr.io/notifiarr/starrproxy:local`. `docker/compose.yml` runs it on port 10000 and mounts `root/app/www/public` into the container, so PHP, JS, and template edits show up without a rebuild.

## Code style

Taken from `.vscode/settings.json`, `eslint.config.js`, and the existing files:

- PHP: PSR-12 with the braces for functions on a new line, and aligned consecutive assignments.
- JS: 4-space indent, single quotes, semicolons.
- JSON and YAML: 4-space indent.
- LF at the end of the file, no trailing blank lines. Most PHP files in the repo use CRLF line endings; keep a file's existing line endings when you edit it.

## App templates

A template is `root/app/www/public/templates/<starr>/<app>.json`, where `<starr>` is `lidarr`, `prowlarr`, `radarr`, `readarr`, `sonarr`, or `whisparr`. It maps each API path to the lowercase HTTP methods the app needs, and nothing more:

```json
{
    "/api/v3/system/status": [
        "get"
    ]
}
```

Add the app to the "Current templates" list in the README. Grant only the endpoints the app calls, and check them against the app's source or docs.

## Database migrations

Schema or settings changes go in a new `root/app/www/public/migrations/NNN_name.php`, numbered after the last one.

## Commits and PRs

- Commit titles in the repo history are short and imperative, mostly `fix:`, `feat:`, `chore:`, and `docs:` prefixes, with the scope in parentheses when useful (`fix(templates): ...`).
- Issues use the forms in `.github/ISSUE_TEMPLATE/`.
