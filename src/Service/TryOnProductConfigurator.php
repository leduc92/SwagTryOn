<?php declare(strict_types=1);

namespace Shopware\SwagTryOn\Service;

use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[Package('inventory')]
final class TryOnProductConfigurator
{
    public const EXTENSION_NAME = 'swag_try_on';

    private const CONFIG_PREFIX = 'SwagTryOn.config.';

    private const CUSTOM_FIELD_ENABLED = 'swag_try_on_enabled';

    private const CUSTOM_FIELD_WEAR_MODE = 'swag_try_on_wear_mode';

    private const CUSTOM_FIELD_OVERLAY_URL = 'swag_try_on_overlay_url';

    private const CUSTOM_FIELD_OVERLAY_LEFT_URL = 'swag_try_on_overlay_left_url';

    private const CUSTOM_FIELD_OVERLAY_RIGHT_URL = 'swag_try_on_overlay_right_url';

    private const CUSTOM_FIELD_SCALE = 'swag_try_on_scale';

    private const CUSTOM_FIELD_OFFSET_X = 'swag_try_on_offset_x';

    private const CUSTOM_FIELD_OFFSET_Y = 'swag_try_on_offset_y';

    public function __construct(
        private readonly SystemConfigService $systemConfigService,
        private readonly RouterInterface $router,
    ) {
    }

    public function buildForProduct(SalesChannelProductEntity $product, SalesChannelContext $salesChannelContext): ?ArrayStruct
    {
        $salesChannelId = $salesChannelContext->getSalesChannelId();
        if (!$this->readBoolConfig('enabled', $salesChannelId, true)) {
            return null;
        }

        $customFields = $product->getCustomFields() ?? [];
        if (!$this->readBoolValue($customFields[self::CUSTOM_FIELD_ENABLED] ?? null, false)) {
            return null;
        }

        $overlayImageUrl = $this->readStringValue($customFields[self::CUSTOM_FIELD_OVERLAY_URL] ?? null) ?? $this->resolveProductCoverUrl($product);
        if ($overlayImageUrl === null) {
            return null;
        }

        $leftOverlayImageUrl = $this->readStringValue($customFields[self::CUSTOM_FIELD_OVERLAY_LEFT_URL] ?? null) ?? $overlayImageUrl;
        $rightOverlayImageUrl = $this->readStringValue($customFields[self::CUSTOM_FIELD_OVERLAY_RIGHT_URL] ?? null) ?? $overlayImageUrl;

        $wearMode = $this->normalizeWearMode(
            $this->readStringValue($customFields[self::CUSTOM_FIELD_WEAR_MODE] ?? null)
                ?? $this->readStringConfig('defaultWearMode', $salesChannelId, 'watch')
        );

        $mobileUrl = $this->router->generate(
            'storefront.swag.try.on.viewer',
            ['productId' => $product->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return new ArrayStruct([
            'productId' => $product->getId(),
            'productName' => $product->getName() ?? $product->getProductNumber(),
            'wearMode' => $wearMode,
            'overlayImageUrl' => $overlayImageUrl,
            'overlayImageUrls' => [
                'center' => $overlayImageUrl,
                'left' => $leftOverlayImageUrl,
                'right' => $rightOverlayImageUrl,
            ],
            'scale' => $this->readFloatValue($customFields[self::CUSTOM_FIELD_SCALE] ?? null, 1.0),
            'offsetX' => $this->readFloatValue($customFields[self::CUSTOM_FIELD_OFFSET_X] ?? null, 0.0),
            'offsetY' => $this->readFloatValue($customFields[self::CUSTOM_FIELD_OFFSET_Y] ?? null, 0.0),
            'mobileUrl' => $mobileUrl,
            'mediapipeBundleUrl' => $this->readStringConfig('mediapipeBundleUrl', $salesChannelId),
            'mediapipeWasmRoot' => $this->readStringConfig('mediapipeWasmRoot', $salesChannelId),
            'mediapipeHandModelUrl' => $this->readStringConfig('mediapipeHandModelUrl', $salesChannelId),
        ], self::EXTENSION_NAME);
    }

    private function normalizeWearMode(?string $wearMode): string
    {
        $normalized = mb_strtolower(trim((string) $wearMode));

        return $normalized === 'ring' ? 'ring' : 'watch';
    }

    private function resolveProductCoverUrl(SalesChannelProductEntity $product): ?string
    {
        $cover = $product->getCover();
        $media = $cover?->getMedia();

        return $media?->getUrl();
    }

    private function readBoolConfig(string $key, string $salesChannelId, bool $default): bool
    {
        return $this->readBoolValue(
            $this->systemConfigService->get(self::CONFIG_PREFIX . $key, $salesChannelId),
            $default
        );
    }

    private function readStringConfig(string $key, string $salesChannelId, ?string $default = null): ?string
    {
        return $this->readStringValue(
            $this->systemConfigService->get(self::CONFIG_PREFIX . $key, $salesChannelId),
            $default
        );
    }

    private function readBoolValue(mixed $value, bool $default): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        if (\is_string($value)) {
            $normalized = mb_strtolower(trim($value));

            if (\in_array($normalized, ['1', 'true', 'yes'], true)) {
                return true;
            }

            if (\in_array($normalized, ['0', 'false', 'no'], true)) {
                return false;
            }
        }

        if (\is_int($value) || \is_float($value)) {
            return (bool) $value;
        }

        return $default;
    }

    private function readFloatValue(mixed $value, float $default): float
    {
        if (\is_float($value) || \is_int($value)) {
            return (float) $value;
        }

        if (\is_string($value) && is_numeric($value)) {
            return (float) $value;
        }

        return $default;
    }

    private function readStringValue(mixed $value, ?string $default = null): ?string
    {
        if (!\is_string($value)) {
            return $default;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? $default : $trimmed;
    }
}
