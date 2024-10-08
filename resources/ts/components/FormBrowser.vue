<script setup lang="ts">

import {ref, Ref, computed} from "vue";
import Progress from "./Progress.vue";
import DataTable from 'primevue/datatable';
import Column from "primevue/column";
import {capital} from "../formatting";
import Spinner from "./Spinner.vue";
import ModalConfirmation from "./ModalConfirmation.vue";
import {FilterService, FilterMatchMode} from "@primevue/core/api";

const props = defineProps<{
    startingPlayerName?: string,
    staff?: boolean
}>();

type Form = {
    name: string,
    private?: number, // We'll list private forms IF they're mastered
    gender: string,
    size?: number,
    ballCount: number,
    ballSize: number,
    breastCount: number,
    breastSize: number,
    clitCount: number,
    clitSize: number,
    cockCount: number,
    cockSize: number,
    cuntCount: number,
    cuntSize: number,
    lstats?: { [lstat: string]: string[] }, // List of lstats and which parts they're in
    tags: string[], // List of tags
    flags: { [bodyPart: string]: string[] } // List of flags by bodypart
    powers: { [bodyPart: string]: string[] } // List of powers by bodypart
    powersBonus: { [requirement: string]: string[] } // List of powers by requirement (setN)
    kemo: string[], // List of bodyparts that support it
    chubby: string[], // List of bodyparts that support it
    color: string[], // List of bodyparts that support it
    dividers: string[],
    noMastering?: number,
    noFunnel?: number,
    noReward?: number,
    noZap?: number,
    noNative?: number,
    noExtract?: number,
    bypassImmune?: number,
    sayVerb?: string,
    holiday?: string,
    // These only appear to staff
    staffonly?: number, // Not a field from the muck, computed flag for logic
    hidden?: number,
    placement?: string[], // Maybe allow with terminal download?
    powersetNote?: string,
    placementNote?: string,
    specialNote?: string
}

const formDatabase: Ref<Form[]> = ref([]);
const channel = mwiWebsocket.channel('forms');
const formsToLoad: Ref<number | null> = ref(null);
const formsToLoadRemaining: Ref<number> = ref(0);
const detailedOutput: Ref<boolean> = ref(false);

const filters = ref({
    name: {value: null, matchMode: FilterMatchMode.CONTAINS},
    powers: {value: null, matchMode: 'filteredNestedList'},
    tags: {value: null, matchMode: FilterMatchMode.CONTAINS},
    flags: {value: null, matchMode: 'filteredNestedList'},
    global: {value: 'mastered', matchMode: 'filteredFormList'}
});

type Target = {
    name?: string,
    loading?: boolean,
    error?: string,
    forms?: { [form: string]: number }
}
const targets: Ref<[Target, Target, Target, Target]> = ref([{}, {}, {}, {}]);
const changeTargetModal: Ref<InstanceType<typeof ModalConfirmation> | null> = ref(null);
const changeTargetIndex: Ref<number> = ref(0);
const changeTargetName: Ref<string> = ref('');

channel.on('formDatabase', (data: number) => {
    formDatabase.value = [];
    formsToLoadRemaining.value = data;
    formsToLoad.value = data;
});

channel.on('formListing', (data: Form) => {
    formDatabase.value.push(data);
    formsToLoadRemaining.value--;
});

type FormMasteryResponse = {
    who: string,
    forms?: { [form: string]: number },
    error?: string
}

channel.on('mastery', (data: FormMasteryResponse) => {
    // User might have entered the same target in multiple rows, so just step through them all
    let updatedCount = 0;
    for (const target of targets.value) {
        if (target.name !== data.who) continue;
        target.name = data.who;
        target.error = data.error;
        target.forms = data.forms;
        target.loading = false;
        updatedCount++;
    }
    if (!updatedCount) {
        console.log("Unable to find a target to update for: ", data);
        return;
    }
});

const loading = computed(() : boolean => {
    return (!formsToLoad.value || formsToLoadRemaining.value > 0);
});

// Returns a 0 to 100 value, not a ratio.
const loadingPercentage = computed(() => {
    if (!formsToLoad.value) return 0;
    return (formsToLoad.value - formsToLoadRemaining.value) * 100 / formsToLoad.value;
});

