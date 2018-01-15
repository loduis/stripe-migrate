## Stripe migrate subscription tool

> **Note**: This source only work for one subscription, for customer

This is an simple tool for migrate stripe subscription, for run this process you need:

- Write email to stripe team for create a copy of customer in new account
- Stop your webhooks in the accounts
- Run the migration

### That makes ?
- When method all is invoke this create the plan, or you can create plan invoice method plan
- Copy the current subscription of customer in the new account
- Set the **trial_end** the new account to the **current_period_end** the old account
- Mark subscription for cancel at end period

### Usage Instructions

Migrate the subscription the one customer

```php
use Stripe\Tools\Migrate\Subscription as Migrate;

require './vendor/autoload.php';

$migrate = new Migrate(
    'stripe from account api key',
    'stripe to account api key'
    //, true // force to live
);

$callback = function ($err, $to, $from, $customer) {
    if ($err) {
        throw $err;
    }
    echo 'Customer: -> ', $customer, PHP_EOL;
    echo 'Old subscription: -> ', $from, PHP_EOL;
    echo 'New subscription: -> ', $to, PHP_EOL;
    // in this callback youn can update you database ids
    // if the callback return false the old subscription is not cancelled
    // you can no return any o return true for cancel subscription
};

if ($migrate->run('stripe customer id', $callback) === false) {
    echo 'It was not possible to find an active subscription in',
    ' the old account or he already has a subscription in the new account';
}

```

Migrate the subscription the all customers

```php
use Stripe\Tools\Migrate;

require './vendor/autoload.php';

$migrate = new Migrate(
    'stripe from account api key',
    'stripe to account api key'
);

$migrate->run(function ($err, $to, $from, $customer) {
    if ($err) {
        throw $err;
    }
    echo 'Customer: -> ', $customer, PHP_EOL;
    echo 'Old subscription: -> ', $from, PHP_EOL;
    echo 'New subscription: -> ', $to, PHP_EOL; // you need store in database
    echo '---------------------------------', PHP_EOL;
})
```
