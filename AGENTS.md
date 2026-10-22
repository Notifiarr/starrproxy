# AGENTS.md

TLDR: PHP and JS app in a Docker image. Read [CONTRIBUTING.md](CONTRIBUTING.md) first; it holds the branch target, header rule, code style, and local checks. This file only adds what an agent needs on top.

## Layout

| Path | Contents |
| --- | --- |
| `root/app/www/public/` | The app: `classes/`, `functions/`, `pages/`, `ajax/`, `api/`, `crons/`, `migrations/`, `js/`, `templates/` |
| `root/etc/` | nginx template, PHP ini, crontab, s6 services |
| `docker/` | `Dockerfile`, `build.sh`, `compose.yml` for local development |
| `.github/workflows/` | `docker-publish.yml` (image build), `checks.yml` (pre-commit) |

## Rules

1. Target `develop`.
2. New `.php` files start with the header block from CONTRIBUTING.md. Run `pre-commit run --all-files` before you commit.
3. Do not add the header to, or reformat, files you did not create.
4. Keep each file's existing line endings (most PHP files are CRLF).
5. Do not edit `root/app/www/public/libraries/` (vendored).
6. Templates grant the minimum endpoints an app calls; verify each against the app's source or docs.
7. Follow the PR and commit conventions in CONTRIBUTING.md and any AI disclosure the project asks for.

## Build and test

```bash
sh docker/build.sh
npm ci && npm run lint
pre-commit run --all-files
```

`docker/build.sh` tags the image `ghcr.io/notifiarr/starrproxy:local`. `docker/compose.yml` runs that image on port 10000 with the local `root/app/www/public` mounted in. Run it from the `docker/` directory.

The repo has no unit test suite; verify a change against the running container.
