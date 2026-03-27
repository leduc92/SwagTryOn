<?php declare(strict_types=1);

namespace Shopware\SwagTryOn\Storefront\Controller;

use Shopware\Core\Content\Product\ProductException;
use Shopware\Core\Content\Product\SalesChannel\Detail\AbstractProductDetailRoute;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\RoutingException;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Framework\Routing\StorefrontRouteScope;
use Shopware\SwagTryOn\Service\TryOnProductConfigurator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Route(defaults: [PlatformRequest::ATTRIBUTE_ROUTE_SCOPE => [StorefrontRouteScope::ID]])]
#[Package('inventory')]
final class TryOnController extends StorefrontController
{
    public function __construct(
        private readonly AbstractProductDetailRoute $productDetailRoute,
        private readonly TryOnProductConfigurator $tryOnProductConfigurator,
    ) {
    }

    #[Route(path: '/try-on/{productId}', name: 'storefront.swag.try.on.viewer', methods: ['GET'])]
    public function viewer(string $productId, Request $request, SalesChannelContext $salesChannelContext): Response
    {
        if ($productId === '') {
            throw RoutingException::missingRequestParameter('productId');
        }

        $criteria = (new Criteria())
            ->addAssociation('manufacturer')
            ->addAssociation('cover.media');

        $product = $this->productDetailRoute->load($productId, $request, $salesChannelContext, $criteria)->getProduct();
        $tryOnConfig = $this->tryOnProductConfigurator->buildForProduct($product, $salesChannelContext);

        if ($tryOnConfig === null) {
            throw ProductException::productNotFound($productId);
        }

        return $this->renderStorefront('@SwagTryOn/storefront/page/swag-try-on/viewer.html.twig', [
            'product' => $product,
            'tryOn' => $tryOnConfig,
        ]);
    }
}
