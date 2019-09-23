#!/bin/bash

# export HOSTS_FILE=hosts.local
# あるいは
# HOSTS_FILE ./samlple.sh
# などとして実行する

if [ -z "$HOSTS_FILE" ]; then
    echo "undefined HOSTS_FILE env, exit."
    exit 1;
fi

# もし実行先サーバーにPythonがなければインストールする（ansibleの要件）
ansible -i ${HOSTS_FILE} web-server -K --become -m raw -a "apt update ; apt -y install python python-simplejson"

# apt パッケージのアップデート
ansible-playbook -i ${HOSTS_FILE} 1_update_all_package.yml
# PHP最新版をいれるため、ppa:ondrejの非公式レポジトリを追加
ansible-playbook -i ${HOSTS_FILE} 2_add_ondrej-php_repo.yml
# Apache,PHPなどのミドルウェアをインストール
ansible-playbook -i ${HOSTS_FILE} 3_apt_install_apache_php_middleware.yml
# Apacheの設定ファイルなどを生成してアップロード
ansible-playbook -i ${HOSTS_FILE} 4_setup_apache.yml
# 4_setup_apacheで作成したダミーファイルを削除
ansible-playbook -i ${HOSTS_FILE} 5_remove_my_app.yml
# サンプルアプリをデプロイ
ansible-playbook -i ${HOSTS_FILE} 6_setup_sample_app.yml

# 最後にApacheを再起動
ansible -i ${HOSTS_FILE} web-server --become -m raw -a "systemctl restart apache2 ; systemctl status apache2  |cat"
