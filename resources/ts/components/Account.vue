<script setup lang="ts">

import {Ref, ref} from 'vue';
import ModalConfirmation from './ModalConfirmation.vue';
import {usdToString, carbonToString, arrayToList} from "../formatting";
import {csrf, lex} from "../siteutils";
import {Account, AccountEmail, AccountSubscription} from "../defs";
import DataTable from 'primevue/datatable';
import Column from "primevue/column";

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

const confirmMakeEmailPrimary = (email: AccountEmail) => {
    emailToMakePrimary.value = email.email;
    confirmPrimaryEmailModal.value.show();
}

const makeEmailPrimary = () => {
    (document.getElementById('changeEmailForm') as HTMLFormElement).submit();
}

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

            <DataTable :value="account.subscriptions" stripedRows>
                <Column header="Type" field="type"></Column>
                <Column header="Amount (USD)" field="amount_usd">
                    <template #body="{ data }">
                        {{ usdToString((data as AccountSubscription).amount_usd) }}
                    </template>
                </Column>
                <Column header="Interval (days)" field="recurring_interval"></Column>
                <Column header="Next (approx)" field="next_charge_at">
                    <template #body="{ data }">
                        {{ carbonToString((data as AccountSubscription).next_charge_at) }}
                    </template>
                </Column>
                <Column header="Status" field="status">
                    <template #body="{ data }">
                        {{ friendlySubscriptionStatus((data as AccountSubscription).status) }}
                    </template>
                </Column>
                <Column>
                    <template #body="{ data }">
                        <a :href="(data as AccountSubscription).url"><i class="fas fa-search"></i></a>
                        <button class="btn btn-secondary ms-2"
                                v-if="(data as AccountSubscription).status === 'active'"
                                @click="cancelSubscription(data as AccountSubscription)"
                        >
                            Cancel
                        </button>
                    </template>
                </Column>
            </DataTable>

            <p>Payments made via subscriptions also show on the Account Transactions page.</p>
        </div>

        <h2 class="mt-2">Emails</h2>

        <DataTable :value="account.emails" stripedRows>
            <Column header="Email" field="email"></Column>
            <Column header="Primary?" field="isPrimary" headerClass="d-flex justify-content-center" class="text-center">
                <template #body="{ data }">
                    <i class="fa-solid fa-check w-100 text-center"
                       v-if="(data as AccountEmail).isPrimary"
                    ></i>
                </template>
            </Column>
            <Column header="Registered" field="createdAt">
                <template #body="{ data }">
                    {{ carbonToString((data as AccountEmail).createdAt) }}
                </template>
            </Column>
            <Column header="Verified" field="verifiedAt">
                <template #body="{ data }">
                    {{ carbonToString((data as AccountEmail).verifiedAt) }}
                </template>
            </Column>
            <Column>
                <template #body="{ data }">
                    <button class="btn btn-secondary" v-if="!(data as AccountEmail).isPrimary"
                            @click="confirmMakeEmailPrimary(data)"
                    >
                        Make Primary
                    </button>
                </template>
            </Column>
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
