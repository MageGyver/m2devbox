<?php

namespace Devbox\Recipe;

use Devbox\AbstractRecipe;
use Devbox\Devbox;

class Mage241 extends AbstractRecipe
{
    const VERSION = '2.4.1';

    protected function getExpectedContainers(): array
    {
        return [
            'mage2devbox-241-db',
            'mage2devbox-241-elastic',
            'mage2devbox-241-web',
        ];
    }

    protected function dockerBuild()
    {
        $this->status('<info>Building Docker containers...</info>');
        $this->dockerCompose('build');
    }

    protected function composerCreateProject()
    {
        if ($this->mageFileExists('composer.json')) {
            $this->status('<info>Composer project already created</info>');
            return;
        }

        $this->status('<info>Composer create-project...</info> <comment>(this might take several minutes)</comment>');
        $this->createMageSrcDir();
        $this->inDocker(
            'web',
            'composer create-project                                             \\
            --repository-url=https://repo.magento.com/                                    \\
            magento/project-community-edition='.static::getVersion().'                    \\
            /var/www/.mageinstall.tmp/                                                    \\
            && rsync -avzh --remove-source-files /var/www/.mageinstall.tmp/ /var/www/html \\
            && find /var/www/.mageinstall.tmp -type d -empty -delete',
            [$this->getMageSrcDir(), '/var/www/html']
        );
    }

    protected function composerInstall()
    {
        $this->dockerBuild();
        $this->composerCreateProject();

        if ($this->mageDirExists('vendor') && $this->mageFileExists('app/etc/vendor_path.php')) {
            $this->status('<info>Composer dependencies already installed</info>');
            return;
        }

        $this->status('<info>Composer install...</info> <comment>(this might take several minutes)</comment>');
        $this->inDocker('web', 'composer install', [$this->getMageSrcDir(), '/var/www/html']);
    }

    protected function installMagento()
    {
        $this->composerInstall();

        if ($this->mageFileExists('app/etc/env.php')) {
            $this->status('<info>Magento already installed</info>');
            return;
        }

        $this->status('<info>Installing Magento %s...</info>', [static::getVersion()]);

        $installCommand = <<<'COMMAND'
            /var/www/html/bin/magento setup:install                                 \
                --admin-firstname=Admin                                             \
                --admin-lastname=Admin                                              \
                --admin-email="admin@example.com"                                   \
                --admin-user="$(MAGE_ADMIN_USER)"                                   \
                --admin-password="$(MAGE_ADMIN_PASS)"                               \
                --base-url="http://$(MAGE_WEB_DOMAIN):$(DOCKER_WEB_PORT)/"          \
                --backend-frontname=admin                                           \
                --db-host="mage2devbox-241-db"                                      \
                --db-name="magento2_241"                                            \
                --db-user="magento2"                                                \
                --db-password="magento2"                                            \
                --language="$(MAGE_LANG)"                                           \
                --currency="$(MAGE_CURRENCY)"                                       \
                --timezone="$(TIMEZONE)"                                            \
                --use-rewrites=1                                                    \
                --use-secure=0                                                      \
                --base-url-secure="https://$(MAGE_WEB_DOMAIN):$(DOCKER_WEB_PORT)/"  \
                --use-secure-admin=0                                                \
                --session-save=files                                                \
                --elasticsearch-host="mage2devbox-241-elastic"                      \
                --elasticsearch-port="$(DOCKER_ES_PORT)"                            \
            && composer require markshust/magento2-module-disabletwofactorauth      \
            && bin/magento module:enable MarkShust_DisableTwoFactorAuth             \
            && bin/magento setup:upgrade                                            \
            && bin/magento config:set twofactorauth/general/enable 0                \
            && chown -R www-data:www-data /var/www/html/                            \
COMMAND;
        $installCommand = Devbox::extrapolateEnv($installCommand);

        $this->inDocker('web', $installCommand, [$this->getMageSrcDir(), '/var/www/html'], false);
    }

    protected function emptyDb()
    {
        $command = "mysqldump -u magento2 -pmagento2 --add-drop-table --no-data magento2_241 | grep ^DROP | mysql -u magento2 -pmagento2 magento2_241";

        $this->status('<info>Emptying database...</info>');
        $this->inDocker('db', $command);
    }

}
