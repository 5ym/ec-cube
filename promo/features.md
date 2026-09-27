# UZone 機能棚卸し（宣伝動画用）

調査日: 2026-09-27／調査方法: 各リポジトリの default ブランチを shallow clone して読んだ（コードの変更はしていない）

対象リポジトリ: `uzone_web` / `uzone_api` / `uzone_order_api` / `uzone_parts_reception` / `uzone_maker_master` / `uzone_maker_master_factory` / `uzone_admin_web` / `uzone-crm`

> パスは `リポジトリ名/…` で表記。行番号は調査時点の値。
> 秘密情報・社内URL・顧客データは記載していない。

---

## 0. まず押さえておくこと（事前の想定と違った点）

| 事前の想定 | 実際のコード |
|---|---|
| `uzone_order_api` ＝ 購入者向けの注文API | **仕入先（リビルトメーカー3社）のWeb発注サイトへ自動で発注するロボット**（Lambda + Step Functions + Playwright）。購入者向けの注文・決済はすべて `uzone_api` にある |
| `uzone_parts_reception` ＝ 部品お問い合わせのメール処理 | **AI電話受付**（Twilio + 音声認識 + Bedrock）。新品消耗品の見積電話に応答する。README上はデモ段階で、発注確定はフラグで無効になっている |
| コーションプレートのOCRはAPI側にある | 3つのバックエンドのどこにもない。フロントが **UZone API とは別のホストにある読み取りAPI** に画像を送っている。読み取った値は既存の検索APIのパラメータとして届く |
| 「部品が掲載されたらメールで通知」 | **入荷したら知らせる機能（入荷通知・restock）は未実装**。実装されているのは「お問い合わせに回答があったとき」のメールとアプリ内通知。FAQの「見つかった際はメールにて通知…同時にUZoneに掲載」は実装より言い過ぎになっている |
| 請求書払い ＝ 後払い決済サービス | フロントの規約ダイアログには「マネーフォワードケッサイ・月額最大30万円・月末締め翌月末」とある。一方 API は**自社の掛け売り**（締め日は20日・25日・末日、`uzone_api/src/config/charge.php`）で、後払いサービスとのAPI連携はない。**動画では「1ヶ月ごとにまとめて請求」までにとどめた** |
| 適合ラベルは4種類（適合／一部適合／互換品／適合未確認） | フロントには4種類の定義があるが、**APIが返す値は 1（適合）と 0（適合未確認）だけ**。「一部適合」「互換品」は実際には表示されない |

---

## 1. 機能一覧

### 優先度：高（動画に入れた機能）

#### H1. コーションプレートを撮影して車両を特定
- **何ができるか**
  - スマホのカメラでコーションプレートを撮ると、次の項目を読み取る。
    - メーカー・型式・車台番号
    - スズキは補助番号、ホンダはモデルコード
  - 読み取りに失敗したときは、四隅をタップして範囲を指定し直せる。
  - 読み取った値は、修正画面で直せる。
  - 英字のメーカー名は、国内8社の maker_id に変換する。
- **ユーザーにとっての価値**
  - 車検証が手元になくても、車体のプレート1枚で部品検索を始められる。
  - 入庫車の前で、そのまま調べられる。
- **根拠**
  - `uzone_web/src/views/search/CautionPlateView/index.tsx`
  - `uzone_web/src/views/search/CautionPlateView/utils/ApiService.ts`
  - `uzone_web/src/views/search/CautionPlateView/CautionDialog/index.tsx`
  - `uzone_web/src/views/search/CautionPlateEditView/`
  - `uzone_web/src/constants/makers.ts`
  - `uzone_web/src/routes/mobileOnly.cautionPlate.tsx`（モバイル専用）
  - 読み取った車台番号の補正: `uzone_api/src/app/Helpers/Helpers.php` の `completeChassisNo()` と `extractRightModia()`
- **宣伝での優先度**: **高**

