# IaaS

## 概要

IaaSとはInfrastructure as a serviceの略で、ここでは仮想マシン、つまりはEC2などを指します。単体ではVPSと似ています。大きく異なるのは、インスタンス（サーバー）の作成、削除、イメージの作成などをウェブコンソールなどから行う事ができ、スケールアウト（サーバーを並べて性能を向上させる）が実現できる事です。また、追加のマネージドサービスのオプションがあり、それらと組み合わせて一部の管理運用やチューニングを委託できます。

スケールアウトやマネージドサービス(PaaSだとAddon)をみればPaaSとも近いですが、自由度が高い分PaaSよりも構築に知識が必要です。

## クラウドベンダーの選定

- Amazon Web Services （AWS） https://aws.amazon.com/jp/
- Google Cloud Platform （GCP） https://cloud.google.com/?hl=ja
- Microsoft Azure Cloud Computing Platform & Services https://azure.microsoft.com/ja-jp/

各社は世界規模で多様な機能を取り揃えています。上記以外にも様々なクラウドがありますが提供されるマネージドサービスには差があります。PHPでもオブジェクトストレージのS3、RDBMSのRDS、ロードバランサのELB、監視やログ集約のCloudWatch Logなどはよく使われます。これらの活用で特にクラウドの恩恵がうけられますので、それらを抜きにIaaSだけで比較しないようにしましょう。今回はAWSを想定します。

## 設定

AWSは情報が豊富です。しかしながら速いスピードで変化しており、本書も皆さんが読む時点で乖離している可能性がありますので注意してください（最新の情報は、公式の英語ドキュメントを参照しましょう）。

最初のとっかかりとしては、公式ドキュメントを用いてEC2 インスタンスを起動できるようになる事です。執筆時点では以下のドキュメントです。

> Amazon EC2 でのセットアップ - Amazon Elastic Compute Cloud https://docs.aws.amazon.com/ja_jp/AWSEC2/latest/UserGuide/get-set-up-for-amazon-ec2.html

以下、上記ドキュメントを進めるにあたってつまずきやすい箇所や、PHPアプリケーション開発で必要となるマネージドサービスの設定を記述していきます。

### VPC(仮想ネットワーク)設定

VPSではサーバーが直接インターネットに接続されており、IPパケットフィルタリング程度の知識でサービスが提供できますが、AWSはVPCと呼ばれる仮想的なネットワーク設計が必要です。

単純な構成ではVPCを一つ作成した後、インターネットゲートウェイを作成してそのVPCにアタッチ。ルートテーブルを編集し、別々のアベイラビリティゾーン（AZ）を指定したサブネットを2つを追加する（一部機能は複数AZが前提で、AZはサブネットで指定するため）構成です。今回は踏み台サーバーなどを作らないのでサブネットの「パブリックIPv4アドレスの自動割り当て」は有効にしてください。

上記作成時にはIPアドレスの設計をする必要があります。今回は単純にVPCに`10.1.0.0/16`を、サブネットには`10.1.0.0/24`と`10.1.1.0/24`をそれぞれ設定し、ルートテーブルで送信先`0.0.0.0/0`のターゲットを作成したインターネットゲートウェイに設定します。

### EC2(サーバーインスタンス)作成

EC2にはAMI(Amazon Machine Image)という既成のディスクイメージがあり、今回公式のAMIをベースに環境を構築します。今回はUbuntu LTS18.04（`ami-0eeb679d57500a06c`）を選択しました。インスタンスサイズは最小の`t2.micro`でかまいません。

セキュリティグループとはいわゆるIPパケットフィルタリングです。新しく作成し、ルール追加でSSHに加えて、タイプ「HTTP」を追加します。ソース（接続元IP）には`0.0.0.0/0`を指定しますが、この時「インターネットからアクセスができます（世界に向けて開かれています）」などの警告が表示されます。後述のELBを使うなどして直接インターネットからインスタンスへ接続が不要ならばアクセス制限するのがセキュリティ上は好ましいですが、今回は簡略化のために直接インターネットからインスタンスへアクセスができるようにします、理解の上ですすみましょう。

