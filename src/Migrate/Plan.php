<?php

namespace Stripe\Tools\Migrate;

use Stripe\Plan as Creator;
use Stripe\Tools\Finder\Plan as Finder;

class Plan extends Model
{
    use Clonable;

    const FINDER = Finder::class;

    protected function create($data)
    {
        return Creator::create($data);
    }
}
