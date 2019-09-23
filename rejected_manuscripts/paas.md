# PaaS

## 概要

PaaSはPlatform as a serviceの略です。「コードを書く、Addonを有効にする、デプロイする」というステップで開発ができ、インフラの運用もおまかせで、さらにスケールアウトやスケールアップで負荷へ柔軟な対応が可能です。

短所としては、転入時や転出時はコードや運用方法をが大きくと変えざるを得ない事と、規模が大きくなるにつれ費用が増大する事です。とはいえ初期はお金より開発のスピード感が重要な現場は多いでしょう。

## 選定

以下にPaaSの一例を挙げます。

- Heroku https://jp.heroku.com/
- Microsoft Azure Web App Service https://azure.microsoft.com/ja-jp/services/app-service/web/
- Google App Engine https://cloud.google.com/appengine/
- Amazon Web Sercice Elastic beanstalk https://aws.amazon.com/jp/elasticbeanstalk/

PaaSに定番といえるスタイルはなく、進化の速度も早いため、各社でかなりの差異があります。この中でGAE（FaaS風）やEastic Beanstalk（IaaS風）は特に差があります。

今回はPaaSの代名詞ともいえるHerokuを前提といたします。

## 環境構築

### 設定

Herokuは非常によくできたチュートリアルがあり、それをたどれば小一時間でサンプルが公開できます。

> https://devcenter.heroku.com/articles/getting-started-with-php

抜粋すると、herokuにサインアップをした後、Heroku CLI をインストールします。そのherokuコマンドをつかって`heroku login`を実行完了すれば準備は完了です。

あとは`heroku create`でPaaS上にアプリが作成され、自前、あるいはサンプルアプリのgitとひも付ければ、後は`git push`でデプロイでき、アプリの公開が開始されます。非常にスピーディーです。

## 開発

PaaSでは永続的に稼働するVPSやレンサバと異なり、常時サーバーインスタンス（Dyno）の削除と作成が繰り返されます。その際にはストレージ（HDDやSSD）にあるファイルは都度消滅するため、それが問題とならない設計が必要です。

一体どうやって？と思うかもしれませんが。DBが別のサーバーにあるのはレンサバでも普通であり、ファイルも同様に外部のストレージに格納するようにして、セッション、Cronなどは提供されているAddon（追加のマネージドなオプション）を活用すれば可能です。

### データベース

HerokuではDBもAddonとして提供されており、例としてPostgreSQLを使う場合は以下のように実行すればAddonが追加できます。

```
$ heroku addons:create heroku-postgresql:hobby-dev
```

> Heroku Postgres | Heroku Dev Center https://devcenter.heroku.com/articles/heroku-postgresql

後はPHPから接続するだけですが、DB接続情報はコンパネなどで確認するのではなく、実行時にPHPから参照できる環境変数をパースして利用します。以下はその例です。

```php
$db = parse_url(getenv("DATABASE_URL"));
$pdo = new PDO("pgsql:" . sprintf(
    "host=%s;port=%s;user=%s;password=%s;dbname=%s",
    $db["host"],
    $db["port"],
    $db["user"],
    $db["pass"],
    ltrim($db["path"], "/")
));
```

また、DBに直接SQLを発行したい作業は、`heroku pg:psql`と実行することで、psqlコマンドが利用できます。

> aptやbrewで`psql`コマンドがつかえるように準備が必要です。詳しくはHerokuのドキュメントを参照してください。
> あるいは`heroku run bash`でDynoにログインすれば`psql`コマンドが入っているので、環境変数を見て接続も可能です
> ```
> $ heroku run bash -a {app_name}
> Running bash on ⬢ myapp... up, run.8782 (Free)
> $ export|grep DATABASE_URL
> declare -x DATABASE_URL="postgres://{ユーザー名}:{パスワード}@{ホスト名}:{ポート}/{DB名}"
> $ psql -U {ユーザー名} -W -h {ホスト名} -p {ポート} {DB名}
> Password: {パスワード}
> Type "help" for help.
> 
> XXXXXXXXX=> CREATE TABLE ...
> ```

### セッション

PHPの`$_SESSION`を利用するには、DB同様にDyno外にセッション情報を保存する必要があります。今回はアドオンのRedisに保存します。

`redisの追加`
```bash
$ heroku addons:create heroku-redis:hobby-dev
# クレジットカードが登録されていないとredisは有効化できません
```

> Heroku Redis - Add-ons - Heroku Elements https://elements.heroku.com/addons/heroku-redis

Redisにセッション情報を保存する例です。DB同様に環境変数をパースして接続情報に変換する必要があります。