起動直前にインスタンスにSSHログインする際に使うキーペアを作成（あるいは選択）します。ここでダウンロードした秘密鍵は復旧できません、安全な場所や`~/.ssh`などに保存し、macやLinuxでは`$ chmod 600 my_aws_key.pem`などとしてパーミッションを修正しましょう。

### ログインしての基本的な設定

早速sshでサーバーにログインします。EC2インスタンス一覧から起動したインスタンスを選択し、下部の情報欄のIPv4 パブリック IPに接続します（以下ではx.x.x.xと記述します）。なお、Ubuntuではログインユーザー名は`ubuntu`です。

`OpenSSH clientを使う場合`

```
$ ssh -i ~/.ssh/my_aws_key.pem ubuntu@x.x.x.x
```

ログインできない場合はエラーメッセージを確認し、秘密鍵にや指定にミスはないか。接続ができないならIPを間違えていないか、セキュリティグループでSSHが許可されているか、VPCの設定(ルートやゲートウェイ)を失敗していないかなどを確認しましょう。

### UbuntuにおけるApache+PHPの設定

手動での設定はVPSの節を参照ください。Ansibleなどの構成管理ツールを使うことで、構成情報をgitなどで明確に管理できるようにもなります。Ansibleはsshで自動的な作業をリモート実行でき、環境構築作業の`apt install`や設定ファイルをアップロード、デーモンの再起動などができます。

>  Ansible Documentation https://docs.ansible.com/

`ansible実行例`

```
$ ansible-playbook -i hosts.dev install_middleware.yml
{略}
TASK [Install a list of packages] *************
ok: [x.x.x.x]
{略}

x.x.x.x               : ok=3    changed=0    unreachable=0    failed=0
```

`Ansibleの playbook 例`

```
- hosts: web-server
  tasks:
    - name: Install a list of packages
      apt:
        name: "{{ packages }}"
      vars:
        packages:
          - apache2
```


紙面の都合上、サンプルコードは以下のURLを参照ください。

> https://github.com/uzulla/PHPforEveryone-Supplement

### デプロイ

手動のデプロイはVPSの節を参照ください。前節のセットアップと同様、デプロイツールをつかうことで１コマンドでデプロイを実行できます。PHPではDeployerが有名です。

> Deployer https://deployer.org/

`deployer 実行例`

```
$ php deployer.phar deploy
Stage is dev
✈︎ Deploying master on x.x.x.x
{省略}
➤ Executing task deploy:update_code
Cloning into '/home/ubuntu/myapp/releases/2'...
Counting objects: 372, done.
Compressing objects: 100% (196/196), done.
Writing objects: 100% (372/372), done.
Total 372 (delta 137), reused 372 (delta 137)
Connection to y.y.y.y closed.
✔ Ok
{省略}
Successfully deployed!
```

Deployerは設定ファイルの`deploy.php`自体がPHPコードなので、AWSのAPIを操作し、インスタンスIPリストを生成してデプロイなども可能です。

実行時にはインスタンスにはsshで接続できる環境が必要ですが、残念ながら踏み台サーバー(jump server)経由でのアクセス機能が組み込まれていません。パブリックIPがないインスタンスにデプロイするなら、後述のopen ssh設定で踏み台サーバー上でDeployerを実行したり、sshの詳細な設定が可能なAnsibleを用いて各インスタンスでDeployerを実行するなどが考えられます。

`.ssh/config例`

```
Host my-private-ip-server
    HostName {インスタンスのVPC内プライベートIP}
    ProxyCommand ssh -W %h:%p {踏み台サーバーホスト名}
```

紙面の都合上、サンプルコードは以下のURLにて確認ください。

https://github.com/uzulla/PHPforEveryone-uzulla-sample

### RDBMS（データベース）

EC2インスタンス上に自分でMysqlなどを用意もできますが、マネージドなRDBMSを使えば保守・運用・バックアップ・チューニングなどを任せる事ができます。

