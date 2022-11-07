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
        <table id="email-table" class="table table-striped" style="width:100%">
            <thead>
            <tr>
                <th scope="col">Email</th>
                <th scope="col" class="text-center">Primary?</th>
                <th scope="col">Registered</th>
                <th scope="col">Verified</th>
            </tr>
            </thead>
        </table>

        <h2 class="mt-2">Account Controls</h2>
        <div class="row g-2">
            <div class="col-12 col-sm-6">
                <button class="w-100 btn btn-primary">Change Password</button>
            </div>
            <div class="col-12 col-sm-6">
                <button class="w-100 btn btn-primary">Change to New Email</button>
            </div>
            <div class="col-12 col-sm-6">
                <button class="w-100 btn btn-primary">Card Management</button>
            </div>
            <div class="col-12 col-sm-6">
                <button class="w-100 btn btn-primary">Account Transactions</button>
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

import {onMounted} from 'vue';

const props = defineProps({
    accountCreated: {type: String, required: true},
    subscriptionStatus: {type: String, required: true},
    /** @type {Email[]} */
    emails: {type: Array}
});

const carbonToString = (carbonString) => {
    if (!carbonString) return '--';
    return new Date(carbonString).toLocaleString();
}

const capital = (text) => {
    if (typeof(text) !== 'string') text = text.toString();
    return text && text.substring(0, 1).toUpperCase() + text.slice(1);
}

onMounted(() => {
    document.getElementById('email-table');
    $('#email-table').DataTable({
        columns: [
            {data: 'email'},
            {data: 'isPrimary', render: capital, className: 'dt-center'},
            {data: 'createdAt', render: carbonToString},
            {data: 'verifiedAt', render: carbonToString}
        ],
        data: props.emails,
        paging: false,
        info: false,
        searching: false
    });
});
</script>

<style scoped>

</style>
