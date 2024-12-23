<script setup lang="ts">

import {ref, Ref, computed} from "vue";
import Progress from "./Progress.vue";
import {arrayToList, arrayToStringWithNewlines, capital} from "../formatting";
import Spinner from "./Spinner.vue";
import ModalConfirmation from "./ModalConfirmation.vue";

import DataTable from 'datatables.net-vue3';
import DataTablesLib, {Api, Config as DataTableOptions} from 'datatables.net-bs5';
import {DataTablesNamedSlotProps} from "../defs";
DataTable.use(DataTablesLib);

const props = defineProps<{
    startingPlayerName?: string,
    staff?: boolean
}>();

type Form = {
    name: string,
    private?: number, // We'll list private forms IF they're mastered
    gender: string,
    size: number,
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

type Target = {
    name?: string,
    loading?: boolean,
    error?: string,
    forms?: { [form: string]: number }
}

let dtApi: Api | null = null;
const formDatabase: Ref<Form[]> = ref([]);
const channel = mwiWebsocket.channel('forms');
const formsToLoad: Ref<number | null> = ref(null);
const formsToLoadRemaining: Ref<number> = ref(0);
const detailedOutput: Ref<boolean> = ref(false);

const targets: Ref<[Target, Target, Target, Target]> = ref([{}, {}, {}, {}]);
const changeTargetModal: Ref<InstanceType<typeof ModalConfirmation> | null> = ref(null);
const changeTargetIndex: Ref<number> = ref(0);
const changeTargetName: Ref<string> = ref('');

const filters = ref({
    name: '',
    gender: '',
    powers: '',
    tags: '',
    flags: '',
    global: ''
});

const updateFilterForName = () => {
    if (dtApi) {
        let nameColumn = dtApi.columns('name:name');
        nameColumn.search(filters.value.name).draw();
    }
}

const updateFilterForTags = () => {
    if (dtApi) {
        let nameColumn = dtApi.columns('tags:name');
        nameColumn.search(filters.value.name).draw();
    }
}

const updateFilterForFlags = () => {
    if (dtApi) {
        let nameColumn = dtApi.columns('flags:name');
        nameColumn.search(filters.value.name).draw();
    }
}

const updateFilterForPowers = () => {
    if (dtApi) {
        let nameColumn = dtApi.columns('powers:name');
        nameColumn.search(filters.value.name).draw();
    }
}

const renderList = (data: string[], _type: string, _row: any, _meta: object) => {
    return arrayToList(data);
}

const renderListWithNewLines = (data: string[], _type: string, _row: any, _meta: object) => {
    return arrayToStringWithNewlines(data);
}


const renderNestedListItemsOnly = (nestedList: { [lstat: string]: string[] }): string => {
    if (!nestedList) return '';
    const result = [];
    for (const key in nestedList) {
        for (const value of nestedList[key]) {
            if (result.indexOf(value) == -1) result.push(value);
        }
    }
    return result.join(', ');
}

const renderNestedListKeysOnly = (nestedList: { [lstat: string]: string[] } | undefined ): string => {
    if (!nestedList) return '';
    const result = [];
    for (const key in nestedList) {
        if (result.indexOf(key) == -1) result.push(key);
    }
    return result.join(', ');
}

const tableOptions: DataTableOptions = {
    info: false,
    paging: false,
    layout: {
        topEnd: null
    },
    language: {
        emptyTable: "No forms to view."
    },
    scrollY: '400px',
    columns: [
        {data: 'name', name: 'name'},
        {data: 'gender', name: 'gender'},
        {data: 'size', defaultContent: ''},
        {data: 'cockCount'},
        {data: 'cockSize'},
        {data: 'ballCount'},
        {data: 'ballSize'},
        {data: 'cuntCount'},
        {data: 'cuntSize'},
        {data: 'clitCount'},
        {data: 'clitSize'},
        {data: 'breastCount'},
        {data: 'breastSize'},
        {data: 'sayVerb'},
        {data: 'holiday', defaultContent: ''},
        {data: 'tags', name: 'tags', render: renderList},
        {data: 'flags', name: 'flags', render: renderNestedListItemsOnly},
        {data: 'powers', name: 'powers', render: renderNestedListItemsOnly},
        {data: 'lstats', render: renderNestedListKeysOnly},
        {data: 'kemo', render: renderList},
        {data: 'chubby', render: renderList},
        {data: 'color', render: renderList},
        {data: null},
        {data: null},
        {data: null},
        {data: 'private', name: 'private', defaultContent: ''},
        {data: 'noMastering', name: 'no-master'},
        {data: 'noFunnel', name: 'no-funnel'},
        {data: 'noZap', name: 'no-zap'},
        {data: 'noNative', name: 'no-native'},
        {data: 'noExtract', name: 'no-extract'},
        {data: 'noReward', name: 'no-reward'},
        {data: 'bypassImmune', name: 'bypass-immunity'},
        // Staff Only
        {data: 'placement', defaultContent: '', visible: props.staff, render: renderListWithNewLines},
        {data: 'placementNote', defaultContent: '', visible: props.staff},
        {data: 'powerNote', defaultContent: '', visible: props.staff},
        {data: 'specialNote', defaultContent: '', visible: props.staff},
        // Comparison targets
        {data: 'target1', defaultContent: '', visible: false},
        {data: 'target2', defaultContent: '', visible: false},
        {data: 'target3', defaultContent: '', visible: false},
        {data: 'target4', defaultContent: '', visible: false}

    ],
    initComplete: () => {
        dtApi = new DataTablesLib.Api('table');
    }
};

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

const highestUsedTargetIndex = () => {
    let result = 0;
    for (let i = 0; i < targets.value.length; i++) {
        if (targets.value[i].name) result = i;
    }
    return result;
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
                           v-model="filters.global" value="mastered"
                    >
                    <label class="btn btn-outline-secondary" for="filter_mastered">Mastered Forms</label>

                    <input type="radio" class="btn-check" name="filter" id="filter_unmastered" autocomplete="off"
                           v-model="filters.global" value="unmastered"
                    >
                    <label class="btn btn-outline-secondary" for="filter_unmastered">Un-mastered Forms</label>

                    <input type="radio" class="btn-check" name="filter" id="filter_none" autocomplete="off"
                           v-model="filters.global" value="none"
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

            <DataTable id="table" class="table table-dark table-hover table-striped"
                       :options="tableOptions" :data="formDatabase"
            >
                <thead>
                <tr>
                    <th>Form</th>
                    <th>Gender</th>
                    <th>Size</th>

                    <th>Cock Count</th>
                    <th>Cock Size</th>
                    <th>Ball Count</th>
                    <th>Ball Size</th>
                    <th>Cunt Count</th>
                    <th>Cunt Size</th>
                    <th>Clit Count</th>
                    <th>Clit Size</th>
                    <th>Breast Count</th>
                    <th>Breast Size</th>

                    <th>Say Verb</th>
                    <th>Holiday</th>
                    <th>Tags</th>
                    <th>Flags</th>
                    <th>Powers</th>

                    <th>Local Stats</th>
                    <th>Kemo Support</th>
                    <th>Chubby Support</th>
                    <th>Color Support</th>
                    <th>Arm Divider</th>
                    <th>Leg Divider</th>
                    <th>Tail Divider</th>

                    <th>Private</th>
                    <th>Can't Master</th>
                    <th>Can't Funnel</th>
                    <th>Can't Zap</th>
                    <th>Can't be Native</th>
                    <th>Can't Extract</th>
                    <th>Not Rewarded</th>
                    <th>Bypass Immunity</th>

                    <th>Placement</th>
                    <th>Placement Note</th>
                    <th>Power Note</th>
                    <th>Special Note</th>

                    <th>{{ targets[0].name }}</th>
                    <th>{{ targets[1].name }}</th>
                    <th>{{ targets[2].name }}</th>
                    <th>{{ targets[3].name }}</th>
                </tr>
                <tr>
                    <th data-dt-order="disable">
                        <input type="text" v-model="filters.name" @input="updateFilterForName"
                               class="form-control" placeholder="Search by name"
                        />
                    </th>
                    <th data-dt-order="disable"></th>
                    <th data-dt-order="disable"></th>
                    <th data-dt-order="disable"></th>
                    <th data-dt-order="disable"></th>
                    <th data-dt-order="disable"></th>
                    <th data-dt-order="disable"></th>
                    <th data-dt-order="disable"></th>
                    <th data-dt-order="disable"></th>
                    <th data-dt-order="disable"></th>
                    <th data-dt-order="disable"></th>
                    <th data-dt-order="disable"></th>
                    <th data-dt-order="disable"></th>
                    <th data-dt-order="disable"></th>
                    <th data-dt-order="disable"></th>
                    <th data-dt-order="disable">
                        <input type="text" v-model="filters.tags" @input="updateFilterForTags"
                               class="form-control" placeholder="Search by Tag"
                        />
                    </th>
                    <th data-dt-order="disable">
                        <input type="text" v-model="filters.flags" @input="updateFilterForFlags"
                               class="form-control" placeholder="Search by Flag"
                        />
                    </th>
                    <th data-dt-order="disable">
                        <input type="text" v-model="filters.powers" @input="updateFilterForPowers"
                               class="form-control" placeholder="Search by Power"
                        />
                    </th>
                    <th data-dt-order="disable"></th>
                    <th data-dt-order="disable"></th>
                    <th data-dt-order="disable"></th>
                    <th data-dt-order="disable"></th>
                    <th data-dt-order="disable"></th>
                    <th data-dt-order="disable"></th>
                    <th data-dt-order="disable"></th>
                    <th data-dt-order="disable"></th>
                    <th data-dt-order="disable"></th>
                    <th data-dt-order="disable"></th>
                    <th data-dt-order="disable"></th>
                    <th data-dt-order="disable"></th>
                    <th data-dt-order="disable"></th>
                    <th data-dt-order="disable"></th>
                    <th data-dt-order="disable"></th>
                    <th data-dt-order="disable"></th>
                    <th data-dt-order="disable"></th>
                    <th data-dt-order="disable"></th>
                    <th data-dt-order="disable"></th>
                    <th data-dt-order="disable"></th>
                    <th data-dt-order="disable"></th>
                    <th data-dt-order="disable"></th>
                    <th data-dt-order="disable"></th>
                </tr>
                </thead>

                <template #column-gender="dt: DataTablesNamedSlotProps">
                    <i class="fa-solid" :class="genderClassForForm((dt.rowData as Form))"></i>
                    {{ capital((dt.rowData as Form).gender) }}
                </template>

                <template #column-private="dt: DataTablesNamedSlotProps">
                    <i class="fa-solid fa-check w-100 text-center" v-if="(dt.rowData as Form).private"></i>
                </template>

                <template #column-no-master="dt: DataTablesNamedSlotProps">
                    <i class="fa-solid fa-check w-100 text-center" v-if="(dt.rowData as Form).noMastering"></i>
                </template>

                <template #column-no-funnel="dt: DataTablesNamedSlotProps">
                    <i class="fa-solid fa-check w-100 text-center" v-if="(dt.rowData as Form).noFunnel"></i>
                </template>

                <template #column-no-reward="dt: DataTablesNamedSlotProps">
                    <i class="fa-solid fa-check w-100 text-center" v-if="(dt.rowData as Form).noReward"></i>
                </template>

                <template #column-no-zap="dt: DataTablesNamedSlotProps">
                    <i class="fa-solid fa-check w-100 text-center" v-if="(dt.rowData as Form).noZap"></i>
                </template>

                <template #column-no-native="dt: DataTablesNamedSlotProps">
                    <i class="fa-solid fa-check w-100 text-center" v-if="(dt.rowData as Form).noNative"></i>
                </template>

                <template #column-no-extract="dt: DataTablesNamedSlotProps">
                    <i class="fa-solid fa-check w-100 text-center" v-if="(dt.rowData as Form).noExtract"></i>
                </template>

                <template #column-bypass-immunity="dt: DataTablesNamedSlotProps">
                    <i class="fa-solid fa-check w-100 text-center" v-if="(dt.rowData as Form).bypassImmune"></i>
                </template>

            </Datatable>

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
    .form-control {
        min-width: 180px;
    }
</style>