AWSにはRDSがあり、MysqlやPostgreSQLなどを選べます。またAuroraと呼ばれるDBがあり、少し前のバージョンのMysqlやPostgreSQLとの互換性をもちながらさらなる性能と可用性、拡張性を主張しています。検討してもよいでしょう。今回はMysqlを想定します。

RDS インスタンスの作成はコントロールパネルから可能です。インスタンスサイズは初めてなら最小の`micro`でよいでしょう、後でのインスタンスの変更は簡単に行えます（手法によりますが、一番単純な手法なら数分〜十分程度のダウンタイムが必要です）。マルチAZについても実サービス運用時には検討してください。ユーザー名、DB名、パスワードは任意でかまいませんが、パスワードは後で確認できませんので注意してください。

EC2と同様セキュリティグループは設定が必須です。新規作成し、インバウンドルールにタイプ「MYSQL/Aurora」。ソース（接続元）はカスタムで、DBへ接続するEC2インスタンスのセキュリティグループIDを設定します(EC2の設定から確認してください)。

作成後RDSインスタンスのステータスが「利用可能」に変わると、DBの接続情報が確認できます。

`EC2 インスタンスから RDS接続例`
```
$ sudo apt install mysql-client
$ mysql -h {エンドポイント(ホスト名)} -u {マスタユーザー名} -p {DB名}
Enter password: {パスワードを入力}
mysql>
```

もし接続できない場合は特にVPC設定やセキュリティグループを確認してください。

EC2インスタンスからでなく手元のPCから接続したい場合は、EC2 インスタンスを踏み台としてsshポートフォワードで接続したり、あるいは最初からssh接続に対応したツール（例: Sequel Proや、dbeaverなど）を使うとよいでしょう。


なお、RDSインスタンスへはログインができません、よって一部のSQL（例: `SELECT 〜 INTO OUTFILE 〜`でCSVへの出力など）やファイル出力設定のスロークエリログはつかえません（スロークエリログ自体は別途確認できます、AWSのドキュメントを参照してください）。

また、RDSは脆弱性のパッチ対応も自動対応されますが、強制的にダウンタイムが発生します。必ず詳細を確認しましょう。

RDSはPaaS(Heroku)同様にタイムゾーンはUTCで動作します。設定をすればJSTはつかえますが、他のコンポーネントも基本UTCとなっており、あちこちでタイムゾーンを変えるのは大変な上にミスの元です。UTC前提での設計としましょう（具体的にはPaaSの節を確認ください）。


### セッションストレージ

PaaSと同様にスケールアウトするならば別途KVSなどを用意してセッション情報を保持します。AWSではElasticacheと呼ばれるマネージドなKVSが提供されており、Memcachedやredisなどが選択できます。注意点はほぼRDSと同様なので省略します。

> 性能が必要ない場合は、ライブラリの利用や自分でセッションハンドラを実装することで、RDS（のMysqlなど）をセッションストレージとする方法もあります。

### ストレージ

S3をつかうのが一般的です、利用方法はPaaSの節を参照してください。

S3以外にEFSというEC2インスタンスからで直接NFSとしてマウントできるストレージも提供されています。要件で通常のディスクのように扱いたい場合は検討してみてください。ただし、S3のように直接インターネットに配信する機能はありません。

### メール送信

EC2インスタンスにはMTA(Postfixなど)を導入できますが、EC2から外部へのメール送信はSPAM対策として規制されています。AWSの提供するSESや、PaaS同様に外部サービスのMailgunなどを利用する方が良いでしょう。

もしSESを利用する場合、本番運用前には送信レート制限を引き上げる申請を行い、SPAM判定がなされないようにSPFやDKIMの設定を行いましょう。また、外部のサービスを利用する場合でも前述の理由で25番ポートのSMTPを用いず、Web API経由での送信や、25番以外のポート（587や465）での送信をしてください。


> Transactional Email API Service For Developers | Mailgun https://www.mailgun.com/
> EC2 インスタンスのポート 25 の抑制を解除する https://aws.amazon.com/jp/premiumsupport/knowledge-center/ec2-port-25-throttle/
> Amazon SES の送信制限の管理 - Amazon Simple Email Service https://docs.aws.amazon.com/ja_jp/ses/latest/DeveloperGuide/manage-sending-limits.html

