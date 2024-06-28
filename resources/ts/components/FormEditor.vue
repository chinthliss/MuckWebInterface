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
    owner?: number
    _: {
        approved: boolean
        review: boolean
        revise: boolean
        createdAt?: number // Timestamp
        editedAt?: number // Timestamp
        log?: FormLog[]
        notes?: string[]
    }

    height: number
    mass: number
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
    heat: boolean
    template: boolean // This is set but actually computed and auto-controlled muck side.
    viewers: string
    sexless: boolean

    noExtract: boolean
    noReward: boolean
    noFunnel: boolean
    noZap: boolean
    noMastering: boolean
    noNative: boolean
    bypassImmune: boolean
    private: boolean
    hidden: boolean
    special?: string
    powerset?: string
    placement?: string

    victory: string[]
    oVictory: string[]
    defeat: string[]

    skin: {
        flags: string
        transformation: string
        shortDescription: string
        description: string
        kemoDescription: string
        template: boolean
    }
    head: {
        flags: string
        transformation: string
        description: string
        kemoDescription: string
        template: boolean
    }
    torso: {
        flags: string
        transformation: string
        description: string
        kemoDescription: string
        template: boolean
    }
    arms: {
        flags: string
        transformation: string
        description: string
        kemoDescription: string
        template: boolean
    }
    legs: {
        flags: string
        transformation: string
        description: string
        kemoDescription: string
        template: boolean
    }
    groin: {
        flags: string
        transformation: string
        cockDescription: string
        cuntDescription: string
        clitDescription: string
        template: boolean
    }
    ass: {
        flags: string
        transformation: string
        description: string
        template: boolean
    }
}

const presentFormId: Ref<string | null> = ref(null);
const presentForm: Ref<Form | null> = ref(null);
const viewOnly: Ref<boolean> = ref(false);
const staff: Ref<boolean> = ref(false);
const formSelector: Ref<InstanceType<typeof FormEditorFormSelection> | null> = ref(null);

const confirmDeleteModal: Ref<InstanceType<typeof ModalConfirmation> | null> = ref(null);
const formToDelete: Ref<string> = ref('');

const confirmSubmitModal: Ref<InstanceType<typeof ModalConfirmation> | null> = ref(null);

const createFormModal: Ref<InstanceType<typeof ModalConfirmation> | null> = ref(null);
const newFormName: Ref<string> = ref('');

const error: Ref<string> = ref('');
const errorModal: Ref<InstanceType<typeof ModalMessage> | null> = ref(null);

// Notes gets a special separate entry because the muck treats it as an array and html as a \n separated string
const notes: Ref<string> = ref('');

const channel = mwiWebsocket.channel('contribute');

let pendingSaves: { [id: string]: string } = {};
let pendingSaveId: number | null = null;

const oneWordStatus = computed((): string => {
    if (!presentForm.value) return '';
    if (presentForm.value._.revise) return 'Revision Needed';
    if (presentForm.value._.review) return 'Awaiting Review';
    return presentForm.value._.approved ? 'Finished' : 'Under Construction';
});

const statusDescription = computed((): string => {
    if (!presentForm.value) return '';
    if (presentForm.value._.revise) return 'Staff have reviewed the form and some additional work is needed. After reviewing staff feedback you can submit the form again.';
    if (presentForm.value._.review) return 'The form is awaiting staff review. You can view it but not make any changes.';
    if (presentForm.value._.approved) return 'This form has been finalized. You can view it but not make any changes.';
    return 'This is a new or unfinished form. After you have completed enough of the required content you can submit the form for review.';
});

const hasTemplatedParts = computed((): boolean => {
    if (!presentForm.value) return false;
    return presentForm.value.skin.template ||
        presentForm.value.head.template ||
        presentForm.value.torso.template ||
        presentForm.value.arms.template ||
        presentForm.value.legs.template ||
        presentForm.value.groin.template ||
        presentForm.value.ass.template;
});

const unloadForm = () => {
    presentFormId.value = null;
    presentForm.value = null;
    notes.value = '';
}

