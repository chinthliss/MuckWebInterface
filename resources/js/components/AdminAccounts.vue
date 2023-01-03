<template>
    <div class="container">

        <div class="row">
            <div class="col">
                <h1>Account Browser</h1>
            </div>
        </div>

        <!-- Quick jump to a known ID -->
        <div class="d-flex justify-content-center align-items-center">
            <label class="me-2" for="InputAccountId">Know the ID already?</label>
            <input class="w-auto form-control me-2" type="text" id="InputAccountId" placeholder="Account ID"
                   v-model="quickOpen">
            <button class="btn btn-primary">View</button>
        </div>

        <hr>

        <!-- Search form -->
        <form class="mt-2">

            <div class="row">

                <label class="col-form-label text-end mt-2 col-6 col-lg-2" for="InputEmail">Email</label>
                <div class="mt-2 col-6 col-lg-2">
                    <input class="form-control" type="email" id="InputEmail" placeholder="Email" v-model="searchEmail">
                </div>

                <label class="col-form-label text-end mt-2 col-6 col-lg-2" for="InputCharacter">Character</label>
                <div class="mt-2 col-6 col-lg-2">
                    <input type="text" class="form-control" id="InputCharacter" placeholder="Character"
                           v-model="searchCharacter">
                </div>
            </div>

            <div class="row">

                <label class="col-form-label text-end mt-2 col-6 col-lg-2" for="InputCreatedAfter">Created After</label>
                <div class="mt-2 col-6 col-lg-2">
                    <input type="date" class="form-control" id="InputCreatedAfter" placeholder="Created After"
                           v-model="searchCreatedAfter">
                </div>

                <label class="col-form-label text-end mt-2 col-6 col-lg-2" for="InputCreatedBefore">Created
                    Before</label>
                <div class="mt-2 col-6 col-lg-2">
                    <input type="date" class="form-control" id="InputCreatedBefore" placeholder="Created Before"
                           v-model="searchCreatedBefore">
                </div>

                <button type="button" class="btn btn-primary mt-2 col-12 col-lg-2" @click="doAccountSearch">
                    <i class="fas fa-search btn-icon-left"></i>Search
                </button>

            </div>
        </form>

        <hr>

        <div v-if="tableLoading" class="text-center">
            <span class="spinner-border text-primary me-2" role="status" aria-hidden="true"></span>
            <div>Loading..</div>
        </div>
        <div v-else-if="!tableData" class="text-center">Waiting on search request...</div>
        <DataTable v-else class="table table-dark table-hover table-striped table-bordered"
                   :options="tableConfiguration" :data="tableData">
        </DataTable>
    </div>
</template>

<script setup>
import {ref} from 'vue';
import DataTable from 'datatables.net-vue3';
import {carbonToString} from "../formatting";

const props = defineProps({
    apiUrl: {type: String, required: true}
});

const quickOpen = ref('');
const searchEmail = ref('');
const searchCharacter = ref('');
const searchCreatedBefore = ref('');
const searchCreatedAfter = ref('');

const tableLoading = ref(false);

const listCharacters = (characters) => {
    let names = [];
    for (const character of characters) {
        names.push(character.name)
    }
    return names.join(', ');
};

const tableConfiguration = {
    columns: [
        {data: 'id', title: 'ID'},
        {data: 'primaryEmail', title: 'Primary Email'},
        {data: 'characters', title: 'Characters', render: listCharacters},
        {data: 'created', title: 'Created', render: carbonToString},
        {data: 'lastConnected', title: 'Last Connected', render: carbonToString}
    ],
    language: {
        "emptyTable": "No accounts found matching the present criteria."
    },
    paging: false,
    info: false,
    searching: false
};

const tableData = ref();

const doAccountSearch = () => {
    let searchCriteria = {};
    if (searchCharacter.value) searchCriteria.character = searchCharacter.value;
    if (searchEmail.value) searchCriteria.email = searchEmail.value;
    if (searchCreatedAfter.value) searchCriteria.createdAfter = searchCreatedAfter.value;
    if (searchCreatedBefore.value) searchCriteria.createdBefore = searchCreatedBefore.value;

    tableLoading.value = true;
    tableData.value = [];

    axios
        .get(props.apiUrl, {params: searchCriteria})
        .then(response => {
            tableData.value = response.data;
        })
        .catch(error => {
            console.log("Request failed:", error);
            tableData.value = null;
        })
        .finally(() => tableLoading.value = false);
}

</script>

<style scoped>

</style>
