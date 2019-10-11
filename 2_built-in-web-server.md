以下は書籍「みんなのPHP」、第４章「ビルトインウェブサーバー」節の追加情報等となります。

## Windows で Ubuntuを使う手順

現在のWindows はUbuntu(Linux)を動かすことができます。Windowsで直接PHPを動かすことはできますが、他の環境との差異や、昨今のPHPをとりまくツール群の使い方には差異があるため、基本的にはWindowsでもLinux環境上でPHPを動かすことを推奨します。

なお、これはWindows 10 バージョン1709からの機能で、それ以前のWindowsはアップデートが必要です。

大まかな手順としては

- Windows Subsystem Linuxを有効化する
- Ubuntu (18.04LTS)をStoreからインストールする
- PHPをaptでインストールする
- アプリケーションを用意、作成する

となります。

### Windows Subsystem Linux（WSL）を有効化

https://docs.microsoft.com/ja-jp/windows/wsl/install-win10

スタートメニューからPowerShellを右クリックして管理者権限で実行し、以下を実行します。

```
> Enable-WindowsOptionalFeature -Online -FeatureName Microsoft-Windows-Subsystem-Linux
# `>`部分はシェルプロンプトの意味ですので、Enable〜から入力してください 
```

実行すると、再起動するか質問されるので、YとタイプしてからEnterキーで了承します。

## Ubuntuのインストール

スタートメニューからMicrosoft storeを開き、ubuntuで検索します。結果にでてくるUbuntu 18.04LTS をインストールして下さい。

インストールが完了したら、スタートメニューからUbuntuを実行します。

初回実行時、Ubuntuはユーザー名とパスワードを別途指定する必要があります。ユーザー名はアルファベットか数字のみ、記号や空白はいれないことを推奨します。（たとえば、ubuntu など）

これらが完了すると、シェル（bash）が起動します。

### PHPのインストール

UbuntuにはPHPがデフォルトでは入っていませんので、aptでインストールします。 php7.3系を入れたいので、非公式のレポジトリを追加します。

Sudo時のパスワードは先程設定したパスワードです

```
$ sudo apt install software-properties-common
$ sudo apt-add-repository ppa:ondrej/php
$ sudo apt update
$ sudo apt instal php7.3 php7.3-xml php7.3-mbstring php7.3-zip php7.3-sqlite3 php7.3-curl php7.3-gd git make sqlite3 unzip
```

インストールが完了すると、phpコマンドが利用できます

```
$ php -v 
```

でバージョンが表示されるか確認してください

### 作業ディレクトリの作成

WSL内のファイルはWindows(のExplorerなど)から直接見えませんが、`/mnt/c/` 以下が `c:\` とマップされています。`/mnt/c/project`（Windowsからは`c:\project`）を作成して、そちらで作業することにします。

```
# ディレクトリ作成、このディレクトリは c:\project\phpinfo になります
$ mkdir -p /mnt/c/project/phpinfo
$ cd /mnt/c/project/phpinfo

# phpinfo()が記述されたindex.phpを作成する。`c:\project\phpinfo\index.php` をエディタで作成してもかまいません。
$ echo "<?php phpinfo();" > index.php

# ビルトインウェブサーバーを起動
$ php -S 127.0.0.1:8080

# ビルトインウェブサーバーを起動したまま、ブラウザで http://127.0.0.1:8080/ をひらき、phpinfo出力（青い画面で、Versionが表示されている）を確認します。
# 確認ができたら、Ctrl+Cで終了
```

以上でPHPのビルトインウェブサーバーの利用準備がととのいました。以後は基本的にLinuxと同じ操作をして下さい。


### 補足：サンプルアプリケーションのデプロイ例

```
# サンプルアプリケーションのクローン（ダウンロード）
$ cd  /mnt/c/project/
$ git clone https://github.com/uzulla/Mizam.git
$ cd Mizam

# サンプルアプリケーションの初期設定（アプリに同梱された自動化スクリプトを利用）
$ make dev-setup

# アプリを起動する
$ make start
# あるいは
$ cd public
$ php -S 127.0.0.1:8080

