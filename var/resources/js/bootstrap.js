import 'admin-lte/dist/css/adminlte.css';
import '@fortawesome/fontawesome-free/css/all.min.css';
import 'select2/dist/css/select2.min.css';
import 'select2-bootstrap-5-theme/dist/select2-bootstrap-5-theme.min.css';
import 'summernote/dist/summernote-bs5.css';
import 'datatables.net-bs5/css/dataTables.bootstrap5.min.css';
import 'datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css';
import 'flatpickr/dist/flatpickr.min.css';
import 'flatpickr/dist/plugins/monthSelect/style.css'
import '../css/app.css';

import $ from 'jquery';

window.$ = window.jQuery = $;

import select2 from 'select2';

select2($);

import * as adminlte from 'admin-lte';

window.adminlte = adminlte;

import Swal from 'sweetalert2';

window.Swal = Swal;

import * as bootstrap from 'bootstrap/dist/js/bootstrap.bundle.js';

window.bootstrap = bootstrap;

import flatpickr from 'flatpickr';

window.flatpickr = flatpickr;

import 'datatables.net-bs5';
import 'datatables.net-responsive-bs5';
import 'summernote';

import htmx from 'htmx.org';

htmx.config.historyCacheSize = 0;
htmx.config.refreshOnHistoryMiss = true;
window.htmx = htmx;

import {Toast} from './ui/toast';
import {confirmDialog, promptConfirmDialog} from './ui/dialog';
import {showLoading} from './ui/loading';
import {userSelect2} from "./ui/select2";
import {openPageInModal} from "./modal/openPageInModal";
import './registry'

window.Toast = Toast;
window.confirmDialog = confirmDialog;
window.promptConfirmDialog = promptConfirmDialog;
window.showLoading = showLoading;
window.userSelect2 = userSelect2;
window.openPageInModal = openPageInModal;
window.getCurrentPage = function () {
  return document.querySelector('[data-page]')?._customPageLogic;
};