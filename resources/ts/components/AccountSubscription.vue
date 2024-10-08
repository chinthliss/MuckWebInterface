<script setup lang="ts">

import {carbonToString} from "../formatting";
import {accountId} from "../siteutils";
import {Ref, ref} from "vue";
import {AccountSubscription, AccountTransaction} from "../defs";
import AccountTransactions from "./AccountTransactions.vue";

const props = defineProps<{
    subscriptionIn: AccountSubscription,
    transactionsIn: AccountTransaction[]
}>();

const subscription: Ref<AccountSubscription> = ref(props.subscriptionIn);
const transactions: Ref<AccountTransaction[]> = ref(props.transactionsIn);

const friendlyStatus = () => {
    switch (subscription.value.status) {
        case 'approval_pending':
            return 'Approval Pending';
        case 'user_declined':
            return 'User declined';
        case 'active':
            return subscription.value.last_charge_at ? 'Active' : 'Pending First Charge';
        case 'suspended':
            return "Suspended";
        case 'cancelled':
            return "Cancelled";
        case 'expired':
            return "Expired";
        default:
            return 'Unknown';
    }
}

</script>

<template>
    <div class="container">

        <h1>Subscription Details</h1>

        <dl class="row">

            <div class="row" v-if="accountId() !== subscription.account_id">
                <dt class="col-sm-3">Account ID</dt>
                <dd class="col-sm-9">{{ subscription.account_id }}</dd>
            </div>

            <div class="row">
                <dt class="col-sm-3 text-primary">Id</dt>
                <dd class="col-sm-9">{{ subscription.id }}</dd>
            </div>

            <div class="row">
                <dt class="col-sm-3 text-primary">Type</dt>
                <dd class="col-sm-9">{{ subscription.type }}</dd>
            </div>

            <div class="row">
                <dt class="col-sm-3 text-primary">Amount (USD)</dt>
                <dd class="col-sm-9">${{ subscription.amount_usd }}</dd>
            </div>

            <div class="row">
                <dt class="col-sm-3 text-primary">Recurring Interval (Days)</dt>
                <dd class="col-sm-9">{{ subscription.recurring_interval }}</dd>
            </div>

            <div class="row">
                <dt class="col-sm-3 text-primary">Status</dt>
                <dd class="col-sm-9">{{ friendlyStatus() }}</dd>
            </div>

            <div class="row">
                <dt class="col-sm-3 text-primary">Created</dt>
                <dd class="col-sm-9">{{ carbonToString(subscription.created_at) }}</dd>
            </div>

            <div class="row">
                <dt class="col-sm-3 text-primary">Last Charge</dt>
                <dd class="col-sm-9">{{
                        subscription.last_charge_at ? carbonToString(subscription.last_charge_at)
                            : 'No charge has been made yet'
                    }}
                </dd>
            </div>

            <div class="row">
                <dt class="col-sm-3 text-primary">Next Charge</dt>
                <dd class="col-sm-9">{{
                        subscription.next_charge_at ? carbonToString(subscription.next_charge_at)
                            : 'No charge pending'
                    }}
                </dd>
            </div>

            <div v-if="subscription.closed_at" class="row">
                <dt class="col-sm-3 text-primary">Closed</dt>
                <dd class="col-sm-9">{{ carbonToString(subscription.closed_at) }}</dd>
            </div>
        </dl>

        <h2>Transactions</h2>
        <AccountTransactions :transactions-in="transactions"></AccountTransactions>
    </div>
</template>

<style scoped>

</style>
