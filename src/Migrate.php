<?php

namespace Stripe\Tools;

use Stripe\Tools\Migrate\Plan;
use Stripe\Tools\Migrate\Model;
use Stripe\Tools\Migrate\Coupon;
use Stripe\Tools\Migrate\Subscription;
use Stripe\Tools\Finder\Customer as Finder;
use function Stripe\Tools\find_active_subscription;

class Migrate extends Model
{
    public function run(callable $callback)
    {
        (new Plan($this->fromKey, $this->toKey))->run();
        (new Coupon($this->fromKey, $this->toKey))->run();
        $finder = new Finder($this->fromKey);
        $filter = function ($customer) {
            return find_active_subscription($customer);
        };
        $migrate = new Subscription($this->fromKey, $this->toKey);
        foreach($finder->all($filter) as $customer) {
            $migrate->run($customer, $callback);
        }
    }
}
