<script setup lang="ts">

import {ref, Ref, computed} from "vue";
import ModalConfirmation from "./ModalConfirmation.vue";
import ModalMessage from "./ModalMessage.vue";
import FormEditorFormSelection from "./FormEditorFormSelection.vue";
import {timestampToString} from "../formatting";

type Form = {
    name: string,
    mass: number,
    height: number,
    owner?: number,
    approved: boolean,
    review: boolean,
    revise: boolean,
    createdAt: number // Timestamp
    editedAt: number // Timestamp
}

const presentFormId: Ref<string | null> = ref(null);
const presentForm: Ref<Form | null> = ref(null);
const viewOnly: Ref<boolean> = ref(false);
const formSelector: Ref<InstanceType<typeof FormEditorFormSelection> | null> = ref(null);

const confirmDeleteModal: Ref<InstanceType<typeof ModalConfirmation> | null> = ref(null);
const formToDelete: Ref<string> = ref('');

const confirmSubmitModal: Ref<InstanceType<typeof ModalConfirmation> | null> = ref(null);

const createFormModal: Ref<InstanceType<typeof ModalConfirmation> | null> = ref(null);
const newFormName: Ref<string> = ref('');

const error: Ref<string> = ref('');
const errorModal: Ref<InstanceType<typeof ModalMessage> | null> = ref(null);

const channel = mwiWebsocket.channel('contribute');

const oneWordStatus = computed((): string => {
    if (!presentForm.value) return '';
    if (presentForm.value.revise) return 'Revision Needed';
    if (presentForm.value.review) return 'Awaiting Review';
    return presentForm.value.approved ? 'Finished' : 'Under Construction';
});

const statusDescription = computed((): string => {
    if (!presentForm.value) return '';
    if (presentForm.value.revise) return 'Staff have reviewed the form and some additional work is needed. After reviewing staff feedback you can submit the form again.';
    if (presentForm.value.review) return 'The form is awaiting staff review. You can view it but not make any changes.';
    if (presentForm.value.approved) return 'This form has been finalized. You can view it but not make any changes.';
    return 'This is a new or unfinished form. After you have completed enough of the required content you can submit the form for review.';
});

const loadForm = (selected: string) => {
    presentFormId.value = selected;
    presentForm.value = null;
    channel.send('getForm', presentFormId.value);
}

const unloadForm = () => {
    presentFormId.value = null;
    presentForm.value = null;
}

const startDeleteForm = () => {
    if (confirmDeleteModal.value) confirmDeleteModal.value.show();
}

const deleteForm = () => {
    if (formToDelete.value == presentFormId.value) {
        channel.send('deleteForm', presentFormId.value);
    } else {
        error.value = "Cancelled - The name you enter must match the form name.";
        if (errorModal.value) errorModal.value.show();
    }
}

const startSubmitForm = () => {
    if (confirmSubmitModal.value) confirmSubmitModal.value.show();
}

const submitForm = () => {
    error.value = "Not implemented yet";
    if (errorModal.value) errorModal.value.show();
    //TODO: Form submission
}

const startCreateForm = () => {
    if (createFormModal.value) createFormModal.value.show();
}

const createForm = () => {
    if (newFormName.value) {
        channel.send('createForm', newFormName.value);
    } else {
        error.value = "Cancelled - No name was given for the new form..";
        if (errorModal.value) errorModal.value.show();
    }
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

channel.on('deleteForm', (response: DeleteFormResponse) => {
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

channel.on('createForm', (response: CreateFormResponse) => {
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
                             @update="loadForm" @new="startCreateForm"
    >
    </FormEditorFormSelection>

    <div v-if="!presentFormId">
        No form selected. <br/>Select a form above to begin editing.
    </div>
    <div v-else-if="!presentForm">
        Loading form..
    </div>
    <div v-else>
        <h3>{{ presentFormId }}</h3>

        <!-- Tabs -->
        <ul class="nav nav-tabs nav-fill mt-2 sticky-top" id="form-editor-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="nav-status-tab"
                        data-bs-toggle="tab" data-bs-target="#nav-status"
                        type="button" role="tab" aria-controls="nav-status" aria-selected="true"
                >Status
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="nav-properties-tab"
                        data-bs-toggle="tab" data-bs-target="#nav-properties"
                        type="button" role="tab" aria-controls="nav-properties" aria-selected="false"
                >Properties
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="nav-bodyparts-tab"
                        data-bs-toggle="tab" data-bs-target="#nav-bodyparts"
                        type="button" role="tab" aria-controls="nav-bodyparts" aria-selected="false"
                >Descriptions
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="nav-victorydefeat-tab"
                        data-bs-toggle="tab" data-bs-target="#nav-victorydefeat"
                        type="button" role="tab" aria-controls="nav-victorydefeat" aria-selected="false"
                >Victory & Defeat
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content border p-4" id="nav-tabContent">

            <!-- Status -->
            <div class="tab-pane show active" id="nav-status" role="tabpanel" aria-labelledby="nav-status-tab">

                <div>Status: {{ oneWordStatus }}</div>
                <div class="text-muted">{{ statusDescription }}</div>

                <div class="mt-2">Created: {{ timestampToString(presentForm.createdAt) }} </div>

                <div class="mt-2">Last edited: {{ timestampToString(presentForm.editedAt) }} </div>

                <div class="mt-2">
                    <button class="btn btn-primary me-2" @click="startSubmitForm">
                        <i class="fas fa-thumbs-up btn-icon-left"></i>Submit Form
                    </button>

                    <button class="btn btn-secondary me-2" @click="startDeleteForm">
                        <i class="fas fa-trash btn-icon-left"></i>Delete Form
                    </button>
                </div>

            </div>

            <!-- Properties -->
            <div class="tab-pane show" id="nav-properties" role="tabpanel" aria-labelledby="nav-properties-tab">
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

                <h4 class="mt-2">Say Verbs</h4>
                <div class="row">
                    <div class="mt-2 col-12 col-xl-6">
                        <label id="2nd-person-say-label" for="2nd-person-say" class="form-label">
                            2nd person (say, purr, bark)
                        </label>
                        <input id="2nd-person-say" type="text" class="form-control" :disabled="viewOnly"
                               placeholder="2nd Person" v-model="presentForm.say"
                        >
                    </div>

                    <div class="mt-2 col-12 col-xl-6">
                        <label id="3rd-person-say-label" for="3rd-person-say" class="form-label">
                            3rd person (says, purrs, barks)
                        </label>
                        <input id="3rd-person-say" type="text" class="form-control" :disabled="viewOnly"
                               placeholder="3rd Person" v-model="presentForm.osay"
                        >
                    </div>
                </div>

                <h4 class="mt-2">Bodypart counts and sizes</h4>
                <div class="row">
                    <div class="mt-2 col-12 col-xl-3">
                        <label for="cock-count" class="form-label">C Count</label>
                        <input id="cock-count" type="number" class="form-control" :disabled="viewOnly"
                               placeholder="#"  v-model="presentForm.cockCount"
                        >
                    </div>

                    <div class="mt-2 col-12 col-xl-9">
                        <div>C Length</div>
                    </div>

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
        <div class="text-center">
            Changes are saved automatically as you make them.
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

    <!-- Modal to submit a form -->
    <modal-confirmation ref="confirmSubmitModal" @yes="submitForm"
                        title="Submit Form" yes-label="Submit" no-label="Cancel"
    >
        <p>Are you sure you're finished with this form? You won't be able to make any changes after submitting it.</p>
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
