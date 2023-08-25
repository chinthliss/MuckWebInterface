/**
 * Load and configure dependencies for our app here.
 */

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

// If the page has been served for a specific character, we'll tell Axios to automatically include that too.
import { characterDbref } from "./siteutils";
let dbref = characterDbref();
if (dbref) {
    window.axios.defaults.headers.common['X-CHARACTER-DBREF'] = dbref;
}

/**
 * jQuery
 */

import jQuery from 'jquery';

window.$ = jQuery;

/**
 * DataTables
 */

import DataTable from 'datatables.net-vue3';
import DataTablesLib from 'datatables.net-bs5';
DataTable.use(DataTablesLib);

/**
 * Websocket
 */
import MwiWebsocket from "muckwebinterface-websocket";
window.mwiWebsocket = MwiWebsocket;
if (!mwiWebsocketAuthUrl || !mwiWebsocketUrl)
    console.log("Websocket configuration wasn't set.");
else {
    mwiWebsocket.start({
        'authenticationUrl': mwiWebsocketAuthUrl,
        'websocketUrl': mwiWebsocketUrl
    })
}
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
