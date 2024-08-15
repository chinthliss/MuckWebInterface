<script setup lang="ts">

import {Ref, ref} from 'vue';
import {AccountCard} from "../defs";
import DataTable from 'primevue/datatable';
import Column from "primevue/column";

const props = defineProps<{
    profileId: string,
    cardsIn: AccountCard[],
    links: {
        addCard: string,
        deleteCard: string,
        setDefaultCard: string
    }
}>();

const cards: Ref<AccountCard[]> = ref(props.cardsIn);

const errors: Ref<{[errorKey:string]: string}> = ref({});

const cardNumber: Ref<string> = ref('');
const expiryDate: Ref<string> = ref('');
const securityCode: Ref<string> = ref('');
const pendingRequest: Ref<boolean> = ref(false);

const addCard = (event: Event) => {
    event.preventDefault();
    if (pendingRequest.value) return;
    pendingRequest.value = true;
    axios.post(props.links.addCard, {
        'cardNumber': cardNumber.value,
        'expiryDate': expiryDate.value,
        'securityCode': securityCode.value
    }).then(response => {
        cardNumber.value = '';
        expiryDate.value = '';
        securityCode.value = '';
        cards.value.push(response.data);
        //Any new card is the default, so need to reflect this
        cards.value.forEach((card) => {
            card.isDefault = card.id === response.data.id;
        });
    }).catch(error => {
        if (error.response?.data?.errors)
            errors.value = error.response.data.errors;
        else console.log("Error in addCard: " + error);
    }).finally(() => {
        pendingRequest.value = false;
    });
};

const deleteCard = (card: AccountCard) => {
    if (pendingRequest.value) return;
    pendingRequest.value = true;
    axios.delete(props.links.deleteCard, {
        data: {'id': card.id}
    }).then(_response => {
        cards.value = cards.value.filter(existingCard => existingCard != card);
    }).catch(error => {
        if (error.response?.data?.errors)
            errors.value = error.response.data.errors;
        else console.log("Error in deleteCard: " + error);
    }).finally(() => {
        pendingRequest.value = false;
    });
}

const setCardAsDefault = (card: AccountCard) => {
    if (pendingRequest.value) return;
    pendingRequest.value = true;
    axios.patch(props.links.setDefaultCard, {
        'id': card.id
    }).then(_response => {
        cards.value.forEach((existingCard) => {
            existingCard.isDefault = existingCard.id == card.id;
        });
    }).catch(error => {
        if (error.response?.data?.errors)
            errors.value = error.response.data.errors;
        else console.log("Error in setDefaultCard: " + error);
    }).finally(() => {
        pendingRequest.value = false;
    });
}

</script>

<template>
    <div class="container">

        <h1>Card Management</h1>

        <DataTable :value="cards" stripedRows>
            <template #empty>You have no cards registered.</template>
            <Column header="Type" field="cardType"></Column>
            <Column header="Ends With" field="maskedCardNumber"></Column>
            <Column header="Expiry" field="expiryDate"></Column>
            <Column>
                <template #body="{ data }">
                    <button class="btn btn-secondary ms-2"
                            @click="deleteCard(data as AccountCard)">
                        <i class="fas fa-trash btn-icon-left"></i>Delete
                    </button>
                    <button v-if="!(data as AccountCard).isDefault"
                            class="btn btn-secondary ms-2"
                            @click="setCardAsDefault(data as AccountCard)"
                    >
                        <i class="fas fa-check btn-icon-left"></i>Make Default
                    </button>
                </template>

            </Column>
        </DataTable>


        <div id="add-card" class="border border-primary rounded p-3 mt-4">
            <h2>Register New Card</h2>
            <form>
                <div class="row">
                    <div class="col">
                        <label for="inputCardNumber">Card Number</label>
                        <input type="text" class="form-control" id="inputCardNumber"
                               placeholder="Enter the long number across the front of the card"
                               v-model="cardNumber"
                               v-bind:class="{ 'is-invalid' : errors.cardNumber }"
                        >
                        <div class="invalid-feedback" role="alert">
                            <p v-for="error in errors.cardNumber">{{ error }}</p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xl-5 mt-2">
                        <label for="inputExpiryDate">Expiry Date</label>
                        <input type="text" class="form-control" id="inputExpiryDate"
                               placeholder="Enter the expiry date in the form MM/YYYY"
                               v-model="expiryDate"
                               v-bind:class="{ 'is-invalid' : errors.expiryDate }"
                        >
                        <div class="invalid-feedback" role="alert">
                            <p v-for="error in errors.expiryDate">{{ error }}</p>
                        </div>
                    </div>
                    <div class="col-xl-5 mt-2">
                        <label for="inputSecurityCode">Security Code</label>
                        <input type="text" class="form-control" id="inputSecurityCode"
                               placeholder="Enter the security code from the back of the card"
                               v-model="securityCode"
                               v-bind:class="{ 'is-invalid' : errors.securityCode }"
                        >
                        <div class="invalid-feedback" role="alert">
                            <p v-for="error in errors.securityCode">{{ error }}</p>
                        </div>
                    </div>
                    <div class="col-xl-2">
                        <div class="mb-2">&nbsp;</div>
                        <button id="addCardButton" class="btn btn-primary btn-block" @click="addCard">
                            Add New Card
                        </button>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col">
                        <div class="invalid-feedback" role="alert">
                            <p v-for="error in errors.other">{{ error }}</p>
                        </div>
                    </div>
                </div>
            </form>
        </div>

    </div>
</template>

<style scoped>

</style>
