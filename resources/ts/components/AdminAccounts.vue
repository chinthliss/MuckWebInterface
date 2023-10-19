<script setup lang="ts">
import {ref, Ref} from 'vue';
import {carbonToString} from "../formatting";
import Spinner from "./Spinner.vue";
import {Account, Character} from "../defs";
import DataTable, {DataTableRowClickEvent} from 'primevue/datatable';
import Column from "primevue/column";

const props = defineProps<{
    apiUrl: string,
    accountRoot: string
}>();

const quickOpen: Ref<string> = ref('');
const searchEmail: Ref<string> = ref('');
const searchCharacter: Ref<string> = ref('');
const searchCreatedBefore: Ref<string> = ref('');
const searchCreatedAfter: Ref<string> = ref('');

const tableLoading: Ref<boolean> = ref(false);
const tableData: Ref<any[] | null> = ref(null);


const listCharacters = (characters: Character[]): string => {
    let names = [];
    for (const character of characters) {
        names.push(character.name)
    }
    return names.join(', ');
};

const rowClicked = (event: DataTableRowClickEvent) => {
    const url = event.data?.url;
    if (url)
        window.open(url, '_blank');
    else
        console.log("Clicked row doesn't have an associated url with it!");
}

type SearchCriteria = {
    character?: string
    email?: string
    createdAfter?: string
    createdBefore?: string
}
const doAccountSearch = () => {
    let searchCriteria = {} as SearchCriteria;
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
            console.log("Search request failed:", error.message || error);
        })
        .finally(() => tableLoading.value = false);
}

const jumpToAccount = () => {
    window.location = props.accountRoot.replace('DUMMY', quickOpen.value);
};

</script>

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
            <button class="btn btn-primary" @click="jumpToAccount">View</button>
        </div>

        <hr>

        <!-- Search form -->
        <form class="mt-2">

            <div class="row">

                <label class="col-form-label text-end mt-2 col-6 col-xl-2" for="InputEmail">Email</label>
                <div class="mt-2 col-6 col-xl-2">
                    <input class="form-control" type="email" id="InputEmail" placeholder="Email" v-model="searchEmail">
                </div>

                <label class="col-form-label text-end mt-2 col-6 col-xl-2" for="InputCharacter">Character</label>
                <div class="mt-2 col-6 col-xl-2">
                    <input type="text" class="form-control" id="InputCharacter" placeholder="Character"
                           v-model="searchCharacter">
                </div>
            </div>

            <div class="row">

                <label class="col-form-label text-end mt-2 col-6 col-xl-2" for="InputCreatedAfter">Created After</label>
                <div class="mt-2 col-6 col-xl-2">
                    <input type="date" class="form-control" id="InputCreatedAfter" placeholder="Created After"
                           v-model="searchCreatedAfter">
                </div>

                <label class="col-form-label text-end mt-2 col-6 col-xl-2" for="InputCreatedBefore">Created
                    Before</label>
                <div class="mt-2 col-6 col-xl-2">
                    <input type="date" class="form-control" id="InputCreatedBefore" placeholder="Created Before"
                           v-model="searchCreatedBefore">
                </div>

                <button type="button" class="btn btn-primary mt-2 col-12 col-xl-2" @click="doAccountSearch">
                    <i class="fas fa-search btn-icon-left"></i>Search
                </button>

            </div>
        </form>

        <hr>

        <Spinner v-if="tableLoading"/>
        <div v-else-if="!tableData" class="text-center">No data loaded yet...</div>
        <div v-else class="table-responsive-xl">
            <DataTable :value="tableData" stripedRows @row-click="rowClicked">
                <template #empty>No accounts found matching the present criteria.</template>
                <Column header="ID" field="id"></Column>
                <Column header="Primary Email" field="primaryEmail"></Column>
                <Column header="Characters" field="characters">
                    <template #body="{ data }">
                        {{ listCharacters((data as Account).characters) }}
                    </template>
                </Column>
                <Column header="Created" field="createdAt">
                    <template #body="{ data }">
                        {{ carbonToString((data as Account).createdAt) }}
                    </template>
                </Column>
                <Column header="Last Connected" field="lastConnected">
                    <template #body="{ data }">
                        {{ carbonToString((data as Account).lastConnected) }}
                    </template>
                </Column>
            </DataTable>
        </div>
    </div>
</template>

<style scoped>
:deep(tr) {
    cursor: pointer;
}
</style>
