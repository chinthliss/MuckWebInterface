<script setup lang="ts">

import {ref, onMounted, Ref} from 'vue';
import DataTable from 'datatables.net-vue3';
import {carbonToString, usdToString} from "../formatting";
import Spinner from "./Spinner.vue";
import {AccountTransaction} from "../defs";

import * as jQuery from 'jquery';
const $ = jQuery;

const props = defineProps<{
    api: string
}>();

const table: Ref<any> = ref();
const transactions: Ref<AccountTransaction[]> = ref([]);
const loading: Ref<boolean> = ref(true);
const filter: Ref<string> = ref('');

const renderIdWithLink = (data: any, type: string, row: any): string => {
    return `<a href="${row.url}">${data}</a>`;
}

const refreshFilter = () => {
    if (!table) return;
    table.value.dt.draw();
}

const transactionsTableConfiguration = {
    columns: [
        {data: 'account_id'},
        {data: 'id', render: renderIdWithLink, className: "text-truncate small limit-column-width"},
        {data: 'type'},
        {data: 'created_at', render: carbonToString, type: 'date'},
        {data: 'paid_at', render: carbonToString, type: 'date'},
        {data: 'completed_at', render: carbonToString, type: 'date'},
        {data: 'total_usd', render: usdToString},
        {data: 'account_currency_quoted'},
        {data: 'items'},
        {data: 'subscription_id'},
        {data: 'result'}
    ],
    language: {
        "emptyTable": "No transactions found."
    },
    order: [[3, 'asc']],
    paging: false,
    info: false
};

const loadTransactions = () => {
    loading.value = true;
    axios.get(props.api)
        .then(response => {
            transactions.value = response.data;
        })
        .catch(error => {
            console.log("An error occurred whilst refreshing the transaction list: ", error.message || error);
        })
        .finally(() => {
            loading.value = false;
        });
};

onMounted(() => {
    // Set an external callback for filtering
    $.fn.dataTable.ext.search.push(function (settings, data, _dataIndex) {
        let type = data[2];
        return (!filter.value || type === filter.value);
    });
    loadTransactions();
});

</script>

<template>
    <div class="container">

        <h1>Account Transactions (Admin)</h1>

        <div class="btn-group" role="group" aria-label="Type Filter">
            <input type="radio" class="btn-check" name="filter" id="filterNone" autocomplete="off"
                   v-model="filter" value="" @change="refreshFilter">
            <label class="btn btn-outline-primary" for="filterNone">All</label>

            <input type="radio" class="btn-check" name="filter" id="filterCard" autocomplete="off"
                   v-model="filter" value="card" @change="refreshFilter">
            <label class="btn btn-outline-primary" for="filterCard">Card</label>

            <input type="radio" class="btn-check" name="filter" id="filterPayPal" autocomplete="off"
                   v-model="filter" value="PayPal" @change="refreshFilter">
            <label class="btn btn-outline-primary" for="filterPayPal">PayPal</label>

            <input type="radio" class="btn-check" name="filter" id="filterPatreon" autocomplete="off"
                   v-model="filter" value="Patreon" @change="refreshFilter">
            <label class="btn btn-outline-primary" for="filterPatreon">Patreon</label>

        </div>

        <Spinner v-if="loading"/>
        <DataTable v-else ref="table"
                   class="table table-dark table-hover table-striped table-bordered small"
                   :options="transactionsTableConfiguration" :data="transactions">
            <thead>
            <tr>
                <th scope="col">Account</th>
                <th scope="col">Id</th>
                <th scope="col">Type</th>
                <th scope="col">Created</th>
                <th scope="col">Paid</th>
                <th scope="col">Completed</th>
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
:deep(.limit-column-width) {
    max-width: 140px;
}
</style>
