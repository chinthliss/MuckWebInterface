<script setup lang="ts">

import {ref, onMounted, Ref} from "vue";
import {AvatarGradient} from "../defs";

type ColorSet = {
    skin1: string,
    skin2: string
    skin3: string,
    hair: string,
    eyes: string
}

type DecodedJson = {
    base: string,
    head?: string,
    arms?: string,
    legs?: string,
    groin?: string,
    ass?: string,
    skin?: string,
    colors?: ColorSet
}

type DrawingStepLayer = {
    colorChannel: string,
    layerIndex: number
}

type DrawingStep = {
    dollName: string,
    part: string,
    subPart: string,
    layers: DrawingStepLayer[]
}

const props = defineProps<{
    drawingSteps: DrawingStep[],
    dolls: string[],
    gradients: AvatarGradient[],
    initialCode: string,
    baseUrl: string,
    renderUrl: string,
    avatarWidthIn?: number,
    avatarHeightIn?: number
}>();

const avatarWidth = props.avatarWidthIn ?? 384;
const avatarHeight = props.avatarHeightIn ?? 640;
const avatarImgSrc: Ref<string> = ref('');

const code: Ref<string> = ref("");
const json: Ref<DecodedJson> = ref({base:''});
const head = ref("");
const torso = ref("");
const arms = ref("");
const legs = ref("");
const groin = ref("");
const ass = ref("");
const skin = ref("");
const colors = ref({
    skin1: '',
    skin2: '',
    skin3: '',
    hair: '',
    eyes: ''
});


onMounted(() => {
    code.value = props.initialCode;
    json.value = JSON.parse(atob(code.value));
    torso.value = json.value.base;
    head.value = json.value.head ?? '';
    arms.value = json.value.arms ?? '';
    legs.value = json.value.legs ?? '';
    groin.value = json.value.groin ?? '';
    ass.value = json.value.ass ?? '';
    skin.value = json.value.skin ?? '';
    if (json.value.colors) {
        colors.value.skin1 = json.value.colors.skin1 || '';
        colors.value.skin2 = json.value.colors.skin2 || '';
        colors.value.skin3 = json.value.colors.skin3 || '';
        colors.value.hair = json.value.colors.hair || '';
        colors.value.eyes = json.value.colors.eyes || '';
    }
    avatarImgSrc.value = props.renderUrl + '/' + code.value;
});

const updateAndRefresh = () => {
    let newJson = {
        base: torso.value,
        male: true,
        female: true
    } as DecodedJson;
    if (head.value && head.value !== torso.value) newJson.head = head.value;
    if (arms.value && arms.value !== torso.value) newJson.arms = arms.value;
    if (legs.value && legs.value !== torso.value) newJson.legs = legs.value;
    if (groin.value && groin.value !== torso.value) newJson.groin = groin.value;
    if (ass.value && ass.value !== torso.value) newJson.ass = ass.value;
    if (skin.value) newJson.skin = skin.value;

    let setColors = {} as ColorSet;
    if (colors.value.skin1) setColors.skin1 = colors.value.skin1;
    if (colors.value.skin2) setColors.skin2 = colors.value.skin2;
    if (colors.value.skin3) setColors.skin3 = colors.value.skin3;
    if (colors.value.hair) setColors.hair = colors.value.hair;
    if (colors.value.eyes) setColors.eyes = colors.value.eyes;
    if (Object.keys(setColors).length > 0) newJson.colors = setColors;

    let newCode = btoa(JSON.stringify(newJson));
    window.location = props.baseUrl + '/' + newCode;
}

const layerListToString = (unparsed: DrawingStepLayer[]): string => {
    let parsed = [];
    for (let i = 0; i < unparsed.length; i++) {
        parsed.push("layer " + unparsed[i].layerIndex + ", color " + unparsed[i].colorChannel);
    }
    return parsed.join(' >> ');
};

</script>

