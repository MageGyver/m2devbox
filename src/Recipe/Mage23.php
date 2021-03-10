<?php
/*
 * This file is part of the m2devbox project.
 * (c) Steffen Rieke <m2devbox@aenogym.de>
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

namespace Devbox\Recipe;

use Devbox\AbstractRecipe;
use Devbox\Devbox;

class Mage23 extends AbstractRecipe
{
    protected function getExpectedContainers(): array
    {
        return [
            "m2devbox-{$this->getShortVersion()}-db",
            "m2devbox-{$this->getShortVersion()}-web",
        ];
    }

    protected function composerCreateProject()
    {
        if ($this->mageFileExists('composer.json')) {
            $this->status('<info>🤵 Composer project already created</info>');
            return;
        }

        $this->status('<info>🤵 Composer create-project...</info>');
        $this->createMageSrcDir();
        $this->inDocker(
            'web',
            'cd /var/www/html                                                    \\
            && composer create-project                                                    \\
            --repository-url=https://repo.magento.com/                                    \\
            magento/project-community-edition='.$this->getVersion().'                     \\
            /var/www/.mageinstall.tmp/                                                    \\
            && rsync -avzh --remove-source-files /var/www/.mageinstall.tmp/ /var/www/html \\
            && find /var/www/.mageinstall.tmp -type d -empty -delete'
        );
    }

    protected function composerInstall()
    {
        $this->dockerBuild();
        $this->composerCreateProject();

        if ($this->mageDirExists('vendor') && $this->mageFileExists('app/etc/vendor_path.php')) {
            $this->status('<info>🤵 Composer dependencies already installed</info>');
            return;
        }

        $this->status('<info>🤵 Composer install...</info>');
        $this->inDocker(
            'web',
            'composer install --prefer-dist --no-interaction'
        );
    }

    protected function installMagento()
    {
        $this->composerInstall();

        if ($this->mageFileExists('app/etc/env.php')) {
            $this->status('<info>🛒 Magento already installed</info>');
            return;
        }

        $this->status('<info>🛒 Installing Magento %s...</info>', [$this->getVersion()]);

        $installCommand = <<<COMMAND
            /var/www/html/bin/magento setup:install                                 \
                --admin-firstname=Admin                                             \
                --admin-lastname=Admin                                              \
                --admin-email="admin@example.com"                                   \
                --admin-user="$(M2D_MAGE_ADMIN_USER)"                               \
                --admin-password="$(M2D_MAGE_ADMIN_PASS)"                           \
                --base-url="http://$(M2D_MAGE_WEB_DOMAIN):$(M2D_WEB_PORT)/"         \
                --backend-frontname=admin                                           \
                --db-host="m2devbox-{$this->getShortVersion()}-db"                  \
                --db-name="magento2_{$this->getShortVersion()}"                     \
                --db-user="magento2"                                                \
                --db-password="magento2"                                            \
                --language="$(M2D_MAGE_LANG)"                                       \
                --currency="$(M2D_MAGE_CURRENCY)"                                   \
                --timezone="$(M2D_TIMEZONE)"                                        \
                --use-rewrites=1                                                    \
                --use-secure=0                                                      \
                --base-url-secure="https://$(M2D_MAGE_WEB_DOMAIN):$(M2D_WEB_PORT)/" \
                --use-secure-admin=0                                                \
                --session-save=files                                                \
            && chown -R www-data:www-data /var/www/html/
COMMAND;
        $installCommand = Devbox::extrapolateEnv($installCommand);

        $this->inDocker(
            'web',
            $installCommand,
            'db'
        );
    }

}
