import $ from 'jquery';

window.$ = window.jQuery = $;

require('./bootstrap');
require('alpinejs');
require('select2');
require('jquery-mask-plugin');



$(document).ready(function() {

    $('.mask-date-time').mask('0000/00/00 00:00');

    $('.select2').select2({
        searchInputPlaceholder: 'Search'
    });

});
