<?php
namespace Deployer;

// composer での利用時
// require_once (__DIR__."/../vendor/autoload.php");
// require __DIR__.'/../vendor/deployer/deployer/recipe/common.php';

// deployer.phar での利用時
require 'recipe/common.php';

// 設定ファイル名を環境変数を参照し決定
$stage = getenv('STAGE') ?? 'dev';
echo "Stage is {$stage}" . PHP_EOL;
require($stage . '.settings.php');

// Project name
set('application', 'myapp');

// Project repository
set('repository', 'git@github.com:uzulla/Tinitter.git');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true);

// Shared files/dirs between deploys
set('shared_files', []);
set('shared_dirs', []);

// Writable dirs by web server
// set('writable_dirs', ['.', 'local_storage', 'cache']);
// set('allow_anonymous_stats', false);

// Tasks
desc('Deploy project');
task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code', // git pull
//    'deploy:shared',
    'deploy:vendors', // composer install
    'deploy:writable', // change permission
//    'deploy:clear_paths',
//    'backend_db_reset', // 本番なら不要
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
    'success'
]);

// [Optional] If deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');

// DB初期化（開発用のsqliteの例）
// task('backend_db_reset', function () use ($sqlite_data_reset_flag) {
//     if ($sqlite_data_reset_flag) {
//         $orig_release_path = get('orig_release_path');
//         run("cd {$orig_release_path} && make backend-db-reset && chmod 666 backend/sqlite.db");
//     }
// });
