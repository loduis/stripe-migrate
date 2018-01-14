<?php

namespace Stripe\Tools\Migrate;

use Stripe\Coupon as Creator;
use Stripe\Tools\Finder\Coupon as Finder;

class Coupon extends Model
{
    use Clonable;

    const FINDER = Finder::class;

    protected function create($data)
    {
        unset(
            $data['times_redeemed'],
            $data['valid']
        );

        return Creator::create($data);
    }
}
