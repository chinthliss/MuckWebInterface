<script setup lang="ts">

import {Ref, ref} from 'vue';
import DataTable from 'datatables.net-vue3';
import {carbonToString, usdToString} from "../formatting";
import {AccountTransaction} from "../defs";

const props = defineProps<{
    transactionsIn: AccountTransaction
}>();

const transactions: Ref<AccountTransaction> = ref(props.transactionsIn);

const renderResult = (result) => {
    if (!result) return 'Open'
    if (result === 'fulfilled') return 'Fulfilled';
    if (result === 'user_declined') return 'User Declined';
    if (result === 'vendor_refused') return 'Vendor Declined';
    if (result === 'expired') return 'Expired';
    return result;
}

const renderIdWithLink = (data, type, row) => {
    return `<a href="${row.url}">${data}</a>`;
}

const transactionsTableConfiguration = {
    columns: [
        {data: 'id', render: renderIdWithLink},
        {data: 'created_at', render: carbonToString},
        {data: 'completed_at', render: carbonToString},
        {data: 'type'},
        {data: 'total_usd', render: usdToString},
        {data: 'account_currency_quoted'},
        {data: 'items'},
        {data: 'subscription_id'},
        {data: 'result', render: renderResult}
    ],
    language: {
        "emptyTable": "You have no transactions on file."
    },
    paging: false,
    info: false,
    searching: false
};

</script>

<template>
    <div class="container">

        <h1>Account Transactions</h1>

        <DataTable class="table table-dark table-hover table-striped table-bordered"
                   :options="transactionsTableConfiguration"
                   :data="transactions"
        >
            <thead>
            <tr>
                <th scope="col">Id</th>
                <th scope="col">Created</th>
                <th scope="col">Completed</th>
                <th scope="col">Type</th>
                <th scope="col">USD</th>
                <th scope="col">Account Currency</th>
                <th scope="col">Items</th>
                <th scope="col">Subscription?</th>
                <th scope="col">Result</th>
            </tr>
            </thead>
        </DataTable>

    </div>

</template>

<style scoped>

</style>
