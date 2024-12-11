<script setup lang="ts">

import {Ref, ref} from 'vue';
import ModalConfirmation from './ModalConfirmation.vue';
import {arrayToList, carbonToString, usdToString} from "../formatting";
import {csrf, lex} from "../siteutils";
import {Account, AccountEmail, AccountSubscription, DataTablesNamedSlotProps} from "../defs";
import DataTable from 'datatables.net-vue3';
import DataTablesLib, {Config as DataTableOptions} from 'datatables.net-bs5';

DataTable.use(DataTablesLib);

const props = defineProps<{
    accountIn: Account
    links: {
        changePassword: string,
        changeEmail: string,
        newEmail: string,
        accountCurrencyHistory: string,
        cardManagement: string,
        transactions: string
    }
}>();

const account: Ref<Account> = ref(props.accountIn);
const emailToMakePrimary: Ref<string> = ref('');
const confirmPrimaryEmailModal: Ref<InstanceType<typeof ModalConfirmation> | null> = ref(null);

const confirmMakeEmailPrimary = (email: AccountEmail) => {
    emailToMakePrimary.value = email.email;
    if (confirmPrimaryEmailModal.value) confirmPrimaryEmailModal.value.show();
}

const makeEmailPrimary = () => {
    (document.getElementById('changeEmailForm') as HTMLFormElement).submit();
}

const emailTableOptions: DataTableOptions = {
    info: false,
    paging: false,
    searching: false,
    columns: [
        {data: 'email'},
        {name: 'primary', data: 'isPrimary', className: 'text-center', orderable: false},
        {data: 'createdAt', render: carbonToString},
        {data: 'verifiedAt', render: carbonToString},
        {data: null, name: 'controls', orderable: false}
    ],
    headerCallback: (thead) => {
        thead.childNodes[2];
    }
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


const overallSubscriptionStatus = () => {
    if (!account.value.subscriptionActive) return 'No Active Subscription';
    if (account.value.subscriptionRenewing) return 'Active, renews sometime before ' + account.value.subscriptionExpires;
    return 'Active, expires sometime before ' + account.value.subscriptionExpires;
}

const cancelSubscription = (subscription: AccountSubscription) => {
    // TODO - restore subscription cancelling
    console.log("Should cancel subscription: ", subscription);
}

const subscriptionTableOptions: DataTableOptions = {
    info: false,
    paging: false,
    searching: false,
    columns: [
        {data: 'type'},
        {data: 'amount_usd', render: usdToString},
        {data: 'recurring_interval'},
        {data: 'next_charge_at', render: carbonToString},
        {data: 'status', render: friendlySubscriptionStatus},
        {data: null, name: 'controls', orderable: false}
    ]
};

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

            <DataTable class="table table-dark table-hover table-striped"
                       :options="subscriptionTableOptions" :data="account.subscriptions"
            >
                <thead>
                <tr>
                    <th>Type</th>
                    <th>Amount (USD)</th>
                    <th>Interval (days)</th>
                    <th>Next (approx)</th>
                    <th>Status</th>
                    <th></th>
                </tr>
                </thead>
                <template #column-controls="dt: DataTablesNamedSlotProps">
                    <a :href="(dt.rowData as AccountSubscription).url"><i class="fas fa-search"></i></a>
                    <button class="btn btn-secondary ms-2"
                            v-if="(dt.rowData as AccountSubscription).status === 'active'"
                            @click="cancelSubscription(dt.rowData as AccountSubscription)"
                    >
                        Cancel
                    </button>
                </template>

            </DataTable>

            <p>Payments made via subscriptions also show on the Account Transactions page.</p>
        </div>

        <h2 class="mt-2">Emails</h2>

        <DataTable class="table table-dark table-hover table-striped"
                   :options="emailTableOptions" :data="account.emails"
        >
            <thead>
            <tr>
                <th>Email</th>
                <th>Primary?</th>
                <th>Registered</th>
                <th>Verified</th>
                <th></th>
            </tr>
            </thead>
            <template #column-primary="dt: DataTablesNamedSlotProps">
                <i class="fa-solid fa-check w-100 text-center"
                   v-if="(dt.rowData as AccountEmail).isPrimary"
                ></i>
            </template>
            <template #column-controls="dt: DataTablesNamedSlotProps">
                <button class="btn btn-secondary" v-if="!(dt.rowData as AccountEmail).isPrimary"
                        @click="confirmMakeEmailPrimary(dt.rowData)"
                >
                    Make Primary
                </button>
            </template>
        </DataTable>
        <div class="d-flex align-items-center">
            <a :href="links.newEmail">
                <button class="btn btn-primary mt-2">Add new Email</button>
            </a>
            <div class="ms-2 text-muted">
                Adding a new email will also cause the new email to become your primary email.
            </div>
        </div>

        <h2 class="mt-2">Account Controls</h2>
        <div class="row g-2">
            <div class="col-12 col-sm-6">
                <a :href="links.changePassword">
                    <button class="w-100 btn btn-primary">Change Password</button>
                </a>
            </div>
            <div class="col-12 col-sm-6">
                <a :href="links.accountCurrencyHistory">
                    <button class="w-100 btn btn-primary">{{ lex('accountCurrency') }} History</button>
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
                            title="Change Primary Email?" yes-label="Change" no-label="Cancel"
        >
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