const loadForm = (selected: string) => {
    unloadForm();
    presentFormId.value = selected;
    channel.send('getForm', presentFormId.value);
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

const saveValues = () => {
    pendingSaveId = null;
    for (const id in pendingSaves) {
        const value = pendingSaves[id];
        delete pendingSaves[id];
        channel.send('updateForm', {
            form: presentFormId.value,
            propName: id,
            propValue: value
        });
    }
}

const queueSave = (propName: string, propValue: string) => {
    pendingSaves[propName] = propValue;
    if (!pendingSaveId) pendingSaveId = setTimeout(saveValues, 1000);
}

const queueSaveFromElement = (e: InputEvent) => {
    const element = e.target as HTMLInputElement;
    if (!element?.id) {
        console.log("Couldn't queue save value as the element triggering it has no id: ", e);
        return;
    }
    // Because checkboxes are weird, we can't just use their value..
    if (element.type !== 'checkbox')
        queueSave(element.id, element.value);
    else
        queueSave(element.id, element.checked ? '1' : '')
}

// The code editors don't return an InputEvent unfortunately, so need their own mechanism
const queueSaveFromEditor = (e: { id: string, value; string }) => {
    if (!e?.id) {
        console.log("Couldn't queue save editor value as the element triggering it has no id: ", e);
        return;
    }
    queueSave(e.id, e.value);
}

type GetFormResponse = {
    error?: string
    form?: Form
    canEdit?: boolean
    staff?: boolean
}

channel.on('form', (response: GetFormResponse) => {
    if (response.error) {
        error.value = response.error;
        if (errorModal.value) errorModal.value.show();
        return;
    }
    viewOnly.value = !(response.canEdit == true);
    staff.value = response.staff ?? false;
    const form = response.form;
    if (!form) {
        presentForm.value = null;
    } else {
        // Handle some fixes and translations
        if (form._.notes) notes.value = form._.notes.join('\n');
        if (!form.oVictory) form.oVictory = [];
        if (!form.victory) form.victory = [];
        if (!form.defeat) form.defeat = [];
        presentForm.value = form;
    }
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
});

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
});

