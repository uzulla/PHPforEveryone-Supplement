## 1リクエストあたりに必要なメモリ量の確認

1リクエストあたりに必要なメモリ量は、プログラムの最後に`memory_get_peak_usage()`を実行することで確認できます。

```php
<?php
// 処理
echo memory_get_peak_usage();
```

ただ、上記だと様々なところでexitされうる実際のアプリケーションでは使いづらいので、例えば以下のように`shutdown_function`を先頭に差し込むと、プログラム終了時にログに出力することができます。

```php
<?php
register_shutdown_function(function () {
    $memory = memory_get_peak_usage(false);
    $url = $_SERVER['REQUEST_URI'] ?? "-";
    error_log("memory peak: " . sprintf("%.2f kbytes", $memory / 1024)) . " {$url}";
});

// your code.
echo "Hello, world" . PHP_EOL;
$big_array = [];
$i = 1000000;
while ($i--) { // メモリを大きく消費する
    $big_array[] = $i;
}
```

## 帯域の確認方法

帯域計測ツールとしてはspeedtest-cliなどがあります。試用期間中はネットワークに制限がかかる業者も多くありますので、可能であれば調査費用と割り切って実際に契約も行っての試験をおすすめします（その場合、最低契約期間に注意しましょう）。

> sivel/speedtest-cli https://github.com/sivel/speedtest-cli

速度だけではなくpingなどで確認できるネットワークレイテンシも重要です。ping値は時間帯で変動するので、長時間動かしてのチェックを推奨します。以下のようなコマンドを実行し、丸一日の変動を確保して確認するとよいでしょう。ping値は計測するマシンにかなり依存するので、Wifi経由でなく、Etherなど優先接続された(あるいは別のVPSなどから)計測することを勧めます。

```
$ watch -t -n 30 "(LANG=C date '+%Y-%m-%d %H:%M:%S'| tr '\n' ' ' && ping -c 3 some.example.jp |grep round) | tee -a pinglog"
```

```
$ cat pinglog
2019-10-09 12:07:16 round-trip min/avg/max/stddev = 43.002/55.466/67.397/9.966 ms
2019-10-09 12:07:41 round-trip min/avg/max/stddev = 44.805/77.981/137.774/42.365 ms
2019-10-09 12:07:46 round-trip min/avg/max/stddev = 41.737/76.068/132.125/39.971 ms
```

## ディスク容量について

ディスクサイズはOS＋アプリ＋データの３〜４倍程度で十分ですが、２倍を切ると不便が生じがちです。ただ容量を無駄に消費しやすいのがログとバックアップで、ローテーション（圧縮や削除）することで節約しましょう。

## 無料ドメインなどのキャンペーン

VPSならドメインはどこで契約してもかわりません。転出を防ぐ条項があって縛られないか注意しましょう。また、ネームサーバーの適切な運用には知識と手間が必要です。できるだけCloudFlareやAWSのRoute53などをSaaSを利用しましょう。

## UbuntuにおけるApache+mod_phpの設定

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

あるいは、AnsibleやDeployerで処理することも検討できます。[本レポジトリの`environment-setup-sample/ansbile`にサンプルがあります](environment-setup/ansible/README.md)。


### 備考 Nginx+fastCGIについて

Nginxは一般にApacheをしのぐ配信性能を持ちます。同時接続数が多い高負荷サイトでは有用ですが、NginxがPHP自体の速度を高速化するわけではありませんので効果は用途次第です。

NginxはPHPとFastCGIで連携して動作しますが、その設定はApacheとくらべてとても複雑です。また`php-fpm`は別途で設定して運用する必要があります。


## エラー関連

典型的なエラー周りの設定例を挙げます。

`php.ini例`

```
# PHPのエラーログを有効にします
log_errors=1
# 画面に出力するか？
display_errors=0
# スタートアップのエラーを画面に出力するか？
display_startup_errors=0
# エラーのレベルを指定（-1であらゆるエラーを出力、E_ALLでもかまいません)
error_reporting=-1
# エラーファイルを独自指定するか？
# コメントアウトして指定しなければSAPIに応じますが、たとえばApacheのエラーログ等に出力されます
error_log=/home/ubuntu/php_error.log
```

ログは確認しなければ意味がありませんが、標準のApacheのエラーログと一緒に出力する方式では、404 notfoundなども混じって膨大な量になって無視されがちです。`error_log`を指定して、重要であるphpのエラーのみが出力されるログを作るとよいでしょう。

また、ログ送料を減らした上でswatch(ソフトウェア)やPapertrail(SaaS)などを活用し、条件マッチでアラートを飛ばす仕組みを構築するとと便利です。

### 同時接続数増加時の対応について

計算としては、１リクエストが消費するメモリ量、あるいはphp.iniのmemory_limit ✕ maxclientということになります。

なお、(Nginx+)php-fpmの環境ではhttpdとphpの同時実行数は別々に設定できるため、このタイプのメモリ不足は起こりづらくなります。

### デプロイユーザーとPHPの実行ユーザー

デプロイ(ファイルを設置するためにログインするアカウント)ユーザーとPHPの実行ユーザーが異なると、作業に支障がある場合があります。共用のサーバーでなければログインユーザーの権限で動作させてもよいでしょう。

例えば以下のように変更することで、Apacheの実行ユーザーを変更できます。

`httpd.conf`
```
#User www-data
User ubuntu
```

## デプロイ

一般的な解決策は、別にファイル一式を別ディレクトリに準備し、、シンボリックリンクで切り替える方法です。この手法だと旧コードに戻す事も可能です。

