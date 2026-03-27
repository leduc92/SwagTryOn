<?php declare(strict_types=1);

namespace Shopware\SwagTryOn;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;

#[Package('inventory')]
final class SwagTryOn extends Plugin
{
    private const CUSTOM_FIELD_SET_NAME = 'swag_try_on';

    private const SYSTEM_CONFIG_PREFIX = 'SwagTryOn.config.';

    public function install(InstallContext $installContext): void
    {
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);

        $this->removeCustomFieldSet($connection);
        $this->removeSystemConfig($connection);
    }

    public function activate(ActivateContext $activateContext): void
    {
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
    }

    public function update(UpdateContext $updateContext): void
    {
    }

    public function postInstall(InstallContext $installContext): void
    {
    }

    public function postUpdate(UpdateContext $updateContext): void
    {
    }

    private function removeCustomFieldSet(Connection $connection): void
    {
        $connection->executeStatement(
            'DELETE FROM `custom_field_set` WHERE `name` = :name',
            ['name' => self::CUSTOM_FIELD_SET_NAME]
        );
    }

    private function removeSystemConfig(Connection $connection): void
    {
        $connection->executeStatement(
            'DELETE FROM `system_config` WHERE `configuration_key` LIKE :prefix',
            ['prefix' => self::SYSTEM_CONFIG_PREFIX . '%']
        );
    }
}