### スケールアップ

スケールアップとは、サーバーインスタンス１台あたりの性能上げる事です。現状をAMIとして保存し、より強力なインスタンスで起動します（AMIとしての保存方法は後述)。

インスタンスを変更する際に外部から見えるパブリックIPアドレスを固定するための仕組みとしてElasticIPがあり、動作中のインスタンス間での付け替えも可能です。

### スケールアウト、AMIの作成

EC2のインスタンスを複数起動し性能を向上させるスケールアウトは、インスタンスを複数作成して、ELBでアクセスを分散させる事でできます。インスタンスを複数つくるのには、元となる既存インスタンスからAMIを作成し、そこから複製をつくるのが一般的です。

今回はスケールアウトの挙動を確認するため、以下の`index.php`を設置して、アクセスすると"hello! I am i-XXXXX" などと表示されることを確認してから進んでください。

```
<?php
echo "hello! I am ".file_get_contents("http://169.254.169.254/latest/meta-data/instance-id");
# インスタンスメタデータから自身のインスタンスIDを取得しています
```

まず適切にセットアップされたEC2を用意したらそれをAMI化します。ウェブコンソールインスタンス一覧より、イメージ→イメージの作成ウィザードを実行します、AMI名称は任意でかまいません。この作業時にインスタンスのスナップショット（その時点のバックアップ）が取得され、それがAMIとして登録されます。「再起動しない」チェックボックスで稼働したままスナップショットを作成しますが、正しく動作しない状態で取得される可能性があります。

AMIの一覧にてステータスが「available」と利用が可能になるのを待ちます(数分〜十分程度)。完了したら、AMI一覧から選択し、新しくインスタンスを作成してみましょう。起動したら元のインスタンスと同様に動作するかを確認しましょう。

これが単純なAMIイメージの作成とそれを用いたインスタンス作成です。確認ができたらインスタンスを「終了」して消してください。なお、AMIを保持し続けるのも費用が発生します。不要になればAMI一覧から登録解除し、参照するスナップショットを削除しましょう。

### スケールアウト、ELB

次にELBを設定します。ターゲットグループの作成、ALB（ELBの種類）の作成、ターゲットグループへのEC2インスタンスの追加を行います。

まずEC2ダッシュボード左側のロードバランシングからターゲットグループを開き、ターゲットグループを作成します。ターゲットの種類はインスタンスを設定し、今回はプロトコルにはHTTPを指定しておきます。

次にロードバランシング→ロードバランサをひらき、ロードバランサーを作成します。今回ALB（Appllication Load Balancer）を選択します。

名前をつけて、スキームは「インターネット向け」とし、今回「リスナー」にはHTTPを設定します。HTTPSを利用するには証明書の登録が別途必要なので、今回割愛します。この時「いずれのセキュアリスナーも使用していません」と警告が表示されますが、今回はそのまま進みます。

AZには、EC2インスタンスを作成するサブネットを少なくとも2つ指定します。

セキュリティグループはEC2と同様ですが、新しいセキュリティグループを作成し、タイプ「HTTP」を設定しておきます。

ルーティングの設定では、ターゲットグループに「既存のターゲットグループ」で先程作成したターゲットグループを設定します。進めると、この段階ではターゲットグループに登録されているターゲットがない旨が表示されますが、後で追加しますので問題は有りません。

内容を確認した後に「作成」ボタンで実際に作成してください

作成が完了すると、ロードバランサ一覧から選んでの詳細で`〜.elb.amazonaws.com`といったDNS名が確認できます。このホスト名をブラウザで開くことでアクセスができます。この時点ではターゲットグループにEC2インスタンスが１台も無いので「503 Service Temporarily Unavailable」と表示されます。

### スケールアウト、インスタンス作成とターゲットグループへの割当

今回用に作成済みのAMIよりインスタンスを１つ作成します。インスタンスが作成できたら、ロードバランシング→ターゲットグループから今回のターゲットグループを選び、情報パネルのターゲットタブの編集ボタンを押します。

