<?php

namespace Stripe\Tools\Customer;

use Stripe\Stripe;
use Stripe\Customer;
use function Stripe\Tools\find_active_subscription;

class Finder
{
    const MAX_LIMIT = 100;


    public function __construct($key)
    {
        Stripe::setApiKey($key);
    }

    public function all(array $params = ['limit' => self::MAX_LIMIT])
    {
        if (isset($params['limit']) && $params['limit'] > static::MAX_LIMIT) {
            $params['limit'] = static::MAX_LIMIT;
        }

        yield from $this->findAll($params);
    }

    protected function findAll(array $params)
    {
        $customers = Customer::all($params);
        $data = $customers->data;
        foreach ($data as $customer) {
            if ($this->isActive($customer)) {
                yield($customer);
            }
        }
        if ($customers->has_more) {
            usleep(1000);
            $last = end($data);
            $params['starting_after'] = $last->id;
            yield from $this->findAll($params);
        }
    }

    protected function isActive($customer)
    {
        return find_active_subscription($customer);
    }
}
