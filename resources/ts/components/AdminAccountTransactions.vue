<script setup lang="ts">
import {ref, onMounted, Ref} from 'vue';
import {carbonToString, usdToString} from "../formatting";
import Spinner from "./Spinner.vue";
import {AccountTransaction} from "../defs";
import DataTable from 'primevue/datatable';
import Column from "primevue/column";
import {FilterService} from "primevue/api";

const props = defineProps<{
    api: string
}>();

const transactions: Ref<AccountTransaction[]> = ref([]);
const loading: Ref<boolean> = ref(true);

const filters = ref({
    global: {value: '', matchMode: 'paymentTypeFilter'}
});

FilterService.register('paymentTypeFilter', (id: string, type: string) => {
    if (!type) return true;
    const transaction: AccountTransaction = transactions.value.find((entry) => entry.id == id);
    return (transaction && transaction.type == type);
});

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
                   v-model="filters.global.value" value="">
            <label class="btn btn-outline-primary" for="filterNone">All</label>

            <input type="radio" class="btn-check" name="filter" id="filterCard" autocomplete="off"
                   v-model="filters.global.value" value="Card">
            <label class="btn btn-outline-primary" for="filterCard">Card</label>

            <input type="radio" class="btn-check" name="filter" id="filterPayPal" autocomplete="off"
                   v-model="filters.global.value" value="Paypal">
            <label class="btn btn-outline-primary" for="filterPayPal">PayPal</label>

            <input type="radio" class="btn-check" name="filter" id="filterPatreon" autocomplete="off"
                   v-model="filters.global.value" value="Patreon">
            <label class="btn btn-outline-primary" for="filterPatreon">Patreon</label>

        </div>

        <Spinner v-if="loading"/>
        <DataTable v-else :value="transactions" stripedRows sortField="created_at" sortOrder="1"
                   v-model:filters="filters" :globalFilterFields="['id']">
            <template #empty>No transactions to display.</template>
            <Column header="Account" field="account_id" sortable></Column>
            <Column header="Id" field="id" sortable>
                <template #body="{ data  }">
                    <a :href="(data as AccountTransaction).url">{{ (data as AccountTransaction).id }}</a>
                </template>
            </Column>
            <Column header="Type" field="type"></Column>
            <Column header="Created" field="created_at" sortable>
                <template #body="{ data }">
                    {{ carbonToString((data as AccountTransaction).created_at) }}
                </template>
            </Column>
            <Column header="Paid" field="paid_at" sortable>
                <template #body="{ data }">
                    {{ carbonToString((data as AccountTransaction).paid_at) }}
                </template>
            </Column>
            <Column header="Completed" field="completed_at" sortable>
                <template #body="{ data }">
                    {{ carbonToString((data as AccountTransaction).completed_at) }}
                </template>
            </Column>
            <Column header="USD" field="total_usd" sortable>
                <template #body="{ data }">
                    {{ usdToString((data as AccountTransaction).total_usd) }}
                </template>
            </Column>
            <Column header="Account Currency" field="account_currency_quoted" sortable></Column>
            <Column header="Items" field="items" sortable></Column>
            <Column header="Subscription?" field="subscription_id" sortable></Column>
            <Column header="Result" field="result" sortable></Column>
        </DataTable>

    </div>

</template>

<style scoped>
:deep(.limit-column-width) {
    max-width: 140px;
}
</style>
