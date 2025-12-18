/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';
import './styles/fonts_nunito_local.css';
import './toast.js';
import { boot as bootProtocol } from './protocol-polling.js';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

bootProtocol();