<template>
    <div class="container">
        <h2>Avatar Paper Doll Tester</h2>

        <div class="d-flex flex-column flex-xl-row">

            <!-- Avatar -->
            <div class="me-xl-4">
                <div class="avatarHolder">
                    <img class="avatar" v-if="avatarImgSrc" :width="avatarWidth" :height="avatarHeight" :src="avatarImgSrc"
                         alt="Avatar Render"
                    >
                </div>
            </div>

            <!-- Doll Controls -->
            <div class="me-xl-4">

                <div class="form-group">
                    <label for="torso">Torso (Base)</label>
                    <select class="form-control" id="torso" v-model="torso" @change="updateAndRefresh">
                        <option :value="doll" v-for="doll in dolls">{{ doll }}</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="head">Head</label>
                    <select class="form-control" id="head" v-model="head" @change="updateAndRefresh">
                        <option value="">(Same as base)</option>
                        <option :value="doll" v-for="doll in dolls">{{ doll }}</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="arms">Arms</label>
                    <select class="form-control" id="arms" v-model="arms" @change="updateAndRefresh">
                        <option value="">(Same as base)</option>
                        <option :value="doll" v-for="doll in dolls">{{ doll }}</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="legs">Legs</label>
                    <select class="form-control" id="legs" v-model="legs" @change="updateAndRefresh">
                        <option value="">(Same as base)</option>
                        <option :value="doll" v-for="doll in dolls">{{ doll }}</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="groin">Groin</label>
                    <select class="form-control" id="groin" v-model="groin" @change="updateAndRefresh">
                        <option value="">(Same as base)</option>
                        <option :value="doll" v-for="doll in dolls">{{ doll }}</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="ass">Ass</label>
                    <select class="form-control" id="ass" v-model="ass" @change="updateAndRefresh">
                        <option value="">(Same as base)</option>
                        <option :value="doll" v-for="doll in dolls">{{ doll }}</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="skin">Skin</label>
                    <select class="form-control" id="skin" v-model="skin" @change="updateAndRefresh">
                        <option value="">(Unset)</option>
                        <option :value="doll" v-for="doll in dolls">{{ doll }}</option>
                    </select>
                </div>

            </div>

            <!-- Gradient Controls -->
            <div>

                <div class="form-group">
                    <label for="skin1">Fur / Skin 1</label>
                    <select class="form-control" id="skin1" v-model="colors.skin1" @change="updateAndRefresh">
                        <option value="">(Default)</option>
                        <option :value="gradient" v-for="gradient in gradients">{{ gradient }}</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="skin2">Fur / Skin 2</label>
                    <select class="form-control" id="skin2" v-model="colors.skin2" @change="updateAndRefresh">
                        <option value="">(Default)</option>
                        <option :value="gradient" v-for="gradient in gradients">{{ gradient }}</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="skin3">Bare Skin</label>
                    <select class="form-control" id="skin3" v-model="colors.skin3" @change="updateAndRefresh">
                        <option value="">(Default)</option>
                        <option :value="gradient" v-for="gradient in gradients">{{ gradient }}</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="hair">Hair</label>
                    <select class="form-control" id="hair" v-model="colors.hair" @change="updateAndRefresh">
                        <option value="">(Default)</option>
                        <option :value="gradient" v-for="gradient in gradients">{{ gradient }}</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="eyes">Eye</label>
                    <select class="form-control" id="eyes" v-model="colors.eyes" @change="updateAndRefresh">
                        <option value="">(Default)</option>
                        <option :value="gradient" v-for="gradient in gradients">{{ gradient }}</option>
                    </select>
                </div>

            </div>

        </div>

        <h3 class="mt-2">Technical:</h3>
        <div>
            <div class="label">Code</div>
            <div class="value small text-break">{{ code }}</div>
        </div>
        <div>
            <div class="label">JSON</div>
            <div class="value">{{ json }}</div>
        </div>
        <div>
            <div class="label">Drawing Steps</div>
            <div class="value">
                <ul>
                    <li v-for="step in drawingSteps">
                        {{ step.part }}/{{ step.subPart }} from {{ step.dollName }}, using:
                        {{ layerListToString(step.layers) }}
                    </li>
                </ul>
            </div>
        </div>


    </div>
</template>

<style scoped lang="scss">
@use 'resources/sass/variables' as *;
.avatarHolder img {
    border: 1px solid $primary;
    background-image: linear-gradient(45deg, #808080 25%, transparent 25%), linear-gradient(-45deg, #808080 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #808080 75%), linear-gradient(-45deg, transparent 75%, #808080 75%);
    background-size: 20px 20px;
    background-position: 0 0, 0 10px, 10px -10px, -10px 0;
}

.label {
    color: $primary;
}

.value {
    margin-bottom: 4px;
}
</style>
