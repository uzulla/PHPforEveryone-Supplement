# ansible サンプル

インストール直後のUbuntu 18.04 LTSをAnsibleでApache+PHP環境を設定し、アプリケーションをデプロイする例です。

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

## 

`sample.hosts`をコピーして、`my.hosts` などを作成し、

```
$ ansible-playbook -i my.hosts 1_update_all_package.yml
```

等として実行していきます。

具体的に実行される内容は[sample.sh](sample.sh)と、そこに記述された各yml(playbook)を参照してください。

`sample.sh`すべて実行する例ですが、指定する`*.hosts`ファイルを `HOSTS_FILE` 環境変数で指定できます。

```
$ HOSTS_FILE=my.hosts ./sample.sh
```

## ref

- https://www.ansible.com/
- https://docs.ansible.com/ansible/latest/index.html