#### H2. 車検証のQRコードを連続で読み取る
- **何ができるか**
  - シャッターは押さない。プレビューを100msごとに解析し続ける（zxing-wasm）。
  - 普通車・軽自動車・一時抹消の3種類に対応。
  - 「QRコード n 個中 m 個」と進み具合を表示する。
  - 読み取る項目は、型式指定番号・類別区分番号・型式・車台番号。
- **ユーザーにとっての価値**
  - 車検証を手で書き写す作業と、入力ミスがなくなる。
- **根拠**
  - `uzone_web/src/views/search/InspectionCertificateView/`（`WebCapture/`, `utils/collectQrCode.ts`, `utils/qrDecoder.ts`）
  - `uzone_web/src/views/search/InspectionCertificateEditView/`
  - `uzone_api/src/app/Enums/SearchType.php`（QR と CAUTION の検索経路を注文データに記録している）
- **宣伝での優先度**: **高**

#### H3. 型式・車台番号から適合を判定（国内8メーカーの電子部品カタログ由来のマスタ）
- **何ができるか**
  - 車両情報から、その車に合う純正品番の候補を出す。
    - 入力: 型式指定番号＋類別区分番号、車台番号、型式、認定型式、モデルコード
  - 候補の品番で在庫を引き、商品ごとに「適合」「適合未確認」を表示する。
  - 代替品番・後継品番・左右違い・色違いは、統合品番にまとめてある。在庫はこの単位でヒットする。
  - リビルトメーカーのクロス表を使い、メーカーをまたぐ互換もつないでいる。
- **ユーザーにとっての価値**
  - 品番を調べて照合する手間がなくなる。
  - 誤発注と返品のリスクが下がる。
  - 中古・リビルトでも、純正品番の単位で選べる。
- **規模**（`uzone_maker_master` の実測値。宣伝に使うときは「各社の電子部品カタログ由来」と注記する）

  | 項目 | 規模 |
  |---|---|
  | 対応メーカー | 国内8社（トヨタ・日産・ホンダ・マツダ・スバル・三菱・スズキ・ダイハツ）<br>根拠: `uzone_api/src/app/Enums/Maker.php` |
  | 純正品番 | 約64.6万点 |
  | 適合データ（compatible_parts） | 約4,314万行 |
  | 車台番号ごとの仕様データ | 約1.34億行 |
  | 型式指定番号×類別区分番号 | 約30万通り（型式指定番号は約6,000）。マツダ・スバルは含まない |
  | メーカーをまたぐ統合 | 約1.5万グループ・約6.1万品番 |

- **根拠**
  - 適合判定
    - `uzone_api/src/app/Http/Controllers/API/Master/MasterController.php`（329行〜 `buildPartsNoInfo`）
    - `uzone_api/src/app/Repositories/Parts/PartsRepository.php`（メーカーごとの `fetch{Maker}Parts`）
    - `uzone_api/src/app/Services/ItemService.php`（704〜865行）
  - 画面表示
    - `uzone_web/src/components/ItemMatching.tsx`
    - `uzone_web/src/views/items/ItemsView/MultiplePartNumbersNotice`
    - `uzone_web/src/views/items/ItemDetailView/ProductBasicDetail/IntegratedPartsInfoAccordion.tsx`
  - マスタ
    - `uzone_maker_master/MakersInitData/*`
    - `uzone_maker_master/MakersInitData/C_cross_integration/cross_integration.csv`
    - `uzone_maker_master_factory/0_cross_maker/run.sh`
- **宣伝での優先度**: **高**
- **注意**
  - データの時点がメーカーによって大きく違う。マツダは2011年頃、ダイハツと日産（旧データ）は2019年8月、スバルは2021年5月まで。
  - このため「最新型まで対応」とは言わない。

