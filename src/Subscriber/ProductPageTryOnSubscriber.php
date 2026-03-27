<?php declare(strict_types=1);

namespace Shopware\SwagTryOn\Subscriber;

use Shopware\Core\Framework\Log\Package;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Shopware\SwagTryOn\Service\TryOnProductConfigurator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('inventory')]
final class ProductPageTryOnSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TryOnProductConfigurator $tryOnProductConfigurator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductPageLoadedEvent::class => 'onProductLoaded',
        ];
    }

    public function onProductLoaded(ProductPageLoadedEvent $event): void
    {
        $tryOnConfig = $this->tryOnProductConfigurator->buildForProduct(
            $event->getPage()->getProduct(),
            $event->getSalesChannelContext()
        );

        if ($tryOnConfig === null) {
            return;
        }

        $event->getPage()->addExtension(TryOnProductConfigurator::EXTENSION_NAME, $tryOnConfig);
    }
}
