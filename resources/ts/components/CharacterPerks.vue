<script setup lang="ts">

import {ref, Ref} from "vue";
import ModalConfirmation from "./ModalConfirmation.vue";
import {ansiToHtml, capital} from "../formatting";

type Perk = {
    id: string,
    name: string,
    description: string,
    notes: string,
    cost: number
    tags: string[],
    vanity?: boolean,
    excludes?: string[],
    excluded?: boolean,
    owned?: boolean
}

const perks: Ref<Perk[]> = ref([]);
const perksToLoad: Ref<number | null> = ref(null);
const perksToLoadRemaining: Ref<number> = ref(1); // Starting at 1 to cover initial loading
const tags: Ref<string[]> = ref([]);
const tagFilter: Ref<{ [tag: string]: boolean }> = ref({});
const perkPoints: Ref<number> = ref(0);
const vanityPoints: Ref<number> = ref(0);
const showAll: Ref<boolean> = ref(true);

const updateNotesModal: Ref<InstanceType<typeof ModalConfirmation> | null> = ref(null);
const perkBeingUpdated: Ref<Perk | null> = ref(null);
const notesBeingUpdated: Ref<string> = ref('');

const channel = mwiWebsocket.channel('character');

channel.on('perksCatalogue', (data: number) => {
    perks.value = [];
    perksToLoadRemaining.value = data;
    perksToLoad.value = data;
});

channel.on('perk', (data: Perk) => {
    data.excluded = false;
    // Tag processing - force lowercase and add to global list if not seen before
    if (data.tags) {
        const lowerCaseTags = [];
        for (let tag of data.tags) {
            tag = tag.toLowerCase();
            lowerCaseTags.push(tag);
            if (tags.value.indexOf(tag) === -1) {
                tags.value.push(tag)
                tagFilter.value[tag] = false;
            }
        }
        data.tags = lowerCaseTags;
    } else data.tags = []; // Now mandatory
    // Description processing - replace \n with actual newline character
    data.description = data.description.replace(/\\n/g, '\n');
    perks.value.push(data);
    perksToLoadRemaining.value--;
});

type PerkStatusUpdate = {
    perkPoints: number,
    vanityPoints: number,
    owned: {
        name: string,
        notes: string
    }
};

channel.on('perkStatus', (update: PerkStatusUpdate) => {
    perkPoints.value = update.perkPoints;
    vanityPoints.value = update.vanityPoints;
    // Clear existing values
    for (const perk of perks.value) {
        perk.owned = false;
        delete perk.notes;
    }
    // Push updates of owned perks
    for (const ownedPerk of update.owned) {
        const perk = perks.value.find((possiblePerk) => possiblePerk.name == ownedPerk.name);
        if (perk) {
            perk.owned = true;
            perk.notes = ownedPerk.notes.replace(/\\n/g, '\n');
        } else console.log("Couldn't find owned perk in catalog: ", ownedPerk);
    }
    recalculateExclusions();
});

const presentCostsForPerk = (perk: Perk): [number, number] => {
    let vanityCost = 0;
    if (perk.vanity) {
        vanityCost = Math.min(perk.cost, vanityPoints.value);
    }
    return [vanityCost, perk.cost ? perk.cost - vanityCost : 0];
};

const presentCostForPerkAsString = (perk: Perk): string => {
    const costs = presentCostsForPerk(perk);
    const output = [];
    if (costs[0]) {
        output.push(costs[0] + " vanity points");
    }
    if (costs[1]) {
        output.push(costs[1] + " perk points");
    }
    return output.join(', ');
};

const canAfford = (perk: Perk): boolean => {
    const costs = presentCostsForPerk(perk);
    return costs[1] <= perkPoints.value;
};
const buyPerk = (perk: Perk): void => {
    if (canAfford(perk)) {
        channel.send('buyPerk', perk.name);
        // Muck sends a 'perkStatus' response after the purchase
    }
};

const startUpdatingNotes = (perk: Perk): void => {
    perkBeingUpdated.value = perk;
    notesBeingUpdated.value = perk.notes;
    if (updateNotesModal.value) updateNotesModal.value.show();
};

const saveUpdatedNotes = (): void => {
    if (!perkBeingUpdated.value) return;
    channel.send('updatePerkNotes', {perk: perkBeingUpdated.value?.name, notes: notesBeingUpdated.value});
    perkBeingUpdated.value.notes = notesBeingUpdated.value;
};

const shallWeShow = (perk: Perk): boolean => {
    let show = false;
    if (perk.owned || showAll.value) show = true;
    for (const filter in tagFilter.value) {
        if (tagFilter.value[filter] && perk.tags.indexOf(filter) === -1) show = false;
    }
    return show;
}
const recalculateExclusions = () => {
    let excluded = [];
    // Pass 1, calculation exclusions
    for (const perk of perks.value) {
        if (perk.owned && perk.excludes) {
            excluded = excluded.concat(perk.excludes);
        }
    }
    // console.log("Perks excluded: ", excluded);
    // Pass 2, flag excluded
    for (const perk of perks.value) {
        perk.excluded = (excluded.indexOf(perk.name) !== -1);
    }
}

