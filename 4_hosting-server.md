# ログを出力するphp.iniを実行時に設定する例

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

# デフォルトドメインの www に設置する.htaccess例

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

#  Virtual Host（サブドメイン、マルチドメイン機能）

通常レンサバ外部のネームサーバーでドメインを運用しつつ、そのドメインをレンサバで使うことは通常できません。ただしさくらのレンサバなど一部の業者では工夫と自己責任で可能です。