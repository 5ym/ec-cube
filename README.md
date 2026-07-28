# ec-cube (custom Docker build)

[EC-CUBE](https://github.com/EC-CUBE/ec-cube) の**最新リリース**をベースに、[FrankenPHP](https://frankenphp.dev/) (PHP 8.3, Alpine) 上で動く独自 Docker イメージをビルド・配布するためのリポジトリです。

EC-CUBE 本体のソースコードはこのリポジトリには含まれません。ビルド時に [`Dockerfile`](Dockerfile) が `https://github.com/EC-CUBE/ec-cube/releases` から該当バージョンのソース一式を取得し、`composer install` を実行してイメージを作成します。

## イメージの取得

```shell
docker pull ghcr.io/5ym/ec-cube:latest
```

`latest` タグは EC-CUBE の最新リリースを取り込んだイメージです。特定バージョンで固定したい場合は EC-CUBE のリリースタグ (例: `4.2.3-p2`) のタグを指定してください。

## ローカルビルド

```shell
docker build -t ec-cube --build-arg ECCUBE_VERSION=latest .
# 特定バージョンを指定する場合
docker build -t ec-cube --build-arg ECCUBE_VERSION=4.2.3-p2 .
```

## ローカル起動

DB は PostgreSQL のみサポートしています。

```shell
docker compose up
```

初回のみ、DBスキーマ作成と初期データ投入が必要です。

```shell
docker compose exec ec-cube bin/console eccube:install --no-interaction
```

ログイン情報は `compose.yml` の `ECCUBE_ADMIN_USER` / `ECCUBE_ADMIN_PASS` (デフォルト `admin` / `password`) です。管理画面は `http://localhost:8080/admin/` からアクセスできます。

## 自動ビルド

[`.github/workflows/docker.yml`](.github/workflows/docker.yml) が以下のタイミングでイメージをビルドし `ghcr.io/5ym/ec-cube` に push します。

- このリポジトリへの push 時
- 毎日 03:00 (JST) — EC-CUBE の新しいリリースが出ていないか確認するため
- 手動実行 (`workflow_dispatch`) — ビルドする EC-CUBE のバージョンを指定可能