インスタンス一覧より、チェックボックスでターゲットグループに追加するインスタンスを選び、「登録済みに追加」を押して登録済みターゲットにそのインスタンスを反映してから保存を押します。

登録済みターゲットリストのインスタンスは最初はステータスが`unhealthy`(動作が確認できない状態)などと表示されますが、しばらくするとヘルスチェックに合格し`Healthy`(動作中)となります。ロードバランサのURLを開いて表示されることを確認してください。

同様の要件で、もう一台作成してターゲットに追加します。正しく登録できれば二台がALBの下で振り分けアクセスされ、リロードをつづけると表示されるインスタンスIDが切り替わる（それぞれのサーバーに振り分けされている）ことが確認できます。これが（手動の）スケールアウト（複数台をならべて、負荷を分散させる）です。

このときに片方だけ、`index.php`を削除し、レスポンスに404が発生するようにします。その状態でALB経由のアクセスを繰り返すと、最初はエラーが混在してレスポンスされますが、少しするとヘルスチェックで異常が検知され、エラーサーバーには振り分けされなくなります（なおヘルスチェックはヘルスチェックURLに対して行われるもので、それ以外のURLは関係しません）、これが障害インスタンスの自動的な切り離しです。

ここでターゲットグループを確認するとステータスが`unhealthy`となっており、モニタリングタブでは「HTTP 4XX」のカウントグラフが上昇していることがわかります。

これがALBを用いた冗長化、可用性の向上です。完全にエラーをなくすことはできませんが、問題のあるサーバーを自動的に切り離す事ができました。ここで再度`index.php`をもどすと、しばらくして再度`healthy`と判定され、また自動的に戻されます。

### スケールアウト、オートスケール 

実際には、自動的にスケールアウト（台数拡大）・スケールイン（縮小）をしたいと考えるでしょう。それはオートスケールと呼びます。

EC2ダッシュボード左側からAUTO SCALING→AutoScaling グループをひらき、Auto Scalingグループを作成します。

通常のEC2インスタンス作成時と同様にすすめられますが、AMI選択時には左側タブのマイAMIより自分が作成したAMIを選択してください。

作成を押すと、そのままAuto Scalingグループの設定に遷移します。任意のグループ名をつけ、開始時のグループサイズは１としてください。

スケーリングポリシーの設定については、「スケーリングポリシーを使用して、このグループのキャパシティを調整する」を選択し、「スケーリング範囲」には、「１」および「２」インスタンスとしておきます。これがスケールアウト・スケールインの下限上限数です。

スケールグループサイズには、メトリクスタイプを指定してスケールアウト条件を設定できます。ここではCPUの平均使用率を選びます。ここで指定した数字（%）のCPUの利用率が平均的に上回った際に自動的にスケールアウトします。今回はテストなので50%にしておきます。

通知の設定では、オートスケール時のアラート（通知）を設定できます。通知してほしいタイミングにチェックをし、「通知の送信先」には任意の名前をつけ ( a-zA-Z\-な英数にしましょう。執筆時点では入力欄で警告されず、作成時に `Service: AmazonSNS 〜 Error Code: InvalidParameter;` などとわかりづらくエラーになりました) 、受信者にはメールアドレスを設定します。なお、この設定をした後「AWS Notification - Subscription Confirmation」件名のメールがとどきます、そちらで「Confirm subscription」しておきましょう。

完了するとオートスケーリンググループが作成されますので、作成後にオートスケーリンググループの一覧からの編集で「ターゲットグループ」にELB設定時に作成したターゲットグループを指定します。オートスケール時にはこの指定のターゲットグループにEC2インスタンスが自動的に追加・削除されます。

さて、実際にオートスケールするか確認しましょう。オートスケーリンググループの画面をひらくと、デフォルト１台と設定しているのですでに１台のインスタンスが作成されているはずです。sshでログインしましょう。ログインができたら、オートスケールが必要と判定させるため、CPU負荷を上げます。

```
$ yes >> /dev/null
# なにも出力されませんがCPU負荷が上がります、Ctrl+Cで終了します。
# コア数の多いインスタンスサイズだと、これだけは不足する場合があります
```

