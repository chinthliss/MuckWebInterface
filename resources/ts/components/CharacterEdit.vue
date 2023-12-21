<script setup lang="ts">

import {ref, Ref} from "vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import {characterDbref} from "../siteutils";

type CustomField = {
    field: string,
    value: string
}

const shortDescription: Ref<string | null> = ref(null);
const blockShortDescriptionEdit: Ref<boolean> = ref(true);
const customFields: Ref<CustomField[]> = ref([]);
const channel = mwiWebsocket.channel('character');
const dbref = characterDbref();

const saveShortDescription = () => {
    blockShortDescriptionEdit.value = true;
    channel.send('updateShortDescription', shortDescription.value);
}

const startEditingCustomField = (field: CustomField ) => {
    console.log(field);
    throw "StartEditingCustomField not implemented."
}

const deleteCustomField = (field: CustomField) => {
    console.log(field);
    throw "DeleteCustomField not implemented."
}


channel.on('custom', (data) => {
    customFields.value = data;
});

channel.on('shortDescription', (data) => {
    blockShortDescriptionEdit.value = false;
    shortDescription.value = data;
});

channel.send('bootCharacterEdit', dbref);

</script>

<template>
    <div>
        <h4>Short Description</h4>
        <p>This is a simple description displayed in quick glances in rooms and on the character's profile.</p>
        <textarea class="w-100" v-model="shortDescription" :disabled="blockShortDescriptionEdit"></textarea>
        <div class="float-end">
            <button class="btn btn-primary" @click="saveShortDescription" :disabled="blockShortDescriptionEdit">Save</button>
        </div>
    </div>

    <div>
        <h4>Description</h4>
        <p>Full descriptions are viewed in game when in the same area as other players. They can edited from within game.</p>
    </div>

    <div>
        <h4>Custom Fields</h4>
        <p>These are shown on the character's profile and allow you to set any details you want, e.g. history, rumours, common knowledge, player availability, etc.</p>
        <DataTable :value="customFields" stripedRows>
            <template #empty>No custom fields configured.</template>
            <Column header="Field" field="field" sortable></Column>
            <Column header="Value" field="value"></Column>
            <Column>
                <template #body="{ data }">
                    <button class="btn btn-primary"
                            @click="startEditingCustomField(data)"
                    >Edit</button>
                    <button class="btn btn-secondary ms-2"
                            @click="deleteCustomField(data)"
                    >Delete</button>
                </template>
            </Column>

        </DataTable>
    </div>
</template>

<style scoped>

</style>
