<?php

namespace Stripe\Tools\Migrate;

use Stripe\Stripe;

abstract class Model
{
    protected $fromKey;

    protected $toKey;

    public function __construct($fromKey, $toKey)
    {
        $this->fromKey = $fromKey;
        $this->toKey = $toKey;
    }

    protected function useKey($name)
    {
        $key = $name . 'Key';
        Stripe::setApiKey($this->$key);
    }
}