#### H4. 部品お問い合わせ（掲載されていない在庫も探す）と、回答からそのまま注文
- **何ができるか**
  - 検索で0件のとき、または一覧から、車両情報と欲しい部品を送れる。
  - 画面の文言
    - 「16時までのお問い合わせは当日中に返答」
    - 「サイト上に表示していない在庫からお探し」
  - 回答はメールとアプリ内のお知らせで届く。
  - 回答画面で部品を選んで「選択した部品を注文する」を押せば、そのまま購入できる。
  - 見積を印刷できる（「リビルトパーツ、リサイクルパーツ 問い合わせ用紙」）。
  - 電話・FAX・LINE で受けたお問い合わせも、Salesforce を経由して UZone の画面に表示される。
- **ユーザーにとっての価値**
  - サイトで見つからなくても、同じ画面のまま探してもらえる。
  - 見積から注文まで、電話やFAXを往復させずに済む。
- **根拠**
  - `uzone_web/src/views/itemInquiries/`（NewView, DetailView, `DetailView/components/PrintContent`）
  - `uzone_web/src/views/search/PartsView/Dialog`
  - `uzone_api/src/app/Http/Controllers/API/User/Search/PartInquiry/RegisterController.php`
  - `uzone_api/src/app/Services/SalesforceSyncOpportunityDataService.php`（72行〜。回答の同期と、通知・メール）
  - `uzone_api/src/app/Services/User/PartInquiryService.php`（100行〜。回答からの注文）
- **宣伝での優先度**: **高**

#### H5. 請求書払い（法人・個人事業主）
- **何ができるか**
  - 決済方法は「クレジットカード決済」（Stripe）か「請求書発行」を選べる。
  - 請求書発行は「※1ヶ月毎に請求書を発行」。
  - 法人と個人事業主が使える（コードの条件は `userType !== 3`）。
  - 新品の消耗品は請求書払いのみ。
- **ユーザーにとっての価値**
  - 整備工場の月締めの経理に合わせられる。
  - 担当者が立て替えたり、カードを共有したりしなくて済む。
- **根拠**
  - `uzone_web/src/views/checkout/CheckoutView/ShippingDetail/index.tsx`（586行付近）
  - `uzone_web/src/views/consumables/CheckoutView`
  - `uzone_api/src/config/charge.php`
- **宣伝での優先度**: **高**

---

### 優先度：中（動画ではB2Bシーンで短く触れた、または入れていない機能）

| # | 何ができるか | ユーザーにとっての価値 | 根拠 | 優先度 |
|---|---|---|---|---|
| M1 | **購入時に在庫をリアルタイムで確認**<br>・中古部品ネットワーク（JARA）の在庫APIを、購入前と購入確定時に並列で呼ぶ<br>・売り切れていれば、その場でカートから外す<br>・注文後は在庫予約と送り状番号の同期まで自動で進む | 「注文したのに在庫がなかった」を防げる | `uzone_api/src/app/Services/JaraService.php`（`checkSoldOutItems`）<br>`uzone_api/src/app/Services/OrderService.php`（612行 `checkJaraStock`）<br>`uzone_api/src/app/Http/Controllers/API/Order/CheckoutController.php`（110行） | 中（動画ではB2Bシーンに入れた） |
| M2 | **担当者の管理**<br>・会社アカウントの下に担当者を登録する<br>・問い合わせと購入のときに担当者を選ぶ | 複数の整備士で1つのアカウントを使っても、誰が頼んだか分かる | `uzone_web/src/views/mypage/` の incharges<br>`uzone_api` の `/api/user/users/incharges` | 中（動画に入れた） |
| M3 | **納品書の一覧**<br>・車台番号ごとの納品書を印刷・PDF出力できる | 車両単位で原価管理や請求の突き合わせができる | `uzone_web/src/views/mypage/DeliveryNotesView`<br>`uzone_api` の `/api/user/delivery_notes` | 中（動画に入れた） |
| M4 | **純正品番での検索** | 品番が分かっているときに最短で探せる | `uzone_web/src/views/items/SearchPartsNumberView`<br>`/api/user/items/genuine_part_number` | 中 |
| M5 | **同じ車台番号の部品の提案**（同じ車から取った部品） | 色や年式がそろった部品をまとめて買える | `uzone_web/src/views/items/ItemDetailView/SuggestProducts`<br>`/api/user/items/chassis_number` | 中 |
| M6 | **絞り込みと並び替え**<br>・状態: 中古A/B/C・リビルト・パーフェクトリビルト・社外新品<br>・走行距離、カラーNo.、同じ型式のみ<br>・並び替え: 納期順、程度がよい順 など | 納期と品質の条件ですぐ比較できる | `uzone_web/src/views/items/ItemsView/SearchFilters`<br>`uzone_web/src/views/items/ItemsView/SortOrderSelect` | 中 |
| M7 | **配送希望日の指定、配送先の複数登録、お届け予定日と出荷締め時刻の表示** | 入庫スケジュールに合わせて手配できる | `uzone_web/src/views/checkout/CheckoutView`<br>`uzone_web/src/views/items/ItemDetailView/ProductShippingInformation` | 中 |
| M8 | **保証の表示**<br>・中古品は到着後7日間<br>・リビルト品は商品ごとに「5年/5万km」〜「1年/1万km」 | リビルトの安心材料になる | `uzone_web/src/views/items/ItemDetailView/ProductBasicDetail/index.tsx`<br>`uzone_api/src/config/item.php` | 中 |

