<template>
    <div class="container">

        <div class="d-flex align-items-center">
            <div class="flex-grow-1">
                <h1>Account {{ account.id }}</h1>
            </div>
            <div>
                <div v-if="account.locked">
                    <div class="text-danger p-2">Account locked as of {{ carbonToString(account.locked) }}</div>
                    <button class="btn btn-danger float-end" @click="unlockAccount">
                        <i class="fas fa-unlock btn-icon-left"></i>Unlock Account
                    </button>
                </div>
                <div v-else>
                    <button class="btn btn-danger float-end" @click="lockAccount">
                        <i class="fas fa-lock btn-icon-left"></i>Lock Account
                    </button>
                </div>
            </div>
        </div>

        <dl class="row">
            <dt class="col-sm-2 text-primary">Created</dt>
            <dd class="col-sm-10">{{ carbonToString(account.createdAt) }}</dd>

            <dt class="col-sm-2 text-primary">Veterancy</dt>
            <dd class="col-sm-10">{{ account.veterancy }} Month(s)</dd>

            <dt class="col-sm-2 text-primary">Last Connected</dt>
            <dd class="col-sm-10">{{ carbonToString(account.lastConnected) }}</dd>

            <dt class="col-sm-2 text-primary">{{ lex('accountCurrency') }}</dt>
            <dd class="col-sm-10">{{ account.currency }}</dd>

            <dt class="col-sm-2 text-primary">Subscription</dt>
            <dd class="col-sm-10">{{ overallSubscriptionStatus() }}</dd>

            <dt class="col-sm-2 text-primary">Referrals</dt>
            <dd class="col-sm-10">{{ account.referrals }}</dd>

            <dt class="col-sm-2 text-primary">Supporter Points</dt>
            <dd class="col-sm-10">{{ account.supporterPoints }}</dd>

            <dt class="col-sm-2 text-primary">Flags</dt>
            <dd class="col-sm-10">{{ arrayToList(account.flags, 'None') }}</dd>

            <dt class="col-sm-2 text-primary">Website Roles</dt>
            <dd class="col-sm-10">{{ arrayToList(account.roles, 'None') }}</dd>

            <dt class="col-sm-2 text-primary">Patreon User</dt>
            <dd class="col-sm-10">
                <div v-if="account.patreon">{{ account.patreon.patreonId }}</div>
                <div v-else>No</div>
            </dd>

            <dt class="col-sm-2 text-primary">Characters ({{ lex('game_name') }})</dt>
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
                <div class="row g-2 mt-2">
                    <div class="col-12 col-xl-3">
                        <label for="NewAccountNote" class="col align-middle">New Account Note</label>
                    </div>
                    <div class="col-12 col-xl-6">
                        <input type="text" class="form-control" id="NewAccountNote" placeholder="New Account Note"
                               v-model="newAccountNote">
                    </div>
                    <div class="col-12 col-xl-3">
                        <button class="btn btn-primary form-control align-middle" @click="addAccountNote">
                            <i class="fas fa-note-sticky btn-icon-left"></i>Add Account Note
                        </button>
                    </div>
                </div>
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

/**
 * @typedef {object} Character
 * @property {int} dbref
 * @property {string} type
 * @property {string} name
 * @property {string} created
 */

/**
 * @typedef {object} AccountNote
 * @property {int} accountId
 * @property {string} whenAt
 * @property {string} body
 * @property {string} staffMember
 * @property {string} game
 */

/**
 * @typedef {object} Account
 * @property {int} id
 * @property {string} createdAt
 * @property {string} verifiedAt
 * @property {string} lockedAt
 * @property {string} lastConnected
 * @property {string} primaryEmail
 * @property {string} url
 * @property {int} referrals
 * @property {int} supporterPoints
 * @property {int} veterancy
 * @property {int} currency
 * @property {string[]} flags
 * @property {string[]} roles
 * @property {Email[]} emails
 * @property {Character[]} characters
 * @property {AccountNote[]} notes
 * @property {object} [patreon]
 * @property {boolean} subscriptionActive
 * @property {boolean} subscriptionRenewing
 * @property {string} subscriptionExpires
 * @property {array} subscriptions
 */

import {ref} from 'vue';
import {arrayToList, carbonToString} from "../formatting";
import {lex} from "../siteutils";
import CharacterCard from "./CharacterCard.vue";
import DataTable from 'datatables.net-vue3';

const props = defineProps({
    account: {type: Object, required: true},
    apiUrl: {type: String, required: true}
});

/**
 * @type {Ref<Account>}
 */
const account = ref(props.account);
const newAccountNote = ref('');

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

const addAccountNote = () => {
    axios
        .post(props.apiUrl, {operation: 'addAccountNote', note: newAccountNote.value})
        .then(response => {
            if (response?.data === 'OK') location.reload();
            else console.log("Unrecognized response to adding an account note: ", response);
        })
        .catch(error => {
            console.log("Request failed:", error);
        })
};

const lockAccount = () => {
    axios
        .post(props.apiUrl, {operation: 'lock'})
        .then(response => {
            if (response?.data === 'OK') account.value.locked = new Date().toISOString();
            else console.log("Unrecognized response to locking account: ", response);
        })
        .catch(error => {
            console.log("Request failed:", error);
        })
}


const unlockAccount = () => {
    axios
        .post(props.apiUrl, {operation: 'unlock'})
        .then(response => {
            if (response?.data === 'OK') account.value.locked = null;
            else console.log("Unrecognized response to unlocking account: ", response);
        })
        .catch(error => {
            console.log("Request failed:", error);
        })

}

const overallSubscriptionStatus = () => {
    if (!account.value.subscriptionActive) return 'No Subscription';
    if (account.value.subscriptionRenewing) return 'Active, renews sometime before ' + account.value.subscriptionExpires;
    return 'Active, expires sometime before ' + account.value.subscriptionExpires;
}
</script>

<style scoped>

</style>
