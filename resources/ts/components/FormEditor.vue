<script setup lang="ts">

import {computed, onMounted, Ref, ref} from "vue";
import ModalConfirmation from "./ModalConfirmation.vue";
import ModalMessage from "./ModalMessage.vue";
import FormEditorFormSelection from "./FormEditorFormSelection.vue";
import FormEditorTestConfigurator from "./FormEditorTestConfigurator.vue";
import {timestampToString} from "../formatting";
import FormEditorCodeEditor from "./FormEditorCodeEditor.vue";
import FormEditorCompareToLive from "./FormEditorCompareToLive.vue";

const props = defineProps<{
    links: {
        rootUrl: string,
        helpRoot: string,
        termsOfService: string
    },
    initialForm: string
}>();

type FormLog = {
    when: number // Timestamp
    name: string
    account?: number // Only transmitted if we're staff
    what: string // Fixed list of events, such as 'Submitted for Review'
    message: string[]
}

export type Form = {
    name: string
    owner?: number
    credit?: string
    _approved: boolean
    _published: boolean
    _review: boolean
    _revise: boolean
    _deleted: boolean // Not actually sent but will be set on getting the form
    _createdAt?: number // Timestamp
    _editedAt?: number // Timestamp
    _deletedAt?: number // Timestamp
    _log?: FormLog[]
    _notes?: string[]
    _notesTranslated?: string // Created when the form is loaded
    _viewers: string

    height: number
    mass: number
    tags: string
    say2ndPerson: string
    say3rdPerson: string
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

    skinFlags: string
    skinTransformation: string
    skinShortDescription: string
    skinDescription: string
    skinKemoDescription: string
    skinTemplate: boolean

    headFlags: string
    headTransformation: string
    headDescription: string
    headKemoDescription: string
    headTemplate: boolean

    torsoFlags: string
    torsoTransformation: string
    torsoDescription: string
    torsoKemoDescription: string
    torsoTemplate: boolean

    armsFlags: string
    armsTransformation: string
    armsDescription: string
    armsKemoDescription: string
    armsTemplate: boolean

    legsFlags: string
    legsTransformation: string
    legsDescription: string
    legsKemoDescription: string
    legsTemplate: boolean

    groinFlags: string
    groinTransformation: string
    groinCockDescription: string
    groinCuntDescription: string
    groinClitDescription: string
    groinTemplate: boolean

    assFlags: string
    assTransformation: string
    assDescription: string
    assTemplate: boolean
}

const presentFormId: Ref<string | null> = ref(null);
const presentForm: Ref<Form | null> = ref(null);
const publishedForm: Ref<Form | null> = ref(null); // Used to compare with
const previews: Ref<{
    form?: string
    defeat?: string
    victory?: string
    oVictory?: string
}> = ref({});
const viewOnly: Ref<boolean> = ref(false);
const staff: Ref<boolean> = ref(false);
const formSelector: Ref<InstanceType<typeof FormEditorFormSelection> | null> = ref(null);

const confirmDeleteModal: Ref<InstanceType<typeof ModalConfirmation> | null> = ref(null);
const formToDelete: Ref<string> = ref('');

const confirmSubmitModal: Ref<InstanceType<typeof ModalConfirmation> | null> = ref(null);
const submissionNotes: Ref<string> = ref('');

const confirmCancelSubmitModal: Ref<InstanceType<typeof ModalConfirmation> | null> = ref(null);
const cancelSubmissionNotes: Ref<string> = ref('');

const confirmRequestRevisionModal: Ref<InstanceType<typeof ModalConfirmation> | null> = ref(null);
const requestRevisionNotes: Ref<string> = ref('');

const confirmApproveModal: Ref<InstanceType<typeof ModalConfirmation> | null> = ref(null);
const confirmApproveNotes: Ref<string> = ref('');
const confirmApproveRating: Ref<number> = ref(1);

const createFormModal: Ref<InstanceType<typeof ModalConfirmation> | null> = ref(null);
const newFormName: Ref<string> = ref('');

const error: Ref<string> = ref('');
const errorModal: Ref<InstanceType<typeof ModalMessage> | null> = ref(null);

const compareFormModal: Ref<InstanceType<typeof ModalMessage> | null> = ref(null);

const previewConfigurationModal: Ref<InstanceType<typeof ModalMessage> | null> = ref(null);
const subjectConfiguration: Ref<InstanceType<typeof FormEditorTestConfigurator> | null> = ref(null);
const otherConfiguration: Ref<InstanceType<typeof FormEditorTestConfigurator> | null> = ref(null);

const channel = mwiWebsocket.channel('contribute');

let pendingSaves: { [id: string]: string } = {};
let pendingSaveId: number | null = null;

const helpLink = (helpFile: string) => {
    return props.links.helpRoot + '/' + helpFile;
}

const oneWordStatus = computed((): string => {
    if (!presentForm.value) return '';
    if (presentForm.value._deleted) return 'Deleted';
    if (presentForm.value._revise) return 'Revision Needed';
    if (presentForm.value._review) return 'Awaiting Review';
    return presentForm.value._approved ? 'Finished' : 'Under Construction';
});

