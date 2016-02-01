<?php

/**
 * (c) Spryker Systems GmbH copyright protected
 */

namespace Spryker\Zed\Wishlist\Business\Storage;

use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\WishlistChangeTransfer;
use Generated\Shared\Transfer\WishlistTransfer;
use Spryker\Zed\Product\Business\ProductFacade;

class InMemory implements StorageInterface
{

    /**
     * @var ProductFacade
     */
    protected $facadeProduct;

    /**
     * @var WishlistTransfer
     */
    protected $wishlistTransfer;

    /**
     * @param WishlistTransfer $wishlistTransfer
     * @param ProductFacade $facadeProduct
     */
    public function __construct(WishlistTransfer $wishlistTransfer, ProductFacade $facadeProduct)
    {
        $this->wishlistTransfer = $wishlistTransfer;
        $this->facadeProduct = $facadeProduct;
    }

    /**
     * @param WishlistChangeTransfer $wishlistChange
     *
     * @return \Generated\Shared\Transfer\WishlistTransfer
     */
    public function addItems(WishlistChangeTransfer $wishlistChange)
    {
        $wishlistIndex = $this->createIndex();
        foreach ($wishlistChange->getItems() as $wishlistItem) {
            if (isset($wishlistIndex[$wishlistItem->getGroupKey()])) {
                $key = $wishlistIndex[$wishlistItem->getGroupKey()];
                $existingItem = $this->wishlistTransfer->getItems()[$key];
                $existingItem->setQuantity($wishlistItem->getQuantity() + $existingItem->getQuantity());
            } else {
                $productConcrete = $this->facadeProduct->getProductConcrete($wishlistItem->getSku());
                $wishlistItem->setIdProductAbstract($productConcrete->getIdProductAbstract());
                $this->wishlistTransfer->addItem($wishlistItem);
            }
        }

        return $this->wishlistTransfer;
    }

    /**
     * @param WishlistChangeTransfer $wishlistChange
     *
     * @return \Generated\Shared\Transfer\WishlistTransfer
     */
    public function removeItems(WishlistChangeTransfer $wishlistChange)
    {
        $wishlistIndex = $this->createIndex();
        foreach ($wishlistChange->getItems() as $key => $wishlistItem) {
            if (isset($wishlistIndex[$wishlistItem->getGroupKey()])) {
                $this->decreaseItem($wishlistIndex[$wishlistItem->getGroupKey()], $wishlistItem);
            } else {
                $this->decreaseByProductIdentifier($wishlistIndex, $wishlistItem);
            }
        }

        return $this->wishlistTransfer;
    }

    /**
     * @param WishlistChangeTransfer $wishlistChange
     *
     * @return \Generated\Shared\Transfer\WishlistTransfer
     */
    public function increaseItems(WishlistChangeTransfer $wishlistChange)
    {
        return $this->addItems($wishlistChange);
    }

    /**
     * @param WishlistChangeTransfer $wishlistChange
     *
     * @return \Generated\Shared\Transfer\WishlistTransfer
     */
    public function decreaseItems(WishlistChangeTransfer $wishlistChange)
    {
        return $this->removeItems($wishlistChange);
    }

    /**
     * @param array $wishlistIndex
     * @param ItemTransfer $itemToChange
     *
     * @return void
     */
    protected function decreaseByProductIdentifier(array $wishlistIndex, ItemTransfer $itemToChange)
    {
        foreach ($this->wishlistTransfer->getItems() as $key => $item) {
            if ($item->getSku() === $itemToChange->getSku()) {
                $this->decreaseItem($wishlistIndex[$item->getGroupKey()], $itemToChange);

                return;
            }
        }
    }

    /**
     * @param int $index
     * @param ItemTransfer $itemToChange
     *
     * @return void
     */
    protected function decreaseItem($index, ItemTransfer $itemToChange)
    {
        $existingItems = $this->wishlistTransfer->getItems();
        $existingItem = $existingItems[$index];
        $newQuantity = $existingItem->getQuantity() - $itemToChange->getQuantity();

        if ($newQuantity > 0 && $itemToChange->getQuantity() > 0) {
            $existingItem->setQuantity($newQuantity);
        } else {
            unset($existingItems[$index]);
        }
    }

    /**
     * @return array
     */
    protected function createIndex()
    {
        $wishlistItem = $this->wishlistTransfer->getItems();
        $wishlistIndex = [];
        foreach ($wishlistItem as $key => $cartItem) {
            if (!empty($cartItem->getGroupKey())) {
                $wishlistIndex[$cartItem->getGroupKey()] = $key;
            }
        }

        return $wishlistIndex;
    }

}