### 優先度：低

| # | 何ができるか | 根拠 | 優先度 |
|---|---|---|---|
| L1 | **将来在庫（ダミー商品）**<br>・在庫が0件でも、平均価格×1.5の「将来在庫」として一覧に並ぶ<br>・カード決済はできない | `uzone_api/src/app/Services/ItemService.php`（2441行〜 `setDummyItems`）<br>`uzone_web/src/views/items/dummyItem.ts` | 低（誤解を招きやすいので、宣伝より先に表示を改善したい。§3） |
| L2 | **新品整備消耗品**（IDOM限定。プロモートV8で適合を判定、送料無料ライン、請求書払いのみ） | `uzone_web/src/views/consumables/*`<br>`uzone_api/src/app/Services/Consumable/ConsumableFitmentSyncService.php` | 低（対象ユーザーが限定されている） |
| L3 | **商品詳細の印刷、見積印刷、満足度評価** | `uzone_web/src/views/items/ItemDetailView/PrintDialog`<br>`uzone_web/src/components/SatisfactionRatingDialog` | 低 |
| L4 | **仕入先への自動発注**（リビルト3社。仕切価格の一致確認、倉庫別の在庫と締め時刻から出荷元を判断） | `uzone_order_api/src/domain/order/shipping_decision.py` ほか | 低（社内の仕組み。「発送が速い」を言うときの裏付けになる） |
| L5 | **AI電話受付**（デモ段階） | `uzone_parts_reception/cmd/server/main.go` | 低（未リリース） |
| L6 | **コア返却が必要かどうかの表示**（商品詳細のみ） | `uzone_web/src/views/items/ItemDetailView/ProductBasicDetail/index.tsx`（32〜37行） | 低（返却の手続き自体は未実装） |

---

## 2. 実装済みなのに、サイトやFAQで打ち出していない機能

FAQ（`uzone_web/src/views/other/FaqView/index.tsx`）とトップの meta 説明が扱っているのは、次の範囲だけ。
- コーションプレート／車検証QR
- 適合／適合未確認
- 部品お問い合わせ
- 請求書払い／クレジットカード
- 7日間保証、返品

このほかに実装されているのに打ち出していないもの:

1. **回答からの注文と見積印刷**（H4）。FAQは「メールで通知」としか書いていない
2. **「16時までは当日中に回答」「サイトに出していない在庫からも探す」**。画面の中にだけある
3. **購入時のリアルタイム在庫確認**（M1）
4. **納品書のPDF出力、担当者の管理**（M2, M3）。法人向けの訴求としてかなり強い
5. **純正品番での検索、同じ車台番号の部品の提案**（M4, M5）
6. **電話・FAX・LINE の問い合わせも UZone の画面に集約される**（Salesforce 連携）
7. **リビルト品の長期保証（最長5年/5万km）の表示**（M8）

