/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.scss';

const $ = require("jquery");
const axios = require("axios");
const swal = require("sweetalert2");
global.$ = global.jQuery = $;
global.axios = axios;
global.Swal = swal;

// require('@popperjs/core');
require('bootstrap/dist/js/bootstrap.bundle');

require("./components/includes/nicescroll");
const moment = require("moment");
global.moment = moment;
require("./components/includes/stisla");

require("./components/includes/datatables/core");
require("./components/includes/datatables/datatable-bs4");
require("select2");
require("select2/dist/css/select2.css");

require("fullcalendar");
require("fullcalendar/dist/fullcalendar.css");
require("fullcalendar/dist/locale/fr");

require('./components/script');
require('./components/custom');

require("@fortawesome/fontawesome-free/css/all.css");
import './bootstrap';



