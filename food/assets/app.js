/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';


// this "modifies" the jquery module: adding behavior to it
// the bootstrap module doesn't export/return anything
//var $ = require('jquery');

//require("jquery-ui/ui/widgets/autocomplete");
//require('select2');

// start the Stimulus application
//import './scripts/main';
//import './scripts/tools';
//import './scripts/ajax';
import './styles/global.scss';
//import 'select2/dist/css/select2.css';


// REACT
import React from 'react';
import ReactDOM from 'react-dom';
import {BrowserRouter} from 'react-router-dom';
import Home from './components/Home';

window.fadesCount = 1040;

ReactDOM.render(<BrowserRouter><Home/></BrowserRouter>, document.getElementById('root'));


