import './bootstrap';
import.meta.glob('@spa/**/*.js', {eager: true});

import {initHtmx} from './htmx';
initHtmx();
