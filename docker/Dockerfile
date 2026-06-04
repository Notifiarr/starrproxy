# syntax=docker/dockerfile:1@sha256:87999aa3d42bdc6bea60565083ee17e86d1f3339802f543c0d03998580f9cb89
FROM ghcr.io/linuxserver/baseimage-alpine-nginx:3.23@sha256:e26dae7e0c918cced5ba13efcebbf5db027e6064a0f84b10f2e73916827d023d
ARG TARGETARCH

# Version labels
ARG COMMIT=unknown
ARG COMMITS=0
ARG BRANCH=unknown
ARG COMMIT_MSG=unknown

# Install system packages
ARG PHP_VERSION=85
ARG INSTALL_PACKAGES="php${PHP_VERSION}-sqlite3 php${PHP_VERSION}-pecl-memcached memcached"
RUN apk add --repository=https://dl-cdn.alpinelinux.org/alpine/v3.23/main --no-cache --update ${INSTALL_PACKAGES}

# Image specific changes for starrproxy
COPY /docker/branding /etc/s6-overlay/s6-rc.d/init-adduser/branding

RUN \
    echo "**** customize base lsio image ****" && \
    # Apply branding branch and hash
    sed -i "s@\${BRANCH}@${BRANCH}@g" /etc/s6-overlay/s6-rc.d/init-adduser/branding && \
    sed -i "s@\${COMMIT}@${COMMIT:0:7}@g" /etc/s6-overlay/s6-rc.d/init-adduser/branding && \
    # Migrations not needed because we ship with our own static nginx config
    rm -r /etc/s6-overlay/s6-rc.d/init-migrations && \
    rm /etc/s6-overlay/s6-rc.d/user/contents.d/init-migrations && \
    rm /etc/s6-overlay/s6-rc.d/init-adduser/dependencies.d/init-migrations && \
    # Read above.
    rm -r /etc/s6-overlay/s6-rc.d/init-nginx && \
    rm /etc/s6-overlay/s6-rc.d/user/contents.d/init-nginx && \
    rm -r /etc/s6-overlay/s6-rc.d/init-samples && \
    rm /etc/s6-overlay/s6-rc.d/user/contents.d/init-samples && \
    rm -r /etc/s6-overlay/s6-rc.d/init-version-checks && \
    rm /etc/s6-overlay/s6-rc.d/user/contents.d/init-version-checks && \
    rm /etc/s6-overlay/s6-rc.d/init-nginx-end/dependencies.d/init-version-checks && \
    # Overwritten by root/etc/php**/conf.d/99_starrproxy.ini
    rm -r /etc/s6-overlay/s6-rc.d/init-php && \
    rm /etc/s6-overlay/s6-rc.d/user/contents.d/init-php && \
    # We are running on port 80 only
    rm -r /etc/s6-overlay/s6-rc.d/init-keygen && \
    rm /etc/s6-overlay/s6-rc.d/user/contents.d/init-keygen && \
    rm /etc/s6-overlay/s6-rc.d/init-permissions/dependencies.d/init-keygen && \
    # Moved to /config/logs. Before: /config/log*
    rm -r /etc/s6-overlay/s6-rc.d/init-folders && \
    rm /etc/s6-overlay/s6-rc.d/user/contents.d/init-folders && \
    # Disable custom files
    rm -r /etc/s6-overlay/s6-rc.d/init-custom-files && \
    rm /etc/s6-overlay/s6-rc.d/user/contents.d/init-custom-files && \
    rm /etc/s6-overlay/s6-rc.d/init-services/dependencies.d/init-custom-files

ARG PHP_FPM_CONF="/etc/php${PHP_VERSION}/php-fpm.conf"
RUN \
    echo "**** update config paths ****" && \
    # Don't allow include of additional php-fpm conf files
    sed -i '\|include=/config/php/\*.conf|d' "${PHP_FPM_CONF}" && \
    # Update php-fpm log paths to /config/logs
    sed -i 's|error_log = /config/log/php/error.log|error_log = /config/logs/php/error.log|g' "${PHP_FPM_CONF}" && \
    # Update logrotate paths to /config/logs
    sed -i 's|/config/log/php/\*.log|/config/logs/php/*.log|' /etc/logrotate.d/nginx && \
    sed -i 's|/config/log/nginx/\*.log|/config/logs/nginx/*.log|' /etc/logrotate.d/php-fpm && \
    sed -i 's|/config/log/logrotate.status|/config/logs/logrotate.status|g' /etc/periodic/daily/logrotate

# Pass docker env vars to php-fpm
ARG PHP_WWW_CONF="/etc/php${PHP_VERSION}/php-fpm.d/www.conf"
RUN \
    echo "**** configure php-fpm to pass env vars ****" && \
    sed -E -i 's/^;?clear_env ?=.*$/clear_env = no/g' "${PHP_WWW_CONF}" && \
    grep -qxF 'clear_env = no' "${PHP_WWW_CONF}" || echo 'clear_env = no' >> "${PHP_WWW_CONF}" && \
    echo "env[PATH] = /usr/local/bin:/usr/bin:/bin" >> "${PHP_WWW_CONF}"

# Healthcheck
HEALTHCHECK --interval=60s --timeout=30s --start-period=180s --start-interval=10s --retries=5 \
    CMD curl -f http://localhost/health > /dev/null || exit 1

# Copy root files
COPY root/ /

# Update constants.php with version labels
RUN echo -e "\n//-- DOCKERFILE DEFINES"                         >> /app/www/public/includes/constants.php \
    && echo "define('DOCKERFILE_BUILD_DATE', '${BUILD_DATE}');" >> /app/www/public/includes/constants.php \
    && echo "define('DOCKERFILE_COMMIT', '${COMMIT}');"         >> /app/www/public/includes/constants.php \
    && echo "define('DOCKERFILE_COMMITS', '${COMMITS}');"       >> /app/www/public/includes/constants.php \
    && echo "define('DOCKERFILE_BRANCH', '${BRANCH}');"         >> /app/www/public/includes/constants.php

# Expose only HTTP and set config volume
EXPOSE 80
VOLUME /config
