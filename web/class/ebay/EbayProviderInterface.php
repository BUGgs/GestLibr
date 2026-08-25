<?php

interface EbayProviderInterface
{
    /**
     * @return string|bool Listing id (ItemID compatible) or false on failure
     */
    public function addItem(array $params);

    /**
     * @return string|bool Listing id (ItemID compatible) or false on failure
     */
    public function reviseItem(array $params);

    /**
     * @return bool
     */
    public function endItem(array $params);

    /**
     * @return mixed
     */
    public function getOrders(array $params);

    /**
     * @return mixed
     */
    public function getSellerList(array $params);
}
