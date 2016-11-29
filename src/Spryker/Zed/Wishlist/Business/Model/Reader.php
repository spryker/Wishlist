<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Wishlist\Business\Model;

use ArrayObject;
use Generated\Shared\Transfer\WishlistOverviewRequestTransfer;
use Generated\Shared\Transfer\WishlistOverviewResponseTransfer;
use Generated\Shared\Transfer\WishlistPaginationTransfer;
use Generated\Shared\Transfer\WishlistTransfer;
use Propel\Runtime\Util\PropelModelPager;
use Spryker\Zed\Wishlist\Business\Exception\MissingWishlistException;
use Spryker\Zed\Wishlist\Business\Transfer\WishlistTransferMapperInterface;
use Spryker\Zed\Wishlist\Dependency\QueryContainer\WishlistToProductInterface;
use Spryker\Zed\Wishlist\Persistence\WishlistQueryContainerInterface;

class Reader implements ReaderInterface
{

    /**
     * @var \Spryker\Zed\Wishlist\Persistence\WishlistQueryContainerInterface
     */
    protected $queryContainer;

    /**
     * @var \Spryker\Zed\Wishlist\Dependency\QueryContainer\WishlistToProductInterface
     */
    protected $productQueryContainer;

    /**
     * @var \Spryker\Zed\Wishlist\Business\Transfer\WishlistTransferMapperInterface
     */
    protected $transferMapper;

