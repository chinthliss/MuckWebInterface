<script setup lang="ts">

import {ref, Ref} from "vue";
import ModalConfirmation from "./ModalConfirmation.vue";
import ModalMessage from "./ModalMessage.vue";
import FormEditorFormSelection from "./FormEditorFormSelection.vue";

const presentFormId: Ref<string | null> = ref(null);
const channel = mwiWebsocket.channel('contribute');

const confirmDeleteModal: Ref<InstanceType<typeof ModalConfirmation> | null> = ref(null);
const formToDelete: Ref<string> = ref('');

const createFormModal: Ref<InstanceType<typeof ModalConfirmation> | null> = ref(null);
const newFormName: Ref<string> = ref('');

const error: Ref<string> = ref('');
const errorModal: Ref<InstanceType<typeof ModalMessage> | null> = ref(null);

const onFormSelected = (selected: string) => {
    presentFormId.value = selected;
}

const deleteForm = () => {
    if (formToDelete.value == presentFormId.value) {
        //TODO: Implement DeleteForm process
        error.value = "Not Implemented Yet.";
        if (errorModal.value) errorModal.value.show();
    } else {
        error.value = "Cancelled - The name you enter must match the form name.";
        if (errorModal.value) errorModal.value.show();
    }
}
const startDeleteForm = () => {
    if (confirmDeleteModal.value) confirmDeleteModal.value.show();
}

const createForm = () => {
    //TODO: Implement CreateForm process
    error.value = "Not Implemented Yet.";
    if (errorModal.value) errorModal.value.show();
}

const startCreateForm = () => {
    if (createFormModal.value) createFormModal.value.show();
}

</script>

<template>
    <FormEditorFormSelection :start-expanded="presentFormId == null" @update="onFormSelected" @new="startCreateForm">
    </FormEditorFormSelection>

    <div>
    </div>
    <div v-if="!presentFormId">
        No form selected. <br/>Select a form above to begin editing.
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

            <!-- Overview -->
            <div class="tab-pane show active" id="nav-overview" role="tabpanel" aria-labelledby="nav-overview-tab">
                Overview

                Status of the form (approved/review/etc)

                <div>
                    <button class="btn btn-secondary me-2" @click="startDeleteForm">
                        <i class="fas fa-trash btn-icon-left"></i>Delete Form
                    </button>
                </div>
            </div>

            <!-- Bodyparts -->
            <div class="tab-pane show" id="nav-bodyparts" role="tabpanel" aria-labelledby="nav-bodyparts-tab">
                Descriptions & Transformations
            </div>

            <!-- Victory/Defeat -->
            <div class="tab-pane show" id="nav-victorydefeat" role="tabpanel" aria-labelledby="nav-victorydefeat-tab">
                Victory & Defeat Messages
            </div>


        </div>
    </div>

    <!-- Modal to delete a form -->
    <modal-confirmation ref="confirmDeleteModal" @yes="deleteForm"
                        title="Delete Form" yes-label="Delete" no-label="Cancel">

        <p>WARNING: Deleting is a permanent act. It cannot be undone!</p>
        <p>Please enter the form's name to confirm this is intentional before clicking delete.</p>

        <label for="formToDelete" class="form-label">Enter the name of the form:</label>
        <input type="text" class="form-control" id="formToDelete" v-model="formToDelete">

    </modal-confirmation>

    <!-- Modal to create a new form -->
    <modal-confirmation ref="createFormModal" @yes="createForm"
                        title="Create New Form" yes-label="Create" no-label="Cancel">

        <label for="newFormName" class="form-label">Enter the name of the form:</label>
        <input type="text" class="form-control" id="newFormName" v-model="newFormName">

        <p>WARNING: Do not enter pokemon, disney characters, or any other copyrighted material. Fictional races made in the last century ARE copyrighted, don't use them.</p>
        <p>Any content entered becomes property of the game, and its owning company(Silver Games LLC). Please review the Terms of Service.</p>
    </modal-confirmation>

    <!-- Modal for error messages -->
    <modal-message ref="errorModal">
        {{ error }}
    </modal-message>

</template>

<style scoped>

</style>
