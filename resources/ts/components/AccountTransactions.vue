<script setup lang="ts">

import {Ref, ref} from 'vue';
import {carbonToString, usdToString} from "../formatting";
import {AccountTransaction, DataTablesNamedSlotProps} from "../defs";

import DataTable from 'datatables.net-vue3';
import DataTablesLib, {Config as DataTableOptions} from 'datatables.net-bs5';
DataTable.use(DataTablesLib);

const props = defineProps<{
    transactionsIn: AccountTransaction[]
}>();

const transactions: Ref<AccountTransaction[]> = ref(props.transactionsIn);

const renderResult = (result: string): string => {
    if (!result) return 'Open'
    if (result === 'fulfilled') return 'Fulfilled';
    if (result === 'user_declined') return 'User Declined';
    if (result === 'vendor_refused') return 'Vendor Declined';
    if (result === 'expired') return 'Expired';
    return result;
}

const tableOptions: DataTableOptions = {
    info: false,
    paging: false,
    language: {
        emptyTable: "No transactions to show."
    },
    columns: [
        {data: 'id'},
        {data: 'created_at', render: carbonToString},
        {data: 'completed_at', render: carbonToString},
        {data: 'type'},
        {data: 'total_usd', render: usdToString},
        {data: 'account_currency_quoted'},
        {data: 'items'},
        {data: 'subscription_id'},
        {data: 'result', render: renderResult}
    ]
};
</script>

<template>
    <DataTable class="table table-dark table-hover table-striped" :options="tableOptions" :data="transactions">
        <thead>
        <tr>
            <th>Id</th>
            <th>Created</th>
            <th>Completed</th>
            <th>Type</th>
            <th>Total (USD)</th>
            <th>Account Currency</th>
            <th>Items</th>
            <th>Subscription</th>
            <th>Result</th>
        </tr>
        </thead>
        <template #column-id="dt: DataTablesNamedSlotProps">
            <a :href="dt.rowData.url">{{ dt.rowData.id }}</a>
        </template>
    </DataTable>
</template>

<style scoped>

</style>