PHPはSession handlerを変更、あるいは実装することで`$_SESSION`などをそのまま使うことができますのでコード全体の修正は不要です。あるいはLaravelなどは独自に対応をしていますのでLaravelのドキュメントを参照ください。

`redis＋Predisによるセッション設定例`
```php
# 事前に composer require predis/predis でライブラリを追加しておく
# session_start()よりも前に以下が実行されるように
$parsed_redis_url = parse_url(getenv("REDIS_URL"));
$client = new Client(
  [
    'scheme' => 'tcp',
    'host' => $parsed_redis_url['host'],
    'port' => $parsed_redis_url['port'],
    'password' => $parsed_redis_url['pass']
  ],
  ['prefix' => 'sessions:']
);

$handler = new Handler($client);
$handler->register();
// これ以後はいつもの`session_start()`でセッション開始可能
```

`heroku redis:cli`でredis cliに接続し、`keys *`を実行すれば保存されているかを確認できます。(前述のコードなら、`sessions:`といったキーがあるはずです)

```
$ heroku redis:cli
 ▸    To proceed, type {あなたのアプリ名} or re-run this command with --confirm {あなたのアプリ名}
> {ここで、あなたのアプリ名を入力してEnter}
Connecting to redis-***** (REDIS_URL):
****.com:xxxx> keys *
1) sessions:v------------------------------i
```

### ログ

スケールアウトすると各Dynoにログがばらけてしまいますが、Addonの追加なしに`heroku logs`コマンドで集約確認できます。ただ、これは短期的なものに限られるため、`heroku logs -t`にて待ち受けつつ確認しましょう。


あるいは、papertrailなどの専用ログ集約＆検索Addonを利用しましょう。

```
$ heroku addons:create papertrail:choklad
# Heroku ダッシュボードからPapertrailを開くことで、確認ができます。
```

> Papertrail - Add-ons - Heroku Elements https://elements.heroku.com/addons/papertrail

### ストレージ

HerokuではファイルはAWS S3に保存することが一般的です。

S3の設定はここでは割愛しますが（AWSのドキュメントをご確認ください）、S3のバケットを作成し、そのS3 bucketにアクセスできるアクセスキーとシークレットアクセスキーが必要です。

AWS公式のPHPライブラリは、PHPを拡張するStream wrapperが提供されており、`file_get_contents`などに直接`s3://作成したBucket名/file_name`などとURL指定するだけでファイルの読み書きでき、特に既存コードからの書き換えにおいて便利です。

> AWS SDK for PHP | AWS https://aws.amazon.com/jp/sdk-for-php/

ただし、Stream wrapper経由だとACLが設定できないため、画像のサムネイルなど直接S3からユーザーへ配信させたい場合は別途`putObjectAcl`でACLの変更が必要です、なおミスでACLをpublicにできないようにAWSは安全対策をとっており、S3側での設定も必要です。

`stream wrapper機能をつかったファイルの書き込み例`
```php
# AWS_ACCESS_KEY_IDと、AWS_SECRET_ACCESS_KEYは環境変数に設定しておく
$s3 = new S3Client(['version' => 'latest',
                    'region' => 'ap-northeast-1' ]);
$s3->registerStreamWrapper(); // Stream wrapperの有効化
$bucket = "作成したBuket名";
# ファイルのS3への書き込み（同様に読み込みも可能）
file_put_contents("s3://{$bucket}/filename.txt", $data);
# 必要ならば、public-read化(インターネットに直接公開したいファイルのみ！)
$result = $s3_client->putObjectAcl([
        'ACL' => 'public-read',
        'Bucket' => $bucket,
        'Key' => "filename.txt",
    ]);
```

### 設定（環境変数）の定義

`heroku config:set` にて設定します、一度設定した値は`heroku config:unset`されるまで 保持されます。これは環境変数なので、コードからは`getenv`関数にて参照できます。

`設定方法例`
```
# 設定
$ heroku config:set DB_TYPE=sqlite
# 確認
$ heroku config
=== {アプリ名} Config Vars
DB_TYPE:         sqlite
```

`PHPコード例`
```
echo getenv("DB_TYPE");
```

### 開発・確認環境の作成

Herokuにはアプリを（コストが許す限り）いくつも作成できますが、Herokuアプリにデプロイサれるファイルは「アプリごとにある、Heroku内部のGitレポジトリのmasterブランチ」と結合しています。そしてそのmasterブランチは、通常は手元のアプリのgitレポジトリのmasterブランチと同一のブランチ（＝内容）です。