# 起動したらブラウザで http://127.0.0.1:8080/  開きます
```

### 補足：Windowsのエディタについて

Windowsには様々なエディタがありますが、最近ですとVisual Studio codeを用いるとよいでしょう。

https://code.visualstudio.com/

AddonでPHP IntelephenseなどPHP用の拡張をいれるとよいでしょう。

## サンプルコード

https://github.com/uzulla/Mizam

### セットアップと起動

```
$ git clone git@github.com:uzulla/Mizam.git
$ cd Mizam
$ make dev-setup
# composer.pharのダウンロード、composer installの実行、サンプル設定ファイルのコピー、sqlite DBの作成などが行われます。
$ make start
# あるいは
$ cd public
$ php -S 127.0.0.1:8080
# ビルトインウェブサーバーが起動しますので、ブラウザで開いて下さい。
```

## ビルトインウェブサーバーのログ出力

### ファイルへの同時保存

teeがあれば、画面へ出しつつファイルにも保存が可能です。

```
$ php -S 127.0.0.1:8080 2>&1 | tee log
```

### php.iniでの設定

`php.ini`に設定することでも変更が可能です。

`コードに記述する例`

```
<?php
ini_set('error_log', 'my_error.log');
echo $undefined_var;
```

`php.iniに記述する例`

```
[php]
error_log='my_error.log'
```

## Basic認証とIP制限の実装例

静的ファイルは不可能ですが、PHPの動的な箇所ならばBasic認証をつくる事はできます。

`サンプルコード`

```
<?php 
# 実コードでは
# $plain_username,$hashed_password,$allow_ip_list
# はこのようにコードにうめこまないようにしましょう。
# ここで用いている hashed_password は以下のようにして生成できます。
# $ php -r 'echo password_hash("好きなパスワード", PASSWORD_DEFAULT).PHP_EOL;'

$plain_username = 'unique_user_name';
$hashed_password = '$2y$10$yeYvL5a5byS1QvTIgyCYcOWZNNApNC5Ags1J2.uZz5iwwnyfQFEfy';

$allow_ip_list = ['192.168.0.1', '127.0.0.1'];

$input_username = $_SERVER['PHP_AUTH_USER'] ?? false;
$input_password = $_SERVER['PHP_AUTH_PW'] ?? false;
if (
    !password_verify($input_password, $hashed_password) ||
    $input_username !== $plain_username
) {
    error_log("認証失敗");
    header('WWW-Authenticate: Basic realm="Basic Auth", charset="UTF-8"');
    echo "認証失敗";
    exit;
}

$client_ip = $_SERVER['REMOTE_ADDR'] ?? false;
# ロードバランサやプロキシが入る場合はREMOTE_ADDRは使えません。
# よって、以下のように"X-Forwarded−For"ヘッダーをつかいますが、
# 信用できるインフラにおいてのみ使えます。無条件に以下コードをもちいてはいけません。
# Injectionの危険性があります。
// $client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? false;

if ($client_ip === false || array_search($client_ip, $allow_ip_list) === false) {
    error_log("IP制限");
    http_response_code(403);
    echo "IP制限";
    exit;
}

echo "認証成功";
// ...
```

## ルータースクリプト例

> ルータースクリプトの情報 https://www.php.net/manual/ja/features.commandline.webserver.php

もし静的なファイルが不要で（例えばAPIとしてしか使わないケース）あれば、以下のようにすることで「必ず」index.phpに処理をさせることもできます。たとえば、`.`がURLに入るケースにも適用できます。

```
$ php -S 127.0.0.1:8080 index.php
```

## socatをTLS Termination Proxy となるリバースプロキシに用いたビルトインウェブサーバーでのhttps通信の実現

`socatを使う例`

```
# 準備
$ brew install socat # Linuxなら `sudo apt install socat`

$ openssl genrsa -out server.key 2048
$ openssl req -new -key server.key -x509 -days 9999 -out server.crt
$ cat server.key server.crt > server.key_and_crt

# PHPを起動
$ php -S 127.0.0.1:8080

# 以下は別にシェルを開いて
# socatでリバースプロキシを起動
$ socat openssl-listen:3443,fork,verify=0,cert=server.key_and_crt TCP4:localhost:8080

# この状態で、https://localhost:3443/ にアクセス
```



## 設定情報の設定例

### 環境変数を設定して、ビルトインウェブサーバーを起動するシェルスクリプト

```
#!/bin/bash

# 環境変数
export ENV=dev
export MY_NAME=hogehoge

# 起動
php -S 127.0.0.1:8080
```

環境変数をphpdotenvから読み込む例

`ライブラリの準備`

```
$ composer require vlucas/phpdotenv
```

`my.env例`

```
# 環境変数名=パラメタ と記述します。
DB_USER=my-db-user
DB_PASS="my db password!"
```

`コード例`

```
<?php
require_once(__DIR__ . "/../vendor/autoload.php");

Dotenv::create('/path/to/my.env')->load();

$my_db_user = getenv('my-db-user');
```

実際の利用例としては、サンプルコードの`src/Env.php`などをご覧ください。