この状態で10分ほど待つと、Auto Scalingグループのインスタンス数が2に上昇し、なおかつターゲットグループに追加されていることを確認してください。増加が確認できれば、さきほどのコマンドをC-cで終了し、また10分ほど放置、インスタンス数が元に戻っていることを確認しましょう。通知ーメールが届いているかも確認してください。

これが基本的なオートスケーリングの手法です。CPU負荷ではなくネットワーク帯域などの他のメトリクスからスケール指定をすることも可能です。詳しくはAWSのドキュメントや書籍を確認してください。

なお、オートスケールで起動されるインスタンスはAMIを指定しますが、実運用ではコードが更新されAMIは古くなります。都度AMIを作成しなおしてオートスケーリンググループを作成し直すことは単純で確実ではありますが、起動時にコードを自動で更新するとよいでしょう。

### スケールアウト、起動時のデプロイ

起動直後にコードを更新する前にヘルスチェックがhealthyにならないようにデフォルトでhttpdを立ち上がらなくすることが重要です。Apacheの自動起動をオフにするには以下の様にしておき、コード更新が完了したら`systemctl start apache2`するようにします。

`自動的にApacheを起動しないようにする`
```
$ systemctl disable apache2
```

以下に起動時に`git pull`してからApacheを起動する`rc.local`の設定例を挙げます。`/tmp.startup.log`ログはCloudwatch logなどで監視しましょう。ここでは`mail`で失敗時の通知をしていますが`slackcat`などのSlackに通知するツールを使っても良いでしょう。また、可能であればDeployerなどのデプロイツールの利用も検討してください。

`rc.local の例`
```
#!/bin/bash

notify()
{
    mail your_address@example.jp -s "startup error" < /tmp/startup.log
}

set -eu
:> /tmp/startup.log

trap 'notify' ERR

echo startup `date +%Y/%m/%d\ %H:%M:%S` 2>&1 >> /tmp/startup.log
curl -s http://169.254.169.254/latest/meta-data/instance-id >> /tmp/startup.log
echo "" >> /tmp/startup.log

export GIT_SSH_COMMAND="ssh -i ~ubuntu/.ssh/deploy_key -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null"
sudo -E -u ubuntu sh -c "cd /home/ubuntu/myapp && git pull 2>&1" 2>&1 >> /tmp/startup.log
sudo -E -u ubuntu sh -c "cd /home/ubuntu/myapp && /usr/bin/php composer.phar install 2>&1" 2>&1 >> /tmp/startup.log

systemctl start apache2 2>&1 >> /tmp/startup.log
echo "startup done" 2>&1 >> /tmp/startup.log
```

## 開発

IaaSを最大限活用するには、PaaSと同様に変動する永続化が必要なデータは外部に持つ必要があります（EC2を、VPSと同様に１台だけで運用するなら、問題はありません）。そのため、プログラミング上の注意点はPaaSの節を参照してください。

### 設定ファイルの記述について

DB接続情報などといった設定はファイルに記述してAMIの中に設定ファイルとして記述する事が多いですが、EC2では起動時に指定する「Tag」でAMIの外部に設定が記述できます。以下はEC2からTagを取得する例です。

