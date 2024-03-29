<?php
namespace app\cart\storage;

interface StorageInterface
{
    /**
     * @return array of cart items
     */
    public function load(): array;

    /**
     * @param array $items from cart
     */
    public function save(array $items);
}