> なおデプロイにつかう`git push heroku master`は、現在のmasterブランチを、heroku内部のGitレポジトリのmasterブランチにpushしています。

しかし、通常のgitを利用した開発は`master`ブランチからtopicブランチを切って作業します(ここでは`my-dev`とする)。その最中で`master`ではない`my-dev`ブランチをためしにデプロイしたい事は当然発生し、それは `git push heroku my-dev:master`などとすれば可能です。

ただ、本来はmasterにマージされてからpushされるべき所を逸脱した行為であり、その後`my-dev`ブランチ以外のもともとのの`master`を含む別ブランチをpushすると、Heroku内部のGitで不整合となり、デプロイが失敗します。まったく別のコミットツリーを`master`としてpushしているのでこれは必然です。これは`--force`オプションを利用することで検証を無視してpushする使い方もありますが、本来は安全のために拒否しているのであり、本当に間違えた時（たとえば開発中のブランチを本番環境にpushしてしまうなど）破滅的な事態となるため、習慣とするのは避けるべきでしょう。また、その危険を受容してコードをforce pushしても、DBのマイグレーションをどうするかの問題も残ります。

現実的には固有のブランチごとにアプリを作成し、そこは特定のブランチに固定して運用することになるでしょう。これなら間違えても、今度こそ正しくコンフリクトで気づけるはずです。なお、ローカルにcloneされたgitレポジトリとアプリとひも付けは、以下のようにすれば変更可能です。

```
$ git checkout my-dev
$ heroku git:remote --app {アプリ名}
$ git push heroku my-dev:master
```

可能なら、Herokuが推奨するCI連携や、Review Apps、Pipeline機能を活用した高度なワークフローへ移行を検討しましょう。

> Heroku と継続的デリバリー | Heroku https://jp.heroku.com/continuous-delivery
> Review Apps | Heroku Dev Center https://devcenter.heroku.com/articles/github-integration-review-apps
> Pipelines | Heroku Dev Center https://devcenter.heroku.com/articles/pipelines

### cron

Herokuでは、SchedulerというAddonを用いてCronが実行できます。

```
$ heroku addons:create scheduler:standard
$ heroku addons:open scheduler
# ウェブブラウザで、設定画面が開きます
```

> > Heroku Scheduler | Heroku Dev Center https://devcenter.heroku.com/articles/scheduler

cronに指定するのはなんらかの実行コマンドで、基本的には`heroku run {command}`で実行できるコマンドを指定することになります。例えば`heroku run .heroku/php/bin/php path/to/your.php`として実行するコマンドなら、`.heroku/php/bin/php path/to/your.php`を設定します。

Heroku内部はGitレポジトリのrootディレクトリが`/app`にマウントされるイメージですが、一度`heroku run bash`でログインして確認するとよいでしょう。

cron実行時のログはアプリケーションサーバー同様に`heroku logs`で確認ができます。

なお、HerokuはUTCで動作しており、日本標準時とは時差があります。考慮して指定してください。

### 日時

HerokuはUTC（協定世界時）の動作を想定しているために日本標準時（JST）と9時間の時差があります、よって日時を扱うのに注意が必要です。時刻をどの様に扱うかは様々なやり方がありますが、内部的にはすべて`DateTime`などで処理し、保存時はUTCやUnix秒。表示やユーザー入力haJSTと時差変換をするのが一般的です。

なお、php.iniで`date.timezone`の設定をすると`date`や`strtotime`関数などは指定のタイムゾーンで変換・計算しますが、えられた日時文字列でDBへクエリすると時刻ズレが発生します。慣れていても、局所的に対応すると暗黙的でミスにつながります。`DateTimeZone`で時差を明示できる`DateTime`などをつかうとよいでしょう。

`DateTime利用例`
```
# 日本時刻の日時文字列をパース
$dt = new DateTime("2019/08/01 00:00:00", new DateTimeZone("Asia/Tokyo"));
echo $dt->format(DateTime::ATOM);
# => 2019-08-01T00:00:00+09:00 ← 時差が保持されている
$dt->setTimezone(new DateTimeZone("UTC")); # インスタンスをUTCに変更
echo $dt->format("Y/m/d H:i:s");;
# => 2019/07/31 15:00:00 ← 正しくUTCで出力
```

### メール送信

MailgunなどのAddonを使いましょう。

```
$  heroku addons:create mailgun:starter
```

> Mailgun - Add-ons - Heroku Elements https://elements.heroku.com/addons/mailgun