```
$ sudo apt install aws-cli
$ INSTANCE_ID=`/usr/bin/curl -s http://169.254.169.254/latest/meta-data/instance-id`
$ aws --region "ap-northeast-1" ec2 describe-instances| jq -r --arg iid $INSTANCE_ID  '.Reservations[].Instances[] | select(.InstanceId==$iid) | .Tags[] | {(.Key): .Value} ' | jq -s add
{
  "I_AM_TAG_NAME": "I_AM_TAG_VALUE",
  "ENV": "dev"
}
```

ただ、PHPはリクエストごとに初期化されるために、起動時にこれを取得するのは現実的ではありませんし、Tagは設定できる上限数があります。よって、インスタンス起動時などにこれを設定ファイルへ保存するべきでしょう。すると、起動後にTagを変更しての修正はしづらくなります。そもそも全構成でTagが適切に設定されているかの確認も面倒です。

Tagは`prod`や`stg`など、`ENV`環境変数を設定する程度に留めて（インスタンス起動後にはまず変更しないでしょう）読み込む設定ファイルを選択するためにつかうとよいでしょう。

一般的には機密情報を含む設定ファイルをgitで管理するのはセキュリティ上禁忌されていますが、設定ファイルはアプリケーションの開発にともなって頻繁に更新されるため、プライベートなrepoのみで開発されている現場は少なく有りません。

一例として、起動時に実行される`rc.local`にてTagsからENV情報をファイルに保存し、Apache経由でPHPに環境変数を渡す場合は以下のようにします。なお、`rc.local`には`chmod +x`などで実行権限を忘れずにつけてください。

`/etc/rc.local`

```
#!/bin/sh
rm ~ubuntu/MyEnvironmentFile
INSTANCE_ID=`/usr/bin/curl -s http://169.254.169.254/latest/meta-data/instance-id`
ENV=`aws --region "ap-northeast-1" ec2 describe-instances| jq -r --arg iid $INSTANCE_ID  '.Reservations[].Instances[] | select (.InstanceId==$iid) | .Tags[] | select(.Key=="ENV") | .Value ' `
echo ENV=$ENV > ~ubuntu/MyEnvironmentFile
```

`/lib/systemd/system/apache2.service`

```
[Service]
# EnvironmentFileを追加
EnvironmentFile=/path/to/MyEnvironmentFile
```

`sample.php`

```
<?php
var_dump(getenv("ENV"));
```


## 運用

### ログ

スケールアウトするサーバーにおいては各サーバーにログが分散してしまうため、その集約が課題です。以下に典型例をあげます。

- CloudWatch Logを用いる
- Papertrailなどの外部サービスを用いる
- Fluentdなどで自前で集約基盤を構築する

今回はCloudWatch logの利用例を挙げます。

> クイックスタート: 実行中の EC2 Linux インスタンスに CloudWatch Logs エージェントをインストールして設定する - Amazon CloudWatch Logs https://docs.aws.amazon.com/ja_jp/AmazonCloudWatch/latest/logs/QuickStartEC2Instance.html

CloudWatch logはエージェントをサーバーにインストールし、ログファイル監視して随時自動送信します。

送信のためにはもちろん認証設定が必要ですが、EC2はインスタンスにIAMロールを設定でき、認証情報をサーバーに保存しなくてもよい仕組みがあるので、今回はそちらを利用してみます。

まずはIAMで任意名前のポリシーを作成します。作成時は以下のJSONをセットします。

```
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": [
        "logs:CreateLogGroup",
        "logs:CreateLogStream",
        "logs:PutLogEvents",
        "logs:DescribeLogStreams"
    ],
      "Resource": [
        "arn:aws:logs:*:*:*"
    ]
  }
 ]
}
```

ポリシーが設定できたら、EC2に任意の名称のロールを作成し、先程作成したポリシーをアタッチしてください。作成したロールはEC2の起動時やEC2インスタンス一覧よりインスタンスの設定→IAMロールの割当より設定が可能です。

後はCloudWatch Logs のエージェントをインストールします。Ubuntuでは以下のようにして行います。

```
$ curl https://s3.amazonaws.com/aws-cloudwatch/downloads/latest/awslogs-agent-setup.py -O
# 執筆時、公式ドキュメント通りではエラーがでましたので、以下で回避しました
$ sudo apt install python 
$ sudo python ./awslogs-agent-setup.py --region ap-northeast-1
{省略}
Step 3 of 5: Configuring AWS CLI ...
AWS Access Key ID [None]:{EC2にロールを割り当てているので空欄}
AWS Secret Access Key [None]:{EC2にロールを割り当てているので空欄}
Default region name [us-east-1]: ap-northeast-1
Default output format [None]:{空欄}

Step 4 of 5: Configuring the CloudWatch Logs Agent ...
Path of log file to upload [/var/log/syslog]: /home/ubuntu/site/my_app/logs/error_log {← 監視したいログファイルパス}
Destination Log Group name [/home/ubuntu/site/my_app/logs/error_log]: my_app/logs/error_log {← ロググループ名称、好きなものを}

