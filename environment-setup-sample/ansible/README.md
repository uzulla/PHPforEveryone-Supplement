# ansible サンプル

インストール直後のUbuntu 18.04 LTSをAnsibleでApache+PHP環境を設定し、アプリケーションをデプロイする例です。

AWS EC2の Ubuntu 18.04 LTSイメージにてテストしています。

## ansible install

`macインストール例`
```
$ brew install ansible
```

`linux インストール例`
```
$ apt install ansible
```

> Windows は Ubuntu(WSL)環境を作成し（[../../2_built-in-web-server.md](参考情報))、そこで使うと良いでしょう。

## hostsの作成

`sample.hosts`をコピーして、`my.hosts` などを作成します。対象のIPと秘密鍵へのPathを修正してください。

```
$ ansible-playbook -i my.hosts 1_update_all_package.yml
```

等として実行していきます。

具体的に実行される内容は[sample.sh](sample.sh)と、そこに記述された各`yml`(playbook)を参照してください。

`sample.sh`はすべて実行する例です。`sample.sh`で指定する`*.hosts`ファイルは、`HOSTS_FILE` 環境変数で指定できます。

`例`
```
$ HOSTS_FILE=my.hosts ./sample.sh
```

## ref

- https://www.ansible.com/
- https://docs.ansible.com/ansible/latest/index.html

