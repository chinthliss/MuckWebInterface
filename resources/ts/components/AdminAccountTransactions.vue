<script setup lang="ts">
import {ref, onMounted, Ref} from 'vue';
import {carbonToString, usdToString} from "../formatting";
import Spinner from "./Spinner.vue";
import {AccountTransaction, DataTablesNamedSlotProps} from "../defs";

import DataTable from 'datatables.net-vue3';
import DataTablesLib, {Api, Config as DataTableOptions} from 'datatables.net-bs5';
DataTable.use(DataTablesLib);

const props = defineProps<{
    api: string
}>();

const transactions: Ref<AccountTransaction[]> = ref([]);
const loading: Ref<boolean> = ref(true);
let dtApi: Api | null = null;
const typeFilter: Ref<string> = ref('');

const tableOptions: DataTableOptions = {
    info: false,
    paging: false,
    language: {
        emptyTable: "No transactions to show."
    },
    columns: [
        {data: 'account_id'},
        {data: 'id', className: 'limit-column-width', name: 'id'},
        {data: 'type'},
        {data: 'created_at', render: carbonToString},
        {data: 'paid_at', render: carbonToString},
        {data: 'completed_at', render: carbonToString},
        {data: 'total_usd', render: usdToString},
        {data: 'account_currency_quoted'},
        {data: 'items'},
        {data: 'subscription_id'},
        {data: 'result'}
    ],
    initComplete: () => {
        dtApi = new DataTablesLib.Api('table')
    }
};

const filterChanged = () => {
    if (dtApi) {
        let column = dtApi.columns(2);
        column.search(typeFilter.value, {exact: true}).draw();
    }
}
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
    loadTransactions();
});

</script>

<template>
    <div class="container">

        <h1>Account Transactions (Admin)</h1>

        <div class="btn-group" role="group" aria-label="Type Filter">
            <input type="radio" class="btn-check" name="filter" id="filterNone" autocomplete="off"
                   v-model="typeFilter" value="" @change="filterChanged">
            <label class="btn btn-outline-primary" for="filterNone">All</label>

            <input type="radio" class="btn-check" name="filter" id="filterCard" autocomplete="off"
                   v-model="typeFilter" value="Card" @change="filterChanged">
            <label class="btn btn-outline-primary" for="filterCard">Card</label>

            <input type="radio" class="btn-check" name="filter" id="filterPayPal" autocomplete="off"
                   v-model="typeFilter" value="Paypal" @change="filterChanged">
            <label class="btn btn-outline-primary" for="filterPayPal">PayPal</label>

            <input type="radio" class="btn-check" name="filter" id="filterPatreon" autocomplete="off"
                   v-model="typeFilter" value="Patreon" @change="filterChanged">
            <label class="btn btn-outline-primary" for="filterPatreon">Patreon</label>

        </div>

        <Spinner v-if="loading"/>
        <DataTable v-else id="table" class="table table-dark table-hover table-striped"
                   :options="tableOptions" :data="transactions"
        >
            <thead>
            <tr>
                <th>Account</th>
                <th>Id</th>
                <th>Type</th>
                <th>Created</th>
                <th>Paid</th>
                <th>Completed</th>
                <th>USD</th>
                <th>Account Currency</th>
                <th>Items</th>
                <th>Subscription?</th>
                <th>Result</th>
            </tr>
            </thead>
            <template #column-id="dt: DataTablesNamedSlotProps">
                <a :href="dt.rowData.url">{{ dt.rowData.id }}</a>
            </template>
        </DataTable>

    </div>

</template>

<style scoped>
:deep(.limit-column-width) {
    max-width: 140px;
}
</style>