```
# httpd等が想定するアプリのディレクトリ: /..../my_app/current
# 古い現在のアプリのディレクトリ: /..../my_app/app_20190801
# 新しいアプリのディレクトリ: /..../my_app/app_20190902

$ cp -a /..../my_app/app_20190801 /..../my_app/app_20190902
$ cd /..../my_app/app_20190902
$ git pull # ファイルの更新
$ php composer.phar install # 更新後実行すべきタスク
$ ln -sfn /..../my_app/app_20190902 /..../my_app/current
$ sudo systemctl reload apache2
```

この手法の注意点としては、アプリケーションのディレクトリ内にログや、ユーザーのアップロードファイルなどを保持してはならない事です。それらは別途アプリケーションのディレクトリより上位に作成し、シンボリックリンクしておくとよいでしょう。

```
/home/ubuntu/my_app/log: 実体となるLog保存ディレクトリ
/home/ubuntu/my_app/app_old/log: /home/ubuntu/my_app/log へのシンボリックリンク
/home/ubuntu/my_app/app_new/log: 同上
```

なお、このような定形操作はdeployerなどのデプロイツールで自動化をおすすめします。

> Deployer: https://deployer.org/

deployerの例は本レポジトリの`enviroment-setup/deployer`に添付いたします。

### DB スキーマ変更など

DBにSQLを流したい時はclientを手元にインストールし、SSHポートフォワードの機能などを用いてリモート接続するのが良いでしょう。

```
# リモートサーバーの3306ポートと、手元の33306ポートがつながる
$ ssh -L 33306:127.0.0.1:3306 your-server.example.jp
```

あるいは、Sequel ProやDBeaverなどは自前でssh portforward機能を持っています。

> DBeaver https://dbeaver.io/
> Sequel Pro https://www.sequelpro.com/

さらによいのはDB migration toolを活用することですが、今回は省略します。Laravelなどフルスタックフレームワークや、高機能なORMには用意されています。

## 監視用のPHP

`my_check_url_abcd456.php`
```
<?php
try{
  # DB接続確認
  $pdo = new PDO(/*適切に*/);
  $pdo->query(/*適当なSQL*/);
  # 空き容量確認
  $free_space_mb = disk_free_space("/home/ubuntu") /1024 /1024; // byteをMbyteに変換
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

死活監視以外のメトリック等の監視はNewRelicやMackerelなどが有名です。

> New Relic  https://newrelic.co.jp/
> Mackerel https://mackerel.io/ja/


## 設定の記述

VPSにおいても環境変数を用いる事ができます。systemd経由で起動するApache+PHPに環境変数を渡すにはsercviceファイルの`EnviromentFile`に記述するのがよいでしょう。詳しくはサンプルコードを参照してください。

`/home/ubuntu/MyEnviromentFIle (新規作成)`
```
ENV=dev
DB_USER=username
```

`/lib/systemd/system/apache2.service`
```
[Service]
# EnvironmentFile行を追加
EnvironmentFile=/home/ubuntu/MyEnviromentFIle
```

設定変更後は`systemctl daemon-reload`と`systemctl reload apache2`で反映しましょう。

## php.iniの場所

php.iniの場所はコマンドで確認するのが一番よいでしょう。

```
$ php --ini
Loaded Configuration File:         /php.ini
```

### xdebugと手元のPCとのやり取り

xdebugと手元のPCとのやり取りにはsshポートフォワードでローカルポートをリモートサーバーに転送するとよいでしょう

```
# xdebug のinstall
$ sudo apt install php-xdebug

$ ssh -R 127.0.0.1:9000:127.0.0.1:9000 your-dev-server.example.jp
# ポート番号はxdebugの設定に応じて変更してください
```

## xdebugの有効化、無効化

以下のようにして無効(読み込みをさせない)にします。

```
# Ubuntu で、パッケージで導入した場合
$ sudo phpdismod xdebug

# それ以外の場合
$ php --ini
# 上記で読み込まれているすべてのphp.iniの場所を探し、以下の行をコメントアウトする
# zend_extension=xdebug.so
```

有効にしておくが、エラー表示の拡張などはさせないなら以下のようにします。

`/etc/php/conf.d/xdebug.ini (など)`
```
xdebug.default_enable=0
xdebug.remote_autostart=0  
xdebug.remote_enable=0
xdebug.profiler_enable=0
```

個別のサイトのみ必要なら、`.htaccess`やコード中で`ini_set`します

`.htaccess`
```
php_flag xdebug.default_enable 0
# など
```

`コード内の場合`
```
<?php
ini_set('xdebug.default_enable','0');
# など
```

## 運用、パッケージのアップデート

ソフトウェアの適切なバージョンアップは脆弱性の対応に必須です。もしソフトウェアはパッケージで導入しているなら、パッケージマネージャに頼る事ができます。随時`apt update; apt upgrade`を実行し、アップデートしましょう。

> Ubuntu security notices https://usn.ubuntu.com/

なお、アップデートは定期的に自動実行もできます。その例はサンプルコードに記載いたします。

```
# UnattendedUpgradesを利用する例
$ sudo apt update
$ sudo apt install unattended-upgrades
$ sudo dpkg-reconfigure -plow unattended-upgrades
```

ただ、PHPの最新版が必要などで非公式パッケージを利用している場合、バージョンが上がりすぎてアプリに不具合が出る事もあります。確信がなければ本番をアップデートするまえに別のサーバーでテストしましょう。

また、ディストリビューションのサポートにはEOL（End of life）があります。それ以降はパッケージの更新がなくなりますので、OS再インストールなどが必要です。VPSでサービスを稼働させながらOSを再インストールするのは困難なので、EOLがみえてきたら乗り換えを計画しましょう。

