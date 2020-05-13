const mix = require('laravel-mix');

mix
  .options({
    processCssUrls: false
  })
  .sass('resources/sass/form.scss', 'assets/css/form.css')
;
