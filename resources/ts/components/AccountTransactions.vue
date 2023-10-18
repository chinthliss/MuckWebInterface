<script setup lang="ts">

import {Ref, ref} from 'vue';
import {carbonToString, usdToString} from "../formatting";
import {AccountTransaction} from "../defs";
import DataTable from 'primevue/datatable';
import Column from "primevue/column";

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

</script>

<template>
    <div class="container">

        <h1>Account Transactions</h1>

        <DataTable :value="transactions" stripedRows>
            <Column field="id" header="Id">
                <template #body="{ data  }">
                    <a :href="(data as AccountTransaction).url">{{ (data as AccountTransaction).id }}</a>
                </template>
            </Column>
            <Column field="created_at" header="Created">
                <template #body="{ data }">
                    {{ carbonToString((data as AccountTransaction).created_at) }}
                </template>
            </Column>
            <Column field="completed_at" header="Completed">
                <template #body="{ data }">
                    {{ carbonToString((data as AccountTransaction).completed_at) }}
                </template>
            </Column>
            <Column field="type" header="Type">Type</Column>
            <Column field="total_usd" header="USD">
                <template #body="{ data }">
                    {{ usdToString((data as AccountTransaction).total_usd) }}
                </template>
            </Column>
            <Column field="account_currency_quoted" header="Account Currency">Account Currency</Column>
            <Column field="items" header="Items">Items</Column>
            <Column field="subscription_id" header="Subscription"></Column>
            <Column field="result" header="Result">
                <template #body="{ data }">
                    {{ renderResult((data as AccountTransaction).result) }}
                </template>
            </Column>
        </DataTable>

    </div>

</template>

<style scoped>

</style>
