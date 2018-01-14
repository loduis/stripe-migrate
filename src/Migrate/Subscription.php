<?php

namespace Stripe\Tools\Migrate;

use Exception;
use Stripe\Customer;
use Stripe\Subscription as StripeSubscription;
use function Stripe\Tools\find_active_subscription;
use function Stripe\Tools\find_active_or_trialing_subscription;

class Subscription extends Model
{
    public function run($customer, callable $callback)
    {
        try {
            $this->useKey('from');
            $customer = $this->getCustomer($customer);
            $from = find_active_subscription($customer); // old subscription
            if ($from && ($to = $this->create($from))) {
                $result = $callback(null, $to->id, $from->id, $customer->id);
                if ($result !== false) {
                    $this->cancel($from);
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

    protected function create(StripeSubscription $from)
    {
        $this->useKey('to');
        $customer = $this->getCustomer($from->customer);
        if (($to = find_active_or_trialing_subscription($customer)) &&
            $this->migrated($to, $from)
        ) {
            return $to;
        }
        if (!$from->cancel_at_period_end) {
            return StripeSubscription::create(
                $this->getData($from)
            );
        }
    }

    protected function migrated(StripeSubscription $to, StripeSubscription $from)
    {
        $metadata = $to->metadata;

        return isset($metadata->migrated_from) &&
            $metadata->migrated_from == $from->id;
    }

    protected function getData(StripeSubscription $subscription)
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

    protected function cancel(StripeSubscription $subscription)
    {
        if (!$subscription->cancel_at_period_end) {
            $this->useKey('from');
            $subscription->cancel(['at_period_end' => true]);
        }
    }
}