Choose Log Stream name:
  1. Use EC2 instance id.
Enter choice [1]: 1{でEnter}

Choose Log Event timestamp format:
  3. %Y-%m-%d %H:%M:%S (2008-09-08 11:52:54)
Enter choice [1]: 3{でEnter}

Choose initial position of upload:
  1. From start of file.
  2. From end of file.
Enter choice [1]: 2{でEnter}
More log files to configure? [Y]:{複数のログを監視するならYで繰り返し}

# エージェントが起動していることを確認
$ sudo systemctl status awslogs.service
   Active: active (running) since Tue 2019-08-20 04:17:57 UTC; 1h 36min ago
```

以後、ログが追記されると転送され、Cloudwatch logで集約されたログを確認できます。それぞれのインスタンスごとに見る事もできますし、ロググループ指定検索で複数のインスタンスログをまとめて確認もできます。

> https://ap-northeast-1.console.aws.amazon.com/cloudwatch/home?region=ap-northeast-1#logs:

これを複数台でおこなう（あるいは設定済みにしておいたイメージから起動する）とそれぞれのインスタンスのログが一ヶ所に転送、閲覧できるようになり、大変に便利です。

ただ、ログが大きく蓄積すると費用がかさみます。よってエラーログや行動ログなど重要なものにかぎったり、保存期間を適切に設定しましょう。削除したくない場合は、S3にアーカイブしてコストを下げる事も検討してください。

> Amazon S3 へのログデータのエクスポート - Amazon CloudWatch Logs https://docs.aws.amazon.com/ja_jp/AmazonCloudWatch/latest/logs/S3Export.html

なお、ログファイルを転送してもサーバー内のログファイルは残り続けます。ログローテーションを忘れずに行いましょう。多くのAMIはデフォルトで小さいストレージしかアタッチされていませんので、忘れるとディスクフルの障害につながります。

### 監視・運用

多くのクラウドベンダーはCloudWatchのようなメトリック監視を備えています。目視確認では見落とすこともありますので、しきい値を設定してアラートを設定しましょう。最初はディスクフルやメモリフルの監視をするとよいでしょう。

クラウドであってもPaaSと同様に外形監視は行うべきで、まれにある大規模障害にそなえ、（そのサービスもAWSをつかっていないと確認できる）外部サービスにてお客様とコミュニケーションするための手段は準備すべきです。

運用中、自力の解決が不可能な問題に遭遇する場合もあります。AWSでは基本的に技術的なサポートを受けるには有償のプランが必要です。ビジネスとして利用しているならば有料のサポートは必須と考えたほうが良いでしょう。

> AWS サポートのプラン選択 - AWS サポート | AWS https://aws.amazon.com/jp/premiumsupport/signup/

### バックアップ

コードはGitHubで管理し、データはRDSやS3を適切に運用し、ログも転送しつづけていれば、後は設定ファイルやインスタンス内のミドルウェア設定などです。

EC2インスタンスはスナップショットやAMIでバックアップもできますが、ディストリビューション（OS）のアップデートや、手元に環境を作りたい時にAMIでは対応ができません。手順書やAnsibleなどでの自動化を検討しましょう。

また、さらにいえばクラウドの構成（たとえばVPSからELBの設定まで）もバックアップもわすれずにおこないましょう、こちらもできれば（ウェブコンソールを使わず）コード化するなどしてバックアップするとよいでしょう。クラウドは簡単に環境をつくる事ができるため、たまにテスト環境をつくるなどでレストア訓練を行いましょう。


## まとめ

IaaSは拡張性が最高のインフラと言えます、使いこなす事によって世界を相手にするインフラを構築できます。今回は説明のためにウェブコンソールを用いる前提といたしましたが、クラウドをプログラムから、たとえばPHPで制御してすべての操作を全自動に行うことも可能です。

しかし、クラウドは運用の難易度も最高クラスです。クラウドを正しく使いこなすための知識と経験は簡単には習得できませんので、まずは小さいプロジェクトから使い、地道に運用と学習をしていきましょう。

