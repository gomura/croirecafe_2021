var path = require('path');
var fs = require('fs');
var pkg = JSON.parse(fs.readFileSync('./package.json'));
var assetsPath = path.resolve(pkg.path.assetsDir);
var localDomain = "https://croirecafe2021:8890";

var gulp    = require('gulp');
var sass    = require('gulp-sass');
var plumber = require('gulp-plumber');
var notify  = require('gulp-notify');
var sourcemaps = require('gulp-sourcemaps');
var postcss = require("gulp-postcss");
var autoprefixer = require("gulp-autoprefixer");
//var postcssGapProperties = require("postcss-gap-properties");
var browserSync = require('browser-sync');


gulp.task('sass', function() {
    gulp.src(path.join(assetsPath, 'sass/main.scss'))
        .pipe(plumber({
	      errorHandler: notify.onError("Error: <%= error.message %>") //<-
	    }))
		.pipe(sourcemaps.init())
        .pipe(sass())
        //.pipe(sourcemaps.write({includeContent: false}))
		//.pipe(sourcemaps.init({loadMaps: true}))
		.pipe(autoprefixer(['last 3 versions', 'ie >= 8', 'Android >= 4', 'iOS >= 8']))
		//.pipe(sourcemaps.write())
		.pipe(gulp.dest(path.join(assetsPath, 'css/')))
		.pipe(browserSync.stream({match: '**/*.css'}));
	
	gulp.src(path.join(assetsPath, 'sass/admin.scss'))
        .pipe(plumber({
	      errorHandler: notify.onError("Error: <%= error.message %>") //<-
	    }))
		.pipe(sourcemaps.init())
        .pipe(sass())
        //.pipe(sourcemaps.write({includeContent: false}))
		//.pipe(sourcemaps.init({loadMaps: true}))
		.pipe(autoprefixer(['last 3 versions', 'ie >= 8', 'Android >= 4', 'iOS >= 8']))
		.pipe(sourcemaps.write())
		.pipe(gulp.dest(path.join(assetsPath, 'css/')))
		.pipe(browserSync.stream({match: '**/*.css'}));
    
    gulp.src(path.join(assetsPath, 'sass/products.scss'))
        .pipe(plumber({
	      errorHandler: notify.onError("Error: <%= error.message %>") //<-
	    }))
		.pipe(sourcemaps.init())
        .pipe(sass())
        //.pipe(sourcemaps.write({includeContent: false}))
		//.pipe(sourcemaps.init({loadMaps: true}))
		.pipe(autoprefixer(['last 3 versions', 'ie >= 8', 'Android >= 4', 'iOS >= 8']))
		//.pipe(sourcemaps.write())
		.pipe(gulp.dest(path.join(assetsPath, 'css/')))
		.pipe(browserSync.stream({match: '**/*.css'}));

});

gulp.task('browserSync', function () {
  
  var option = {
	  browser: "google chrome",
	  proxy: localDomain,
  }
  return browserSync.init(option);
});

gulp.task('default',["browserSync"], function() {
   	gulp.watch(path.join(assetsPath, './**/*.scss'),['sass']);
	gulp.watch(['./**/*.html','./**/croirecafe2020/**/*.twig','./common/js/*.json','./common/js/*.js']).on('change', function() {
      browserSync.reload()
    });
});
