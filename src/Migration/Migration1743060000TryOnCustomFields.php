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
final class Migration1743060000TryOnCustomFields extends MigrationStep
{
    private const CUSTOM_FIELD_SET_NAME = 'swag_try_on';

    public function getCreationTimestamp(): int
    {
        return 1743060000;
    }

    public function update(Connection $connection): void
    {
        $setId = $this->upsertCustomFieldSet($connection);
        $this->upsertSetRelation($connection, $setId);

        $this->upsertCustomField(
            $connection,
            $setId,
            'swag_try_on_enabled',
            CustomFieldTypes::BOOL,
            [
                'label' => [
                    'en-GB' => 'Enable virtual try-on',
                    'de-DE' => 'Virtuelle Anprobe aktivieren',
                ],
                'componentName' => 'sw-switch-field',
                'customFieldType' => 'switch',
                'customFieldPosition' => 1,
            ]
        );

        $this->upsertCustomField(
            $connection,
            $setId,
            'swag_try_on_wear_mode',
            CustomFieldTypes::SELECT,
            [
                'label' => [
                    'en-GB' => 'Wear mode',
                    'de-DE' => 'Tragemodus',
                ],
                'componentName' => 'sw-single-select',
                'customFieldType' => 'select',
                'customFieldPosition' => 2,
                'options' => [
                    [
                        'value' => 'watch',
                        'label' => [
                            'en-GB' => 'Watch',
                            'de-DE' => 'Uhr',
                        ],
                    ],
                    [
                        'value' => 'ring',
                        'label' => [
                            'en-GB' => 'Ring',
                            'de-DE' => 'Ring',
                        ],
                    ],
                ],
            ]
        );

        $this->upsertCustomField(
            $connection,
            $setId,
            'swag_try_on_overlay_url',
            CustomFieldTypes::TEXT,
            [
                'label' => [
                    'en-GB' => 'Overlay asset URL',
                    'de-DE' => 'Overlay-Asset-URL',
                ],
                'helpText' => [
                    'en-GB' => 'Transparent PNG or hosted asset for the mobile overlay. Leave empty to use the product cover image.',
                    'de-DE' => 'Transparentes PNG oder gehostetes Asset fuer das mobile Overlay. Leer lassen, um das Produktbild zu verwenden.',
                ],
                'componentName' => 'sw-field',
                'customFieldType' => 'text',
                'customFieldPosition' => 3,
            ]
        );

        $this->upsertCustomField(
            $connection,
            $setId,
            'swag_try_on_scale',
            CustomFieldTypes::FLOAT,
            [
                'label' => [
                    'en-GB' => 'Overlay scale',
                    'de-DE' => 'Overlay-Skalierung',
                ],
                'componentName' => 'sw-field',
                'customFieldType' => 'number',
                'customFieldPosition' => 4,
            ]
        );

        $this->upsertCustomField(
            $connection,
            $setId,
            'swag_try_on_offset_x',
            CustomFieldTypes::FLOAT,
            [
                'label' => [
                    'en-GB' => 'Horizontal offset',
                    'de-DE' => 'Horizontaler Offset',
                ],
                'componentName' => 'sw-field',
                'customFieldType' => 'number',
                'customFieldPosition' => 5,
            ]
        );

        $this->upsertCustomField(
            $connection,
            $setId,
            'swag_try_on_offset_y',
            CustomFieldTypes::FLOAT,
            [
                'label' => [
                    'en-GB' => 'Vertical offset',
                    'de-DE' => 'Vertikaler Offset',
                ],
                'componentName' => 'sw-field',
                'customFieldType' => 'number',
                'customFieldPosition' => 6,
            ]
        );
    }

    public function updateDestructive(Connection $connection): void
    {
    }

    private function upsertCustomFieldSet(Connection $connection): string
    {
        $setId = $connection->fetchOne(
            'SELECT LOWER(HEX(id)) FROM custom_field_set WHERE name = :name',
            ['name' => self::CUSTOM_FIELD_SET_NAME]
        );

        if (\is_string($setId) && $setId !== '') {
            return $setId;
        }

        $setId = Uuid::randomHex();

        $connection->insert('custom_field_set', [
            'id' => Uuid::fromHexToBytes($setId),
            'name' => self::CUSTOM_FIELD_SET_NAME,
            'config' => json_encode([
                'label' => [
                    'en-GB' => 'Virtual try-on',
                    'de-DE' => 'Virtuelle Anprobe',
                ],
            ], \JSON_THROW_ON_ERROR),
            'active' => true,
            'created_at' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);

        return $setId;
    }

    private function upsertSetRelation(Connection $connection, string $setId): void
    {
        $relationExists = $connection->fetchOne(
            'SELECT LOWER(HEX(id)) FROM custom_field_set_relation WHERE set_id = :setId AND entity_name = :entityName',
            [
                'setId' => Uuid::fromHexToBytes($setId),
                'entityName' => 'product',
            ]
        );

        if (\is_string($relationExists) && $relationExists !== '') {
            return;
        }

        $connection->insert('custom_field_set_relation', [
            'id' => Uuid::randomBytes(),
            'set_id' => Uuid::fromHexToBytes($setId),
            'entity_name' => 'product',
            'created_at' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);
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