MailgunはWeb API経由か、SMTPによる送信が可能です。すでにSwiftmailerなどを用いている場合はSMTPによる送信が検討できますが、ここではMailgun公式のライブラリ利用例を記述します。

> mailgun/mailgun-php: Mailgun's Official SDK for PHP https://github.com/mailgun/mailgun-php

`composer でライブラリのインストール`
```
$ php composer.phar require mailgun/mailgun-php
```

`mailgun_sample.php`
```php
<?php
require 'vendor/autoload.php';

$mg = \Mailgun\Mailgun::create((getenv('MAILGUN_API_KEY');

$mg->send("my-domain.example.jp",[
    'from'=>'my@my-domain.example.jp',
    'to'=> 'user@users-domain.example.jp',
    'subject' => '件名を入力',
    'text' => 'こんにちは！本文です！'
]);
```

なお、テストしたい場合は、MailgunはPostbinという仕組みを用いてデバッグができます。詳しくは後述のURLを参照してください。

> https://github.com/mailgun/mailgun-php#debugging

## 運用

### バックアップ

herokuは基本的にgit経由でのデプロイとなるため、開発マシンにコードがあり、GitHubなどにもpushされていればコードのバックアップはできているかと思われます。

データベースのバックアップはherokuコマンドから行えます。

```
$ heroku pg:backups:capture
Backing up DATABASE to b001... done
# 必要なら、バックアップを手元にダウンロード
$ heroku pg:backups:download b001
Getting backup from ⬢ myapp... done, #1
$ ls latest.dump
latest.dump
```

自動バックアップは以下のように設定します。

```
$ heroku pg:backups:schedule DATABASE_URL --at '04:00 Asia/Tokyo'
Scheduling automatic daily backups of postgresql-clean-XXXXX at 04:00 Asia/Tokyo... done
# 確認
$ heroku pg:backups:schedules
=== Backup Schedules
DATABASE_URL: daily at 4:00 Asia/Tokyo
# スケジュール削除
$ heroku pg:backups:unschedule DATABASE_URL
```

レストアは`pg:backups:info`でバックアップのIDを確認し、指定して実行します。

```
$ heroku pg:backups:info
=== Backup b001
Database:         DATABASE
Finished at:      2019-08-19 07:49:50 +0000
# ID b001を指定してレストア実行
$ heroku pg:backups:restore b001 DATABASE_URL
 ▸    To proceed, type {アプリ名} or re-run this command with --confirm {アプリ名}

> {アプリ名を入力してEnter}
Starting restore of b001 to postgresql-clean-76924... done
Restoring... done
```

バックアップを他のHeroku内のアプリに転送したりもできます、ドキュメントを確認してください。

> Heroku PGBackups | Heroku Dev Center https://devcenter.heroku.com/articles/heroku-postgres-backups

設定（環境変数）については`heroku config`出力を保存しておくとよいでしょう。

```
$ heroku config
=== myapp Config Vars
DB_TYPE:                   heroku_pg
...
```

### 監視

通常通り、外形監視はしておきましょう。また、やはり別途ウェブアプリ以外にユーザーとコミュニケーションできるSNSアカウントなどを作りましょう。

Heroku自体の障害情報はHeroku Statusから確認できます、こちらをブックマークしておきましょう。

> Heroku Status https://status.heroku.com/

Heroku Status上で正常なのにアプリが正しく動作しない場合は、（Twitterなどで同様の事例がないか検索してみた上で）自分でログなども確認し、どうしても解決できなければHerokuに問い合わせをしましょう。（ただ、時間と共に解決する問題も多いです）

障害かな？と思ったら、単にレスポンスタイムがデータ量の増大とともに悪化しているだけの場合もあります。そのような監視にはNewRelicが便利です。Addonにもありますので、検討してみるとよいでしょう。

> New Relic APM - Add-ons - Heroku Elements https://elements.heroku.com/addons/newrelic

## まとめ

PaaSは特に少人数小規模においては便利でスピード感ある開発が可能です。しかしながら独特の流儀や癖があり、それに逆らうと余計に困難な問題に直面する事があります。PaaSの思想にのっとった使い方をしましょう。

また、基本的にはブラックボックスですので、なにかがおかしい時に詳しい調査が困難なこともしばしばあります、そちらもトレードオフとして覚悟が必要です。

その他に、筆者の意図でもある可搬性の観点からいうと、便利なAddonを増やすと客観的に高度なインフラ構成となってしまいます。覚悟をきめてPaaSと一蓮托生するか、安易なAddon追加の誘惑に耐えて移設を考慮した設計とするかは常に意識しましょう。
