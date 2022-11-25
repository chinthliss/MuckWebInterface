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
                <a :href="links.changepassword">
                    <button class="w-100 btn btn-primary">Change Password</button>
                </a>
            </div>
            <div class="col-12 col-sm-6">
                <a :href="links.changeemail">
                    <button class="w-100 btn btn-primary">Change to new Email</button>
                </a>
            </div>
            <div class="col-12 col-sm-6">
                <a :href="links.cardmanagement">
                    <button class="w-100 btn btn-primary">Card Management</button>
                </a>
            </div>
            <div class="col-12 col-sm-6">
                <a :href="links.transactions">
                    <button class="w-100 btn btn-primary">Account Transactions</button>
                </a>
            </div>
        </div>

        <h2 class="mt-2">Preferences</h2>

        <!-- Change primary email modal -->
        <modal-confirmation id="confirm-primary-email" @yes="makeEmailPrimary"
                            title="Change Primary Email?" yes-label="Change" no-label="Cancel">
            <form id="changeEmailForm" :action="links.changeemail" method="POST">
                <input type="hidden" name="_token" :value="csrf()">
                <input type="hidden" name="email" :value="emailToMakePrimary">
            </form>
            <p>Your primary email is the one you use to login and where notifications are sent to.</p>
            <p>If it hasn't been verified, you'll be prompted to verify it after changing to it.</p>
        </modal-confirmation>

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
import DataTable from 'datatables.net-vue3';
import ModalConfirmation from './ModalConfirmation.vue';
import {carbonToString} from "../formatting";
import {csrf} from "../siteutils";

const props = defineProps({
    accountCreated: {type: String, required: true},
    subscriptionStatus: {type: String, required: true},
    links: {type: Object, required: true},
    /** @type {Email[]} */
    emailsIn: {type: Array}
});

/**
 * @type {Ref<Email[]>}
 */
const emails = ref(props.emailsIn);
const emailToMakePrimary = ref();

const confirmMakeEmailPrimary = (e) => {
    emailToMakePrimary.value = $(e.currentTarget).data('email');
    const modal = new bootstrap.Modal(document.getElementById('confirm-primary-email'));
    modal.show();
}

const makeEmailPrimary = (e) => {
    $('#changeEmailForm').submit();
}

const displayEmailRowForControls = (data, type, row) => {
    let controls = '';
    if (!row.isPrimary) controls += `<button data-email="${row.email}" class="btn btn-secondary btn-make-primary">Make Primary</button>`;
    return controls;
};

const displayEmailRowForIsPrimary = (data) => {
    return data ? 'Primary' : '';
};

const emailTableDrawCallback = () => {
    $('.btn-make-primary').click(confirmMakeEmailPrimary);
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
