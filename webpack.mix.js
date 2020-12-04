const mix = require('laravel-mix');

mix.setPublicPath('assets');
mix
  .options({
    processCssUrls: false
  })
  .sass('resources/sass/form.scss', 'assets/css/form.css')
;
