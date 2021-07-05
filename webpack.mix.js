const mix = require('laravel-mix');

mix
  .disableNotifications()
  .setPublicPath('assets')
  .options({
    processCssUrls: false
  })
  .sass('resources/sass/form.scss', 'assets/css/form.css')
;
