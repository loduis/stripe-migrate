<?php

namespace Stripe\Tools;

function find_subscription_by_status($customer, $status)
{
    if (count($customer->sources->data) > 0 &&
        count($customer->subscriptions->data) >0
    ) {
        $subscriptions = $customer->subscriptions->data;
        foreach ($subscriptions as $subscription) {
            if (in_array($subscription->status, $status)) {
                return $subscription;
            }
        }
    }
}

function find_active_subscription($customer)
{
    return find_subscription_by_status($customer, [
        'active'
    ]);
}

function find_active_or_trialing_subscription($customer)
{
    return find_subscription_by_status($customer, [
        'active',
        'trialing'
    ]);
}
