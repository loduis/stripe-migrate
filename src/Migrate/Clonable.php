<?php

namespace Stripe\Tools\Migrate;

use function Stripe\Tools\array_diff;

trait Clonable
{
    public function run(callable $callback = null)
    {
        $data = array_diff($this->findAll('from'), $this->findAll('to'));
        foreach ($data as $row) {
            unset(
                $row['created'],
                $row['livemode'],
                $row['object']
            );
            $new = $this->create($row);
            if ($callback) {
                $callback($new);
            }
        }
    }

    protected function findAll($key)
    {
        $finder = static::FINDER;
        $key = $key . 'Key';

        return (new $finder($this->$key))->allAsArray();
    }

    abstract protected function create($data);
}
