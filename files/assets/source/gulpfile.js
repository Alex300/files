const { src, dest, series, watch } = require('gulp');
const clean = require('gulp-clean');
const concat = require('gulp-concat');
const cleanCSS = require('gulp-clean-css');
const sourcemaps = require('gulp-sourcemaps');
const rename = require("gulp-rename");
const sass = require('gulp-dart-sass');
const uglify = require('gulp-uglify-es').default;
const webpack = require('webpack-stream');

const javaScriptFiles = [
    // The jQuery UI widget factory, can be omitted if jQuery UI is already included
    'src/upload/js/vendor/jquery.ui.widget.js',

    // The Templates plugin is included to render the upload/download listings
    //'src/JavaScript-Templates/tmpl.min.js',
    'src/JavaScript-Templates/tmpl.js',

    // The Load Image plugin is included for the preview images and image resizing functionality
    'src/JavaScript-Load-Image/js/load-image.all.min.js',

    // The Canvas to Blob plugin is included for image resizing functionality
    'src/JavaScript-Canvas-to-Blob/canvas-to-blob.min.js',

    // The Iframe Transport is required for browsers without support for XHR file uploads
    'src/upload/js/jquery.iframe-transport.js',

    // The basic File Upload plugin
    'src/upload/js/jquery.fileupload.js',

    // The File Upload file processing plugin
    'src/upload/js/jquery.fileupload-process.js',

    // The File Upload image preview & resize plugin
    'src/upload/js/jquery.fileupload-image.js',

    // The File Upload audio preview plugin
    'src/upload/js/jquery.fileupload-audio.js',

    // The File Upload video preview plugin
    'src/upload/js/jquery.fileupload-video.js',

    // The File Upload validation plugin
    'src/upload/js/jquery.fileupload-validate.js',

    // The File Upload user interface plugin
    'src/upload/js/jquery.fileupload-ui.js',

    // The main application script
    //'src/files.js',
];

let mode = 'production'

const modules = [
    'src/Files.js',
];

const cleanDistDirectory = () =>
    src('./dist/', {read: false, allowEmpty: true})
        .pipe(clean());

const buildModules = () =>
    src(modules, {sourcemaps: true})
        .pipe(
            webpack({
                mode: mode,
                devtool: 'inline-source-map'
            })
        )
        .pipe(rename('modules.js'))
        .pipe(dest('./dist', {sourcemaps: true}));

const buildJs = () => {
    //const files = javaScriptFiles;

    //const files = ['./src/header.js'];
    const files = [].concat('./src/header.js', javaScriptFiles, './dist/modules.js');

    //if (mode === 'production') {
    //     files.unshift('./src/header.js');
    //}
    // files.push('./dist/modules.js');

    return src(files, {sourcemaps: true})
        .pipe(sourcemaps.init())
        .pipe(concat('files.min.js'))
        .pipe(uglify())
        .pipe(sourcemaps.write('./'))
        .pipe(dest('../'));
}

const buildCss = () =>
    src([
        'src/scss/header.css',

        // CSS to style the file input field as button and adjust the Bootstrap progress bars
        'src/upload/css/jquery.fileupload.css',
        'src/upload/css/jquery.fileupload-ui.css',

        'src/scss/files.scss',
    ], {sourcemaps: true})
    .pipe(sourcemaps.init())
    .pipe(sass().on('error', sass.logError))
    .pipe(concat('files.min.css'))
    .pipe(cleanCSS())
    .pipe(sourcemaps.write('./'))
    .pipe(dest('../'));

function watchFiles() {
    watch('src/**/*.js', {delay: 100}, series(cleanDistDirectory, buildModules, buildJs, cleanDistDirectory));
    watch('src/scss/**/*.scss', buildCss);
}

/**
 * Build base.js
 * Use command
 * > gulp build
 */
exports.build = series(cleanDistDirectory, buildModules, buildJs, buildCss, cleanDistDirectory);

/**
 * File watcher
 * > gulp watch
 *
 * Default delay is 200 ms
 */
exports.watch = () => {
    mode = 'development';
    return watchFiles();
}