// Send requests for data
channel.send('bootPerks');

</script>

<template>
    <!-- Filter controls -->
    <div class="d-flex justify-content-center mb-2">
        <div class="d-flex align-items-center">
            <div class="me-1 text-primary">Display:</div>
            <div class="me-4 btn-group" role="group" aria-label="Show All?">
                <input type="radio" class="btn-check" id="show_all" autocomplete="off"
                       v-model="showAll" :value="true"
                >
                <label class="btn btn-outline-secondary" for="show_all">Show All</label>

                <input type="radio" class="btn-check" id="show_owned" autocomplete="off"
                       v-model="showAll" :value="false"
                >
                <label class="btn btn-outline-secondary" for="show_owned">Show Owned Only</label>

            </div>
        </div>
    </div>

    <!-- Perk list -->
    <div v-if="perksToLoadRemaining">Loading..</div>
    <div v-else>
        <!-- Floating info bar -->
        <div class="position-sticky top-0 bg-primary text-dark p-1 m-1 rounded-1 z-1 d-flex flex-column flex-lg-row">
            <div class="flex-grow-1 d-flex flex-row flex-lg-column justify-content-evenly">
                <div>Perk Points: {{ perkPoints }}</div>
                <div>Vanity Points: {{ vanityPoints }}</div>
            </div>
            <div class="align-self-center">
                <span class="me-2">Filter Tags:</span>
                <template v-for="tag in tags">
                    <input type="checkbox" class="btn-check" autocomplete="off"
                           :id="'btn-check-' + tag" v-model="tagFilter[tag]"
                    >
                    <label class="btn btn-outline-info text-dark me-2" :for="'btn-check-' + tag">{{
                            capital(tag)
                        }}</label>
                </template>

            </div>
        </div>

        <p>Perks that have the 'vanity' tag will use your vanity points first, then use regular points to make
            up the difference if required. This is reflected in the costs listed.</p>

        <template v-for="perk in perks">
            <div v-if="shallWeShow(perk)" class="card mt-2"
                 v-bind:class="{ 'border-primary-subtle': !perk.owned, 'border-dark-subtle': perk.excluded, 'ms-4': !perk.owned }"
            >
                <!-- Body -->
                <div class="card-body">
                    <div class="float-end">
                        <span v-if="perk.owned" class="text-primary">Owned</span>
                        <span v-else-if="perk.excluded" class="text-warning">Excluded</span>
                        <span v-else-if="perk.cost">Cost: {{ presentCostForPerkAsString(perk) }}</span>
                        <span v-else>Chargen Only</span>
                        <button class="btn btn-primary ms-2" v-if="!perk.excluded && !perk.owned && perk.cost"
                                @click="buyPerk(perk)" :disabled="!canAfford(perk)"
                        >
                            Buy
                        </button>
                    </div>
                    <h5 class="card-title" v-bind:class="{ 'text-primary': perk.owned, 'text-muted': perk.excluded }">
                        {{ perk.name }}
                    </h5>

                    <p class="card-text muck-whitespace" v-bind:class="{ 'text-muted': perk.excluded }"
                       v-html="ansiToHtml(perk.description)"></p>

                    <template v-if="perk.owned">
                        <hr>
                        <div class="float-end">
                            <button class="btn btn-primary" @click="startUpdatingNotes(perk)">Edit Custom Notes</button>
                        </div>
                        <p class="card-text muck-whitespace" v-bind:class="{ 'text-muted': perk.excluded }">{{ perk.notes }}</p>
                    </template>
                </div>

                <!-- Footer -->
                <div class="card-footer text-body-secondary d-flex"
                     v-bind:class="{ 'border-primary-subtle': !perk.owned, 'border-dark-subtle': perk.excluded }"
                >
                    <div class="flex-grow-1">
                        <template v-if="perk.excludes">Excludes: {{ perk.excludes.join(', ') }}</template>
                    </div>
                    <div>
                        Tags:
                        <template v-if="!perk.tags.length">No tags</template>
                        <span v-for="tag in perk.tags" class="badge rounded-pill p-2 text-bg-info ms-1">{{
                                capital(tag)
                            }}</span>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <ModalConfirmation ref="updateNotesModal" @yes="saveUpdatedNotes"
                       title="Update Perk Notes" yes-label="Save Changes" no-label="Cancel"
    >
        <p>Editing the notes for the perk '{{ perkBeingUpdated?.name }}'.</p>
        <form>
            <textarea class="w-100" v-model="notesBeingUpdated"></textarea>
        </form>
    </ModalConfirmation>

</template>

<style scoped>
</style>
