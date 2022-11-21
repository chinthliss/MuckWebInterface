<template>
    <div class="container">

        <div class="row">
            <div class="col">
                <h1>Account</h1>
            </div>
        </div>

        <dl class="row">

            <dt class="col-sm-2 text-primary">Created</dt>
            <dd class="col-sm-10">{{ accountCreated }}</dd>

            <dt class="col-sm-2 text-primary">Subscription</dt>
            <dd class="col-sm-10">{{ subscriptionStatus }}</dd>

        </dl>

        <h2 class="mt-2">Emails</h2>

        <DataTable class="table table-striped" :options="emailTableConfiguration" :data="emails">
            <thead>
            <tr>
                <th scope="col">Email</th>
                <th scope="col" class="text-center">Primary?</th>
                <th scope="col">Registered</th>
                <th scope="col">Verified</th>
                <th scope="col"></th>
            </tr>
            </thead>
        </DataTable>

        <h2 class="mt-2">Account Controls</h2>
        <div class="row g-2">
            <div class="col-12 col-sm-6">
                <a :href="links.changepassword"><button class="w-100 btn btn-primary">Change Password</button></a>
            </div>
            <div class="col-12 col-sm-6">
                <a :href="links.changeemail"><button class="w-100 btn btn-primary">Change to new Email</button></a>
            </div>
            <div class="col-12 col-sm-6">
                <a :href="links.cardmanagement"><button class="w-100 btn btn-primary">Card Management</button></a>
            </div>
            <div class="col-12 col-sm-6">
                <a :href="links.transactions"><button class="w-100 btn btn-primary">Account Transactions</button></a>
            </div>
        </div>

        <h2 class="mt-2">Preferences</h2>
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

import {ref, onMounted} from 'vue';
import DataTable from 'datatables.net-vue3';
import {carbonToString, capital} from "../formatting";

const props = defineProps({
    accountCreated: {type: String, required: true},
    subscriptionStatus: {type: String, required: true},
    links: {type: Object, required: true},
    /** @type {Email[]} */
    emailsIn: {type: Array}
});

const emails = ref(props.emailsIn);

const makeEmailPrimary = (e) => {
    const email = $(e.currentTarget).data('email');
    if (email) {
        for(let i = emails.value.length - 1; i >= 0; i--) {
            //TODO Actually set primary on server
            emails.value[i].isPrimary = (emails.value[i].email === email);
        }
    }
}

const deleteEmail = (e) => {
    const email = $(e.currentTarget).data('email');
    if (email) {
        for(let i = emails.value.length - 1; i >= 0; i--) {
            //TODO Actually delete from server
            if (emails.value[i].email === email) emails.value.splice(i, 1);
        }
    }}

const displayEmailRowForControls = (data, type, row) => {
    let controls = '';
    if (!row.isPrimary) controls += `<button data-email="${row.email}" class="btn btn-secondary btn-make-primary">Make Primary</button>`;
    if (!row.isPrimary) controls += `<button data-email="${row.email}" class="btn btn-secondary btn-delete ms-2">Delete</button>`;
    return controls;
};

const displayEmailRowForIsPrimary = (data) => {
    return data ? 'Primary' : '';
};

const emailTableDrawCallback = () => {
    $('.btn-make-primary').click(makeEmailPrimary);
    $('.btn-delete').click(deleteEmail);
};

const emailTableConfiguration = {
    columns: [
        {data: 'email'},
        {data: 'isPrimary', render: displayEmailRowForIsPrimary, className: 'dt-center'},
        {data: 'createdAt', render: carbonToString},
        {data: 'verifiedAt', render: carbonToString},
        {render: displayEmailRowForControls, sortable: false}
    ],
    paging: false,
    info: false,
    searching: false,
    drawCallback: emailTableDrawCallback
};
</script>

<style scoped>

</style>
