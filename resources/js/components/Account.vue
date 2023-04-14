<template>
    <div class="container">

        <div class="row">
            <div class="col">
                <h1>Account</h1>
            </div>
        </div>

        <dl class="row">

            <dt class="col-sm-2 text-primary">Created</dt>
            <dd class="col-sm-10">{{ carbonToString(account.createdAt) }}</dd>

            <dt class="col-sm-2 text-primary">Veterancy</dt>
            <dd class="col-sm-10">{{ account.veterancy }} Month(s)</dd>

            <dt class="col-sm-2 text-primary">Subscription</dt>
            <dd class="col-sm-10">{{ overallSubscriptionStatus() }}</dd>

            <dt class="col-sm-2 text-primary">{{ lex('accountCurrency') }}</dt>
            <dd class="col-sm-10">{{ account.currency }}</dd>

            <dt class="col-sm-2 text-primary">Supporter Points</dt>
            <dd class="col-sm-10">{{ account.supporterPoints }}</dd>

            <dt class="col-sm-2 text-primary">Flags</dt>
            <dd class="col-sm-10">{{ arrayToList(account.flags, 'None') }}</dd>

        </dl>

        <!-- Subscriptions -->
        <div v-if="account.subscriptions.length > 0">
            <h2 class="mt-2">Subscriptions</h2>
            <!-- TODO Subscription table is in old format -->
            <table class="table table-hover">
                <thead>
                <tr>
                    <th scope="col">Type</th>
                    <th scope="col">Amount (USD)</th>
                    <th scope="col">Interval (days)</th>
                    <th scope="col">Next (approx)</th>
                    <th scope="col">Status</th>
                    <th scope="col"></th>
                    <th scope="col"></th>
                </tr>
                </thead>
                <tr v-for="subscription in account.subscriptions">
                    <td class="align-middle">{{ subscription.type }}</td>
                    <td class="align-middle">${{ subscription.amount_usd }}</td>
                    <td class="align-middle">{{ subscription.recurring_interval }}</td>
                    <td class="align-middle">{{ carbonToString(subscription.next_charge_at) }}</td>
                    <td class="align-middle">{{ friendlySubscriptionStatus(subscription.status) }}</td>
                    <td class="align-middle"><a :href="subscription.url">
                        <i class="fas fa-search"></i>
                    </a></td>
                    <td class="align-middle">
                        <button class="btn btn-secondary" v-if="subscription.status === 'active'"
                                @click="cancelSubscription(subscription.id)">Cancel
                        </button>
                    </td>
                </tr>
            </table>
            <p>Payments made via subscriptions also show on the Account Transactions page.</p>
        </div>

        <h2 class="mt-2">Emails</h2>

        <DataTable class="table table-dark table-hover table-striped table-bordered" :options="emailTableConfiguration" :data="account.emails">
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
        <div>Pending</div>
        <!-- TODO: Restore preferences on account screen -->

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

/**
 * @typedef {object} Account
 * @property {int} id
 * @property {string} createdAt
 * @property {string} verifiedAt
 * @property {string} lockedAt
 * @property {string} lastConnected
 * @property {string} primaryEmail
 * @property {int} referrals
 * @property {int} supporterPoints
 * @property {int} veterancy
 * @property {int} currency
 * @property {string[]} flags
 * @property {string[]} roles
 * @property {Email[]} emails
 * @property {boolean} subscriptionActive
 * @property {boolean} subscriptionRenewing
 * @property {string} subscriptionExpires
 * @property {array} subscriptions
 */

import {ref} from 'vue';
import DataTable from 'datatables.net-vue3';
import ModalConfirmation from './ModalConfirmation.vue';
import {carbonToString, arrayToList} from "../formatting";
import {csrf, lex} from "../siteutils";

const props = defineProps({
    accountIn: {type: Object, required: true},
    links: {type: Object, required: true},
});

/**
 * @type {Ref<Account>}
 */
const account = ref(props.accountIn);
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
    return data ? '<i class="fa-solid fa-check"></i>' : '';
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
        {render: displayEmailRowForControls, sortable: false, className: 'dt-center'}
    ],
    paging: false,
    info: false,
    searching: false,
    drawCallback: emailTableDrawCallback
};

const overallSubscriptionStatus = () => {
    if (!account.value.subscriptionActive) return 'No Subscription';
    if (account.value.subscriptionRenewing) return 'Active, renews sometime before ' + account.value.subscriptionExpires;
    return 'Active, expires sometime before ' + account.value.subscriptionExpires;
}

const cancelSubscription = (id) => {
    // TODO - restore subscription cancelling
}

const friendlySubscriptionStatus = (status) => {
    switch (status) {
        case 'user_declined':
            return 'Never Accepted';
        case 'approval_pending':
            return 'Never Accepted';
        case 'suspended':
            return 'Suspended';
        case 'cancelled':
            return 'Cancelled';
        case 'expired':
            return 'Expired';
        case 'active':
            return 'Active';
    }
    return 'Unknown'
}

</script>

<style scoped>

</style>
