//サーバー設定
const browserSyncOption = {
  //server: distBase,
  browser: "google chrome",
  proxy : "https://croirecafe2021:8890"
}

// 入出力するフォルダを指定
const srcBase = './';
const assetsBase = './common/';
const distBase = './';


const srcPath = {
  'scss': assetsBase + 'sass/*.scss',
  'html': srcBase + '**/*.html',
  'php' : srcBase + '**/*.php',
  'js' : assetsBase + 'js/*.js'
};

const distPath = {
  'css': assetsBase + 'css'  ,
  'html': distBase ,
  'php': distBase ,
};


const gulp = require('gulp');//gulp本体
//scss
const sass = require('gulp-dart-sass');//Dart Sass はSass公式が推奨 @use構文などが使える
const plumber = require("gulp-plumber"); // エラーが発生しても強制終了させない
const notify = require("gulp-notify"); // エラー発生時のアラート出力
const browserSync = require("browser-sync"); //ブラウザリロード




/**
 * sass
 *
 */
const cssSass = () => {
  return gulp.src(srcPath.scss, {
    sourcemaps: true
  })
    .pipe(
      //エラーが出ても処理を止めない
      plumber({
        errorHandler: notify.onError('Error:<%= error.message %>')
      }))
    .pipe(sass({ outputStyle: 'expanded' })) //指定できるキー expanded compressed
    .pipe(gulp.dest(distPath.css, { sourcemaps: './' })) //コンパイル先
    .pipe(browserSync.stream())
    .pipe(notify({
      message: 'Sassをコンパイルしました！',
      onLast: true
    }))
}


/**
 * html
 */
const html = () => {
  return gulp.src(srcPath.html)
    .pipe(gulp.dest(distPath.html))
}

/**
 * php
 */
const php = () => {
  return gulp.src(srcPath.php)
  //.pipe(gulp.dest(distPath.php))
}

/**
 * js
 */
 const js = () => {
  return gulp.src(srcPath.js)
  //.pipe(gulp.dest(distPath.php))
}

/**
 * ローカルサーバー立ち上げ
 */
const browserSyncFunc = () => {
  browserSync.init(browserSyncOption);
}


/**
 * リロード
 */
const browserSyncReload = (done) => {
  browserSync.reload();
  done();
}

/**
 *
 * ファイル監視 ファイルの変更を検知したら、browserSyncReloadでreloadメソッドを呼び出す
 * series 順番に実行
 * watch('監視するファイル',処理)
 */
const watchFiles = () => {
  gulp.watch(srcPath.scss, gulp.series(cssSass))
  //gulp.watch(srcPath.html, gulp.series(html, browserSyncReload))
  //gulp.watch(srcPath.php, gulp.series(php, browserSyncReload))
  //gulp.watch(srcPath.js, gulp.series(js, browserSyncReload))
}

/**
 * seriesは「順番」に実行
 * parallelは並列で実行
 */
exports.default = gulp.series(
  gulp.parallel(html,php,js,cssSass),
  gulp.parallel(watchFiles, browserSyncFunc)
);