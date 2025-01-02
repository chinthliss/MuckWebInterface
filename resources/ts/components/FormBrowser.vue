<script setup lang="ts">

// TODO: Figure out how to solve first keypress being 'eaten' issue on search filters.
import {computed, Ref, ref} from "vue";
import Progress from "./Progress.vue";
import {arrayToList, arrayToStringWithNewlines, capital} from "../formatting";
import Spinner from "./Spinner.vue";
import ModalConfirmation from "./ModalConfirmation.vue";

import DataTable from 'datatables.net-vue3';
import DataTablesLib, {Api, Config as DataTableOptions} from 'datatables.net-bs5';
import 'datatables.net-fixedcolumns-bs5';
import {DataTablesNamedSlotProps} from "../defs";
import ModalMessage from "./ModalMessage.vue";

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
    // Fields added by us
    // For some reason updates won't happen unless we change something on the row
    detail: boolean,
    // And whether the present target has the form
    target0: boolean,
    target1: boolean,
    target2: boolean,
    target3: boolean
}

type TagOrFlagToggle = {
    id: string,
    label: string,
    enabled: boolean
}
const tags: Ref<TagOrFlagToggle[]> = ref([]);
const flags: Ref<TagOrFlagToggle[]> = ref([]);

type Target = {
    name?: string,
    loading?: boolean,
    error?: string,
    forms: { [form: string]: number }
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
const editTagFilterModal: Ref<InstanceType<typeof ModalMessage> | null> = ref(null);
const editFlagFilterModal: Ref<InstanceType<typeof ModalMessage> | null> = ref(null);

const filters = ref({
    name: '',
    gender: '',
    powers: '',
    global: 'mastered'
});

type Section = {
    id: string,
    label: string,
    enabled: boolean,
    staffOnly: boolean,
    columnIndexes: number[]
}

const sections: Ref<Section[]> = ref([
    {
        id: 'parts', label: 'Part Counts and Sizes', enabled: false, staffOnly: false,
        columnIndexes: [5, 6, 7, 8, 9, 10, 11, 12, 13, 14]
    },
    {
        id: 'extra', label: 'Extra Information', enabled: false, staffOnly: false,
        columnIndexes: [15, 16, 17]
    },
    {
        id: 'supports', label: 'Supports', enabled: false, staffOnly: false,
        columnIndexes: [18, 19, 20, 21, 22, 23, 24]
    },
    {
        id: 'restrictions', label: 'Restrictions', enabled: false, staffOnly: false,
        columnIndexes: [25, 26, 27, 28, 29, 30, 31, 32]
    },
    {
        id: 'staff', label: 'Staff', enabled: false, staffOnly: true,
        columnIndexes: [33, 34, 35, 36]
    },
    {
        id: 'mastery', label: 'Mastery', enabled: false, staffOnly: false,
        columnIndexes: [37, 38, 39, 40]
    },
]);

const updateStringFilterOnColumn = (columnName: string, filter: string, boundary: boolean) => {
    if (dtApi) {
        let column = dtApi.columns(`${columnName}:name`);
        column.search(filter, {boundary: boundary}).draw();
    }
}
const updateFilterForName = () => updateStringFilterOnColumn('name', filters.value.name, false);
const updateFilterForPowers = () => updateStringFilterOnColumn('powers', filters.value.powers, false)
const updateFilterForTags = () => {
    const element = document.getElementById('tag-filter');
    if (element) element.innerHTML = tagFilter();
    if (dtApi) dtApi.columns(`tags:name`).draw();
}
const updateFilterForFlags = () => {
    if (!dtApi) return;
    const element = document.getElementById('flag-filter');
    if (element) element.innerHTML = flagFilter();
    if (dtApi) dtApi.columns(`flags:name`).draw();
}

const updateFilterForMode = () => {
    if (dtApi) {
        dtApi.draw();
    }
}

const changeDetailMode = () => {
    for (const form of formDatabase.value) {
        form.detail = detailedOutput.value;
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

const renderNestedListKeysOnly = (nestedList: { [lstat: string]: string[] } | undefined): string => {
    if (!nestedList) return '';
    const result = [];
    for (const key in nestedList) {
        if (result.indexOf(key) == -1) result.push(key);
    }
    return result.join(', ');
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
        emptyTable: "No forms to view.",
        info: "Showing _TOTAL_ _ENTRIES-TOTAL_"
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
        {data: 'sayVerb', defaultContent: ''},
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
        {data: 'target0', name: 'target0', visible: false},
        {data: 'target1', name: 'target1', visible: false},
        {data: 'target2', name: 'target2', visible: false},
        {data: 'target3', name: 'target3', visible: false}
    ],
    initComplete: () => {
        dtApi = new DataTablesLib.Api('table');
        updateSectionDisplay();
        // Global filter for the mode
        dtApi.search.fixed('mode', (_searchString: string, form: Form) => {
            // Since we have up to 4 compare targets, we need to figure out if any of them have the form
            /*
            let masteredCount = 0;
            for (const target of targets.value) {
                if (target && target.forms && target.forms[form.name]) masteredCount++;
            }
            */
            // Optimisation - since we had to cache them, may as well use the existing target meta information
            let masteredCount = Number(form.target0) + Number(form.target1)
                + Number(form.target2) + Number(form.target3);

            if (filters.value.global === 'mastered' && !masteredCount) return false;
            if (filters.value.global === 'unmastered' && masteredCount) return false;

            if (!props.staff) {
                if (form.staffonly) return false;
                // Only show private forms that are present
                if (form.private && !masteredCount) return false;
            }

            return true;
        });

        // Column filter for tags
        dtApi.column('tags:name').search.fixed('tags', (_searchString: string, form: Form) => {
            for (const tag of tags.value) {
                if (!tag.enabled) continue;
                if (!form.tags.includes(tag.id)) return false;
            }
            return true;
        });

        // Column filter for flags
        dtApi.column('flags:name').search.fixed('flags', (_searchString: string, form: Form) => {
            for (const flag of flags.value) {
                if (!flag.enabled) continue;
                let found = false;
                for (const bodypart in form.flags) {
                    if (form.flags[bodypart].includes(flag.id)) found = true;
                }
                if (!found) return false;
            }
            return true;
        });

    }
};

const okayToShowTarget = (target: Target | null): boolean => {
    return (target !== null && !target.loading && !target.error);
}

const updateSectionDisplay = () => {
    if (!dtApi) return;
    for (const section of sections.value) {
        if (section.id !== 'mastery') {
            // Simply toggle all columns
            dtApi.columns(section.columnIndexes).visible(section.enabled);
        } else {
            // These four all only show if on AND they have a target, so we have to set by column
            for (let i = 0; i < 4; i++) {
                const target = targets.value[i];
                const column = dtApi.column(section.columnIndexes[i]);
                column.visible(section.enabled && okayToShowTarget(target), false);
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
    // Make sure the mastery section is enabled - if we set a target we probably want to see it.
    for (const section of sections.value) {
        if (section.id == 'mastery') section.enabled = true;
    }
    for (let i = 0; i < 4; i++) {
        const target = targets.value[i];
        const column = dtApi.column(`target${i}:name`);
        // Update the values for each form
        for (const form of formDatabase.value) {
            // @ts-ignore -- because I can't find the proper way to do this
            form[`target${i}` as keyof Form] = target ? form.name in target.forms : null;
        }
        if (okayToShowTarget(target)) {
            column.visible(true, false);
            const element = column.header(1);
            for (const child of element.getElementsByClassName('dt-column-title')) {
                child.innerHTML = target?.name || 'Unset';
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
    data.detail = detailedOutput.value;
    data.target0 = false;
    data.target1 = false;
    data.target2 = false;
    data.target3 = false
    formDatabase.value.push(data);
    formsToLoadRemaining.value--;

    if (data.tags) { // Tags are a list
        for (const tag of data.tags) {
            const id = tag.toLowerCase();
            if (tags.value.findIndex(x => x.id == id) == -1) {
                tags.value.push({
                    id: id,
                    label: capital(tag),
                    enabled: false
                });
            }
        }
    }

    if (data.flags) { // Flags are a dict of which body part has then, so use the indexes
        for (const bodypart in data.flags) {
            for (const flag of data.flags[bodypart]) {
                const id = flag.toLowerCase();
                if (flags.value.findIndex(x => x.id == id) == -1) {
                    flags.value.push({
                        id: id,
                        label: capital(flag),
                        enabled: false
                    });
                }
            }
        }
    }

    if (!formsToLoadRemaining.value) {
        updateTargetDisplay();
        tags.value.sort((a, b) => {
            return a.id > b.id ? 1 : -1
        });
        flags.value.sort((a, b) => {
            return a.id > b.id ? 1 : -1
        });
    }
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
            if (data.error) target.error = data.error;
            else delete target.error;
            if (data.forms) target.forms = data.forms;
            else target.forms = {};
            target.loading = false;
            updatedCount++;
        }
    }
    if (!updatedCount) {
        console.log("Unable to find a target to update for: ", data);
        return;
    } else updateTargetDisplay();
});

const loading = computed((): boolean => {
    return (!formsToLoad.value || formsToLoadRemaining.value > 0);
});

// Returns a 0 to 100 value, not a ratio.
const loadingPercentage = computed(() => {
    if (!formsToLoad.value) return 0;
    return (formsToLoad.value - formsToLoadRemaining.value) * 100 / formsToLoad.value;
});

const tagFilter = (): string => {
    const list: string[] = [];
    for (const tag of tags.value) {
        if (tag.enabled) list.push(tag.label);
    }
    return arrayToList(list, '(None Set)');
};

const flagFilter = (): string => {
    const list: string[] = [];
    for (const flag of flags.value) {
        if (flag.enabled) list.push(flag.label);
    }
    return arrayToList(list, '(None Set)');
};

const changeTarget = (): void => {
    if (!changeTargetName.value) return;
    targets.value[changeTargetIndex.value] = {
        name: changeTargetName.value,
        loading: true,
        forms: {}
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

const launchTagFilterModal = () => {
    if (editTagFilterModal.value) editTagFilterModal.value.show();
}

const launchFlagFlterModal = () => {
    if (editFlagFilterModal.value) editFlagFilterModal.value.show();
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
            '<a :href="props.helpRoot + '/Theme/Infections'">+help Theme/Infections</a>'.
        </p>

        <Progress v-if="loading" id="form-list-progress-bar"
                  :percentage="loadingPercentage"
                  alt="Form list loading progress"
        ></Progress>
        <div v-else>
            <!-- Section selector -->
            <div class="d-flex align-items-center mb-3">

                <div class="me-2 text-primary">Toggle Sections:</div>
                <div class="flex-grow-1 row g-1" role="group" aria-label="Toggle buttons for sections to display">
                    <template v-for="section in sections">
                        <div v-if="!section.staffOnly || props.staff" class="col-6 col-md-3 col-lg-3 col-xl-2">
                            <input type="checkbox" class="btn-check" :id="`section_${section.id}`" autocomplete="off"
                                   v-model="section.enabled" :value="true" @change="updateSectionDisplay"
                            >
                            <label class="btn btn-outline-primary w-100 h-100" :for="`section_${section.id}`">
                                {{ section.label }}
                            </label>
                        </div>
                    </template>
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
                    <th data-dt-order="disable" class="small">
                        <div class="d-flex align-items-center">
                            <button class="btn btn-secondary me-1" @click="launchTagFilterModal">
                                <span class="d-inline-block text-nowrap">
                                    <i class="fas fa-filter btn-icon-left"></i>Edit
                                </span>
                            </button>
                            <span id="tag-filter">{{ tagFilter() }}</span>
                        </div>
                    </th>
                    <th data-dt-order="disable" class="small">
                        <div class="d-flex align-items-center">
                            <button class="btn btn-secondary me-1" @click="launchFlagFlterModal">
                                <span class="d-inline-block text-nowrap">
                                    <i class="fas fa-filter btn-icon-left"></i>Edit
                                </span>
                            </button>
                            <span id="flag-filter">{{ flagFilter() }}</span>
                        </div>
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
                    <i class="fa-solid fa-check w-100 text-center" v-if="(dt.rowData as Form).target0"></i>
                </template>

                <template #column-target1="dt: DataTablesNamedSlotProps">
                    <i class="fa-solid fa-check w-100 text-center" v-if="(dt.rowData as Form).target1"></i>
                </template>

                <template #column-target2="dt: DataTablesNamedSlotProps">
                    <i class="fa-solid fa-check w-100 text-center" v-if="(dt.rowData as Form).target2"></i>
                </template>

                <template #column-target3="dt: DataTablesNamedSlotProps">
                    <i class="fa-solid fa-check w-100 text-center" v-if="(dt.rowData as Form).target3"></i>
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
            <p v-if="!props.staff">
                You need to have permission to see each target's form list in order for this to work.
            </p>
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

    <modal-message ref="editTagFilterModal" title="Edit Tag Filter" @close="updateFilterForTags">
        <div class="container">
            <div class="row">Tick the tags you want to require, then close this popup and they'll be applied.</div>
            <div class="row">
                <div v-for="tag in tags" class="form-check form-switch mt-2 col-12 col-md-6 col-lg-4">
                    <input class="form-check-input" type="checkbox" role="switch"
                           :id="`flag_${tag.id}`" v-model="tag.enabled"
                    >
                    <label class="form-check-label" :for="`flag_${tag.id}`">{{ tag.label }}</label>
                </div>

            </div>
        </div>
    </modal-message>

    <modal-message ref="editFlagFilterModal" title="Edit Flag Filter" @close="updateFilterForFlags">
        <div class="container">
            <div class="row">Tick the flags you want to require, then close this popup and they'll be applied.</div>
            <div class="row">
                <div v-for="flag in flags" class="form-check form-switch mt-2 col-12 col-md-6 col-lg-4">
                    <input class="form-check-input" type="checkbox" role="switch"
                           :id="`flag_${flag.id}`" v-model="flag.enabled"
                    >
                    <label class="form-check-label" :for="`flag_${flag.id}`">{{ flag.label }}</label>
                </div>

            </div>
        </div>
    </modal-message>


</template>

<style scoped>
.form-control {
    min-width: 140px;
}

/* Lazy fix to prevent the 'Edit' buttons getting too small */
.btn {
    min-width: 74px;
}
</style>