const changeTarget = (): void => {
    if (!changeTargetName.value) return;
    let target = targets.value[changeTargetIndex.value];
    target.loading = true;
    target.name = changeTargetName.value;
    channel.send('getFormMasteryOf', target.name);
}

const launchChangeTarget = (index: number): void => {
    changeTargetIndex.value = index;
    if (changeTargetModal.value) changeTargetModal.value.show();
}

const clearTarget = (index: number) => {
    targets.value[index] = {};
}

const unknownForms = computed((): string => {
    const result = [];
    const formList = formDatabase.value.map((form) => {
        return form.name;
    });
    for (const target of targets.value) {
        for (const form in target.forms) {
            if (formList.indexOf(form) === -1 && result.indexOf(form) === -1) result.push(form)
        }
    }
    return result.join(', ');
});

const genderClassForForm = (form: Form): string => {
    if (form.cockCount)
        if (form.cuntCount)
            return 'fa-venus-mars';
        else
            return 'fa-mars';
    else if (form.cuntCount)
        return 'fa-venus';
    else
        return 'fa-genderless';

}

FilterService.register('filteredFormList', (name: string, mode: string) => {
    const form: Form | undefined = formDatabase.value.find((form) => form.name == name);
    if (!form) return false;
    let count = 0;
    for (const target of targets.value) {
        if (target.forms && target.forms[name]) count++;
    }
    if (mode === 'mastered' && !count) return false;
    if (mode === 'unmastered' && count) return false;

    if (!props.staff) {
        if (form.staffonly) return false;
        // Only show private forms that are present
        if (form.private && !count) return false;
    }
    return true;
});

FilterService.register('filteredNestedList', (data, value) => {
    if (!value) return true;
    for (const key in data) {
        for (const nestedItem of data[key]) {
            if (nestedItem.indexOf && nestedItem.indexOf(value) !== -1) return true;
        }
    }
    return false;
});

const highestUsedTargetIndex = () => {
    let result = 0;
    for (let i = 0; i < targets.value.length; i++) {
        if (targets.value[i].name) result = i;
    }
    return result;
}

const outputNestedListItemsOnly = (nestedList: { [lstat: string]: string[] }): string => {
    if (!nestedList) return '';
    const result = [];
    for (const key in nestedList) {
        for (const value of nestedList[key]) {
            if (result.indexOf(value) == -1) result.push(value);
        }
    }
    return result.join(', ');
}

const outputNestedListKeysOnly = (nestedList: { [lstat: string]: string[] } | undefined ): string => {
    if (!nestedList) return '';
    const result = [];
    for (const key in nestedList) {
        if (result.indexOf(key) == -1) result.push(key);
    }
    return result.join(', ');
}


// Send requests for data
channel.send('getFormDatabase');
if (props.startingPlayerName) {
    changeTargetIndex.value = 0;
    changeTargetName.value = props.startingPlayerName;
    changeTarget();
}

</script>