### サイトの表記と実装が食い違っている点（公開前に直したい）
- FAQ
  - 支払い方法: 個人事業主は「クレジットカードのみ」と書いているが、実装では請求書払いも選べる。
  - 法人の欄の「請求書での支払い」が2行重複している（177〜178行）。
  - 「掲載していない部品」の回答が「②」から始まっている（295行）。
- 購入画面の見出し「決算代行規約」は「決済代行規約」の誤字。
- 返品の条件が文書によって違う。FAQは「お届け後1週間以内・未開封なら可」、特定商取引法の表記は「基本的に対応しておりません」。
- FAQは「見つかったらメールで通知・掲載」と書いているが、**入荷通知は未実装**。回答の通知ならある。
- コーションプレート画面の meta 説明が、車検証の画面と同じ文言を流用している。

---

## 3. 適合判定の精度と「適合未確認」が出る条件

### 判定のしくみ（`uzone_api/src/app/Services/ItemService.php` 743行、846行）
```php
$item->parts_matching = in_array($item->parts_no, $matchingPartsNos) ? 1 : 0;
```
- 1つの部品に対して品番の候補が **1つに絞れたときだけ「適合」（1）** になる。
- 候補が2つ以上あると、その部品の商品は **すべて「適合未確認」（0）** になる。一覧には「適合の可能性がある部品をまとめて表示しています」と出る。

### 「適合未確認」や「ヒット0」になる主な条件
1. 車台番号がマスタにない、または入力が型式指定番号＋類別区分番号や型式名だけのとき。
   - オプション・色・年式で絞れず、候補が複数残る。
   - トヨタは車台番号で見つからないと、年月 → typology → 認定型式の順に探す。この場合オプション情報が null になり、候補を全件採用する。
2. マスタに、オプション・色・年式だけが違う品番が並んでいるとき。
   - 例: 日産は車両のカラーコードが分からないと絞れない。
3. メーカーを特定できない、または複数のメーカーが候補になるとき。輸入車とトラックは対象外。
4. 取り込み時に品番が特定できなかった中古在庫（`parts_no = DUMMY`）は、**車両検索に一切出ない**（`uzone_api/src/app/Jobs/FetchPartsNoJob.php`）。
5. ホンダの「パターンC」
   - 同じ部品に品番が2つ以上あるのに、仕様の区分（`part_feature_category_id`）が空のケース。
   - この場合、フロントが検索をスキップして**在庫があってもヒット0**になる。
   - `603_honda_parts.csv` の `part_feature_id` は99%が空。
   - ホンダの車台番号検索のヒット率は約40%と記録されている（`uzone_maker_master/MakersInitData/6_honda/.claude/docs/search_hit_improvement.md`）。
6. データの鮮度: マツダは2011年頃、ダイハツと日産（旧データ）は2019年8月、スバルは2021年5月まで。これより新しい車は候補が出ない。

### コード上の弱点
- **ダミー商品（将来在庫）が常に「適合」（1）になる**。品番が空でも複数でも同じで、配送の文言「1〜2日後」・発送元東京も固定値（`uzone_api/src/app/Services/ItemService.php` 2640〜2661行）。
- **カートに入れるときの適合フラグ `is_matching` を、クライアントの値のまま保存している**。サーバー側で判定し直していない（`uzone_api/src/app/Http/Requests/CreateCartInfo.php`）。
- **トヨタの車台番号経路**は、型式を前方一致（LIKE）で引いて `first()` を取っている。NCP10 と NCP100 のように前方一致する別の型式で同じフレーム番号があると、どちらを採るかが決まらない（`uzone_api/src/app/Repositories/Parts/PartsRepository.php` 3984〜3987行）。
- エンジン型式と排ガス記号は取得しているのに、適合判定に使っていない（スズキの旧ロジックだけ例外）。
- 並び替えの「走行距離→適合順」（sort=6）が、実際には走行距離でしか並べていない。既定の「オススメ順」も適合を優先しない（`uzone_api/src/app/Services/OperationService.php` 95〜130行）。
- 商品詳細は「適合未確認」（0）を真偽値で判定しているため、ラベルが出ない（`uzone_web/src/views/items/ItemDetailView/index.tsx` 1015行付近）。
- **ホンダがメーカーをまたぐ統合に1件も入っていない**。ホンダのCSVは1行目がテーブル名になっていて、統合スクリプトの読み方と合っていない可能性が高い（`uzone_maker_master_factory/0_cross_maker/*.py`）。
- `GET /api/master/caution_plates/car` はルートの定義だけがあり、コントローラにメソッドがない（呼ぶと500エラー）。
- マスタのCIとテストがない（テストがあるのは三菱だけ）。

