# ec-cube (custom Docker build)

[EC-CUBE](https://github.com/EC-CUBE/ec-cube) の**最新リリース**をベースに、[FrankenPHP](https://frankenphp.dev/) (PHP 8.3, Alpine) 上で動く独自 Docker イメージをビルド・配布するためのリポジトリです。

EC-CUBE 本体のソースコードはこのリポジトリには含まれません。ビルド時に [`Dockerfile`](Dockerfile) が `https://github.com/EC-CUBE/ec-cube/releases` から該当バージョンのソース一式を取得し、`composer install` を実行してイメージを作成します。

## イメージの取得

```shell
docker pull ghcr.io/5ym/ec-cube:latest
```

用意しているタグは以下のとおりです。

| タグ | 中身 |
| --- | --- |
| `latest` | EC-CUBE の最新リリース |
| `4.3.1-p1` などのリリースタグ | 該当リリースで固定 |
| `develop` | EC-CUBE 本体の開発ブランチ (デフォルトブランチ) の最新コミット |
| `develop-<短縮SHA>` | 該当コミットで固定した開発版 |

`develop` は未リリースの開発中コードをビルドしたものです。動作確認用途で、本番では使わないでください。

## ローカルビルド

```shell
docker build -t ec-cube --build-arg ECCUBE_VERSION=latest .
# 特定リリースを指定する場合
docker build -t ec-cube --build-arg ECCUBE_VERSION=4.3.1-p1 .
# 開発ブランチの特定コミットを指定する場合 (40 桁のコミット SHA)
docker build -t ec-cube --build-arg ECCUBE_VERSION=aafc564f97866822dcb1bd62b9641ae8fb173d18 .
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

- このリポジトリへの push 時 — Dockerfile 等が変わるため常に再ビルド
- 毎日 03:00 (JST) — 更新分だけ再ビルド
- 手動実行 (`workflow_dispatch`) — ビルドする EC-CUBE のリリースタグを指定可能。`force` を指定すると更新がなくても再ビルド

毎日のチェックでは無駄なビルドを避けるため、更新があったものだけをビルドします。

- **リリース**: [EC-CUBE の最新リリース](https://github.com/EC-CUBE/ec-cube/releases)を調べ、そのバージョンのタグが GHCR に既にあればスキップします
- **develop**: EC-CUBE 本体のデフォルトブランチの先頭コミットを調べ、そのコミットの `develop-<短縮SHA>` が GHCR に既にあれば (= 差分なし) スキップします
