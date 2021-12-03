/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/style.css';
import './styles/googlefonts_nonito.css';
import './styles/googlefonts_varela-round.css';
import './styles/app.scss';

const $ = require('jquery');
require('bootstrap');

import bsCustomFileInput from 'bs-custom-file-input';

// start the Stimulus application
import './bootstrap.js';
// load scripts for templating
import './template_scripts';
import './fontawesome_5.15.4';

bsCustomFileInput.init();

