以下は書籍「みんなのPHP」、第４章「ビルトインウェブサーバー」節の追加情報等となります。



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

php.ini`に設定することでも変更が可能です。

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



もし静的なファイルが不要で（APIしか存在しないようなケース）あれば、以下のようにすることで「必ず」index.phpに処理をさせることもできます。たとえば、`.`がURLに入るケースにも適用できます。

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

