## サンプルコード

サンプルコードはレンサバ特有のディレクトリ構成に対応していないため、一度手元にDLしてファイルの再配置を行います。

```
$ git@github.com:uzulla/Mizam.git
$ make dev-setup
$ make restriction_htdocs
# 処理内容は`for_hosting_server/mk_restriction_htdocs.sh`を参照してください
$ open restriction_htdocs
```

`restriction_htdocs/`内のファイルを確認し、アップロードしてください。

ただし、レンサバのいくつかはwwwの上位にファイルを置くことができます、その場合はこれを使わずに元々のディレクトリ構成を用いることをおすすめします。

## デフォルトドメインアクセスによる、不意なアクセスの拒否

### デフォルトホストをブロック例

安全のために、レンタルサーバーのデフォルトホスト（ドメイン）へのアクセスをブロック(理由は書籍内にて)。

`想定されるディレクトリ構造`
```
/www ← デフォルトホスト名（デフォルトドメイン）のDocument root ディレクトリ
/www/my-site.example.jp/public/ ← 実際に活用するホスト名のDocumentroot ディレクトリ
```

`/www/.htaccess (デフォルトホストに設置する例)`
```
Require all denied
```

`古い、Apache 2.2以下 の.htaccess例`

```
order allow,deny
deny from all
```

### 別ホスト（別ドメイン）で個別にアクセス許可例

ここでは `my-site.example.jp`がそのドメインであるとする。

`/www/my-site.example.jp/public/.htaccess 例`

```
SetEnvIfNoCase Host "^my-site.example.jp$" allow_host

Require all denied
<RequireAny>
Require env allow_host
</RequireAny>
```

`古い、Apache 2.2以下の.htaccess例`

```
SetEnvIfNoCase Host "^my-site.example.jp$" allow_host

order deny,allow
deny from all
allow from env=allow_host
```

### 注意点

`/www/.htaccess`に前述を設置して「禁止」した後で、（例えば) `/www/my-site.example.jp/public/.htaccess`においては（それぞれで）「許可」が必要です。

なぜなら、`.htaccess`の仕組みとして、アクセス時のURLに対応する実ディレクトリから、再帰的に上位へのぼりながら`.htaccess`ファイルの有無がチェックされていきます。

それが**URLのパス外（URLの`/`よりも上位）であっても適用される**ため、レンサバでよくある*`/www/(デフォルトホストのDocRoot)`の中にしか追加ホストのDocument rootを作成できない環境*だと、アクセス制限が**予期せず**効いてしまいます。

> 個人的には明示するまでは不許可のほうが良いのではないかと思っており、好ましいとおもいますが。

### どうしても別ホストそれぞれで許可しづらい場合 

さまざまな理由で、別ホストそれぞれに`.htaccess`を置けない現場もあります、それを避けるにはデフォルトドメイン「のみ」をブロックする以下の方法があります。

`/www/.htaccess (デフォルトホストに設置する例)`

```
SetEnvIfNoCase Host "^phper.sakura.ne.jp$" deny_host

<RequireAll>
Require all granted
Require not env deny_host
</RequireAll>
```

しかし、そのホスト名以外では到達されない（たとえば、IP直アクセスや、www有無などのAlias的なホスト名でアクセスできる可能性）という確証がなければ避けましょう。

### 参考資料

- mod_authz_core - Apache HTTP Server Version 2.4 https://httpd.apache.org/docs/2.4/mod/mod_authz_core.html#require
- mod_authz_host - Apache HTTP Server Version 2.4 https://httpd.apache.org/docs/2.4/mod/mod_authz_host.html


## ログを出力する：php.iniを実行時に設定する例

コードの冒頭や、index.phpなど一番先頭のファイルに記述してください。

```
<?php
ini_set("log_errors", 1);
ini_set("error_reporting", E_ALL);
# 以下でerror_logの出力場所を指定
ini_set("error_log", "/home/phper/error.log");
# 画面にもエラーをだしてしまいたい場合
ini_set("display_errors", 1);
```

##  Virtual Host（サブドメイン、マルチドメイン機能）

通常レンサバ外部のネームサーバーでドメインを運用しつつ、そのドメインをレンサバで使うことは通常できません。ただしさくらのレンサバなど一部の業者では工夫と自己責任で可能です。




＊＊＊＊

## デフォルトドメインの www に設置する.htaccess例

```
# この時 phper.sakura.ne.jp (例) は使えなくなる
# .htaccessは上位のディレクトリにさかのぼって適用されるので、
# この記述がないとwww以下の全サイトがブロックされてしまう
SetEnvIf Host "^phper.sakura.ne.jp$" host
# もしIP指定でアクセスできるケースではServer_Addrも確認
order allow,deny
allow from all
deny from env=host
```


