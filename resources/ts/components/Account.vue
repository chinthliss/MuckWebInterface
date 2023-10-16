<script setup lang="ts">

import {Ref, ref} from 'vue';
import DataTable from 'datatables.net-vue3';
import ModalConfirmation from './ModalConfirmation.vue';
import {usdToString, carbonToString, arrayToList} from "../formatting";
import {csrf, lex} from "../siteutils";
import {Account} from "../defs";

const props = defineProps<{
    accountIn: Account
    links: {
        changePassword: string,
        changeEmail: string,
        newEmail: string,
        cardManagement: string,
        transactions: string
    }
}>();

const account: Ref<Account> = ref(props.accountIn);
const emailToMakePrimary: Ref<string> = ref('');
const confirmPrimaryEmailModal: Ref<InstanceType<typeof ModalConfirmation> | null> = ref(null);

const confirmMakeEmailPrimary = (e: Event) => {
    emailToMakePrimary.value = (e.currentTarget as HTMLButtonElement).dataset.email;
    confirmPrimaryEmailModal.value.show();
}

const makeEmailPrimary = () => {
    (document.getElementById('changeEmailForm') as HTMLFormElement).submit();
}

const displayEmailRowForControls = (data: any, type: string, row: any) => {
    let controls = '';
    if (!row.isPrimary) controls += `<button data-email="${row.email}" class="btn btn-secondary btn-make-primary">Make Primary</button>`;
    return controls;
};

const displayEmailRowForIsPrimary = (data: any) => {
    return data ? '<i class="fa-solid fa-check"></i>' : '';
};

const emailTableDrawCallback = () => {
    const buttons = document.querySelectorAll('.btn-make-primary') as NodeListOf<HTMLButtonElement>;
    buttons.forEach(el => {
        el.addEventListener('click', confirmMakeEmailPrimary);
    })
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

const displaySubscriptionRowForControls = (data: any, type: string, row: any): string => {
    return `
    <a href="${row.url}"><i class="fas fa-search"></i></a>
    <button class="btn btn-secondary ms-2" v-if="${row.status} === 'active'" @click="cancelSubscription(${row.id})">Cancel</button>
    `;
};

const friendlySubscriptionStatus = (status: string): string => {
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
    return 'Unknown';
}
const subscriptionsTableConfiguration = {
    columns: [
        {data: 'type'},
        {data: 'amount_usd', render: usdToString},
        {data: 'recurring_interval'},
        {data: 'next_charge_at', render: carbonToString},
        {data: 'status', render: friendlySubscriptionStatus},
        {render: displaySubscriptionRowForControls, sortable: false}
    ],
    paging: false,
    info: false,
    searching: false
};

const overallSubscriptionStatus = () => {
    if (!account.value.subscriptionActive) return 'No Active Subscription';
    if (account.value.subscriptionRenewing) return 'Active, renews sometime before ' + account.value.subscriptionExpires;
    return 'Active, expires sometime before ' + account.value.subscriptionExpires;
}

const cancelSubscription = (id) => {
    // TODO - restore subscription cancelling
}

</script>

<template>
    <div class="container">

        <h1>Account</h1>

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

            <DataTable class="table table-dark table-hover table-striped table-bordered"
                       :options="subscriptionsTableConfiguration" :data="account.subscriptions">
                <thead>
                <tr>
                    <th scope="col">Type</th>
                    <th scope="col">Amount (USD)</th>
                    <th scope="col">Interval (days)</th>
                    <th scope="col">Next (approx)</th>
                    <th scope="col">Status</th>
                    <th scope="col"></th>
                </tr>
                </thead>
            </DataTable>

            <p>Payments made via subscriptions also show on the Account Transactions page.</p>
        </div>

        <h2 class="mt-2">Emails</h2>

        <DataTable class="table table-dark table-hover table-striped table-bordered" :options="emailTableConfiguration"
                   :data="account.emails">
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
                <a :href="links.changePassword">
                    <button class="w-100 btn btn-primary">Change Password</button>
                </a>
            </div>
            <div class="col-12 col-sm-6">
                <a :href="links.changeEmail">
                    <button class="w-100 btn btn-primary">Change to new Email</button>
                </a>
            </div>
            <div class="col-12 col-sm-6">
                <a :href="links.cardManagement">
                    <button class="w-100 btn btn-primary">Card Management</button>
                </a>
            </div>
            <div class="col-12 col-sm-6">
                <a :href="links.transactions">
                    <button class="w-100 btn btn-primary">Account Transactions</button>
                </a>
            </div>
        </div>

        <!-- Change primary email modal -->
        <modal-confirmation ref="confirmPrimaryEmailModal" @yes="makeEmailPrimary"
                            title="Change Primary Email?" yes-label="Change" no-label="Cancel">
            <form id="changeEmailForm" :action="links.changeEmail" method="POST">
                <input type="hidden" name="_token" :value="csrf()">
                <input type="hidden" name="email" :value="emailToMakePrimary">
            </form>
            <p>Your primary email is the one you use to login and where notifications are sent to.</p>
            <p>If it hasn't been verified, you'll be prompted to verify it after changing to it.</p>
        </modal-confirmation>

    </div>
</template>

<style scoped>

</style>