### 改善案（効果が大きい順）
1. **適合の値を3段階にする**: 確定／候補内（選択待ち）／将来在庫。候補内と将来在庫は「未確認」とはっきり表示する。カートの適合フラグはサーバー側で計算し直す。
2. **選択肢の回答をAPIに戻す**: 色・オプションの質問への回答（`selection_prompt`）をパラメータで受け取り、サーバー側で候補を絞ってから判定し直す。
3. **トヨタの車台番号経路に、型式の正規表現による制約を加える**（型式名だけの経路ではすでに対策済みで、それと同じ方式）。
4. **認定型式・エンジン型式・排ガス記号・製造年月を補助キーとして全メーカーで使う**。
5. **DUMMY の中古在庫を「候補一致（未確認）」として検索に出す**。候補の品番は `match_parts_no` に残っている。
6. **ホンダを直す**: CSVの形式をそろえてメーカーをまたぐ統合を作り直し、パターンCを自動で抽出する。
7. **マスタを更新する**: マツダ・ダイハツ・スバル・日産（旧データ）を最新化し、「データの時点」をUIに表示する。
8. **マスタのPRにCIを付ける**: ID参照の整合、ヘッダー形式、前回比の行数を確認する。
9. **計測する**: 「ヒット0」「絞り込み不可」になった車両をログに集め、メーカー別のヒット率をダッシュボードで見る。

---

## 4. 動画への反映（promo/uzone-promo.html）

| シーン | 秒 | 内容 | 使った機能 |
|---|---|---|---|
| 00 フック | 0.0–3.8 | 「型式を調べて。品番を調べて。在庫は電話で確認。」に取り消し線 → 「もう、撮るだけ。」 | — |
| 01 | 3.8–10.2 | コーションプレートのスキャン → 型式・車台番号・カラーを抽出 → 車両を特定 | H1 |
| 02 | 10.2–14.4 | 車検証のQRコード3個を連続で読み取り → 型式指定番号・類別区分番号・車台番号 | H2 |
| 03 | 14.4–22.4 | 部品カードに「適合」バッジ → 数字を連打（8メーカー／約64万点／4,300万件超／約1.3億件） | H3 |
| 04 | 22.4–28.8 | お問い合わせフォームの入力 → 16時まで当日回答 → 回答の通知 → そのまま注文 | H4 |
| 05 | 28.8–33.0 | 請求書 ＋「後払い」スタンプ ＋ 担当者の管理／納品書PDF／在庫のリアルタイム確認 | H5, M1–M3 |
| エンド | 33.0–38.0 | UZONE ロゴ、「中古・リビルト部品を、適合で選ぶ。」、uzone.parts、対応8メーカー | — |

- 型式 `XYZ-UZ10`、車台番号 `UZ10-0012345`、型式指定番号 `99999`、類別区分番号 `0042`、品番 `*-UZ0x0`、宛名「株式会社 サンプル自動車整備」は、すべて架空の値。
- 価格は「¥ ——」で伏せている。
- 数字のシーンには「各メーカーの電子部品カタログ由来のデータ（2026年9月時点・UZone調べ）」と注記を入れている。