channel.on('updateFormFailed', (response) => {
    error.value = `Update failed or rejected by the muck when setting '${response.propName}' to '${response.propValue}.'`;
    error.value += "\n\nThis means your changes weren't saved, so you may wish to keep a record elsewhere.";
    if (response.error) error.value += "\n\nActual error returned: " + response.error;
    if (errorModal.value) errorModal.value.show();
});

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

        <!-- Read-Only mode warning -->
        <div v-if="viewOnly" class="mt-2 p-2 rounded text-bg-warning">
            View only mode - you are unable to make edits to this form.
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

                <div class="mt-2">Created: {{ timestampToString(presentForm._.createdAt) }}</div>

                <div class="mt-2">Last edited: {{ timestampToString(presentForm._.editedAt) }}</div>

                <!-- Allowed Viewers -->
                <div class="d-flex mt-2">
                    <label for="viewers" class="col-form-label">Allowed Viewers</label>
                    <input id="viewers" type="text" class="form-control ms-2 flex-grow-1" :disabled="viewOnly"
                           placeholder="List of Viewers" v-model="presentForm.viewers" @input="queueSaveFromElement"
                    >
                </div>
                <div class="text-muted">Space separated list of other people who are allowed to view this form,
                    in case you want to seek assistance or review.
                </div>

                <!-- Template status -->
                <div v-if="hasTemplatedParts" class="mt-2 p-2 rounded text-bg-info">
                    This form has at least one part with template flagged, so will be considered as a template form.
                </div>

                <!-- Notes -->
                <div class="mt-2">
                    <h4>Notes</h4>
                    <label for="notes" class="form-label visually-hidden">Notes</label>
                    <textarea class="form-control" id="notes" rows="3"
                              v-model="notes" @input="queueSaveFromElement" :disabled="viewOnly"
                    ></textarea>
                    <div class="text-muted">These notes can be used to record things of interest, such as:
                        <ul>
                            <li>Overall vision for the form.</li>
                            <li>Power suggestions for each bodypart.</li>
                            <li>Placement suggestions.</li>
                        </ul>
                    </div>

                </div>

                <!-- History -->
                <div class="mt-2">
                    <h4>History</h4>
                    <div v-if="!presentForm._.log || presentForm._.log.length">No history recorded.</div>
                    <table v-else class="table table-dark table-hover table-striped table-responsive small">
                        <thead>
                        <tr>
                            <th scope="col">When</th>
                            <th scope="col">Who</th>
                            <th scope="col">What</th>
                        </tr>
                        </thead>
                        <tr v-for="entry in presentForm?._.log">
                            <td>{{ timestampToString(entry.when) }}</td>
                            <td>{{ entry.who }}</td>
                            <td>{{ entry.what }}</td>
                        </tr>
                    </table>
                </div>

                <div v-if="staff" class="mt-2">
                    <h4>Staff Controls</h4>

                    <!-- No Reward -->
                    <div class="mt-2 form-check">
                        <input class="form-check-input" type="checkbox" id="noreward"
                               v-model="presentForm.noReward" :disabled="viewOnly" @input="queueSaveFromElement"
                        >
                        <label class="form-check-label" for="noreward">No Reward</label>
                    </div>
                    <div class="text-muted">Prevents rewards from containing nanites of this form.</div>

                    <!-- No Extract -->
                    <div class="mt-2 form-check">
                        <input class="form-check-input" type="checkbox" id="noextract"
                               v-model="presentForm.noExtract" :disabled="viewOnly" @input="queueSaveFromElement"
                        >
                        <label class="form-check-label" for="noextract">No Extract</label>
                    </div>
                    <div class="text-muted">Prevents nanites of this form from being extracted from another source.
                    </div>

                    <!-- No Funnel -->
                    <div class="mt-2 form-check">
                        <input class="form-check-input" type="checkbox" id="nofunnel"
                               v-model="presentForm.noFunnel" :disabled="viewOnly" @input="queueSaveFromElement"
                        >
                        <label class="form-check-label" for="nofunnel">No Funnel</label>
                    </div>
                    <div class="text-muted">
                        Prevents this form from being pushed onto somebody else by a funnel.
                    </div>

                    <!-- No Zap -->
                    <div class="mt-2 form-check">
                        <input class="form-check-input" type="checkbox" id="nozap"
                               v-model="presentForm.noZap" :disabled="viewOnly" @input="queueSaveFromElement"
                        >
                        <label class="form-check-label" for="nozap">No Zap</label>
                    </div>
                    <div class="text-muted">Prevents this form from being pushed to somebody else by coyotes.</div>

                    <!-- No Mastering -->
                    <div class="mt-2 form-check">
                        <input class="form-check-input" type="checkbox" id="nomastering"
                               v-model="presentForm.noMastering" :disabled="viewOnly" @input="queueSaveFromElement"
                        >
                        <label class="form-check-label" for="nomastering">No Mastering</label>
                    </div>
                    <div class="text-muted">Prevents this form from being mastered.</div>

                    <!-- No Native -->
                    <div class="mt-2 form-check">
                        <input class="form-check-input" type="checkbox" id="nonative"
                               v-model="presentForm.noNative" :disabled="viewOnly" @input="queueSaveFromElement"
                        >
                        <label class="form-check-label" for="nonative">No Native</label>
                    </div>
                    <div class="text-muted">Prevents this form from being part of someone's native build</div>

                    <!-- Bypass Immune -->
                    <div class="mt-2 form-check">
                        <input class="form-check-input" type="checkbox" id="bypassimmune"
                               v-model="presentForm.bypassImmune" :disabled="viewOnly" @input="queueSaveFromElement"
                        >
                        <label class="form-check-label" for="bypassimmune">Bypass Immunity</label>
                    </div>
                    <div class="text-muted">Allows this form to ignore immunities</div>

                    <!-- Private -->
                    <div class="mt-2 form-check">
                        <input class="form-check-input" type="checkbox" id="private"
                               v-model="presentForm.private" :disabled="viewOnly" @input="queueSaveFromElement"
                        >
                        <label class="form-check-label" for="private">Private</label>
                    </div>
                    <div class="text-muted">Mark the form as private. NOTE: This is for future use,
                        for now private forms MUST also have the word 'private' in the special setting.
                    </div>

                    <!-- Hidden -->
                    <div class="mt-2 form-check">
                        <input class="form-check-input" type="checkbox" id="hidden"
                               v-model="presentForm.hidden" :disabled="viewOnly" @input="queueSaveFromElement"
                        >
                        <label class="form-check-label" for="hidden">Hidden</label>
                    </div>
                    <div class="text-muted">Hidden forms won't show in form listings.</div>

                    <!-- Special -->
                    <div class="d-flex mt-2">
                        <label for="special" class="col-form-label">Special</label>
                        <input id="special" type="text" class="form-control ms-2 flex-grow-1" :disabled="viewOnly"
                               placeholder="Special Notes" v-model="presentForm.special" @input="queueSaveFromElement"
                        >
                    </div>
                    <div class="text-muted">Any special flags, such as Private, Holiday or Dedication.</div>

                    <!-- Powerset -->
                    <div class="d-flex mt-2">
                        <label for="powerset" class="col-form-label">Powerset</label>
                        <input id="powerset" type="text" class="form-control ms-2 flex-grow-1" :disabled="viewOnly"
                               v-model="presentForm.powerset" @input="queueSaveFromElement"
                        >
                    </div>
                    <div class="text-muted">Any outstanding power tasks.
                        NOTE: If this isn't blank a form is considered unreleased and won't be visible.
                    </div>

                    <!-- Placement -->
                    <div class="d-flex mt-2">
                        <label for="placement" class="col-form-label">Placement</label>
                        <input id="placement" type="text" class="form-control ms-2 flex-grow-1" :disabled="viewOnly"
                               v-model="presentForm.placement" @input="queueSaveFromElement"
                        >
                    </div>
                    <div class="text-muted">Any outstanding placement tasks.
                        NOTE: If this isn't blank a form is considered unreleased and won't be visible.
                    </div>


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
                        <input id="height" type="range" v-model.number="presentForm.height" :disabled="viewOnly"
                               class="form-control-range w-100" min="1" max="30" @input="queueSaveFromElement"
                        >
                    </div>
                    <div class="ms-1 sliderValue">{{ presentForm.height }}</div>
                </div>
                <div class="text-muted">5 is average human height.</div>

                <!-- Mass -->
                <div class="d-flex align-items-center mt-2">
                    <div class="sliderLabel">Mass</div>
                    <div class="ms-1 flex-fill">
                        <input id="mass" type="range" v-model.number="presentForm.mass" :disabled="viewOnly"
                               class="form-control-range w-100" min="-100" max="300" @input="queueSaveFromElement"
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
                           placeholder="List of tags" v-model="presentForm.tags" @input="queueSaveFromElement"
                    >
                </div>
                <div class="text-muted">Space separated list of tags to help categorize the form. A list is available
                    from the muck with 'list tags'.
                </div>

                <!-- Scent -->
                <div class="d-flex mt-2">
                    <label for="tags" class="col-form-label me-2">Scent</label>
                    <input id="tags" type="text" class="form-control flex-grow-1" :disabled="viewOnly"
                           placeholder="List of tags" v-model="presentForm.scent" @input="queueSaveFromElement"
                    >
                </div>
                <div class="text-muted">Scent description that will follow phrasing like 'smells like ...'.</div>

                <!-- Heat -->
                <div class="mt-2 form-check">
                    <input class="form-check-input" type="checkbox" id="heat"
                           v-model="presentForm.heat" :disabled="viewOnly" @input="queueSaveFromElement"
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
                               placeholder="2nd Person" v-model="presentForm.say" @input="queueSaveFromElement"
                        >
                    </div>

                    <div class="mt-2 col-12 col-lg-6">
                        <label id="3rd-person-say-label" for="3rd-person-say" class="form-label">
                            3rd person (says, purrs, barks)
                        </label>
                        <input id="3rd-person-say" type="text" class="form-control" :disabled="viewOnly"
                               placeholder="3rd Person" v-model="presentForm.oSay" @input="queueSaveFromElement"
                        >
                    </div>
                </div>

                <!-- Bodypart counts and sizes (including sexless) -->
                <h4 class="mt-2">Bodypart counts and sizes</h4>

                <div class="text-muted">Values specified here are changes that the form will try to apply,
                    e.g. if something is set to 0 the form will try to remove it on infection.
                    <br/>These changes are not guaranteed and can be resisted/blocked.
                </div>

                <!-- Heat -->
                <div class="mt-2 form-check">
                    <input class="form-check-input" type="checkbox" id="sexless"
                           v-model="presentForm.sexless" :disabled="viewOnly" @input="queueSaveFromElement"
                    >
                    <label class="form-check-label" for="heat">Sexless?</label>
                </div>
                <div class="text-muted">If this is set, the form will not attempt to change any sexual attributes.</div>

                <div class="row" v-if="!presentForm.sexless">

                    <div class="mt-2 col-12 col-lg-6">
                        <label for="breast-count" class="form-label">Breast Count</label>
                        <input id="breast-count" type="number" class="form-control" :disabled="viewOnly"
                               placeholder="#" v-model="presentForm.breastCount" @input="queueSaveFromElement"
                        >
                    </div>

                    <div class="mt-2 col-12 col-lg-6">
                        <label for="breast-size" class="form-label">Breast Size (5 is average)</label>
                        <input id="breast-size" type="number" class="form-control" :disabled="viewOnly"
                               placeholder="#" v-model="presentForm.breastSize" @input="queueSaveFromElement"
                        >
                    </div>

                    <div class="mt-2 col-12 col-lg-6">
                        <label for="cunt-count" class="form-label">Cunt Count</label>
                        <input id="cunt-count" type="number" class="form-control" :disabled="viewOnly"
                               placeholder="#" v-model="presentForm.cuntCount" @input="queueSaveFromElement"
                        >
                    </div>

                    <div class="mt-2 col-12 col-lg-6">
                        <label for="cunt-size" class="form-label">Cunt Depth (5 is average)</label>
                        <input id="cunt-size" type="number" class="form-control" :disabled="viewOnly"
                               placeholder="#" v-model="presentForm.cuntSize" @input="queueSaveFromElement"
                        >
                    </div>

                    <div class="mt-2 col-12 col-lg-6">
                        <label for="clit-count" class="form-label">Clit Count</label>
                        <input id="clit-count" type="number" class="form-control" :disabled="viewOnly"
                               placeholder="#" v-model="presentForm.clitCount" @input="queueSaveFromElement"
                        >
                    </div>

                    <div class="mt-2 col-12 col-lg-6">
                        <label for="clit-size" class="form-label">Clit Length (5 is average)</label>
                        <input id="clit-size" type="number" class="form-control" :disabled="viewOnly"
                               placeholder="#" v-model="presentForm.clitSize" @input="queueSaveFromElement"
                        >
                    </div>

                    <div class="mt-2 col-12 col-lg-6">
                        <label for="cock-count" class="form-label">Cock Count</label>
                        <input id="cock-count" type="number" class="form-control" :disabled="viewOnly"
                               placeholder="#" v-model="presentForm.cockCount" @input="queueSaveFromElement"
                        >
                    </div>

                    <div class="mt-2 col-12 col-lg-6">
                        <label for="cock-size" class="form-label">Cock Length (5 is average)</label>
                        <input id="cock-size" type="number" class="form-control" :disabled="viewOnly"
                               placeholder="#" v-model="presentForm.cockSize" @input="queueSaveFromElement"
                        >
                    </div>


                    <div class="mt-2 col-12 col-lg-6">
                        <label for="ball-count" class="form-label">Ball Count</label>
                        <input id="ball-count" type="number" class="form-control" :disabled="viewOnly"
                               placeholder="#" v-model="presentForm.ballCount" @input="queueSaveFromElement"
                        >
                    </div>

                    <div class="mt-2 col-12 col-lg-6">
                        <label for="ball-size" class="form-label">Ball Size (5 is average)</label>
                        <input id="ball-size" type="number" class="form-control" :disabled="viewOnly"
                               placeholder="#" v-model="presentForm.ballSize" @input="queueSaveFromElement"
                        >
                    </div>


                </div>

            </div>

            <!-- Descriptions & Transformations -->
            <div class="tab-pane show" id="nav-bodyparts" role="tabpanel" aria-labelledby="nav-bodyparts-tab">
                <h5>Notes:</h5>
                <ul>
                    <li>Transformation messages are for the player and should be 2nd person (You, your, etc).</li>
                    <li>Descriptions are for anyone looking and should be 3rd person, using the pronouns
                        'they/their/them'
                        - the appropriate pronouns will be substituted in. They should also start with a lower case
                        letter
                        since they're combined elsewhere.
                    </li>
                    <li>Kemonomimi descriptions are an optional variant of the description for supporting that mode. It
                        uses the same rules as descriptions.
                    </li>
                    <li>Flags are a space separated list of attributes the part has.
                        The list is available with 'list flags' on the muck.
                    </li>
                    <li>If a part is set as template, then it will attempt to supplement
                        the present part rather than replacing it. See '+help String Parsing/template Forms'
                    </li>
                </ul>

                <!-- Skin -->
                <h4 class="mt-2">Skin</h4>
                <div class="mt-2 form-check">
                    <input class="form-check-input" type="checkbox" id="skin-template"
                           v-model="presentForm.skin.template" :disabled="viewOnly" @input="queueSaveFromElement"
                    >
                    <label class="form-check-label" for="skin-template">Template?</label>
                </div>
                <div class="mt-2">
                    <label for="skin-flags" class="form-label">Flags</label>
                    <input id="skin-flags" type="text" class="form-control" :disabled="viewOnly"
                           placeholder="Enter a space separated list" v-model="presentForm.skin.flags"
                           @input="queueSaveFromElement"
                    >
                </div>
                <div class="mt-2">
                    <label for="skin-short-description" class="form-label">Short Description</label>
                    <input id="skin-short-description" type="text" class="form-control" :disabled="viewOnly"
                           placeholder="Enter a phrase for an adjective (e.g. dry and scaly) "
                           v-model="presentForm.skin.shortDescription" @input="queueSaveFromElement"
                    >
                    <div class="text-muted">This should be 1 - 4 adjectives and is used during other messages.</div>
                </div>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="skin-transformation" label="Transformation"
                                      :prop-value="presentForm.skin.transformation" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="skin-description" label="Description"
                                      :prop-value="presentForm.skin.description" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="skin-kemo-description" label="Kemo Description"
                                      :prop-value="presentForm.skin.kemoDescription" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>

                <!-- Head -->
                <h4 class="mt-2">Head</h4>
                <div class="mt-2 form-check">
                    <input class="form-check-input" type="checkbox" id="head-template"
                           v-model="presentForm.head.template" :disabled="viewOnly" @input="queueSaveFromElement"
                    >
                    <label class="form-check-label" for="head-template">Template?</label>
                </div>
                <div class="mt-2">
                    <label for="head-flags" class="form-label">Flags</label>
                    <input id="head-flags" type="text" class="form-control" :disabled="viewOnly"
                           @input="queueSaveFromElement"
                           placeholder="Enter a space separated list" v-model="presentForm.head.flags"
                    >
                </div>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="head-transformation" label="Transformation"
                                      :prop-value="presentForm.head.transformation" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="head-description" label="Description"
                                      :prop-value="presentForm.head.description" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="head-kemo-description" label="Kemo Description"
                                      :prop-value="presentForm.head.kemoDescription" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>

                <!-- Torso -->
                <h4 class="mt-2">Torso</h4>
                <div class="mt-2 form-check">
                    <input class="form-check-input" type="checkbox" id="torso-template" @input="queueSaveFromElement"
                           v-model="presentForm.torso.template" :disabled="viewOnly"
                    >
                    <label class="form-check-label" for="torso-template">Template?</label>
                </div>
                <div class="mt-2">
                    <label for="torso-flags" class="form-label">Flags</label>
                    <input id="torso-flags" type="text" class="form-control" :disabled="viewOnly"
                           @input="queueSaveFromElement"
                           placeholder="Enter a space separated list" v-model="presentForm.torso.flags"
                    >
                </div>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="torso-transformation" label="Transformation"
                                      :prop-value="presentForm.torso.transformation" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="torso-description" label="Description"
                                      :prop-value="presentForm.torso.description" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="torso-kemo-description" label="Kemo Description"
                                      :prop-value="presentForm.torso.kemoDescription" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>

                <!-- Arms -->
                <h4 class="mt-2">Arms</h4>
                <div class="mt-2 form-check">
                    <input class="form-check-input" type="checkbox" id="arms-template" @input="queueSaveFromElement"
                           v-model="presentForm.arms.template" :disabled="viewOnly"
                    >
                    <label class="form-check-label" for="arms-template">Template?</label>
                </div>
                <div class="mt-2">
                    <label for="arms-flags" class="form-label">Flags</label>
                    <input id="arms-flags" type="text" class="form-control" :disabled="viewOnly"
                           @input="queueSaveFromElement"
                           placeholder="Enter a space separated list" v-model="presentForm.arms.flags"
                    >
                </div>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="arms-transformation" label="Transformation"
                                      :prop-value="presentForm.arms.transformation" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="arms-description" label="Description"
                                      :prop-value="presentForm.arms.description" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="head-arms-description" label="Kemo Description"
                                      :prop-value="presentForm.arms.kemoDescription" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>

                <!-- Legs -->
                <h4 class="mt-2">Legs</h4>
                <div class="mt-2 form-check">
                    <input class="form-check-input" type="checkbox" id="legs-template" @input="queueSaveFromElement"
                           v-model="presentForm.legs.template" :disabled="viewOnly"
                    >
                    <label class="form-check-label" for="legs-template">Template?</label>
                </div>
                <div class="mt-2">
                    <label for="legs-flags" class="form-label">Flags</label>
                    <input id="legs-flags" type="text" class="form-control" :disabled="viewOnly"
                           @input="queueSaveFromElement"
                           placeholder="Enter a space separated list" v-model="presentForm.legs.flags"
                    >
                </div>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="legs-transformation" label="Transformation"
                                      :prop-value="presentForm.legs.transformation" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="legs-description" label="Description"
                                      :prop-value="presentForm.legs.description" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="legs-kemo-description" label="Kemo Description"
                                      :prop-value="presentForm.legs.kemoDescription" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>

                <!-- Groin -->
                <h4 class="mt-2">Groin</h4>
                <div class="mt-2 form-check">
                    <input class="form-check-input" type="checkbox" id="groin-template" @input="queueSaveFromElement"
                           v-model="presentForm.groin.template" :disabled="viewOnly"
                    >
                    <label class="form-check-label" for="groin-template">Template?</label>
                </div>
                <div class="mt-2">
                    <label for="groin-flags" class="form-label">Flags</label>
                    <input id="groin-flags" type="text" class="form-control" :disabled="viewOnly"
                           @input="queueSaveFromElement"
                           placeholder="Enter a space separated list" v-model="presentForm.groin.flags"
                    >
                </div>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="groin-transformation" label="Transformation"
                                      :prop-value="presentForm.groin.transformation" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="cock-description" label="Cock Description"
                                      :prop-value="presentForm.groin.cockDescription" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="cunt-description" label="Cunt Description"
                                      :prop-value="presentForm.groin.cuntDescription" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="clit-description" label="Clit Description"
                                      :prop-value="presentForm.groin.clitDescription" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>

                <!-- Ass or Tail -->
                <h4 class="mt-2">Ass or Tail</h4>
                <div class="mt-2 form-check">
                    <input class="form-check-input" type="checkbox" id="ass-template"
                           v-model="presentForm.ass.template" :disabled="viewOnly"
                    >
                    <label class="form-check-label" for="ass-template">Template?</label>
                </div>
                <div class="mt-2">
                    <label for="ass-flags" class="form-label">Flags</label>
                    <input id="ass-flags" type="text" class="form-control" :disabled="viewOnly"
                           placeholder="Enter a space separated list" v-model="presentForm.ass.flags"
                    >
                </div>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="ass-transformation" label="Transformation"
                                      :prop-value="presentForm.ass.transformation" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="ass-description" label="Description"
                                      :prop-value="presentForm.ass.description" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>

            </div>

            <!-- Victory & Defeat Messages -->
            <div class="tab-pane show" id="nav-victorydefeat" role="tabpanel" aria-labelledby="nav-victorydefeat-tab">

                <div>TODO: Preview configuration.</div>

                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly" :multiline="true"
                                      prop-name="defeat" label="Monster defeats Player"
                                      :prop-value="presentForm.defeat.join('\n')" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>
                <div class="text-muted">
                    2nd person from the defeated player's perspective,
                    e.g. 'You are defeated by a mutant!'
                </div>
                <div>TODO: Preview</div>

                <hr/>

                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly" :multiline="true"
                                      prop-name="victory" label="Player defeats Monster"
                                      :prop-value="presentForm.victory.join('\n')" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>
                <div class="text-muted">
                    2nd person from the victorious player's perspective,
                    e.g. 'You beat a mutant, using your mutant ways!'
                </div>
                <div>TODO: Preview</div>

                <hr/>

                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly" :multiline="true"
                                      prop-name="ovictory" label="Player seen defeating Monster"
                                      :prop-value="presentForm.oVictory.join('\n')" @input="queueSaveFromEditor"
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