const statusDescription = computed((): string => {
    if (!presentForm.value) return '';
    if (presentForm.value._deleted) return 'This form has been marked as deleted will be completely removed from the database at some point.';
    if (presentForm.value._revise) return staff.value ?
        'The form is awaiting revision from the author.' :
        'Staff have reviewed the form and some additional work is needed. After reviewing staff feedback you can submit the form again.';
    if (presentForm.value._review) return staff.value ?
        'The form is awaiting review and either being accepted or returned for revision.' :
        'The form is awaiting staff review. You can view it but not make any changes.';
    if (presentForm.value._approved) return staff.value ?
        'This form has been finalized. Any changes will not be update the live copy without manual intervention' :
        'This form has been finalized. You can view it but not make any changes.';
    return 'This is a new or unfinished form. After you have completed enough of the required content you can submit the form for review.';
});

const hasTemplatedParts = computed((): boolean => {
    if (!presentForm.value) return false;
    return presentForm.value.skinTemplate ||
        presentForm.value.headTemplate ||
        presentForm.value.torsoTemplate ||
        presentForm.value.armsTemplate ||
        presentForm.value.legsTemplate ||
        presentForm.value.groinTemplate ||
        presentForm.value.assTemplate;
});

const friendlyFormPreview = computed(() => {
    if (!presentForm.value) return 'No form loaded.';
    return previews.value.form ?? 'Preview not available yet.';
});

const unloadForm = () => {
    presentFormId.value = null;
    presentForm.value = null;
    previews.value = {};
    if (formSelector.value) formSelector.value.expand();
}

const loadForm = (selected: string) => {
    unloadForm();
    presentFormId.value = selected;
    console.log("Now editing form: ", selected);
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
    channel.send('updateFormState', {
        form: presentFormId.value,
        action: 'submit',
        notes: submissionNotes.value
    });
}

const startCancelSubmitForm = () => {
    if (confirmCancelSubmitModal.value) confirmCancelSubmitModal.value.show();
}

const cancelSubmitForm = () => {
    channel.send('updateFormState', {
        form: presentFormId.value,
        action: 'cancel-submission',
        notes: cancelSubmissionNotes.value
    });
}

const startRequestRevision = () => {
    if (confirmRequestRevisionModal.value) confirmRequestRevisionModal.value.show();
}

const requestRevision = () => {
    channel.send('updateFormState', {
        form: presentFormId.value,
        action: 'revision',
        notes: requestRevisionNotes.value
    });}

const startApproveForm = () => {
    if (confirmApproveModal.value) confirmApproveModal.value.show();
}

