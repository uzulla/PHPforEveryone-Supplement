<?php

namespace Deployer;

$base_url = 'http://my-app.example.jp';
$sqlite_data_reset_flag = true;
$user = 'ubuntu';

host('3.112.237.39')
    ->set('deploy_path', '~/{{application}}')
    ->user($user)
    ->forwardAgent(true);

// set('branch', getenv('GIT_BRANCH'));
set('branch', "master");

