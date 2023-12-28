<script setup lang="ts">

import {ref, Ref} from "vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import {characterDbref} from "../siteutils";
import ModalConfirmation from "./ModalConfirmation.vue";

type CustomField = {
    field: string,
    value: string
}

const shortDescription: Ref<string | null> = ref(null);
const blockShortDescriptionEdit: Ref<boolean> = ref(true);
const customFields: Ref<CustomField[]> = ref([]);
const channel = mwiWebsocket.channel('character');
const dbref = characterDbref();

const fieldEditModal: Ref<Element | null> = ref(null);
const fieldEditOriginalName: Ref<string> = ref(''); // To capture renames
const fieldEditName: Ref<string> = ref('');
const fieldEditValue: Ref<string> = ref('');

const saveShortDescription = () => {
    blockShortDescriptionEdit.value = true;
    channel.send('updateShortDescription', shortDescription.value);
}

const startEditingCustomField = (field: CustomField) => {
    fieldEditOriginalName.value = field.field;
    fieldEditName.value = field.field;
    fieldEditValue.value = field.value;
    if (fieldEditModal.value) fieldEditModal.value.show();
}

const startAddingCustomField = () => {
    fieldEditOriginalName.value = '';
    fieldEditName.value = '';
    fieldEditValue.value = '';
    if (fieldEditModal.value) fieldEditModal.value.show();
}

const saveCustomFieldEdit = () => {
    if (fieldEditOriginalName.value) {
        channel.send('editCustomField', '?');
    } else {
        channel.send('addCustomField', '?');
    }
}

const deleteCustomField = (field: CustomField) => {
    channel.send('deleteCustomField', field.field);
}

channel.on('customFields', (data) => {
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
            <button class="btn btn-primary" @click="saveShortDescription" :disabled="blockShortDescriptionEdit">Save
            </button>
        </div>
    </div>

    <div>
        <h4>Description</h4>
        <p>Full descriptions are viewed in game when in the same area as other players. They can edited from within
            game.</p>
    </div>

    <div>
        <h4>Custom Fields</h4>
        <p>These are shown on the character's profile and allow you to set any details you want, e.g. history, rumours,
            common knowledge, player availability, etc.</p>
        <DataTable :value="customFields" stripedRows>
            <template #empty>No custom fields configured.</template>
            <Column header="Field" field="field" sortable></Column>
            <Column header="Value" field="value"></Column>
            <Column>
                <template #body="{ data }">
                    <button class="btn btn-primary"
                            @click="startEditingCustomField(data)"
                    >Edit
                    </button>
                    <button class="btn btn-secondary ms-2"
                            @click="deleteCustomField(data)"
                    >Delete
                    </button>
                </template>
            </Column>
        </DataTable>
        <div class="float-end mt-2">
            <button class="btn btn-primary" @click="startAddingCustomField()">Create New Field</button>
        </div>

    </div>

    <modal-confirmation ref="fieldEditModal" @yes="saveCustomFieldEdit"
                        yes-label="Save Changes" no-label="Cancel"
    >
        <div class="mb-2">
            <label for="fieldEditName" class="form-label">Custom field name:</label>
            <input type="text" class="form-control" id="fieldEditName" v-model="fieldEditName">
            <label for="fieldEditName" class="form-label">Custom field value:</label>
            <input type="text" class="form-control" id="fieldEditValue" v-model="fieldEditValue">
        </div>
    </modal-confirmation>
</template>

<style scoped>

</style>
