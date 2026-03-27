<?php declare(strict_types=1);

namespace Shopware\SwagTryOn\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\CustomField\CustomFieldTypes;

/**
 * @internal
 */
#[Package('inventory')]
final class Migration1743061000AddWatchAngleOverlayFields extends MigrationStep
{
    private const CUSTOM_FIELD_SET_NAME = 'swag_try_on';

    public function getCreationTimestamp(): int
    {
        return 1743061000;
    }

    public function update(Connection $connection): void
    {
        $setId = $connection->fetchOne(
            'SELECT LOWER(HEX(id)) FROM custom_field_set WHERE name = :name',
            ['name' => self::CUSTOM_FIELD_SET_NAME]
        );

        if (!\is_string($setId) || $setId === '') {
            return;
        }

        $this->upsertCustomField(
            $connection,
            $setId,
            'swag_try_on_overlay_left_url',
            CustomFieldTypes::TEXT,
            [
                'label' => [
                    'en-GB' => 'Overlay asset URL (left angle)',
                    'de-DE' => 'Overlay-Asset-URL (linker Winkel)',
                ],
                'helpText' => [
                    'en-GB' => 'Transparent PNG for when the wrist turns left.',
                    'de-DE' => 'Transparentes PNG fuer eine nach links gedrehte Hand.',
                ],
                'componentName' => 'sw-field',
                'customFieldType' => 'text',
                'customFieldPosition' => 4,
            ]
        );

        $this->upsertCustomField(
            $connection,
            $setId,
            'swag_try_on_overlay_right_url',
            CustomFieldTypes::TEXT,
            [
                'label' => [
                    'en-GB' => 'Overlay asset URL (right angle)',
                    'de-DE' => 'Overlay-Asset-URL (rechter Winkel)',
                ],
                'helpText' => [
                    'en-GB' => 'Transparent PNG for when the wrist turns right.',
                    'de-DE' => 'Transparentes PNG fuer eine nach rechts gedrehte Hand.',
                ],
                'componentName' => 'sw-field',
                'customFieldType' => 'text',
                'customFieldPosition' => 5,
            ]
        );
    }

    public function updateDestructive(Connection $connection): void
    {
    }

    /**
     * @param array<string, mixed> $config
     */
    private function upsertCustomField(Connection $connection, string $setId, string $name, string $type, array $config): void
    {
        $fieldId = $connection->fetchOne(
            'SELECT LOWER(HEX(id)) FROM custom_field WHERE name = :name',
            ['name' => $name]
        );

        $payload = [
            'name' => $name,
            'type' => $type,
            'config' => json_encode($config, \JSON_THROW_ON_ERROR),
            'active' => true,
            'set_id' => Uuid::fromHexToBytes($setId),
            'updated_at' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ];

        if (\is_string($fieldId) && $fieldId !== '') {
            $connection->update('custom_field', $payload, [
                'id' => Uuid::fromHexToBytes($fieldId),
            ]);

            return;
        }

        $payload['id'] = Uuid::randomBytes();
        $payload['created_at'] = (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        $connection->insert('custom_field', $payload);
    }
}
