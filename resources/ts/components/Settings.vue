<script setup lang="ts">

</script>

<template>
    <div class="container">

        <h1>Settings</h1>

        <p class="lead">Unless otherwise specified, these settings are account wide and will take effect on all
            characters.</p>

        <!-- Use full screen -->
        <!-- TODO - full width setting -->
        <h2>Use full width</h2>
        <div class="row">
            <div class="col-12 col-lg-6">
                <p>Not yet implemented.</p>
            </div>

            <div class="col-12 col-lg-6">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="fullWidth" value="yes" id="fullWidthYes">
                    <label class="form-check-label" for="fullWidthYes">
                        Yes
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="fullWidth" value="no" id="fullWidthYes">
                    <label class="form-check-label" for="fullWidthYes">
                        No
                    </label>
                </div>
            </div>
        </div>

        <!-- Avatar Rendering-->
        <!-- TODO - avatar rendering setting -->
        <h2>Avatar Rendering</h2>
        <div class="row">
            <div class="col-12 col-lg-6">
                <p>Not yet implemented.</p>

            </div>

            <div class="col-12 col-lg-6">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="avatarRendering" value="none"
                           id="avatarRenderingNone"
                    >
                    <label class="form-check-label" for="avatarRenderingNone">
                        None (Avatars won't be rendered where possible)
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="avatarRendering" value="clean"
                           id="avatarRenderingClean"
                    >
                    <label class="form-check-label" for="avatarRenderingClean">
                        Clean (No naughty parts)
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="avatarRendering" value="default"
                           id="avatarRenderingDefault"
                    >
                    <label class="form-check-label" for="avatarRenderingDefault">
                        Default (Female only)
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="avatarRendering" value="explicit"
                           id="avatarRenderingExplicit"
                    >
                    <label class="form-check-label" for="avatarRenderingExplicit">
                        Explicit (Everything)
                    </label>
                </div>
            </div>

        </div>

    </div>

</template>

<style scoped>

</style>
