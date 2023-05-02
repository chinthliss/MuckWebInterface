<template>
    <div class="container">

        <h1>Account Transaction</h1>
        <dl class="row">

            <template v-if="transaction.account_id !== accountId()">
                <dt class="col-sm-3 text-primary">Account ID</dt>
                <dd class="col-sm-9">{{ transaction.account_id }}</dd>
            </template>

            <dt class="col-sm-3 text-primary">Transaction ID</dt>
            <dd class="col-sm-9">{{ transaction.id }}</dd>

            <dt class="col-sm-3 text-primary">Type</dt>
            <dd class="col-sm-9">{{ transaction.type }}</dd>

            <dt class="col-sm-3 text-primary">Purchase Description</dt>
            <dd class="col-sm-9">{{ transaction.purchase_description }}</dd>

            <dt class="col-sm-3 text-primary">Total Price (USD)</dt>
            <dd class="col-sm-9">{{ transaction.total_usd }}</dd>

            <dt class="col-sm-3 text-primary">Status</dt>
            <dd class="col-sm-9">{{ renderStatus() }}</dd>

            <dt class="col-sm-3 text-primary">Created</dt>
            <dd class="col-sm-9">{{ carbonToString(transaction.created_at) }}</dd>

            <dt class="col-sm-3 text-primary">Paid</dt>
            <dd class="col-sm-9">{{ carbonToString(transaction.paid_at) }}</dd>

            <dt class="col-sm-3 text-primary">Completed/Finalised</dt>
            <dd class="col-sm-9">{{ carbonToString(transaction.completed_at) }}</dd>

            <dt class="col-sm-3 text-primary">Account Currency Quoted</dt>
            <dd class="col-sm-9">{{ transaction.account_currency_quoted }}</dd>

            <dt class="col-sm-3 text-primary">Account Currency Rewarded</dt>
            <dd class="col-sm-9">{{ transaction.account_currency_rewarded }}</dd>

            <template v-if="transaction.account_currency_rewarded_items">
                <dt class="col-sm-3 text-primary">
                    Additional Account Currency Rewarded from Items
                </dt>
                <dd class="col-sm-9">{{ transaction.account_currency_rewarded_items }}</dd>
            </template>

            <dt class="col-sm-3 text-primary">Items</dt>
            <dd class="col-sm-9">{{ transaction.items }}</dd>


        </dl>

        <div v-if="transaction.subscription_id">
            This transaction was made as part of subscription {{ transaction.subscription_id }}.
            <div v-if="transaction.subscription_url">
                <a :href="transaction.subscription_url">View subscription</a>
            </div>
        </div>
    </div>
</template>

<script setup>
import {ref} from "vue";
import {carbonToString} from "../formatting";
import {accountId} from "../siteutils";

/**
 * @typedef {object} AccountTransaction
 * @property {string} id
 * @property {int} account_id
 * @property {int} subscription_id
 * @property {string} purchase_description
 * @property {string} type
 * @property {bool} open
 * @property {string} created_at
 * @property {string} paid_at
 * @property {string} completed_at
 * @property {int} total_usd
 * @property {float} account_currency_quoted
 * @property {float} account_currency_rewarded
 * @property {float} account_currency_rewarded_items
 * @property {float} total_account_currency_rewarded
 * @property {int} items
 * @property {string} result
 * @property {string} url
 */

const props = defineProps({
    transactionIn: {type: Object, required: true}
});

/**
 * @type Ref<AccountTransaction>
 */
const transaction = ref(props.transactionIn);

const renderStatus = () => {
    if (!transaction.value.result) return transaction.value.paid_at ? "Paid and pending fulfillment." : 'Open';
    switch (transaction.value.result) {
        case 'fulfilled':
            return 'Fulfilled';
        case 'user_declined':
            return 'User declined transaction';
        case 'vendor_refused':
            return "Payment attempted but wasn't accepted";
        case 'expired':
            return "Timed out (Expired)";
        default:
            return 'Unknown';
    }
};

</script>

<style scoped>

</style>