const approveForm = () => {
    error.value = "Not implemented yet";
    if (errorModal.value) errorModal.value.show();
    //TODO: Approve Form
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

const startCompareFormToLive = () => {
    if (compareFormModal.value) compareFormModal.value.show();
    publishedForm.value = null;
    channel.send('getFormAsPublished', presentFormId.value);
}

const showPreviewConfiguration = () => {
    if (previewConfigurationModal.value) previewConfigurationModal.value.show();
}

const saveValues = () => {
    let updatePreview: boolean = false;
    let updateAllPreviews: boolean = false;
    pendingSaveId = null;
    for (const id in pendingSaves) {
        const value = pendingSaves[id];
        delete pendingSaves[id];
        channel.send('updateForm', {
            form: presentFormId.value,
            propName: id,
            propValue: value
        });
        // Should improve on this
        if (id.includes('size') || id.includes('count')) updateAllPreviews = true;
        if (id.includes('desc')) updatePreview = true;
        if (id === 'defeat' || id === 'victory' || id === 'oVictory') requestFormMessagePreview(id);
    }
    if (updateAllPreviews) requestFullPreviewUpdate();
    else if (updatePreview) requestFormPreview();
}

const queueSave = (propName: string, propValue: string) => {
    pendingSaves[propName] = propValue;
    if (!pendingSaveId) pendingSaveId = setTimeout(saveValues, 1000);
}

const queueSaveFromElement = (e: Event) => {
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
const queueSaveFromEditor = (e: { id: string, value: string }) => {
    if (!e?.id) {
        console.log("Couldn't queue save editor value as the element triggering it has no id: ", e);
        return;
    }
    queueSave(e.id, e.value);
}

const getPreviewConfig = (): { subject: object, other: object } => {
    const subjectConfig = subjectConfiguration.value ? subjectConfiguration.value.getConfig() : {};
    const otherConfig = otherConfiguration.value ? otherConfiguration.value.getConfig() : {};
    return {subject: subjectConfig, other: otherConfig};
}

const requestFormPreview = () => {
    channel.send('previewForm', {form: presentFormId.value, config: getPreviewConfig()});
}

const requestFormMessagePreview = (messageId: string) => {
    channel.send('previewFormMessage', {
        form: presentFormId.value,
        message: messageId,
        config: getPreviewConfig()
    });
}

const requestFullPreviewUpdate = () => {
    requestFormPreview();
    requestFormMessagePreview('victory');
    requestFormMessagePreview('oVictory');
    requestFormMessagePreview('defeat');
}

type GetFormResponse = {
    error?: string
    form?: Form
    canEdit?: boolean
    staff?: boolean
}

const linkToPresentFormId = computed(() => {
    return presentFormId.value ? props.links.rootUrl + '?form=' + encodeURIComponent(presentFormId.value) : ''
})

channel.on('form', (response: GetFormResponse) => {
    if (response.error) {
        error.value = response.error;
        if (errorModal.value) errorModal.value.show();
        return;
    }
    if (!response.form) {
        presentForm.value = null;
        return;
    }
    const form: Form = response.form;
    if (form.name !== presentFormId.value) {
        console.log("Received form data but we're not editing this form?", form);
        return;
    }
    viewOnly.value = !response.canEdit;
    staff.value = response.staff ?? false;

    // Handle some fixes and translations
    if (form._notes) form._notesTranslated = form._notes.join('\n');
    if (form._deletedAt) form._deleted = true;
    if (!form.oVictory) form.oVictory = [];
    if (!form.victory) form.victory = [];
    if (!form.defeat) form.defeat = [];
    presentForm.value = form;
    // Now request the initial previews
    requestFullPreviewUpdate();
})

channel.on('deleteForm', (response: { error?: string, formId?: string }) => {
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

channel.on('createForm', (response: { error?: string, formId?: string }) => {
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

channel.on('formPreview', (response: { form: string, content: string }) => {
    if (!response.form || response.form != presentFormId.value) return;
    if (presentForm.value) previews.value.form = response.content;
});

channel.on('formMessagePreview', (response: { form: string, message: string, content: string[] }) => {
    if (!response.form || response.form != presentFormId.value || !response.message) return;
    let parsedContent = response.content.join('<br/>');
    switch (response.message) {
        case 'defeat':
            previews.value.defeat = parsedContent;
            break;
        case 'victory':
            previews.value.victory = parsedContent;
            break;
        case 'oVictory':
            previews.value.oVictory = parsedContent;
            break;
        default:
            console.log("Unrecognized form message received: " + response.message);
            break;
    }
});

channel.on('publishedForm', (response: Form) => {
    publishedForm.value = response;
});

channel.on('updateFormFailed', (response) => {
    error.value = `Update failed or rejected by the game when setting '${response.propName}' to '${response.propValue}.'`;
    error.value += "\n\nThis means your changes weren't saved, so you may wish to keep a record elsewhere.";
    if (response.error) error.value += "\n\nActual error returned: " + response.error;
    if (errorModal.value) errorModal.value.show();
});

channel.on('formStateUpdate', (response) => {
    if (response.error) {
        // The error from the muck should be more specifically tailored here, so use that.
        error.value = response.error;
        if (errorModal.value) errorModal.value.show();
        return;
    }
    if (!response.form || response.form != presentFormId.value) return;
    // Reload everything to see updates
    if (formSelector.value) formSelector.value.refresh();
    if (presentFormId.value) loadForm(presentFormId.value);
});

onMounted(() => {
    if (props.initialForm) loadForm(props.initialForm);
})


</script>

<template>
    <FormEditorFormSelection ref="formSelector" :start-expanded="props.initialForm == ''"
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
        <h3>Editing - {{ presentFormId }}
            <a :href="linkToPresentFormId">
                <i class="fas fa-link"></i>
            </a>
        </h3>

        <!-- Preview -->
        <div class="card">
            <div class="card-header text-secondary">Preview</div>
            <div class="card-body">
                <div class="card-text" v-html="friendlyFormPreview"></div>
            </div>
            <div class="card-footer text-muted text-center">
                Please note - the preview can be slow to load or update, especially on larger forms.

                <div v-if="presentForm._published" class="alert alert-danger" role="alert">
                    The preview is unreliable with forms that have been published.
                    <br/>Some of the code defaults to the published entry and won't reflect changes here.
                </div>

                <div class="mt-2 text-center">
                    <button class="btn btn-primary" @click="showPreviewConfiguration">
                        <i class="fas fa-cog btn-icon-left"></i>Preview Configuration
                    </button>
                </div>
            </div>
        </div>

        <!-- Deleted or Read-Only mode warning -->
        <div v-if="presentForm._deleted" class="mt-2 p-2 rounded text-bg-danger">
            This form is flagged for deletion.
        </div>
        <div v-else-if="viewOnly" class="mt-2 p-2 rounded text-bg-warning">
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

                <div class="mt-2" v-if="staff">Account: {{ presentForm.owner }}</div>

                <div class="mt-2">Credited Character: {{ presentForm.credit }}</div>

                <div class="mt-2">Created: {{ timestampToString(presentForm._createdAt) }}</div>

                <div class="mt-2">Last edited: {{ timestampToString(presentForm._editedAt) }}</div>

                <!-- Allowed Viewers -->
                <div class="d-flex mt-2">
                    <label for="_viewers" class="col-form-label">Allowed Viewers</label>
                    <input id="_viewers" type="text" class="form-control ms-2 flex-grow-1" :disabled="viewOnly"
                           placeholder="List of Viewers" v-model="presentForm._viewers" @input="queueSaveFromElement"
                    >
                </div>
                <div class="text-muted">Space separated list of other people who are allowed to view this form,
                    in case you want to seek assistance or review.
                    A direct link to this form is available besides the title above.
                </div>

                <!-- Template status -->
                <div v-if="hasTemplatedParts" class="mt-2 p-2 rounded text-bg-info">
                    This form has at least one part with template flagged, so will be considered as a template form.
                </div>

                <!-- Notes -->
                <div class="mt-2">
                    <h4>Notes</h4>
                    <label for="_notes" class="form-label visually-hidden">Notes</label>
                    <textarea class="form-control" id="_notes" rows="3"
                              v-model="presentForm._notesTranslated" @input="queueSaveFromElement" :disabled="viewOnly"
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
                    <div v-if="!presentForm._log || !presentForm._log.length">No history recorded.</div>
                    <table v-else class="table table-dark table-hover table-striped table-responsive small">
                        <thead>
                        <tr>
                            <th scope="col">When</th>
                            <th scope="col">Who</th>
                            <th scope="col">What</th>
                            <th scope="col">Message</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="entry in presentForm?._log">
                            <td>{{ timestampToString(entry.when) }}</td>
                            <td>{{ entry.name }}</td>
                            <td>{{ entry.what }}</td>
                            <td><div v-for="line in entry.message">{{ line }}</div></td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-2">Status: {{ oneWordStatus }}</div>
                <div class="text-muted">{{ statusDescription }}</div>

                <!-- State changing buttons -->
                <div class="mt-2">

                    <!-- Submit button, available if the form is new or awaiting revision. -->
                    <button :disabled="presentForm._review || presentForm._approved"
                            class="btn btn-primary me-2" @click="startSubmitForm"
                    >
                        <i class="fas fa-thumbs-up btn-icon-left"></i>Submit Form for review
                    </button>

                    <!-- Recall Submission button, available if the form is awaiting review. -->
                    <button :disabled="!presentForm._review"
                            class="btn btn-primary me-2" @click="startCancelSubmitForm"
                    >
                        <i class="fas fa-hand btn-icon-left"></i>Recall Submission
                    </button>

                    <!-- Delete button, not available after approval -->
                    <button :disabled="presentForm._approved || presentForm._deleted"
                            class="btn btn-secondary me-2" @click="startDeleteForm"
                    >
                        <i class="fas fa-trash btn-icon-left"></i>Delete Form
                    </button>

                </div>

                <!-- Staff only controls and buttons -->
                <div v-if="staff" class="mt-2">
                    <h3>Staff Controls</h3>

                    <div class="mt-2">

                        <!-- Refuse submission, available if the form is awaiting review -->
                        <button :disabled="!presentForm._review"
                                class="btn btn-primary me-2" @click="startRequestRevision"
                        >
                            <i class="fas fa-x btn-icon-left"></i>Return for Revision
                        </button>

                        <!-- Approve submission, available if the form is awaiting review -->
                        <button :disabled="!presentForm._review"
                                class="btn btn-primary me-2" @click="startApproveForm"
                        >
                            <i class="fas fa-check btn-icon-left"></i>Approve Form
                        </button>

                        <!-- Compare to live, available if published to live -->
                        <button :disabled="!presentForm._published"
                                class="btn btn-secondary me-2" @click="startCompareFormToLive"
                        >
                            <i class="fas fa-magnifying-glass btn-icon-left"></i>Compare to Live
                        </button>

                    </div>

                    <!-- No Reward -->
                    <div class="mt-2 form-check">
                        <input class="form-check-input" type="checkbox" id="noreward"
                               v-model="presentForm.noReward" :disabled="viewOnly" @input="queueSaveFromElement"
                        >
                        <label class="form-check-label" for="noreward">No Reward</label>
                    </div>
                    <div class="text-muted">Prevents rewards from including nanites of this form.</div>

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
                        <label for="2nd-person-say" class="form-label">
                            2nd person (say, purr, bark)
                        </label>
                        <input id="say2ndPerson" type="text" class="form-control" :disabled="viewOnly"
                               placeholder="2nd Person" v-model="presentForm.say2ndPerson" @input="queueSaveFromElement"
                        >
                    </div>

                    <div class="mt-2 col-12 col-lg-6">
                        <label for="3rd-person-say" class="form-label">
                            3rd person (says, purrs, barks)
                        </label>
                        <input id="say3rdPerson" type="text" class="form-control" :disabled="viewOnly"
                               placeholder="3rd Person" v-model="presentForm.say3rdPerson" @input="queueSaveFromElement"
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
                    <label class="form-check-label" for="heat">Sexually Fluid?</label>
                </div>
                <div class="text-muted">
                    If this is set, the form will not attempt to change any sexual attributes.
                    This doesn't mean the form is neuter, merely that it won't try to assert a gender.
                </div>

                <div class="row" v-if="!presentForm.sexless">

                    <div class="mt-2 col-12 col-lg-6">
                        <label for="breastCount" class="form-label">Breast Count</label>
                        <input id="breastCount" type="number" class="form-control" :disabled="viewOnly"
                               placeholder="#" v-model="presentForm.breastCount" @input="queueSaveFromElement"
                        >
                    </div>

                    <div class="mt-2 col-12 col-lg-6">
                        <label for="breastSize" class="form-label">Breast Size (5 is average)</label>
                        <input id="breastSize" type="number" class="form-control" :disabled="viewOnly"
                               placeholder="#" v-model="presentForm.breastSize" @input="queueSaveFromElement"
                        >
                    </div>

                    <div class="mt-2 col-12 col-lg-6">
                        <label for="cuntCount" class="form-label">Cunt Count</label>
                        <input id="cuntCount" type="number" class="form-control" :disabled="viewOnly"
                               placeholder="#" v-model="presentForm.cuntCount" @input="queueSaveFromElement"
                        >
                    </div>

                    <div class="mt-2 col-12 col-lg-6">
                        <label for="cuntSize" class="form-label">Cunt Depth (5 is average)</label>
                        <input id="cuntSize" type="number" class="form-control" :disabled="viewOnly"
                               placeholder="#" v-model="presentForm.cuntSize" @input="queueSaveFromElement"
                        >
                    </div>

                    <div class="mt-2 col-12 col-lg-6">
                        <label for="clitCount" class="form-label">Clit Count</label>
                        <input id="clitCount" type="number" class="form-control" :disabled="viewOnly"
                               placeholder="#" v-model="presentForm.clitCount" @input="queueSaveFromElement"
                        >
                    </div>

                    <div class="mt-2 col-12 col-lg-6">
                        <label for="clitSize" class="form-label">Clit Length (5 is average)</label>
                        <input id="clitSize" type="number" class="form-control" :disabled="viewOnly"
                               placeholder="#" v-model="presentForm.clitSize" @input="queueSaveFromElement"
                        >
                    </div>

                    <div class="mt-2 col-12 col-lg-6">
                        <label for="cockCount" class="form-label">Cock Count</label>
                        <input id="cockCount" type="number" class="form-control" :disabled="viewOnly"
                               placeholder="#" v-model="presentForm.cockCount" @input="queueSaveFromElement"
                        >
                    </div>

                    <div class="mt-2 col-12 col-lg-6">
                        <label for="cockSize" class="form-label">Cock Length (5 is average)</label>
                        <input id="cockSize" type="number" class="form-control" :disabled="viewOnly"
                               placeholder="#" v-model="presentForm.cockSize" @input="queueSaveFromElement"
                        >
                    </div>


                    <div class="mt-2 col-12 col-lg-6">
                        <label for="ballCount" class="form-label">Ball Count</label>
                        <input id="ballCount" type="number" class="form-control" :disabled="viewOnly"
                               placeholder="#" v-model="presentForm.ballCount" @input="queueSaveFromElement"
                        >
                    </div>

                    <div class="mt-2 col-12 col-lg-6">
                        <label for="ballSize" class="form-label">Ball Size (5 is average)</label>
                        <input id="ballSize" type="number" class="form-control" :disabled="viewOnly"
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
                        the present part rather than replacing it. See
                        '<a :href="helpLink('String Parsing/template Forms')">+help String Parsing/template Forms</a>'
                    </li>
                </ul>

                <!-- Skin -->
                <hr/>
                <h4 class="mt-2">Skin</h4>
                <div class="mt-2 form-check">
                    <input class="form-check-input" type="checkbox" id="skinTemplate"
                           v-model="presentForm.skinTemplate" :disabled="viewOnly" @input="queueSaveFromElement"
                    >
                    <label class="form-check-label" for="skinTemplate">Template?</label>
                </div>
                <div class="mt-2">
                    <label for="skinFlags" class="form-label">Flags</label>
                    <input id="skinFlags" type="text" class="form-control" :disabled="viewOnly"
                           placeholder="Enter a space separated list" v-model="presentForm.skinFlags"
                           @input="queueSaveFromElement"
                    >
                </div>
                <div class="mt-2">
                    <label for="skinShortDescription" class="form-label">Short Description</label>
                    <input id="skinShortDescription" type="text" class="form-control" :disabled="viewOnly"
                           placeholder="Enter a phrase for an adjective (e.g. dry and scaly) "
                           v-model="presentForm.skinShortDescription" @input="queueSaveFromElement"
                    >
                    <div class="text-muted">This should be 1 - 4 adjectives and is used during other messages.</div>
                </div>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="skinTransformation" label="Transformation"
                                      :prop-value="presentForm.skinTransformation" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>
                <div class="mt-2 text-muted">Skin descriptions are prefixed by the text 'Their body is covered in..'
                </div>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="skinDescription" label="Description"
                                      :prop-value="presentForm.skinDescription" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="skinKemoDescription" label="Kemo Description"
                                      :prop-value="presentForm.skinKemoDescription" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>

                <!-- Head -->
                <hr/>
                <h4 class="mt-2">Head</h4>
                <div class="mt-2 form-check">
                    <input class="form-check-input" type="checkbox" id="headTemplate"
                           v-model="presentForm.headTemplate" :disabled="viewOnly" @input="queueSaveFromElement"
                    >
                    <label class="form-check-label" for="headTemplate">Template?</label>
                </div>
                <div class="mt-2">
                    <label for="headFlags" class="form-label">Flags</label>
                    <input id="headFlags" type="text" class="form-control" :disabled="viewOnly"
                           @input="queueSaveFromElement"
                           placeholder="Enter a space separated list" v-model="presentForm.headFlags"
                    >
                </div>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="headTransformation" label="Transformation"
                                      :prop-value="presentForm.headTransformation" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>
                <div class="mt-2 text-muted">Head descriptions are prefixed by the text 'Their head is..'</div>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="headDescription" label="Description"
                                      :prop-value="presentForm.headDescription" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="headKemoDescription" label="Kemo Description"
                                      :prop-value="presentForm.headKemoDescription" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>

                <!-- Torso -->
                <hr/>
                <h4 class="mt-2">Torso</h4>
                <div class="mt-2 form-check">
                    <input class="form-check-input" type="checkbox" id="torsoTemplate" @input="queueSaveFromElement"
                           v-model="presentForm.torsoTemplate" :disabled="viewOnly"
                    >
                    <label class="form-check-label" for="torsoTemplate">Template?</label>
                </div>
                <div class="mt-2">
                    <label for="torsoFlags" class="form-label">Flags</label>
                    <input id="torsoFlags" type="text" class="form-control" :disabled="viewOnly"
                           @input="queueSaveFromElement"
                           placeholder="Enter a space separated list" v-model="presentForm.torsoFlags"
                    >
                </div>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="torsoTransformation" label="Transformation"
                                      :prop-value="presentForm.torsoTransformation" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>
                <div class="mt-2 text-muted">Head descriptions are prefixed by the text 'Their torso is..'</div>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="torsoDescription" label="Description"
                                      :prop-value="presentForm.torsoDescription" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="torsoKemoDescription" label="Kemo Description"
                                      :prop-value="presentForm.torsoKemoDescription" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>

                <!-- Arms -->
                <hr/>
                <h4 class="mt-2">Arms</h4>
                <div class="mt-2 form-check">
                    <input class="form-check-input" type="checkbox" id="armsTemplate" @input="queueSaveFromElement"
                           v-model="presentForm.armsTemplate" :disabled="viewOnly"
                    >
                    <label class="form-check-label" for="armsTemplate">Template?</label>
                </div>
                <div class="mt-2">
                    <label for="armsFlags" class="form-label">Flags</label>
                    <input id="armsFlags" type="text" class="form-control" :disabled="viewOnly"
                           @input="queueSaveFromElement"
                           placeholder="Enter a space separated list" v-model="presentForm.armsFlags"
                    >
                </div>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="armsTransformation" label="Transformation"
                                      :prop-value="presentForm.armsTransformation" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>
                <div class="mt-2 text-muted">Head descriptions are prefixed by the text 'Their arms are..'</div>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="armsDescription" label="Description"
                                      :prop-value="presentForm.armsDescription" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="armsKemoDescription" label="Kemo Description"
                                      :prop-value="presentForm.armsKemoDescription" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>

                <!-- Legs -->
                <hr/>
                <h4 class="mt-2">Legs</h4>
                <div class="mt-2 form-check">
                    <input class="form-check-input" type="checkbox" id="legsTemplate" @input="queueSaveFromElement"
                           v-model="presentForm.legsTemplate" :disabled="viewOnly"
                    >
                    <label class="form-check-label" for="legsTemplate">Template?</label>
                </div>
                <div class="mt-2">
                    <label for="legsFlags" class="form-label">Flags</label>
                    <input id="legsFlags" type="text" class="form-control" :disabled="viewOnly"
                           @input="queueSaveFromElement"
                           placeholder="Enter a space separated list" v-model="presentForm.legsFlags"
                    >
                </div>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="legsTransformation" label="Transformation"
                                      :prop-value="presentForm.legsTransformation" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>
                <div class="mt-2 text-muted">
                    Leg descriptions are prefixed by the text 'Their legs are..'.
                    They immediately follow with the ass description.
                </div>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="legsDescription" label="Description"
                                      :prop-value="presentForm.legsDescription" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="legsKemoDescription" label="Kemo Description"
                                      :prop-value="presentForm.legsKemoDescription" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>

                <!-- Ass or Tail -->
                <hr/>
                <h4 class="mt-2">Ass or Tail</h4>
                <div class="mt-2 form-check">
                    <input class="form-check-input" type="checkbox" id="assTemplate" @input="queueSaveFromElement"
                           v-model="presentForm.assTemplate" :disabled="viewOnly"
                    >
                    <label class="form-check-label" for="assTemplate">Template?</label>
                </div>
                <div class="mt-2">
                    <label for="assFlags" class="form-label">Flags</label>
                    <input id="assFlags" type="text" class="form-control" :disabled="viewOnly"
                           @input="queueSaveFromElement"
                           placeholder="Enter a space separated list" v-model="presentForm.assFlags"
                    >
                </div>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="assTransformation" label="Transformation"
                                      :prop-value="presentForm.assTransformation" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>
                <div class="mt-2 text-muted">Ass descriptions immediately follow the leg description'</div>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="assDescription" label="Description"
                                      :prop-value="presentForm.assDescription" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>

                <!-- Groin -->
                <hr/>
                <h4 class="mt-2">Groin</h4>
                <div class="mt-2 form-check">
                    <input class="form-check-input" type="checkbox" id="groinTemplate" @input="queueSaveFromElement"
                           v-model="presentForm.groinTemplate" :disabled="viewOnly"
                    >
                    <label class="form-check-label" for="groinTemplate">Template?</label>
                </div>
                <div class="mt-2">
                    <label for="groinFlags" class="form-label">Flags</label>
                    <input id="groinFlags" type="text" class="form-control" :disabled="viewOnly"
                           @input="queueSaveFromElement"
                           placeholder="Enter a space separated list" v-model="presentForm.groinFlags"
                    >
                </div>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="groinTransformation" label="Transformation"
                                      :prop-value="presentForm.groinTransformation" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>
                <div class="mt-2 text-muted">
                    Groin descriptions are more complicated and will be prefixed with a count and size adjective,
                    and follow with what's being described. E.g. 'they have one huge ... cock'
                </div>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="groinCockDescription" label="Cock Description"
                                      :prop-value="presentForm.groinCockDescription" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="groinCuntDescription" label="Cunt Description"
                                      :prop-value="presentForm.groinCuntDescription" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly"
                                      prop-name="groinClitDescription" label="Clit Description"
                                      :prop-value="presentForm.groinClitDescription" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>

            </div>

            <!-- Victory & Defeat Messages -->
            <div class="tab-pane show" id="nav-victorydefeat" role="tabpanel" aria-labelledby="nav-victorydefeat-tab">
                <h4>Defeat</h4>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly" :multiline="true"
                                      prop-name="defeat" label="Player defeated by Form"
                                      :prop-value="presentForm.defeat.join('\n')" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>
                <div class="text-muted">
                    2nd person from the defeated player's perspective,
                    e.g. 'You are defeated by a mutant!'
                </div>
                <div class="mt-2 text-secondary">Preview</div>
                <p v-html="previews.defeat"></p>
                <div class="mt-2 text-center">
                    <button class="btn btn-primary" @click="showPreviewConfiguration">
                        <i class="fas fa-cog btn-icon-left"></i>Preview Configuration
                    </button>
                </div>

                <hr/>
                <h4>Victory</h4>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly" :multiline="true"
                                      prop-name="victory" label="Player (as form) defeats Monster"
                                      :prop-value="presentForm.victory.join('\n')" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>
                <div class="text-muted">
                    2nd person from the victorious player's perspective when they're in this form,
                    e.g. 'You beat a mutant, using your mutant ways!'
                </div>
                <div class="mt-2 text-secondary">Preview</div>
                <p v-html="previews.victory"></p>
                <div class="mt-2 text-center">
                    <button class="btn btn-primary" @click="showPreviewConfiguration">
                        <i class="fas fa-cog btn-icon-left"></i>Preview Configuration
                    </button>
                </div>

                <hr/>
                <h4>Observed Victory</h4>
                <FormEditorCodeEditor class="mt-2" :viewOnly="viewOnly" :multiline="true"
                                      prop-name="oVictory" label="Form seen defeating Monster"
                                      :prop-value="presentForm.oVictory.join('\n')" @input="queueSaveFromEditor"
                ></FormEditorCodeEditor>
                <div class="text-muted">
                    3rd person from somebody else observing this form's victory,
                    e.g. 'Bob defeats a mutant in a weird mutant way!'
                </div>
                <div class="mt-2 text-secondary">Preview</div>
                <p v-html="previews.oVictory"></p>
                <div class="mt-2 text-center">
                    <button class="btn btn-primary" @click="showPreviewConfiguration">
                        <i class="fas fa-cog btn-icon-left"></i>Preview Configuration
                    </button>
                </div>

            </div>
        </div>
        <div class="text-center">
            Changes are saved automatically as you make them.
        </div>

    </div>

    <!-- Modal for changing the test configuration -->
    <modal-message class="modal-xl" ref="previewConfigurationModal" title="Preview Configuration"
                   @close="requestFullPreviewUpdate"
    >
        <div class="row">
            <div class="col-12 col-xl-6 mt-2">
                <div class="border border-primary rounded-2 p-2">
                    <h4>The Subject</h4>
                    <p>This configuration is used for the 'wearer' of the form:</p>
                    <ul>
                        <li>In descriptions, they're the person being looked at.</li>
                        <li>In defeat messages, they're the victor.</li>
                        <li>In victory messages, they're the loser.</li>
                    </ul>
                    <hr/>
                    <form-editor-test-configurator ref="subjectConfiguration" role="subject"/>
                </div>
            </div>
            <div class="col-12 col-xl-6 mt-2">
                <div class="border border-secondary rounded-2 p-2">
                    <h4>The Other</h4>
                    <p>This configuration is used for the other party:</p>
                    <ul>
                        <li>In descriptions, they're the person doing the looking.</li>
                        <li>In defeat messages, they're the loser.</li>
                        <li>In victory messages, they're the victor.</li>
                    </ul>
                    <hr/>
                    <form-editor-test-configurator ref="otherConfiguration" role="other"/>
                </div>
            </div>
        </div>

    </modal-message>

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
        <p>Are you sure you're finished with this form?</p>
        <p>
            While the form is waiting for staff review you won't be able to make any changes, although you can
            recall the submission if you need to change something before it's reviewed.
        </p>
        <p>
            You can use the box below if you have any notes, comments or concerns you'd like to include with this.
        </p>

        <label for="submission-notes" class="form-label">Notes:</label>
        <textarea type="text" class="form-control" id="submission-notes"
                  v-model="submissionNotes" rows="3">
        </textarea>

    </modal-confirmation>

    <!-- Modal to cancel the submission of a form -->
    <modal-confirmation ref="confirmCancelSubmitModal" @yes="cancelSubmitForm"
                        title="Recall Submission" yes-label="Recall" no-label="Cancel"
    >
        <p>Are you sure you wish to recall the form?</p>
        <p>
            This will allow you to make additional changes, though it may cause confusion
            if someone is already looking at the form.
        </p>
        <p>
            You can use the box below if you have any notes, comments or concerns you'd like to include with this.
        </p>

        <label for="cancel-submission-notes" class="form-label">Notes:</label>
        <textarea type="text" class="form-control" id="cancel-submission-notes"
                  v-model="cancelSubmissionNotes" rows="3">
        </textarea>

    </modal-confirmation>

    <!-- Staff modal to mark a form as requiring revision -->
    <modal-confirmation ref="confirmRequestRevisionModal" @yes="requestRevision"
                        title="Request Revision" yes-label="Request Revision" no-label="Cancel"
    >
        <p>
            This will return the form to the creator for more work to be done on it.
        </p>
        <div>
            Make sure to use the box below to detail what's required.
        </div>

        <label for="request-revision-notes" class="form-label">Notes:</label>
        <textarea type="text" class="form-control" id="request-revision-notes"
                  v-model="requestRevisionNotes" rows="3">
        </textarea>

    </modal-confirmation>

    <!-- Staff modal to approve a form -->
    <modal-confirmation ref="confirmApproveModal" @yes="approveForm"
                        title="Approve Form" yes-label="Approve Form" no-label="Cancel"
    >
        <div class="p-2 mb-2 rounded bg-warning text-dark">Not Implemented Yet</div>
        <div>Select a quality rating for this submission.</div>

        <div class="my-2 d-flex align-items-center justify-content-center">
            <div class="me-2">Rating:</div>
            <div class="btn-group" role="group" aria-label="Approval Rating">

                <input type="radio" class="btn-check" name="rating" id="rating_basic" autocomplete="off"
                       v-model="confirmApproveRating" :value="1"
                >
                <label class="btn btn-outline-primary" for="rating_basic">Basic</label>

                <input type="radio" class="btn-check" name="rating" id="rating_average" autocomplete="off"
                       v-model="confirmApproveRating" :value="2"
                >
                <label class="btn btn-outline-primary" for="rating_average">Average</label>

                <input type="radio" class="btn-check" name="rating" id="rating_advanced" autocomplete="off"
                       v-model="confirmApproveRating" :value="3"
                >
                <label class="btn btn-outline-primary" for="rating_advanced">Advanced</label>

            </div>
        </div>

        <div>
            Examples of things that could contribute towards a better rating:
            <ul>
                <li>Good writing, with precedence for quality over quantity</li>
                <li>Adherence to guidelines, e.g. not assuming actions in descriptions</li>
                <li>String parsing support, where appropriate, for things like:
                    <ul>
                        <li>Flags / preferences</li>
                        <li>Splitters</li>
                        <li>Color choosers</li>
                    </ul>
                </li>
            </ul>
        </div>
        <label for="approve-form-notes" class="form-label">Notes:</label>
        <textarea type="text" class="form-control" id="approve-form-notes"
                  v-model="confirmApproveNotes" rows="3">
        </textarea>
        <div>
            If approved, a form will be automatically published.
            The placement and powerset fields will be set to 'Pending' unless they're already set to something.
        </div>

    </modal-confirmation>

    <!-- Modal to create a new form -->
    <modal-confirmation ref="createFormModal" @yes="createForm"
                        title="Create New Form" yes-label="Create" no-label="Cancel"
    >

        <label for="newFormName" class="form-label">Enter the name of the form (avoid gender specific names):</label>
        <input type="text" class="form-control" id="newFormName" v-model="newFormName">

        <p class="mt-4">WARNING: Do not enter pokemon, disney characters, or any other copyrighted material.
            Fictional races made in the last century ARE copyrighted, don't use them.</p>
        <p>Any content entered becomes property of the game, and its owning company Silver Games LLC.
            If required, please review the <a :href="links.termsOfService">Terms of Service</a>.</p>
    </modal-confirmation>

    <!-- Staff modal for comparing the present form to the one in live -->
    <modal-message class="modal-xl" ref="compareFormModal" title="Compare to Live Form">
        This tool shows the difference between the in-progress version of a form (The 'dev' version) and the
        version published in live.
        <div class="mt-2" v-if="!publishedForm">
            Loading form..
        </div>
        <div class="mt-2" v-else-if="!presentForm">
            Present form should be set!
        </div>
        <div v-else class="mt-2">
            <form-editor-compare-to-live
                :dev-form="publishedForm"
                :live-form="presentForm"
            >
            </form-editor-compare-to-live>
        </div>
    </modal-message>

    <!-- Modal for error messages -->
    <modal-message ref="errorModal">
        <div v-for="line in error.split('\n')">{{ line }}</div>
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
