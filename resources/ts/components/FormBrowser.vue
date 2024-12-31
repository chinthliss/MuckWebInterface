<script setup lang="ts">

import {ref, Ref, computed} from "vue";
import Progress from "./Progress.vue";
import {arrayToList, arrayToStringWithNewlines, capital} from "../formatting";
import Spinner from "./Spinner.vue";
import ModalConfirmation from "./ModalConfirmation.vue";

import DataTable from 'datatables.net-vue3';
import DataTablesLib, {Api, Config as DataTableOptions} from 'datatables.net-bs5';
import 'datatables.net-fixedcolumns-bs5';
import {DataTablesNamedSlotProps} from "../defs";
DataTable.use(DataTablesLib);

const props = defineProps<{
    startingPlayerName?: string,
    staff?: boolean,
    helpRoot?: string
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
    specialNote?: string,
    // Field we'll add manually so we can trigger updates.
    // For some reason updates won't happen unless we change something on the row
    _detail?: boolean,
    // And whether the present target has the form
    _target0: boolean,
    _target1: boolean,
    _target2: boolean,
    _target3: boolean
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

const targets: Ref<[Target | null, Target | null, Target | null, Target | null]> = ref([null, null, null, null]);
const changeTargetModal: Ref<InstanceType<typeof ModalConfirmation> | null> = ref(null);
const changeTargetIndex: Ref<number> = ref(0);
const changeTargetName: Ref<string> = ref('');

const filters = ref({
    name: '',
    gender: '',
    powers: '',
    tags: '',
    flags: '',
    global: 'mastered'
});

const sections = ref({
    parts: false,
    extra: true,
    supports: false,
    restrictions: false,
    staff: props.staff,
    mastery: true
});

const updateFilterOnColumn = (columnName: string, filter: string) => {
    if (dtApi) {
        let column = dtApi.columns(`${columnName}:name`);
        column.search(filter).draw();
    }
}
const updateFilterForName = () => updateFilterOnColumn('name', filters.value.name);
const updateFilterForTags = () => updateFilterOnColumn('tags', filters.value.tags);
const updateFilterForFlags = () => updateFilterOnColumn('flags', filters.value.flags)
const updateFilterForPowers = () => updateFilterOnColumn('powers', filters.value.powers)

const updateFilterForMode = () => {
    if (dtApi) {
        dtApi.draw();
    }
}

const changeDetailMode = () => {
    for (const form of formDatabase.value) {
        form._detail = detailedOutput.value;
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

/**
 * Start and stop indexes for which columns to show/hide per section
 */
const sectionConfiguration = {
    parts: [5, 6, 7, 8, 9, 10, 11, 12, 13, 14],
    extra: [15, 16, 17],
    supports: [18, 19, 20, 21, 22, 23, 24],
    restrictions: [25, 26, 27, 28, 29, 30, 31, 32],
    staff: [33, 34, 35, 36],
    mastery: [37, 38, 39, 40]
}

const tableOptions: DataTableOptions = {
    paging: false,
    fixedColumns: {
        start: 1
    },
    layout: {
        topEnd: null
    },
    language: {
        emptyTable: "No forms to view."
    },
    scrollY: '400px',
    scrollX: true,
    columns: [
        // Core
        {data: 'name', name: 'name'},
        {data: 'gender', name: 'gender'},
        {data: 'size', defaultContent: ''},
        {data: 'tags', name: 'tags', render: renderList},
        {data: 'flags', name: 'flags'},
        // Parts
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
        {data: 'powers', name: 'powers'},
        {data: 'lstats', name: 'lstats'},
        {data: 'kemo', name: 'kemo'},
        {data: 'chubby', name: 'chubby'},
        {data: 'color', name: 'color'},
        {data: null, name: 'arm-divider'},
        {data: null, name: 'leg-divider'},
        {data: null, name: 'tail-divider'},
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
        {data: '_target0', name: 'target0', visible: false},
        {data: '_target1', name: 'target1', visible: false},
        {data: '_target2', name: 'target2', visible: false},
        {data: '_target3', name: 'target3', visible: false}

    ],
    initComplete: () => {
        dtApi = new DataTablesLib.Api('table');
        updateSectionDisplay();
        dtApi.search.fixed('mode', (_searchString: string, form: Form) => {
            // Since we have up to 4 compare targets, we need to figure out if any of them have the form
            /*
            let masteredCount = 0;
            for (const target of targets.value) {
                if (target && target.forms && target.forms[form.name]) masteredCount++;
            }
            */
            // Optimisation - since we had to cache them, may as well use the existing target meta information
            let masteredCount = Number(form._target0) + Number(form._target1)
                + Number(form._target2) + Number(form._target3);

            if (filters.value.global === 'mastered' && !masteredCount) return false;
            if (filters.value.global === 'unmastered' && masteredCount) return false;

            if (!props.staff) {
                if (form.staffonly) return false;
                // Only show private forms that are present
                if (form.private && !masteredCount) return false;
            }

            return true;
        })

    }
};

const okayToShowTarget = (target: Target | null): boolean => {
    return (target !== null && !target.loading && !target.error);
}

const updateSectionDisplay = () => {
    if (!dtApi) return;
    for (const section in sections.value) {
        const showColumn = sections.value[section as keyof typeof sections.value];
        const columnIndexes = sectionConfiguration[section as keyof typeof sectionConfiguration];
        if (section !== 'mastery') {
            // Simply toggle all columns
            dtApi.columns(columnIndexes).visible(showColumn);
        } else {
            // These four all only show if on AND they have a target, so we have to set by column
            for (let i = 0; i < 4; i++) {
                const target = targets.value[i];
                const column = dtApi.column(columnIndexes[i]);
                column.visible(showColumn && okayToShowTarget(target));
            }
        }
    }
    // Update column sizes from any changes
    dtApi.columns.adjust().draw();

}

/**
 * Updates the cached target fields on each form and column visibility
 */
const updateTargetDisplay = () => {
    if (!dtApi) return;
    sections.value.mastery = true; // Assuming we're showing the section if we triggered an udpate
    for (let i = 0; i < 4; i++) {
        let target = targets.value[i];
        let column = dtApi.column(`target${i}:name`);
        if (okayToShowTarget(target)) {
            column.visible(true, false);
            let element = column.header(1);
            for (const child of element.getElementsByClassName('dt-column-title')) {
                child.innerHTML = target?.name || 'Unset';
            }
            // Update the values for each form
            for (const form of formDatabase.value) {
                // @ts-ignore -- because I can't find the proper way to do this
                form[`_target${i}` as keyof Form] = target?.forms ? form.name in target.forms : false;
            }
        } else {
            column.visible(false, false);
        }
    }
    // Update column sizes from any changes
    dtApi.columns.adjust().draw();
};

channel.on('formDatabase', (data: number) => {
    formDatabase.value = [];
    formsToLoadRemaining.value = data;
    formsToLoad.value = data;
});

channel.on('formListing', (data: Form) => {
    data._detail = false;
    data._target0 = false;
    data._target1 = false;
    data._target2 = false;
    data._target3 = false;
    formDatabase.value.push(data);
    formsToLoadRemaining.value--;
    if (!formsToLoadRemaining.value) updateTargetDisplay();
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
        if (target && target.name == data.who) {
            target.name = data.who;
            target.error = data.error;
            target.forms = data.forms;
            target.loading = false;
            updatedCount++;
        }
    }
    if (!updatedCount) {
        console.log("Unable to find a target to update for: ", data);
        return;
    } else updateTargetDisplay();
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
    targets.value[changeTargetIndex.value] = {
        name: changeTargetName.value,
        loading: true
    }
    channel.send('getFormMasteryOf', changeTargetName.value);
}

const launchChangeTarget = (index: number): void => {
    changeTargetIndex.value = index;
    if (changeTargetModal.value) changeTargetModal.value.show();
}

const clearTarget = (index: number) => {
    targets.value[index] = null;
    updateTargetDisplay();
}

const unknownForms = computed((): string => {
    const result = [];
    const formList = formDatabase.value.map((form) => {
        return form.name;
    });
    for (const target of targets.value) {
        if (!target) continue;
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
        if (targets.value[i]) result = i;
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

        <p class="lead">
            This page is for seeing which forms are in the game,
            as well as which ones you have or have not mastered yet.
        </p>
        <p>
            Forms can also be called infections, mutations or nanite strains depending upon context.
            They're an integral part of the game in that they represent the starting point for what you can become
            and host the powers you can obtain.
        </p>
        <p v-if="props.helpRoot">
            For more information see the help file
            '<a :href="props.helpRoot + '/Theme/Infection'">+help Theme/Infections</a>'.
        </p>

        <Progress v-if="loading" id="form-list-progress-bar"
                  :percentage="loadingPercentage"
                  alt="Form list loading progress"
        ></Progress>
        <div v-else>
            <!-- Section selector -->
            <div class="d-lg-flex align-items-center justify-content-center mb-3">
                <div class="me-2 text-primary">Toggle Sections:</div>
                <div class="btn-group" role="group" aria-label="Toggle buttons for sections to display">
                    <input type="checkbox" class="btn-check" id="section_parts" autocomplete="off"
                           v-model="sections.parts" :value="true" @change="updateSectionDisplay"
                    >
                    <label class="btn btn-outline-primary" for="section_parts">Part Counts and Sizes</label>

                    <input type="checkbox" class="btn-check" id="section_extra" autocomplete="off"
                           v-model="sections.extra" :value="true" @change="updateSectionDisplay"
                    >
                    <label class="btn btn-outline-primary" for="section_extra">Extra Information</label>

                    <input type="checkbox" class="btn-check" id="section_supports" autocomplete="off"
                           v-model="sections.supports" :value="true" @change="updateSectionDisplay"
                    >
                    <label class="btn btn-outline-primary" for="section_supports">Supports</label>

                    <input type="checkbox" class="btn-check" id="section_restrictions" autocomplete="off"
                           v-model="sections.restrictions" :value="true" @change="updateSectionDisplay"
                    >
                    <label class="btn btn-outline-primary" for="section_restrictions">Restrictions</label>

                    <template v-if="props.staff">
                        <input type="checkbox" class="btn-check" id="section_staff" autocomplete="off"
                               v-model="sections.staff" :value="true" @change="updateSectionDisplay"
                        >
                        <label class="btn btn-outline-primary" for="section_staff">Staff</label>
                    </template>

                    <input type="checkbox" class="btn-check" id="section_mastery" autocomplete="off"
                           v-model="sections.mastery" :value="true" @change="updateSectionDisplay"
                    >
                    <label class="btn btn-outline-primary" for="section_mastery">Mastery</label>

                </div>
            </div>

            <!-- Mode and detail level selector -->
            <div class="d-lg-flex align-items-center justify-content-center mb-3">
                <div class="me-2 text-primary">Mode:</div>
                <div class="me-4 btn-group" role="group" aria-label="Filter mode buttons">
                    <input type="radio" class="btn-check" name="filter" id="filter_mastered" autocomplete="off"
                           v-model="filters.global" value="mastered" @change="updateFilterForMode"
                    >
                    <label class="btn btn-outline-secondary" for="filter_mastered">Mastered Forms</label>

                    <input type="radio" class="btn-check" name="filter" id="filter_unmastered" autocomplete="off"
                           v-model="filters.global" value="unmastered" @change="updateFilterForMode"
                    >
                    <label class="btn btn-outline-secondary" for="filter_unmastered">Un-mastered Forms</label>

                    <input type="radio" class="btn-check" name="filter" id="filter_none" autocomplete="off"
                           v-model="filters.global" value="none" @change="updateFilterForMode"
                    >
                    <label class="btn btn-outline-secondary" for="filter_none">All Forms</label>

                </div>

                <div class="me-2 text-primary">Detail:</div>
                <div class="btn-group" role="group" aria-label="Detail mode buttons">
                    <input type="radio" class="btn-check" name="detail" id="detail_off" autocomplete="off"
                           v-model="detailedOutput" :value="false" @change="changeDetailMode"
                    >
                    <label class="btn btn-outline-secondary" for="detail_off">Simplify Lists</label>

                    <input type="radio" class="btn-check" name="detail" id="detail_on" autocomplete="off"
                           v-model="detailedOutput" :value="true" @change="changeDetailMode"
                    >
                    <label class="btn btn-outline-secondary" for="detail_on">Detail by part</label>
                </div>
            </div>

            <DataTable id="table" class="table table-dark table-hover table-striped"
                       :options="tableOptions" :data="formDatabase"
            >
                <thead>
                <tr>
                    <th colspan="1" data-dt-order="disable"></th> <!-- Sticky column needs to be separate -->
                    <th colspan="4" data-dt-order="disable" class="text-muted">Core</th>
                    <th colspan="10" data-dt-order="disable" class="text-muted">Part Counts and Size</th>
                    <th colspan="3" data-dt-order="disable" class="text-muted">Extra Information</th>
                    <th colspan="7" data-dt-order="disable" class="text-muted">Supports</th>
                    <th colspan="8" data-dt-order="disable" class="text-muted">Restrictions</th>
                    <th colspan="4" data-dt-order="disable" class="text-muted">Staff</th>
                    <th colspan="4" data-dt-order="disable" class="text-muted">Mastery</th>
                </tr>
                <tr>
                    <th>Form</th>
                    <th>Gender</th>
                    <th>Size</th>
                    <th>Tags</th>
                    <th>Flags</th>

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
                    <th>Can't Nativize</th>
                    <th>Can't Extract</th>
                    <th>Not Rewarded</th>
                    <th>Bypass Immunity</th>

                    <th>Placement</th>
                    <th>Placement Note</th>
                    <th>Power Note</th>
                    <th>Special Note</th>

                    <!-- Target names. These are set dynamically -->
                    <th>Pending</th>
                    <th>Pending</th>
                    <th>Pending</th>
                    <th>Pending</th>
                </tr>
                <!-- Second header row is to host search boxes and is mostly blank -->
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

                    <th colspan="4" data-dt-order="disable"></th>
                </tr>
                </thead>

                <template #column-gender="dt: DataTablesNamedSlotProps">
                    <i class="fa-solid" :class="genderClassForForm((dt.rowData as Form))"></i>
                    {{ capital((dt.rowData as Form).gender) }}
                </template>

                <template #column-flags="dt: DataTablesNamedSlotProps">
                    <template v-if="detailedOutput" v-for="(nestedList, bodyPart) in (dt.rowData as Form).flags">
                        <div>
                    <span class="text-primary">
                        {{ capital(bodyPart as string) }}
                    </span>: {{ nestedList.join(', ') }}
                        </div>
                    </template>
                    <template v-else>{{ renderNestedListItemsOnly((dt.rowData as Form).flags) }}</template>
                </template>

                <template #column-powers="dt: DataTablesNamedSlotProps">
                    <template v-if="detailedOutput" v-for="(nestedList, bodyPart) in (dt.rowData as Form).powers">
                        <div>
                    <span class="text-primary">
                        {{ capital(bodyPart as string) }}
                    </span>: {{ nestedList.join(', ') }}
                        </div>
                    </template>
                    <template v-else>{{ renderNestedListItemsOnly((dt.rowData as Form).powers) }}</template>
                </template>

                <template #column-lstats="dt: DataTablesNamedSlotProps">
                    <template v-if="detailedOutput" v-for="(nestedList, localStat) in (dt.rowData as Form).lstats">
                        <div>
                    <span class="text-primary">
                        {{ capital(localStat as string) }}
                    </span>: {{ nestedList.join(', ') }}
                        </div>
                    </template>
                    <template v-else>{{ renderNestedListKeysOnly((dt.rowData as Form).lstats) }}</template>
                </template>

                <template #column-kemo="dt: DataTablesNamedSlotProps">
                    <template v-if="detailedOutput">
                        {{ (dt.rowData as Form).kemo?.join(', ') }}
                    </template>
                    <template v-else>
                        <i class="fa-solid fa-check w-100 text-center" v-if="(dt.rowData as Form).kemo?.length"></i>
                    </template>
                </template>

                <template #column-chubby="dt: DataTablesNamedSlotProps">
                    <template v-if="detailedOutput">
                        {{ (dt.rowData as Form).chubby?.join(', ') }}
                    </template>
                    <template v-else>
                        <i class="fa-solid fa-check w-100 text-center" v-if="(dt.rowData as Form).chubby?.length"></i>
                    </template>
                </template>

                <template #column-color="dt: DataTablesNamedSlotProps">
                    <template v-if="detailedOutput">
                        {{ (dt.rowData as Form).color?.join(', ') }}
                    </template>
                    <template v-else>
                        <i class="fa-solid fa-check w-100 text-center" v-if="(dt.rowData as Form).color?.length"></i>
                    </template>
                </template>

                <template #column-arm-divider="dt: DataTablesNamedSlotProps">
                    <i class="fa-solid fa-check w-100 text-center"
                       v-if="(dt.rowData as Form).dividers?.indexOf('arm') >= 0"
                    ></i>
                </template>

                <template #column-leg-divider="dt: DataTablesNamedSlotProps">
                    <i class="fa-solid fa-check w-100 text-center"
                       v-if="(dt.rowData as Form).dividers?.indexOf('leg') >= 0"
                    ></i>
                </template>

                <template #column-tail-divider="dt: DataTablesNamedSlotProps">
                    <i class="fa-solid fa-check w-100 text-center"
                       v-if="(dt.rowData as Form).dividers?.indexOf('tail') >= 0"
                    ></i>
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

                <template #column-target0="dt: DataTablesNamedSlotProps">
                    <i class="fa-solid fa-check w-100 text-center" v-if="(dt.rowData as Form)._target0"></i>
                </template>

                <template #column-target1="dt: DataTablesNamedSlotProps">
                    <i class="fa-solid fa-check w-100 text-center" v-if="(dt.rowData as Form)._target1"></i>
                </template>

                <template #column-target2="dt: DataTablesNamedSlotProps">
                    <i class="fa-solid fa-check w-100 text-center" v-if="(dt.rowData as Form)._target2"></i>
                </template>

                <template #column-target3="dt: DataTablesNamedSlotProps">
                    <i class="fa-solid fa-check w-100 text-center" v-if="(dt.rowData as Form)._target3"></i>
                </template>


            </Datatable>

            <div v-if="unknownForms" class="mt-4 alert alert-warning">
                <div>Form mastery was found for the following forms but no information on them was available:</div>
                <div>{{ unknownForms }}</div>
                <div>(This might just mean the form hasn't been released yet.)</div>
            </div>

            <!-- Target rows -->
            <hr>
            <h2>Targets</h2>
            <p>
                By default this page will show your mastered/unmastered forms.
                Here you can change that, as well as set up to 4 targets to compare.
                Mastery for each target shows as the last columns on the table.
            </p>
            <p v-if="!props.staff">You need to have permission to see each target's form list in order for this to work.</p>
            <template v-for="(target, index) in targets">
                <div v-if="index <= highestUsedTargetIndex() + 1" class="d-lg-flex align-items-center mb-2">
                    <div class="flex-grow-1">
                        <span class="text-primary">Target {{ index + 1 }}: </span>
                        <template v-if="target">
                            {{ target.name }}
                        </template>
                        <template v-else>
                            No Target Selected
                        </template>

                    </div>

                    <div>
                        <div v-if="target?.loading" class="me-2">
                            <spinner></spinner>
                        </div>
                        <div v-if="target?.error" class="me-2 text-danger">
                            Can't display: {{ target.error }}
                        </div>
                    </div>

                    <div>
                        <button class="btn btn-primary me-lg-2" @click="launchChangeTarget(index)">
                            <i class="fas fa-search btn-icon-left"></i>Select Target
                        </button>

                        <button class="btn btn-primary me-lg-2" @click="clearTarget(index)"
                                :disabled="!target || !index"
                        >
                            <i class="fas fa-close btn-icon-left"></i>Clear Target
                        </button>
                    </div>

                </div>
            </template>

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
    @import 'datatables.net-fixedcolumns-bs5';

    .form-control {
        min-width: 180px;
    }
</style>
