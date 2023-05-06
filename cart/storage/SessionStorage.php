<?php
namespace app\cart\storage;
use yii\web\Session;

class SessionStorage implements StorageInterface
{
    private Session $session;
    private string $key;

    public function __construct(Session $session, $key)
    {
        $this->key = $key;
        $this->session = $session;
    }

    public function load() :array
    {
        return $this->session->get($this->key, []);
    }

    public function save(array $items)
    {
        $this->session->set($this->key, $items);
    }
}
