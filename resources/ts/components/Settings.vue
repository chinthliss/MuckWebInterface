<script setup lang="ts">

import {ref, Ref} from "vue";

const props = defineProps<{
    apiUrl: string
    initialSettings: {
        avatarPreference: string
    }
}>();

const settings: Ref<object> = ref(props.initialSettings);

const saveSetting = (setting: string): void => {
    const value = settings.value[setting];
    console.log(`Saving ${setting}: ${value}`);
    axios.post(props.apiUrl, {setting, value})
        .then(() => {
            console.log(`Saved ${setting}: ${value}`);
        })
        .catch((error) => {
            console.log(`Failed to save ${setting}: ${value}`, error?.response?.data || error);
        })

};

</script>

<template>
    <div class="container">

        <h1>Settings</h1>

        <p class="lead">These settings are account wide and will take effect on all characters.</p>

        <!-- Avatar Preference-->
        <h2>Avatar Display Preference</h2>
        <div class="row">
            <div class="col-12 col-lg-6">
                <p>This controls how you'd prefer user's avatars to be rendered.</p>
            </div>

            <div class="col-12 col-lg-6">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="avatarPreference" value="hidden"
                           id="avatarPreferenceNone" v-model="settings.avatarPreference"
                           @change="saveSetting('avatarPreference')"
                    >
                    <label class="form-check-label" for="avatarPreferenceNone">
                        Hidden (Avatars won't be displayed where possible)
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="avatarPreference" value="clean"
                           id="avatarPreferenceClean" v-model="settings.avatarPreference"
                           @change="saveSetting('avatarPreference')"
                    >
                    <label class="form-check-label" for="avatarPreferenceClean">
                        Clean (No naughty parts)
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="avatarPreference" value="default"
                           id="avatarPreferenceDefault" v-model="settings.avatarPreference"
                           @change="saveSetting('avatarPreference')"
                    >
                    <label class="form-check-label" for="avatarPreferenceDefault">
                        Default (Female only)
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="avatarPreference" value="explicit"
                           id="avatarPreferenceExplicit" v-model="settings.avatarPreference"
                           @change="saveSetting('avatarPreference')"
                    >
                    <label class="form-check-label" for="avatarPreferenceExplicit">
                        Explicit (Everything)
                    </label>
                </div>
            </div>

        </div>

    </div>

</template>

<style scoped>

</style>
