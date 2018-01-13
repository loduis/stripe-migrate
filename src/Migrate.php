<?php

namespace Stripe\Tools;

use Exception;
use Stripe\Plan;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Subscription;
use Stripe\Tools\Customer\Finder;
use function Stripe\Tools\find_active_subscription;
use function Stripe\Tools\find_active_or_trialing_subscription;

class Migrate
{
    protected $fromKey;

    protected $toKey;

    public function __construct($fromKey, $toKey)
    {
        $this->fromKey = $fromKey;
        $this->toKey = $toKey;
    }

    public function all(callable $callback)
    {
        $this->plan();
        foreach ((new Finder($this->fromKey))->all() as $customer) {
            $this->run($customer, $callback);
        }
    }

    public function plan()
    {
        $this->useFromKey();
        $from = Plan::all();
        $this->useToKey();
        $to = Plan::all();
        $new = [];
        foreach ($from->data as $plan) {
            $result = array_filter($to->data, function ($_plan) use($plan) {
                return $_plan->id == $plan->id;
            });
            if (!$result) {
                $new[] = $plan;
            }
        }
        foreach ($new as $plan) {
            $data = $plan->__toArray(true);
            unset(
                $data['created'],
                $data['livemode'],
                $data['object']
            );
            Plan::create($data);
        }
    }

    public function run($customer, callable $callback)
    {
        try {
            $this->useFromKey();
            $customer = $this->getCustomer($customer);
            $from = find_active_subscription($customer); // old subscription
            if ($from && ($to = $this->createSubscription($from))) {
                $result = $callback(null, $to->id, $from->id, $customer->id);
                if ($result !== false) {
                    $this->cancelSubscription($from);
                }
                return true;
            }
            return false;
        } catch (Exception $e) {
            if ($customer instanceof Customer) {
                $customer = $customer->id;
            }
            $callback($e, null, null, $customer);
        }
    }

    protected function getCustomer($customer)
    {
        if (!$customer instanceof Customer) {
            $customer = Customer::retrieve($customer);
        }

        return $customer;
    }

    protected function createSubscription(Subscription $from)
    {
        $this->useToKey();
        $customer = $this->getCustomer($from->customer);
        if (($to = find_active_or_trialing_subscription($customer)) &&
            $this->migratedSubscription($to, $from)
        ) {
            return $to;
        }
        if (!$from->cancel_at_period_end) {
            return Subscription::create(
                $this->getSubscriptionData($from)
            );
        }
    }

    protected function migratedSubscription(Subscription $to, Subscription $from)
    {
        $metadata = $to->metadata;

        return isset($metadata->migrated_from) &&
            $metadata->migrated_from == $from->id;
    }

    protected function getSubscriptionData(Subscription $subscription)
    {
        $metadata = $subscription->metadata->__toArray();
        $metadata['migrated_from'] = $subscription->id;

        return [
            'customer' => $subscription->customer,
            'application_fee_percent' => $subscription->application_fee_percent,
            'billing' => $subscription->billing,
            'days_until_due' => $subscription->days_until_due,
            'items' => [
                [
                    'plan' => $subscription->plan->id,
                    'quantity' => $subscription->quantity
                ]
            ],
            'metadata' => $metadata,
            'trial_end' => $subscription->current_period_end
        ];
    }

    protected function cancelSubscription(Subscription $subscription)
    {
        if (!$subscription->cancel_at_period_end) {
            $this->useFromKey();
            $subscription->cancel(['at_period_end' => true]);
        }
    }

    protected function useFromKey()
    {
        Stripe::setApiKey($this->fromKey);
    }

    protected function useToKey()
    {
        Stripe::setApiKey($this->toKey);
    }
}
