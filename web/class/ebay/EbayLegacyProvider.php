<?php

class EbayLegacyProvider implements EbayProviderInterface
{
    public function addItem(array $params)
    {
        return legacy_ebay_AddItem($params);
    }

    public function reviseItem(array $params)
    {
        return legacy_ebay_ReviseItem($params);
    }

    public function endItem(array $params)
    {
        return legacy_ebay_EndItem($params);
    }

    public function getOrders(array $params)
    {
        return legacy_ebay_GetOrders($params);
    }

    public function getSellerList(array $params)
    {
        return legacy_ebay_GetSellerList($params);
    }
}