<template>
    <div class="container">

        <h1>Form Browser<span v-if="props.staff"> (Staff Mode)</span></h1>

        <p class="lead">This is presently a root page and should have some introductory text here for new users that
            might click on
            it. Something about forms being a key part of the game?</p>

        <p>By default this page will show forms you've mastered. You can use the controls below to change this and even
            to add additional people to view, if they've given you permission to do so. If more then one
            person is set as a target, additional columns will also appear to compare form mastery.</p>

        <Progress v-if="loading" id="form-list-progress-bar"
                  :percentage="loadingPercentage"
                  alt="Form list loading progress"
        ></Progress>
        <div v-else>

            <!-- Mode and detail selector -->
            <div class="d-lg-flex align-items-center justify-content-center mb-4">
                <div class="me-2 text-primary">Mode:</div>
                <div class="me-4 btn-group" role="group" aria-label="Filter mode">
                    <input type="radio" class="btn-check" name="filter" id="filter_mastered" autocomplete="off"
                           v-model="filters.global.value" value="mastered"
                    >
                    <label class="btn btn-outline-secondary" for="filter_mastered">Mastered Forms</label>

                    <input type="radio" class="btn-check" name="filter" id="filter_unmastered" autocomplete="off"
                           v-model="filters.global.value" value="unmastered"
                    >
                    <label class="btn btn-outline-secondary" for="filter_unmastered">Un-mastered Forms</label>

                    <input type="radio" class="btn-check" name="filter" id="filter_none" autocomplete="off"
                           v-model="filters.global.value" value="none"
                    >
                    <label class="btn btn-outline-secondary" for="filter_none">All Forms</label>

                </div>

                <div class="me-2 text-primary">Detail:</div>
                <div class="btn-group" role="group" aria-label="Detail mode">
                    <input type="radio" class="btn-check" name="detail" id="detail_off" autocomplete="off"
                           v-model="detailedOutput" :value="false"
                    >
                    <label class="btn btn-outline-secondary" for="detail_off">Simplify Lists</label>

                    <input type="radio" class="btn-check" name="detail" id="detail_on" autocomplete="off"
                           v-model="detailedOutput" :value="true"
                    >
                    <label class="btn btn-outline-secondary" for="detail_on">Detail by part</label>
                </div>
            </div>
            <!-- Target rows -->
            <template v-for="(target, index) in targets">
                <div v-if="index <= highestUsedTargetIndex() + 1" class="d-lg-flex align-items-center mb-2">
                    <div class="flex-grow-1">
                        <span class="text-primary">Target {{ index + 1 }}: </span>
                        <template v-if="target.name">
                            {{ target.name }}
                        </template>
                        <template v-else>
                            No Target Selected
                        </template>

                    </div>

                    <div>
                        <div v-if="target.loading" class="me-2">
                            <spinner></spinner>
                        </div>
                        <div v-if="target.error" class="me-2 text-danger">
                            Can't display: {{ target.error }}
                        </div>
                    </div>

                    <div>
                        <button class="btn btn-primary me-lg-2" @click="launchChangeTarget(index)">
                            <i class="fas fa-search btn-icon-left"></i>Select Target
                        </button>

                        <button class="btn btn-primary me-lg-2" @click="clearTarget(index)" :disabled="!target.name">
                            <i class="fas fa-close btn-icon-left"></i>Clear Target
                        </button>
                    </div>

                </div>
            </template>

            <hr>

            <div :style="{ height: '75vh' }">
                <DataTable :value="formDatabase" dataKey="name" size="small" stripedRows scrollable
                           scrollHeight="flex"
                           v-model:filters="filters" filterDisplay="row" :globalFilterFields="['name']"
                           tableStyle="min-width: 50rem"
                >
                    <Column header="Name" field="name" class="fw-bold" frozen :sortable="true" style="min-width: 12rem">
                        <template #filter="{ filterModel, filterCallback }">
                            <input v-model.lazy="filterModel.value" type="text" @input="filterCallback()"
                                   class="p-column-filter" placeholder="Search by name"
                            />
                        </template>
                    </Column>
                    <Column header="Gender" field="gender" :sortable="true" style="min-width: 8rem">
                        <template #body="{ data }">
                            <i class="fa-solid" :class="genderClassForForm((data as Form))"></i>
                            {{ capital((data as Form).gender) }}
                        </template>
                    </Column>
                    <Column header="Size" field="size" class="text-end" :sortable="true"></Column>
                    <Column header="Cock Count" field="cockCount" class="text-end" :sortable="true"></Column>
                    <Column header="Cock Size" field="cockSize" class="text-end" :sortable="true"></Column>
                    <Column header="Ball Count" field="ballCount" class="text-end" :sortable="true"></Column>
                    <Column header="Ball Size" field="ballSize" class="text-end" :sortable="true"></Column>
                    <Column header="Cunt Count" field="cuntCount" class="text-end" :sortable="true"></Column>
                    <Column header="Cunt Size" field="cuntSize" class="text-end" :sortable="true"></Column>
                    <Column header="Clit Count" field="clitCount" class="text-end" :sortable="true"></Column>
                    <Column header="Clit Size" field="clitSize" class="text-end" :sortable="true"></Column>
                    <Column header="Breast Count" field="breastCount" class="text-end" :sortable="true"></Column>
                    <Column header="Breast Size" field="breastSize" class="text-end" :sortable="true"></Column>
                    <Column header="Say Verb" field="sayVerb" class="text-end" :sortable="true"></Column>
                    <Column header="Holiday" field="holiday" class="text-end" :sortable="true"></Column>
                    <Column header="Tags" field="tags" style="min-width: 12rem">
                        <template #body="{ data }">
                            {{ (data as Form).tags?.join(', ') }}
                        </template>
                        <template #filter="{ filterModel, filterCallback }">
                            <input v-model.lazy="filterModel.value" type="text" @input="filterCallback()"
                                   class="p-column-filter" placeholder="Search by tag"
                            />
                        </template>
                    </Column>
                    <Column header="Flags" field="flags" style="min-width: 12rem">
                        <template #body="{ data }">
                            <template v-if="detailedOutput" v-for="(nestedList, bodyPart) in (data as Form).flags">
                                <div>
                                    <span class="text-primary">
                                        {{ capital(bodyPart) }}
                                    </span>: {{ nestedList.join(', ') }}
                                </div>
                            </template>
                            <template v-else>{{ outputNestedListItemsOnly((data as Form).flags) }}</template>
                        </template>
                        <template #filter="{ filterModel, filterCallback }">
                            <input v-model.lazy="filterModel.value" type="text" @input="filterCallback()"
                                   class="p-column-filter" placeholder="Search by flag"
                            />
                        </template>
                    </Column>
                    <Column header="Powers" field="powers" style="min-width: 12rem">
                        <template #body="{ data }">
                            <template v-if="detailedOutput" v-for="(nestedList, bodyPart) in (data as Form).powers">
                                <div>
                                    <span class="text-primary">
                                        {{ capital(bodyPart) }}
                                    </span>: {{ nestedList.join(', ') }}
                                </div>
                            </template>
                            <template v-else>{{ outputNestedListItemsOnly((data as Form).powers) }}</template>
                            <template v-for="(nestedList, requirement) in (data as Form).powersBonus">
                                <div>
                                    <span class="text-secondary">
                                        {{ requirement }} parts
                                    </span>: {{ nestedList.join(', ') }}
                                </div>
                            </template>
                        </template>
                        <template #filter="{ filterModel, filterCallback }">
                            <input v-model.lazy="filterModel.value" type="text" @input="filterCallback()"
                                   class="p-column-filter" placeholder="Search by power"
                            />
                        </template>
                    </Column>
                    <Column header="Local Stats" field="lstats" style="min-width: 12rem">
                        <template #body="{ data }">
                            <template v-if="detailedOutput" v-for="(nestedList, localStat) in (data as Form).lstats">
                                <div>
                                    <span class="text-primary">
                                        {{ capital(localStat) }}
                                    </span>: {{ nestedList.join(', ') }}
                                </div>
                            </template>
                            <template v-else>{{ outputNestedListKeysOnly((data as Form).lstats) }}</template>
                        </template>
                    </Column>
                    <Column header="Kemo Support" field="kemo" :sortable="true">
                        <template #body="{ data }">
                            <template v-if="detailedOutput">
                                {{ (data as Form).kemo?.join(', ') }}
                            </template>
                            <template v-else>
                                <i class="fa-solid fa-check w-100 text-center"
                                   v-if="(data as Form).kemo?.length"
                                ></i>
                            </template>
                        </template>
                    </Column>
                    <Column header="Chubby Support" field="chubby" :sortable="true">
                        <template #body="{ data }">
                            <template v-if="detailedOutput">
                                {{ (data as Form).chubby?.join(', ') }}
                            </template>
                            <template v-else>
                                <i class="fa-solid fa-check w-100 text-center"
                                   v-if="(data as Form).chubby?.length"
                                ></i>
                            </template>
                        </template>
                    </Column>
                    <Column header="Color Support" field="color" :sortable="true">
                        <template #body="{ data }">
                            <template v-if="detailedOutput">
                                {{ (data as Form).color?.join(', ') }}
                            </template>
                            <template v-else>
                                <i class="fa-solid fa-check w-100 text-center"
                                   v-if="(data as Form).color?.length > 0"
                                ></i>
                            </template>

                        </template>
                    </Column>
                    <Column header="Arm Divider" field="armDivider" :sortable="true">
                        <template #body="{ data }">
                            <i class="fa-solid fa-check w-100 text-center"
                               v-if="(data as Form).dividers?.indexOf('arm') >= 0"
                            ></i>
                        </template>
                    </Column>
                    <Column header="Leg Divider" field="legDivider" :sortable="true">
                        <template #body="{ data }">
                            <i class="fa-solid fa-check w-100 text-center"
                               v-if="(data as Form).dividers?.indexOf('leg') >= 0"
                            ></i>
                        </template>
                    </Column>
                    <Column header="Tail Divider" field="tailDivider" :sortable="true">
                        <template #body="{ data }">
                            <i class="fa-solid fa-check w-100 text-center"
                               v-if="(data as Form).dividers?.indexOf('tail') >= 0"
                            ></i>
                        </template>
                    </Column>
                    <Column header="Private" field="private" :sortable="true">
                        <template #body="{ data }">
                            <i class="fa-solid fa-check w-100 text-center" v-if="(data as Form).private"></i>
                        </template>
                    </Column>
                    <Column header="No Mastering" field="noMastering" :sortable="true">
                        <template #body="{ data }">
                            <i class="fa-solid fa-check w-100 text-center" v-if="(data as Form).noMastering"></i>
                        </template>
                    </Column>
                    <Column header="No Funnel" field="noFunnel" :sortable="true">
                        <template #body="{ data }">
                            <i class="fa-solid fa-check w-100 text-center" v-if="(data as Form).noFunnel"></i>
                        </template>
                    </Column>
                    <Column header="No Reward" field="noReward" :sortable="true">
                        <template #body="{ data }">
                            <i class="fa-solid fa-check w-100 text-center" v-if="(data as Form).noReward"></i>
                        </template>
                    </Column>
                    <Column header="No Zap" field="noZap" :sortable="true">
                        <template #body="{ data }">
                            <i class="fa-solid fa-check w-100 text-center" v-if="(data as Form).noZap"></i>
                        </template>
                    </Column>
                    <Column header="No Native" field="noNative" :sortable="true">
                        <template #body="{ data }">
                            <i class="fa-solid fa-check w-100 text-center" v-if="(data as Form).noNative"></i>
                        </template>
                    </Column>
                    <Column header="No Extract" field="noExtract" :sortable="true">
                        <template #body="{ data }">
                            <i class="fa-solid fa-check w-100 text-center" v-if="(data as Form).noExtract"></i>
                        </template>
                    </Column>
                    <Column header="Bypass Immune" field="bypassImmune" :sortable="true">
                        <template #body="{ data }">
                            <i class="fa-solid fa-check w-100 text-center" v-if="(data as Form).bypassImmune"></i>
                        </template>
                    </Column>
                    <!-- Staff -->
                    <template v-if="staff">
                        <Column header="Placement" field="placement" style="min-width: 25rem">
                            <template #body="{ data }">
                                <div v-for="placement in (data as Form).placement">{{ placement }}</div>
                            </template>
                        </Column>
                        <Column header="Placement Note" field="placementNote" style="min-width: 12rem"
                        ></Column>
                        <Column header="Power Note" field="powerNote" style="min-width: 12rem"></Column>
                        <Column header="Special Note" field="specialNote" style="min-width: 12rem"></Column>
                    </template>
                    <!-- Comparison -->
                    <template v-if="highestUsedTargetIndex() > 0" v-for="target in targets">
                        <Column v-if="target.name"
                                :header="target.name"
                        >
                            <template #body="{ data }">
                                <i class="fa-solid fa-check w-100 text-center"
                                   v-if="target.forms && target.forms[(data as Form).name]"
                                ></i>
                            </template>
                        </Column>
                    </template>
                </Datatable>
            </div>

            <div v-if="unknownForms" class="mt-4 alert alert-warning">
                <div>Form mastery was found for the following forms but no information on them was available:</div>
                <div>{{ unknownForms }}</div>
                <div>(This might just mean the form hasn't been released yet.)</div>
            </div>

        </div>
    </div>
    <modal-confirmation ref="changeTargetModal" title="Change Target"
                        yes-label="Search" no-label="Cancel" @yes="changeTarget"
    >
        <div class="mb-2">
            <label for="changeTargetInput" class="form-label">Enter the name of the player you want to view:</label>
            <input type="text" class="form-control" id="changeTargetInput" v-model="changeTargetName">
        </div>
    </modal-confirmation>

</template>

<style scoped>
</style>
