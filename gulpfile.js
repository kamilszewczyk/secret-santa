var $        = require('gulp-load-plugins')();
var argv     = require('yargs').argv;
var gulp     = require('gulp');

// Check for --production flag
var isProduction = !!(argv.production);

// Browsers to target when prefixing CSS.
var COMPATIBILITY = ['last 2 versions', 'ie >= 9'];


// Compile Sass into CSS
// In production, the CSS is compressed
gulp.task('sass', function() {
    var minifycss = $.if(isProduction, $.minifyCss());

    return gulp.src('static/scss/app.scss')
        .pipe($.sourcemaps.init())
        .pipe($.sass()
            .on('error', $.sass.logError))
        .pipe($.autoprefixer({
            browsers: COMPATIBILITY
        }))
        .pipe(minifycss)
        .pipe($.if(!isProduction, $.sourcemaps.write()))
        .pipe(gulp.dest('static/css'));
});

// Build the site, run the server, and watch for file changes
gulp.task('watch', ['sass'], function() {
    gulp.watch(['static/scss/*.scss'], ['sass']);
});
