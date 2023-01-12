<template>
    <div class="container">

        <div class="row">
            <div class="col">
                <h1>Account {{ account.id }}</h1>
            </div>
        </div>

        <div class="row bg-danger p-2" v-if="account.locked">
            <span class="text-center">Account locked as of {{ carbonToString(account.locked) }}</span>
        </div>

        <dl class="row">
            <dt class="col-sm-2 text-primary">Created</dt>
            <dd class="col-sm-10">{{ carbonToString(account.created) }}</dd>


            <dt class="col-sm-2 text-primary">Last Connected</dt>
            <dd class="col-sm-10">{{ carbonToString(account.lastConnected) }}</dd>

            <dt class="col-sm-2 text-primary">Referrals</dt>
            <dd class="col-sm-10">{{ account.referrals }}</dd>

            <dt class="col-sm-2 text-primary">Characters (GAMENAME)</dt> <!-- TODO: Output gamename -->
            <dd class="col-sm-10">
                <character-card v-for="character in account.characters" :character="character"
                                class="me-2"></character-card>
            </dd>


            <dt class="col-sm-2 text-primary">Emails</dt>
            <dd class="col-sm-10">
                <DataTable class="table table-dark table-hover table-striped table-bordered"
                           :options="emailTableConfiguration" :data="account.emails">
                    <thead>
                    <tr>
                        <th scope="col">Email</th>
                        <th scope="col" class="text-center">Primary?</th>
                        <th scope="col">Registered</th>
                        <th scope="col">Verified</th>
                    </tr>
                    </thead>
                </DataTable>
            </dd>

            <dt class="col-sm-2 text-primary">Account Notes</dt>
            <dd class="col-sm-10">
                <DataTable class="table table-dark table-hover table-striped table-bordered"
                           :options="accountNotesTableConfiguration" :data="account.notes">
                    <thead>
                    <tr>
                        <th scope="col">When</th>
                        <th scope="col" class="text-center">Staff Member</th>
                        <th scope="col">Game</th>
                        <th scope="col">Note</th>
                    </tr>
                    </thead>
                </DataTable>
            </dd>

            <dt class="col-sm-2 text-primary">Tickets</dt>
            <dd class="col-sm-10"> <!-- TODO: Show tickets from tickets system -->
                PENDING
            </dd>


        </dl>


    </div>
</template>

<script setup>
/**
 * @typedef {object} Email
 * @property {string} email
 * @property {string} createdAt
 * @property {string} verifiedAt
 * @property {boolean} isPrimary
 */

import {ref} from 'vue';
import {carbonToString} from "../formatting";
import CharacterCard from "./CharacterCard.vue";
import DataTable from 'datatables.net-vue3';

const props = defineProps({
    account: {type: Object, required: true}
});

const displayEmailRowForIsPrimary = (data) => {
    return data ? '<i class="fa-solid fa-check"></i>' : '';
};

const emailTableConfiguration = {
    columns: [
        {data: 'email'},
        {data: 'isPrimary', render: displayEmailRowForIsPrimary, className: 'dt-center'},
        {data: 'createdAt', render: carbonToString},
        {data: 'verifiedAt', render: carbonToString}
    ],
    language: {
        "emptyTable": "No emails associated with this account."
    },
    paging: false,
    info: false,
    searching: false
};

const accountNotesTableConfiguration = {
    columns: [
        {data: 'whenAt', render: carbonToString},
        {data: 'staffMember'},
        {data: 'game'},
        {data: 'body'}
    ],
    language: {
        "emptyTable": "No notes found on this account."
    },
    paging: false,
    info: false,
    searching: false
};

</script>

<style scoped>

</style>