    /**
     * @param \Spryker\Zed\Wishlist\Persistence\WishlistQueryContainerInterface $queryContainer
     * @param \Spryker\Zed\Wishlist\Dependency\QueryContainer\WishlistToProductInterface $productQueryContainer
     * @param \Spryker\Zed\Wishlist\Business\Transfer\WishlistTransferMapperInterface $transferMapper
     */
    public function __construct(
        WishlistQueryContainerInterface $queryContainer,
        WishlistToProductInterface $productQueryContainer,
        WishlistTransferMapperInterface $transferMapper
    ) {
        $this->queryContainer = $queryContainer;
        $this->productQueryContainer = $productQueryContainer;
        $this->transferMapper = $transferMapper;
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistTransfer $wishlistTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistTransfer
     */
    public function getWishlistByName(WishlistTransfer $wishlistTransfer)
    {
        $wishlistTransfer->requireFkCustomer();
        $wishlistTransfer->requireName();

        $wishlistEntity = $this->getWishlistEntityByCustomerIdAndName(
            $wishlistTransfer->getFkCustomer(),
            $wishlistTransfer->getName()
        );

        return $this->transferMapper->convertWishlist($wishlistEntity);
    }

    /**
     * @api
     *
     * @param \Generated\Shared\Transfer\WishlistOverviewRequestTransfer $wishlistOverviewRequestTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistOverviewResponseTransfer
     */
    public function getWishlistOverview(WishlistOverviewRequestTransfer $wishlistOverviewRequestTransfer)
    {
        $wishlistOverviewRequestTransfer->requireWishlist();

        $wishlistTransfer = $wishlistOverviewRequestTransfer->getWishlist();
        $wishlistTransfer->requireFkCustomer();
        $wishlistTransfer->requireName();

        $paginationTransfer = (new WishlistPaginationTransfer())
            ->setPage($wishlistOverviewRequestTransfer->getPage())
            ->setItemsPerPage($wishlistOverviewRequestTransfer->getItemsPerPage());

        $responseTransfer = (new WishlistOverviewResponseTransfer())
            ->setWishlist($wishlistTransfer)
            ->setPagination($paginationTransfer);

        $wishlistEntity = $this->queryContainer
            ->queryWishlistByCustomerId($wishlistTransfer->getFkCustomer())
            ->filterByName($wishlistTransfer->getName())
            ->findOne();

        if (!$wishlistEntity) {
            return $responseTransfer;
        }

        $wishlistTransfer = $this->transferMapper->convertWishlist($wishlistEntity);
        $wishlistOverviewRequestTransfer->setWishlist($wishlistTransfer);
        $itemPaginationModel = $this->getWishlistOverviewPaginationModel($wishlistOverviewRequestTransfer);

        $paginationTransfer = $this->updatePaginationTransfer($paginationTransfer, $itemPaginationModel);
        $items = $this->transferMapper->convertWishlistItemCollection(
            $itemPaginationModel->getResults()
        );

        $items = $this->expandProductId($items);

        $responseTransfer
            ->setWishlist($wishlistTransfer)
            ->setPagination($paginationTransfer)
            ->setItems(new ArrayObject($items));

        return $responseTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistPaginationTransfer $paginationTransfer
     * @param \Propel\Runtime\Util\PropelModelPager $itemPaginationModel
     *
     * @return \Generated\Shared\Transfer\WishlistPaginationTransfer
     */
    protected function updatePaginationTransfer(WishlistPaginationTransfer $paginationTransfer, PropelModelPager $itemPaginationModel)
    {
        $pagesTotal = ceil($itemPaginationModel->getNbResults() / $itemPaginationModel->getMaxPerPage());
        $paginationTransfer->setPagesTotal($pagesTotal);
        $paginationTransfer->setItemsTotal($itemPaginationModel->getNbResults());
        $paginationTransfer->setItemsPerPage($itemPaginationModel->getMaxPerPage());

        if ($paginationTransfer->getPage() <= 0) {
            $paginationTransfer->setPage(1);
        }

        if ($paginationTransfer->getPage() > $pagesTotal) {
            $paginationTransfer->setPage($pagesTotal);
        }

        return $paginationTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistOverviewRequestTransfer $wishlistOverviewRequestTransfer
     *
     * @return \Orm\Zed\Wishlist\Persistence\SpyWishlistItem[]|\Propel\Runtime\Util\PropelModelPager
     */
    protected function getWishlistOverviewPaginationModel(WishlistOverviewRequestTransfer $wishlistOverviewRequestTransfer)
    {
        $wishlistOverviewRequestTransfer->requireWishlist();
        $wishlistOverviewRequestTransfer->getWishlist()->requireIdWishlist();

        $page = $wishlistOverviewRequestTransfer
            ->requirePage()
            ->getPage();

        $maxPerPage = $wishlistOverviewRequestTransfer
            ->requireItemsPerPage()
            ->getItemsPerPage();

        $itemsQuery = $this->queryContainer->queryItemsByWishlistId(
            $wishlistOverviewRequestTransfer->getWishlist()->getIdWishlist()
        );

        return $itemsQuery->paginate($page, $maxPerPage);
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistItemTransfer[] $itemCollection
     *
     * @return \Generated\Shared\Transfer\WishlistItemTransfer[]
     */
    protected function expandProductId(array $itemCollection)
    {
        $skuCollection = [];
        foreach ($itemCollection as $itemTransfer) {
            $skuCollection[] = $itemTransfer->getSku();
        }

        $productCollection = $this->productQueryContainer
            ->queryProduct()
            ->filterBySku_In($skuCollection);

        /* @var \Orm\Zed\Product\Persistence\SpyProduct $productEntity */
        foreach ($productCollection as $productEntity) {
            foreach ($itemCollection as $itemTransfer) {
                if (mb_strtolower($itemTransfer->getSku()) === mb_strtolower($productEntity->getSku())) {
                    $itemTransfer->setIdProduct($productEntity->getIdProduct());
                }
            }
        }

        return $itemCollection;
    }

    /**
     * @param int $idWishlist
     *
     * @return int
     */
    protected function getTotalItemCount($idWishlist)
    {
        return $this->queryContainer->queryWishlistItem()
            ->filterByFkWishlist($idWishlist)
            ->count();
    }

    /**
     * @param int $idWishlist
     *
     * @throws \Spryker\Zed\Wishlist\Business\Exception\MissingWishlistException
     *
     * @return \Orm\Zed\Wishlist\Persistence\SpyWishlist
     */
    public function getWishlistEntityById($idWishlist)
    {
        $wishListEntity = $this->queryContainer->queryWishlist()
            ->filterByIdWishlist($idWishlist)
            ->findOne();

        if (!$wishListEntity) {
            throw new MissingWishlistException(sprintf(
                'Wishlist with id %s not found',
                $idWishlist
            ));
        }

        return $wishListEntity;
    }

    /**
     * @param int $idCustomer
     * @param string $name
     *
     * @throws \Spryker\Zed\Wishlist\Business\Exception\MissingWishlistException
     *
     * @return \Orm\Zed\Wishlist\Persistence\SpyWishlist
     */
    public function getWishlistEntityByCustomerIdAndName($idCustomer, $name)
    {
        $wishlistEntity = $this->queryContainer
            ->queryWishlistByCustomerId($idCustomer)
            ->filterByName($name)
            ->findOne();

        if (!$wishlistEntity) {
            throw new MissingWishlistException(sprintf(
                'Wishlist: %s for customer with id: %s not found',
                $name,
                $idCustomer
            ));
        }

        return $wishlistEntity;
    }

    /**
     * @param int $idCustomer
     * @param string $name
     *
     * @return bool
     */
    protected function hasCustomerWishlist($idCustomer, $name)
    {
        return $this->queryContainer
            ->queryWishlistByCustomerId($idCustomer)
            ->filterByName($name)
            ->count() > 0;
    }

}
