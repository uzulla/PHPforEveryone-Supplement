以下、書籍の補完、追記となります。

# ログ

teeがあれば、画面へ出しつつファイルにも保存が可能です。

```
$ php -S 127.0.0.1:8080 2>&1 | tee log
```

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



# Basic認証とIP制限の実装例

静的ファイルは不可能だが、PHPの動的な箇所ならばBasic認証をつくる事はできる。

```
<?php 
# 実コードでは$plain_username,$hashed_password,$allow_ip_listは
# このようにコードには書かず、安全な場所に記述して参照してください。
# hashed_passwordは以下のようにして生成できます。
# $ php -r 'echo password_hash("very_difficult_password", PASSWORD_DEFAULT).PHP_EOL;'

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

// ロードバランサやプロキシが入る場合はREMOTE_ADDRは使えません
$client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? false;
if ($client_ip === false || array_search($client_ip, $allow_ip_list) === false) {
    error_log("IP制限");
    http_response_code(403);
    echo "IP制限";
    exit;
}

echo "認証成功";
// ...
```

# ルータースクリプト

> ルータースクリプトの情報 https://www.php.net/manual/ja/features.commandline.webserver.php


# socatをTLS Termination Proxy となるリバースプロキシに用いたビルトインウェブサーバーでのhttps通信の実現

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

# 環境変数を設定して、ビルトインウェブサーバーを起動するシェルスクリプト

```
#!/bin/bash

# 環境変数
export ENV=dev
export MY_NAME=hogehoge

# 起動
php -S 127.0.0.1:8080
```
