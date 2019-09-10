# メモリ消費量を確認するコード

以下のコードを先頭に挿入することで、終了時にそのリクエストでのメモリの最大消費値をログに出力します。



```
<?php
# `register_shutdown_function`を設定し、終了時にエラーログへ出力する
register_shutdown_function(function () {
  error_log("memory_get_peak_usage ".memory_get_peak_usage(false));
});
```


# UbuntuにおけるApache+mod_phpの設定

`my-site.conf`
```
<VirtualHost *:80>
  ServerName your.domain.test
  DocumentRoot /home/ubuntu/site/my_app/current/public
  ErrorLog /home/ubuntu/site/my_app/logs/error_log
  CustomLog /home/ubuntu/site/my_app/logs/access_log combined
  <Directory /home/ubuntu/site/my_app/current/public>
    Options ALL -Indexes
    AllowOverride All
    Require all granted
  </Directory>
</VirtualHost>
```

`インストール全文`
```
$ sudo apt install software-properties-common
$ sudo apt-add-repository ppa:ondrej/php
$ sudo apt update 
$ apt search php7.3-cli
php7.3-cli/bionic 7.3.8-1+ubuntu18.04.1+deb.sury.org+1 amd64
# php7.3などとでれば`ppa:ondrej/php`の追加は成功

$ sudo apt install -y apache2 php php-xml php-mbstring php-zip php-sqlite3 php-mysql make sqlite3 git mysql-server mysql-client
$ php -v
PHP 7.3.8
# 7.3 などがインストールされたことを確認

$ systemctl status apache2.service
● apache2.service - The Apache HTTP Server
   Active: active (running)
# Apache起動を確認

# `<?php phpinfo();`とファイルに記述して保存
$ sudo vi /var/www/html/myphpinfo.php
# http://{サーバーのIP}/myphpinfo.php でphpinfoの出力を確認
$ sudo rm /var/www/html/myphpinfo.php

# ubuntuのホームディレクトリ以下にDocument rootを作成
$ mkdir -p ~ubuntu/site/my_app/current/public
$ mkdir -p ~ubuntu/site/my_app/logs
$ echo ”this is my site" > ~ubuntu/site/my_app/current/public/index.html

# Virtual Hostを設定
$ sudo vi /etc/apache2/sites-available/my-site.conf
# my-site.confには以下を入力 ここから
<VirtualHost *:80>
  ServerName your.domain.test
  DocumentRoot /home/ubuntu/site/my_app/current/public
  ErrorLog /home/ubuntu/site/my_app/logs/error_log
  CustomLog /home/ubuntu/site/my_app/logs/access_log combined
  <Directory /home/ubuntu/site/my_app/current/public>
    Options ALL -Indexes
    AllowOverride All
    Require all granted
  </Directory>
</VirtualHost>
# ここまで

# 定番moduleの有効化、追加したVHの有効化をして反映
$ sudo a2enmod rewrite
$ sudo a2enmod ssl
$ sudo a2dissite 000-default
$ sudo a2ensite my-site
$ sudo systemctl restart apache2
# http://XXX.XXX.XXX.XXX/index.html をひらき、`this is my site`が出ることを確認
```


Apacheなどの設定ファイルをログインして直接変更していく場合、`/etc/apache/`ディレクトリを`git init`して都度コミットすることで、変更履歴が確認できて大変便利です。

```
$ sudo git config user.email "your.mail.addr@example.jp"
$ cd /etc/apache
$ sudo git init
$ sudo git add .
$ sudo git commit -a
# 以後、なんらか修正するたびにcommitしていく
```



# 監視用のPHP

`my_check_url_abcd456.php`
```
<?php
try{
  # DB接続確認
  $pdo = new PDO(/*適切に*/);
  $pdo->query(/*適当なSQL*/);
  # 空き容量確認
  $free_space_mb = disk_free_space("/home/ubuntu") /1024 /1024;
  if($free_space_mb<1024){
  	throw new \Exception("disk full");
  }
  echo "OK";
}catch(\Exception $e){
  http_response_code(500);
  exit($e->getMessage());
}
```

このファイルは第三者から推測されづらい、十分に長いURLにするとよいでしょう。

# エラー関連

`php.ini例`

```
# PHPのエラーログを有効にします
log_errors=1
# 画面に出力するか？
display_errors=0
# スタートアップのエラーを画面に出力するか？
display_startup_errors=0
# エラーのレベルを指定（-1であらゆるエラーを出力)
error_reporting=-1
# エラーファイルを独自指定するか？
# コメントアウトして指定しなければ、Apacheのエラーログ等に出力されます
error_log=/home/ubuntu/php_error.log
```

# xdebugの有効化、無効化

オフにしておくには以下のようにして無効にします。（UbuntuLinuxの場合）

```
$ sudo phpdismod xdebug
```

有効にしておくが、表示などはさせないなら以下のようにします。

`/etc/php/conf.d/xdebug.ini`
```
xdebug.default_enable=0
xdebug.remote_autostart=0  
xdebug.remote_enable=0
xdebug.profiler_enable=0
```

個別のサイトのみ必要なら、`.htaccess`やコード中で`ini_set`します

`.htaccess`
```
php_flag xdebug.default_enable 1
# など
```

`コード内の場合`
```
<?php
ini_set('xdebug.default_enable','1');
```


