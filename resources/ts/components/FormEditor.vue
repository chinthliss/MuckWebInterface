<script setup lang="ts">

import {computed, Ref, ref} from "vue";
import ModalConfirmation from "./ModalConfirmation.vue";
import ModalMessage from "./ModalMessage.vue";
import FormEditorFormSelection from "./FormEditorFormSelection.vue";
import {timestampToString} from "../formatting";
import FormEditorCodeEditor from "./FormEditorCodeEditor.vue";

type FormLog = {
    when: number // Timestamp
    who: string
    what: string
}

type Form = {
    name: string
    height: number
    mass: number
    owner?: number
    approved: boolean
    review: boolean
    revise: boolean
    createdAt?: number // Timestamp
    editedAt?: number // Timestamp
    log?: FormLog[]
    tags: string
    say: string
    oSay: string
    breastCount: number
    breastSize: number
    cuntCount: number
    cuntSize: number
    clitCount: number
    clitSize: number
    cockCount: number
    cockSize: number
    ballCount: number
    ballSize: number
    scent: string
    heat: boolean,
    victory: string[],
    oVictory: string[],
    defeat: string[],
    viewers: string,
    skin: {
        flags: string
        transformation: string
        shortDescription: string
        description: string
        kemoDescription: string
    }
    head: {
        flags: string
        transformation: string
        description: string
        kemoDescription: string
    }
    torso: {
        flags: string
        transformation: string
        description: string
        kemoDescription: string
    }
    arms: {
        flags: string
        transformation: string
        description: string
        kemoDescription: string
    }
    legs: {
        flags: string
        transformation: string
        description: string
        kemoDescription: string
    }
    groin: {
        flags: string
        transformation: string
        cockDescription: string
        cuntDescription: string
        clitDescription: string
    }
    ass: {
        flags: string
        transformation: string
        description: string
    }

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
    <div v-if="presentForm">
        <h3>{{ presentFormId }}</h3>

        <!-- Preview -->

        <div class="card">
            <div class="card-header">Preview</div>
            <div class="card-body">
                <div class="card-text" id="formPreview">
                    TODO: Request preview after pending saves are complete.
                    <br/>TODO: Don't request preview until all (or at least 1?) descriptions are set.
                </div>
            </div>
            <div class="card-footer text-muted text-center">The preview can be slow to load, especially on larger
                forms.
            </div>
        </div>

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
                >Parts & Descriptions
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

                <div class="mt-2">Created: {{ timestampToString(presentForm.createdAt) }}</div>

                <div class="mt-2">Last edited: {{ timestampToString(presentForm.editedAt) }}</div>

                <!-- Allowed Viewers -->
                <div class="d-flex mt-2">
                    <label for="viewers" class="col-form-label">Allowed Viewers</label>
                    <input id="viewers" type="text" class="form-control ms-2 flex-grow-1" :disabled="viewOnly"
                           placeholder="List of Viewers" v-model="presentForm.viewers"
                    >
                </div>
                <div class="text-muted">Space separated list of other people who are allowed to view this form,
                    in case you want to seek assistance or review.
                </div>

                <div class="mt-2">
                    <h4>History</h4>
                    <div v-if="!presentForm || !presentForm.log ||  presentForm.log.length">No history recorded.</div>
                    <table v-else class="table table-dark table-hover table-striped table-responsive small">
                        <thead>
                        <tr>
                            <th scope="col">When</th>
                            <th scope="col">Who</th>
                            <th scope="col">What</th>
                        </tr>
                        </thead>
                        <tr v-for="entry in presentForm?.log">
                            <td>{{ timestampToString(entry.when) }}</td>
                            <td>{{ entry.who }}</td>
                            <td>{{ entry.what }}</td>
                        </tr>
                    </table>
                </div>

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

                <!-- Height -->
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

                <!-- Mass -->
                <div class="d-flex align-items-center mt-2">
                    <div class="sliderLabel">Mass</div>
                    <div class="ms-1 flex-fill">
                        <input type="range" v-model.number="presentForm.mass" :disabled="viewOnly"
                               class="form-control-range w-100" min="-100" max="300"
                        >
                    </div>
                    <div class="ms-1 sliderValue">{{ presentForm.mass }}%</div>
                </div>
                <div class="text-muted">This is a percentage-modifier after size.
                    0 is average for the given size, -50 would be half average
                    weight and 100 would be twice average weight.
                </div>

                <!-- Tags -->
                <div class="d-flex mt-2">
                    <label for="tags" class="col-form-label me-2">Tags</label>
                    <input id="tags" type="text" class="form-control flex-grow-1" :disabled="viewOnly"
                           placeholder="List of tags" v-model="presentForm.tags"
                    >
                </div>
                <div class="text-muted">Space separated list of tags to help categorize the form. A list is available
                    from the muck with 'list tags'.
                </div>

                <!-- Scent -->
                <div class="d-flex mt-2">
                    <label for="tags" class="col-form-label me-2">Scent</label>
                    <input id="tags" type="text" class="form-control flex-grow-1" :disabled="viewOnly"
                           placeholder="List of tags" v-model="presentForm.scent"
                    >
                </div>
                <div class="text-muted">Scent description that will follow phrasing like 'smells like ...'.</div>

                <!-- Heat -->
                <div class="mt-2 form-check">
                    <input class="form-check-input" type="checkbox" value="" id="heat"
                           v-model="presentForm.heat" :disabled="viewOnly"
                    >
                    <label class="form-check-label" for="heat">Heat?</label>
                </div>
                <div class="text-muted">Whether this form goes into heat</div>

                <!-- Say Verbs -->
                <h4 class="mt-2">Say Verbs</h4>
                <div class="row">
                    <div class="mt-2 col-12 col-lg-6">
                        <label id="2nd-person-say-label" for="2nd-person-say" class="form-label">
                            2nd person (say, purr, bark)
                        </label>
                        <input id="2nd-person-say" type="text" class="form-control" :disabled="viewOnly"
                               placeholder="2nd Person" v-model="presentForm.say"
                        >
                    </div>

                    <div class="mt-2 col-12 col-lg-6">
                        <label id="3rd-person-say-label" for="3rd-person-say" class="form-label">
                            3rd person (says, purrs, barks)
                        </label>
                        <input id="3rd-person-say" type="text" class="form-control" :disabled="viewOnly"
                               placeholder="3rd Person" v-model="presentForm.oSay"
                        >
                    </div>
                </div>

                <h4 class="mt-2">Bodypart counts and sizes</h4>
                <div class="text-muted">For all size/length values, 5 is average.</div>
                <div class="text-muted">These are also only the defaults for the form and other circumstances can change
                    these values.
                </div>

                <div class="row">

                    <div class="mt-2 col-12 col-lg-6">
                        <label for="breast-count" class="form-label">Breast Count</label>
                        <input id="breast-count" type="number" class="form-control" :disabled="viewOnly"
                               placeholder="#" v-model="presentForm.breastCount"
                        >
                    </div>

                    <div class="mt-2 col-12 col-lg-6">
                        <label for="breast-size" class="form-label">Breast Size</label>
                        <input id="breast-size" type="number" class="form-control" :disabled="viewOnly"
                               placeholder="#" v-model="presentForm.breastSize"
                        >
                    </div>

                    <div class="mt-2 col-12 col-lg-6">
                        <label for="cunt-count" class="form-label">Cunt Count</label>
                        <input id="cunt-count" type="number" class="form-control" :disabled="viewOnly"
                               placeholder="#" v-model="presentForm.cuntCount"
                        >
                    </div>

                    <div class="mt-2 col-12 col-lg-6">
                        <label for="cunt-size" class="form-label">Cunt Depth</label>
                        <input id="cunt-size" type="number" class="form-control" :disabled="viewOnly"
                               placeholder="#" v-model="presentForm.cuntSize"
                        >
                    </div>

                    <div class="mt-2 col-12 col-lg-6">
                        <label for="clit-count" class="form-label">Clit Count</label>
                        <input id="clit-count" type="number" class="form-control" :disabled="viewOnly"
                               placeholder="#" v-model="presentForm.clitCount"
                        >
                    </div>

                    <div class="mt-2 col-12 col-lg-6">
                        <label for="clit-size" class="form-label">Clit Length</label>
                        <input id="clit-size" type="number" class="form-control" :disabled="viewOnly"
                               placeholder="#" v-model="presentForm.clitSize"
                        >
                    </div>

                    <div class="mt-2 col-12 col-lg-6">
                        <label for="cock-count" class="form-label">Cock Count</label>
                        <input id="cock-count" type="number" class="form-control" :disabled="viewOnly"
                               placeholder="#" v-model="presentForm.cockCount"
                        >
                    </div>

                    <div class="mt-2 col-12 col-lg-6">
                        <label for="cock-size" class="form-label">Cock Length</label>
                        <input id="cock-size" type="number" class="form-control" :disabled="viewOnly"
                               placeholder="#" v-model="presentForm.cockSize"
                        >
                    </div>


                    <div class="mt-2 col-12 col-lg-6">
                        <label for="ball-count" class="form-label">Ball Count</label>
                        <input id="ball-count" type="number" class="form-control" :disabled="viewOnly"
                               placeholder="#" v-model="presentForm.ballCount"
                        >
                    </div>

                    <div class="mt-2 col-12 col-lg-6">
                        <label for="ball-size" class="form-label">Ball Size</label>
                        <input id="ball-size" type="number" class="form-control" :disabled="viewOnly"
                               placeholder="#" v-model="presentForm.ballSize"
                        >
                    </div>


                </div>

            </div>

            <!-- Descriptions & Transformations -->
            <div class="tab-pane show" id="nav-bodyparts" role="tabpanel" aria-labelledby="nav-bodyparts-tab">
                <h5>Common notes:</h5>
                <div>Transformation messages are for the player and should be 2nd person (You, your, etc).</div>
                <div>Descriptions are for anyone looking and should be 3rd person, using the pronouns 'they/their/them'
                    - the appropriate pronouns will be substituted in. They should also start with a lower case letter
                    since they're combined elsewhere.
                </div>
                <div>Kemonomimi descriptions are an optional variant of the description for supporting that mode. It
                    uses the same rules as descriptions.
                </div>
                <div>Flags are a space separated list of attributes the part has.
                    The list is available with 'list flags' on the muck.
                </div>

                <!-- Skin -->
                <h4 class="mt-2">Skin</h4>
                <div class="mt-2">
                    <label for="skin-flags" class="form-label">Flags</label>
                    <input id="skin-flags" type="text" class="form-control" :disabled="viewOnly"
                           placeholder="#" v-model="presentForm.skin.flags"
                    >
                </div>
                <div class="mt-2">
                    <label for="skin-transformation" class="form-label">Transformation</label>
                    <input id="skin-transformation" type="text" class="form-control" :disabled="viewOnly"
                           placeholder="#" v-model="presentForm.skin.transformation"
                    >
                </div>
                <div class="mt-2">
                    <label for="skin-short-description" class="form-label">Short Description</label>
                    <input id="skin-short-description" type="text" class="form-control" :disabled="viewOnly"
                           placeholder="#" v-model="presentForm.skin.shortDescription"
                    >
                    <div class="text-muted">This should be 1 - 4 adjectives and is used during other messages.</div>
                </div>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="skin-description" label="Description"
                                      :prop-value="presentForm.skin.description"
                ></FormEditorCodeEditor>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="skin-kemo-description" label="Kemo Description"
                                      :prop-value="presentForm.skin.kemoDescription"
                ></FormEditorCodeEditor>

                <!-- Head -->
                <h4 class="mt-2">Head</h4>
                <div class="mt-2">
                    <label for="head-flags" class="form-label">Flags</label>
                    <input id="head-flags" type="text" class="form-control" :disabled="viewOnly"
                           placeholder="#" v-model="presentForm.head.flags"
                    >
                </div>
                <div class="mt-2">
                    <label for="head-transformation" class="form-label">Transformation</label>
                    <input id="head-transformation" type="text" class="form-control" :disabled="viewOnly"
                           placeholder="#" v-model="presentForm.head.transformation"
                    >
                </div>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="head-description" label="Description"
                                      :prop-value="presentForm.head.description"
                ></FormEditorCodeEditor>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="head-kemo-description" label="Kemo Description"
                                      :prop-value="presentForm.head.kemoDescription"
                ></FormEditorCodeEditor>

                <!-- Torso -->
                <h4 class="mt-2">Torso</h4>
                <div class="mt-2">
                    <label for="torso-flags" class="form-label">Flags</label>
                    <input id="torso-flags" type="text" class="form-control" :disabled="viewOnly"
                           placeholder="#" v-model="presentForm.torso.flags"
                    >
                </div>
                <div class="mt-2">
                    <label for="torso-transformation" class="form-label">Transformation</label>
                    <input id="torso-transformation" type="text" class="form-control" :disabled="viewOnly"
                           placeholder="#" v-model="presentForm.torso.transformation"
                    >
                </div>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="torso-description" label="Description"
                                      :prop-value="presentForm.torso.description"
                ></FormEditorCodeEditor>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="torso-kemo-description" label="Kemo Description"
                                      :prop-value="presentForm.torso.kemoDescription"
                ></FormEditorCodeEditor>

                <!-- Arms -->
                <h4 class="mt-2">Arms</h4>
                <div class="mt-2">
                    <label for="arms-flags" class="form-label">Flags</label>
                    <input id="arms-flags" type="text" class="form-control" :disabled="viewOnly"
                           placeholder="#" v-model="presentForm.arms.flags"
                    >
                </div>
                <div class="mt-2">
                    <label for="arms-transformation" class="form-label">Transformation</label>
                    <input id="arms-transformation" type="text" class="form-control" :disabled="viewOnly"
                           placeholder="#" v-model="presentForm.arms.transformation"
                    >
                </div>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="arms-description" label="Description"
                                      :prop-value="presentForm.arms.description"
                ></FormEditorCodeEditor>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="head-arms-description" label="Kemo Description"
                                      :prop-value="presentForm.arms.kemoDescription"
                ></FormEditorCodeEditor>

                <!-- Legs -->
                <h4 class="mt-2">Legs</h4>
                <div class="mt-2">
                    <label for="legs-flags" class="form-label">Flags</label>
                    <input id="legs-flags" type="text" class="form-control" :disabled="viewOnly"
                           placeholder="#" v-model="presentForm.legs.flags"
                    >
                </div>
                <div class="mt-2">
                    <label for="legs-transformation" class="form-label">Transformation</label>
                    <input id="legs-transformation" type="text" class="form-control" :disabled="viewOnly"
                           placeholder="#" v-model="presentForm.legs.transformation"
                    >
                </div>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="legs-description" label="Description"
                                      :prop-value="presentForm.legs.description"
                ></FormEditorCodeEditor>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="legs-kemo-description" label="Kemo Description"
                                      :prop-value="presentForm.legs.kemoDescription"
                ></FormEditorCodeEditor>

                <!-- Groin -->
                <h4 class="mt-2">Groin</h4>
                <div class="mt-2">
                    <label for="groin-flags" class="form-label">Flags</label>
                    <input id="groin-flags" type="text" class="form-control" :disabled="viewOnly"
                           placeholder="#" v-model="presentForm.groin.flags"
                    >
                </div>
                <div class="mt-2">
                    <label for="groin-transformation" class="form-label">Transformation</label>
                    <input id="groin-transformation" type="text" class="form-control" :disabled="viewOnly"
                           placeholder="#" v-model="presentForm.groin.transformation"
                    >
                </div>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="cock-description" label="Cock Description"
                                      :prop-value="presentForm.groin.cockDescription"
                ></FormEditorCodeEditor>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="cunt-description" label="Cunt Description"
                                      :prop-value="presentForm.groin.cuntDescription"
                ></FormEditorCodeEditor>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="clit-description" label="Clit Description"
                                      :prop-value="presentForm.groin.clitDescription"
                ></FormEditorCodeEditor>

                <!-- Ass or Tail -->
                <h4 class="mt-2">Ass or Tail</h4>
                <div class="mt-2">
                    <label for="ass-flags" class="form-label">Flags</label>
                    <input id="ass-flags" type="text" class="form-control" :disabled="viewOnly"
                           placeholder="#" v-model="presentForm.ass.flags"
                    >
                </div>
                <div class="mt-2">
                    <label for="ass-transformation" class="form-label">Transformation</label>
                    <input id="ass-transformation" type="text" class="form-control" :disabled="viewOnly"
                           placeholder="#" v-model="presentForm.ass.transformation"
                    >
                </div>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="ass-description" label="Description"
                                      :prop-value="presentForm.ass.description"
                ></FormEditorCodeEditor>

            </div>

            <!-- Victory & Defeat Messages -->
            <div class="tab-pane show" id="nav-victorydefeat" role="tabpanel" aria-labelledby="nav-victorydefeat-tab">

                <div>TODO: Preview configuration.</div>

                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly" :multiline="true"
                                      prop-name="defeat" label="Monster defeats Player"
                                      :prop-value="presentForm.defeat.join('\n')"
                ></FormEditorCodeEditor>
                <div class="text-muted">
                    2nd person from the defeated player's perspective,
                    e.g. 'You are defeated by a mutant!'
                </div>
                <div>TODO: Preview</div>

                <hr/>

                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly" :multiline="true"
                                      prop-name="victory" label="Player defeats Monster"
                                      :prop-value="presentForm.victory.join('\n')"
                ></FormEditorCodeEditor>
                <div class="text-muted">
                    2nd person from the victorious player's perspective,
                    e.g. 'You beat a mutant, using your mutant ways!'
                </div>
                <div>TODO: Preview</div>

                <hr/>

                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly" :multiline="true"
                                      prop-name="ovictory" label="Player seen defeating Monster"
                                      :prop-value="presentForm.oVictory.join('\n')"
                ></FormEditorCodeEditor>
                <div class="text-muted">
                    3rd person from an observer's perspective,
                    e.g. 'Bob defeats a mutant in a weird mutant way!'
                </div>
                <div>TODO: Preview</div>

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

        <label for="newFormName" class="form-label">Enter the name of the form (avoid gender specific names):</label>
        <input type="text" class="form-control" id="newFormName" v-model="newFormName">

        <p class="mt-4">WARNING: Do not enter pokemon, disney characters, or any other copyrighted material.
            Fictional races made in the last century ARE copyrighted, don't use them.</p>
        <p>Any content entered becomes property of the game, and its owning company(Silver Games LLC).
            Please review the Terms of Service.</p>
        TODO: Terms of Service link
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
