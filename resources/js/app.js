/**
 * Load and configure dependencies for our app here.
 */

// TODO: Removed lodash as pretty sure nothing is using it now
// import _ from 'lodash';
// window._ = _;

/**
 * Bootstrap
 */

import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

/**
 * Axios HTTP Library
 */

// This library automatically handles sending the CSRF token as a header based on the value of the "XSRF" token cookie.
import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * jQuery
 */

import jQuery from 'jquery';

window.$ = jQuery;

/**
 * DataTables
 */

// These both run and setup automatically as imports.
import 'datatables.net';
import 'datatables.net-bs5';

/**
 * Vue
 */

import {createApp} from 'vue';

const app = createApp({});

// Code to load components manually:
// import ExampleComponent from './components/ExampleComponent.vue';
// app.component('example-component', ExampleComponent);

/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */

Object.entries(import.meta.glob('./**/*.vue', {eager: true})).forEach(([path, definition]) => {
    app.component(path.split('/').pop().replace(/\.\w+$/, ''), definition.default);
});

// Attach Vue to the app, which is the <main> part of the page in our case.
app.mount('#app');
