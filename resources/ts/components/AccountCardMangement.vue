<script setup lang="ts">

import {Ref, ref} from 'vue';
import DataTable from 'datatables.net-vue3';
import {AccountCard} from "../defs";

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

const errors: Ref<object> = ref({});

const cardNumber: Ref<string> = ref('');
const expiryDate: Ref<string> = ref('');
const securityCode: Ref<string> = ref('');
const pendingRequest: Ref<boolean> = ref(false);

const renderControlsColumn = (data: any, type: string, row: any): string => {
    let controls = ''
    controls += `<button class="btn btn-secondary ms-2 card-delete-button" data-id="${row.id}"><i class="fas fa-trash btn-icon-left"></i>Delete</button>`;
    if (!row.isDefault)
        controls += `<button class="btn btn-secondary ms-2 card-set-default-button" data-id="${row.id}"><i class="fas fa-check btn-icon-left"></i>Make Default</button>`;

    return controls;
};

const linkButtonsInTable = () => {
    const deleteButtons = document.querySelectorAll('.card-delete-button') as NodeListOf<HTMLButtonElement>;
    // TODO: Delete card should really have a confirmation
    deleteButtons.forEach(el => {
        el.addEventListener('click', deleteCard);
    })

    const makeDefaultButtons = document.querySelectorAll('.card-set-default-button') as NodeListOf<HTMLButtonElement>;
    makeDefaultButtons.forEach(el => {
        el.addEventListener('click', setCardAsDefault);
    })
};

const cardTableConfiguration = {
    columns: [
        {data: 'cardType'},
        {data: 'maskedCardNumber'},
        {data: 'expiryDate'},
        {render: renderControlsColumn, sortable: false, className: 'text-nowrap'}
    ],
    language: {
        "emptyTable": "You have no cards registered."
    },
    paging: false,
    info: false,
    searching: false,
    ordering: false,
    drawCallback: linkButtonsInTable
};

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

const deleteCard = (event: Event) => {
    if (pendingRequest.value) return;
    pendingRequest.value = true;
    const id = (event.currentTarget as HTMLButtonElement).dataset.id;
    axios.delete(props.links.deleteCard, {
        data: {'id': id}
    }).then(_response => {
        cards.value = cards.value.filter(card => card.id != id);
    }).catch(error => {
        if (error.response?.data?.errors)
            errors.value = error.response.data.errors;
        else console.log("Error in deleteCard: " + error);
    }).finally(() => {
        pendingRequest.value = false;
    });
}

const setCardAsDefault = (event: Event) => {
    if (pendingRequest.value) return;
    pendingRequest.value = true;
    const id = (event.currentTarget as HTMLButtonElement).dataset.id;
    axios.patch(props.links.setDefaultCard, {
        'id': id
    }).then(_response => {
        cards.value.forEach((card) => {
            card.isDefault = card.id == id;
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

        <DataTable class="table table-dark table-hover table-striped table-bordered" :options="cardTableConfiguration"
                   :data="cards">
            <thead>
            <tr>
                <th scope="col">Type</th>
                <th scope="col">Ends With</th>
                <th scope="col">Expiry</th>
                <th scope="col"></th>
            </tr>
            </thead>
        </DataTable>


        <div id="add-card" class="border border-primary rounded p-3">
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
