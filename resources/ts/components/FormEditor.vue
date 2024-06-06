<script setup lang="ts">

import {ref, Ref} from "vue";
import ModalConfirmation from "./ModalConfirmation.vue";
import ModalMessage from "./ModalMessage.vue";
import FormEditorFormSelection from "./FormEditorFormSelection.vue";

type Form = {
    name: string,
    mass: number,
    height: number
}

const presentFormId: Ref<string | null> = ref(null);
const presentForm: Ref<Form | null> = ref(null);
const viewOnly: Ref<boolean> = ref(false);
const formSelector: Ref<InstanceType<typeof FormEditorFormSelection> | null> = ref(null);

const confirmDeleteModal: Ref<InstanceType<typeof ModalConfirmation> | null> = ref(null);
const formToDelete: Ref<string> = ref('');

const createFormModal: Ref<InstanceType<typeof ModalConfirmation> | null> = ref(null);
const newFormName: Ref<string> = ref('');

const error: Ref<string> = ref('');
const errorModal: Ref<InstanceType<typeof ModalMessage> | null> = ref(null);

const channel = mwiWebsocket.channel('contribute');

const loadForm = (selected: string) => {
    presentFormId.value = selected;
    presentForm.value = null;
    channel.send('getForm', presentFormId.value);
}

const unloadForm = () => {
    presentFormId.value = null;
    presentForm.value = null;
}

const deleteForm = () => {
    if (formToDelete.value == presentFormId.value) {
        channel.send('deleteForm', presentFormId.value);
    } else {
        error.value = "Cancelled - The name you enter must match the form name.";
        if (errorModal.value) errorModal.value.show();
    }
}

const startDeleteForm = () => {
    if (confirmDeleteModal.value) confirmDeleteModal.value.show();
}

const createForm = () => {
    if (newFormName.value) {
        channel.send('createForm', newFormName.value);
    } else {
        error.value = "Cancelled - No name was given for the new form..";
        if (errorModal.value) errorModal.value.show();
    }
}

const startCreateForm = () => {
    if (createFormModal.value) createFormModal.value.show();
}

type GetFormResponse = {
    error?: string,
    form?: Form,
    canEdit?: boolean
}

channel.on('form', (response: GetFormResponse) => {
    if (response.error) {
        error.value = response.error;
        if (errorModal.value) errorModal.value.show();
        return;
    }
    viewOnly.value = !(response.canEdit == true);
    presentForm.value = response.form ?? null;
})

type DeleteFormResponse = {
    error?: string,
    formId?: string
}

channel.on('deleteForm', (response:DeleteFormResponse) => {
    if (response.error) {
        error.value = response.error;
        if (errorModal.value) errorModal.value.show();
        return;
    }

    if (response.formId == presentFormId.value) {
        if (formSelector.value) formSelector.value.refresh();
        unloadForm();
    }
})

type CreateFormResponse = {
    error?: string,
    formId?: string
}

channel.on('createForm', (response:CreateFormResponse) => {
    if (response.error) {
        error.value = response.error;
        if (errorModal.value) errorModal.value.show();
        return;
    }
    if (response.formId) {
        if (formSelector.value) formSelector.value.refresh();
        loadForm(response.formId);
    }
})


</script>

