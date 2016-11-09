<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Wishlist\Business;

use Generated\Shared\Transfer\WishlistItemTransfer;
use Generated\Shared\Transfer\WishlistOverviewRequestTransfer;
use Generated\Shared\Transfer\WishlistTransfer;

interface WishlistFacadeInterface
{

    /**
     * Specification:
     *  - Creates wishlist for a specific customer with given name
     *  - Required values of WishlistTransfer: name, fkCustomer.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\WishlistTransfer $wishlistTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistTransfer
     */
    public function createWishlist(WishlistTransfer $wishlistTransfer);

    /**
     * Specification:
     *  - Updates wishlist
     *  - Required values of WishlistTransfer: idWishlist.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\WishlistTransfer $wishlistTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistTransfer
     */
    public function updateWishlist(WishlistTransfer $wishlistTransfer);

    /**
     * Specification:
     *  - Removes wishlist and its items
     *  - Required values of WishlistTransfer: idWishlist.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\WishlistTransfer $wishlistTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistTransfer
     */
    public function removeWishlist(WishlistTransfer $wishlistTransfer);

    /**
     * Specification:
     *  - Adds collection of items to a wishlist
     *  - Required values of WishlistTransfer: fkCustomer, name.
     *  - Required values of WishlistItemTransfer: fkProduct.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\WishlistTransfer $wishlistTransfer
     * @param array|\Generated\Shared\Transfer\WishlistItemTransfer[] $wishlistItemCollection
     *
     * @return void
     */
    public function addItemCollection(WishlistTransfer $wishlistTransfer, array $wishlistItemCollection);

    /**
     * Specification:
     *  - Removes all wishlist items
     *  - Required values: idWishlist.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\WishlistTransfer $wishlistTransfer
     *
     * @return void
     */
    public function emptyWishlist(WishlistTransfer $wishlistTransfer);

    /**
     * Specification:
     *  - Adds item to wishlist
     *  - Required values of WishlistItemTransfer: fkCustomer, fkProduct. Optional: wishlistName
     *    In case wishlist name is not provided the default value will be used.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\WishlistItemTransfer $wishlistItemTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistItemTransfer
     */
    public function addItem(WishlistItemTransfer $wishlistItemTransfer);

    /**
     * Specification:
     *  - Removes item from wishlist
     *  - Required values of WishlistItemTransfer: fkCustomer, fkProduct. Optional: wishlistName
     *    In case wishlist name is not provided the default value will be used.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\WishlistItemTransfer $wishlistItemTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistItemTransfer
     */
    public function removeItem(WishlistItemTransfer $wishlistItemTransfer);

    /**
     * Specification:
     *  - Returns wishlist by specific name for a given customer
     *  - Required values: fkCustomer, name
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\WishlistTransfer $wishlistTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistTransfer
     */
    public function getWishlistByName(WishlistTransfer $wishlistTransfer);

    /**
     * Specification:
     *  - Returns wishlist by specific name for a given customer, with paginated items.
     *  - Pagination is controlled with page, itemsPerPage, orderBy and orderDirection values of WishlistOverviewRequestTransfer.
     *  - Required values of WishlistTransfer: fkCustomer, name.
     *  - Required values of WishlistOverviewRequestTransfer: WishlistTransfer.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\WishlistOverviewRequestTransfer $wishlistOverviewRequestTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistOverviewResponseTransfer
     */
    public function getWishlistOverview(WishlistOverviewRequestTransfer $wishlistOverviewRequestTransfer);

}
