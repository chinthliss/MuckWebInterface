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

        <div v-if="loading" class="text-center">
            <span class="spinner-border text-primary me-2" role="status" aria-hidden="true"></span>
            <div>Loading..</div>
        </div>
        <DataTable v-else id="Transactions-Table"
                   class="table table-dark table-hover table-striped table-bordered small"
                   :options="transactionsTableConfiguration" :data="transactions">
            <thead>
            <tr>
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

<script setup>

import {ref, onMounted} from 'vue';
import DataTable from 'datatables.net-vue3';
import {carbonToString} from "../formatting";

/** @type {Ref<AccountTransaction[]>} */
const transactions = ref([]);

const loading = ref(true);

const filter = ref('');
const props = defineProps({
    api: {type: String, required: true}
});

const renderIdWithLink = (data, type, row) => {
    return `<a href="${row.url}">${data}</a>`;
}

const refreshFilter = () => {
    const table = $('#Transactions-Table').DataTable();
    table.draw();
}

const transactionsTableConfiguration = {
    columns: [
        {data: 'id', render: renderIdWithLink, className: "text-truncate small limit-column-width"},
        {data: 'type'},
        {data: 'created_at', render: carbonToString},
        {data: 'paid_at', render: carbonToString},
        {data: 'completed_at', render: carbonToString},
        {data: 'total_usd'},
        {data: 'account_currency_quoted'},
        {data: 'items'},
        {data: 'subscription_id'},
        {data: 'result'}
    ],
    language: {
        "emptyTable": "No transactions found."
    },
    order: [[2, 'asc']],
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
    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        let type = data[1];
        return (!filter.value || type === filter.value);
    });
    loadTransactions();
});

</script>


<style scoped>
:deep(.limit-column-width) {
    max-width: 140px;
}
</style>