<template>
    <FormEditorFormSelection ref="formSelector" :start-expanded="presentFormId == null"
                             @update="loadForm" @new="startCreateForm">
    </FormEditorFormSelection>

    <div v-if="!presentFormId">
        No form selected. <br/>Select a form above to begin editing.
    </div>
    <div v-else-if="!presentForm">
        Loading form..
    </div>
    <div v-else>
        <div>Editing form: {{ presentFormId }}</div>
        <ul class="nav nav-tabs nav-fill mt-2" id="form-editor-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="nav-overview-tab"
                        data-bs-toggle="tab" data-bs-target="#nav-overview"
                        type="button" role="tab" aria-controls="nav-overview" aria-selected="true"
                >Overview & Status
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="nav-bodyparts-tab"
                        data-bs-toggle="tab" data-bs-target="#nav-bodyparts"
                        type="button" role="tab" aria-controls="nav-bodyparts" aria-selected="false"
                >Descriptions & Transformations
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="nav-victorydefeat-tab"
                        data-bs-toggle="tab" data-bs-target="#nav-victorydefeat"
                        type="button" role="tab" aria-controls="nav-victorydefeat" aria-selected="false"
                >Victory & Defeat Messages
                </button>
            </li>
        </ul>
        <div class="tab-content border p-4" id="nav-tabContent">

            <!-- Overview & Status -->
            <div class="tab-pane show active" id="nav-overview" role="tabpanel" aria-labelledby="nav-overview-tab">
                Status of the form (approved/review/etc)

                <div class="d-flex align-items-center mt-2">
                    <div class="sliderLabel">Mass</div>
                    <div class="ms-1 flex-fill">
                        <input type="range" v-model.number="presentForm.mass" :disabled="viewOnly"
                               class="form-control-range w-100" min="-100" max="300"
                        >
                    </div>
                    <div class="ms-1 sliderValue">{{ presentForm.mass }}</div>
                </div>
                <div class="text-muted">This is a percentage-modifier. 0 is average weight, -50 would be half average
                    weight and 100 would be twice average weight.
                </div>

                <div class="d-flex align-items-center mt-2">
                    <div class="sliderLabel">Height</div>
                    <div class="ms-1 flex-fill">
                        <input type="range" v-model.number="presentForm.height" :disabled="viewOnly"
                               class="form-control-range w-100" min="1" max="30"
                        >
                    </div>
                    <div class="ms-1 sliderValue">{{ presentForm.height }}</div>
                </div>
                <div class="text-muted">5 is average human height.</div>

                <div class="text-center">
                    Changes are saved automatically as you make them.
                </div>
                <div>
                    <button class="btn btn-secondary me-2" @click="startDeleteForm">
                        <i class="fas fa-trash btn-icon-left"></i>Delete Form
                    </button>
                </div>
            </div>

            <!-- Descriptions & Transformations -->
            <div class="tab-pane show" id="nav-bodyparts" role="tabpanel" aria-labelledby="nav-bodyparts-tab">
                Descriptions & Transformations
            </div>

            <!-- Victory & Defeat Messages -->
            <div class="tab-pane show" id="nav-victorydefeat" role="tabpanel" aria-labelledby="nav-victorydefeat-tab">
                Victory & Defeat Messages
            </div>


        </div>
    </div>

    <!-- Modal to delete a form -->
    <modal-confirmation ref="confirmDeleteModal" @yes="deleteForm"
                        title="Delete Form" yes-label="Delete" no-label="Cancel"
    >

        <p>WARNING: Deleting is a permanent act. It cannot be undone!</p>
        <p>Please enter the form's name to confirm this is intentional before clicking delete.</p>

        <label for="formToDelete" class="form-label">Enter the name of the form:</label>
        <input type="text" class="form-control" id="formToDelete" v-model="formToDelete">

    </modal-confirmation>

    <!-- Modal to create a new form -->
    <modal-confirmation ref="createFormModal" @yes="createForm"
                        title="Create New Form" yes-label="Create" no-label="Cancel"
    >

        <label for="newFormName" class="form-label">Enter the name of the form:</label>
        <input type="text" class="form-control" id="newFormName" v-model="newFormName">

        <p>WARNING: Do not enter pokemon, disney characters, or any other copyrighted material. Fictional races made in
            the last century ARE copyrighted, don't use them.</p>
        <p>Any content entered becomes property of the game, and its owning company(Silver Games LLC). Please review the
            Terms of Service.</p>
    </modal-confirmation>

    <!-- Modal for error messages -->
    <modal-message ref="errorModal">
        {{ error }}
    </modal-message>

</template>

<style scoped>
.sliderLabel {
    min-width: 80px;
}

.sliderValue {
    min-width: 32px;
}
</style>
