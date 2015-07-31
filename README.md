# IMI Data Converter
CSVやMicrosoft Excel等の表形式データを[共通語彙基盤](http://imi.ipa.go.jp/)に則ったデータ構造に変換し、XMLやRDFのファイルフォーマットで出力するツールです。
本ツールは[独立行政法人情報処理推進機構](http://www.ipa.go.jp/)（IPA）が取り組む[共通語彙基盤整備事業](http://goikiban.ipa.go.jp/node4)の一部として開発され、[https://imi.ipa.go.jp/tools/0051/](https://imi.ipa.go.jp/tools/0051/)にて公開されています。
このレポジトリは、ツールの構築を受託した[インフォ・ラウンジ合同会社](http://info-lounge.jp/)がそのソースコードをオープンにするものです。
なお、共通語彙基盤の詳細は[こちら](http://goikiban.ipa.go.jp/)をご覧ください。

## 動作サンプル
###入力
|日程|曜日|イベント名|概略|開始時間|終了時間|会場|施設ID|会場詳細|対象児童|対象詳細|担当課|担当課TEL|備考|更新日|
|:------|:------|:------|:------|:------|:------|:------|:------|:------|:------|:------|:------|:------|:------|:------|
|毎週|火|子育て支援者による育児相談|先輩ママである子育て支援者による、気軽な育児相談を行っています|10:00|12:00|富岡並木地区センター|F7005|プレイルーム|乳幼児とその保護者||こども家庭支援課|045-788-7787|1-kz-chishin.csvと関連付けあり|2014/4/1|
|毎週|水|子育て支援者による育児相談|先輩ママである子育て支援者による、気軽な育児相談を行っています|10:00|12:00|六浦地区センター|F7007|プレイルーム|乳幼児とその保護者||こども家庭支援課|045-788-7787|1-kz-chishin.csvと関連付けあり|2014/4/1|

###出力
入力データのうち一部をマッピングし、RDF/XMLで出力した結果
```
<?xml version="1.0" encoding="utf-8" ?>
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:ic="http://imi.ipa.go.jp/ns/core/2/">

  <rdf:Description rdf:about="http://www.city.yokohama.lg.jp/kanazawa/施設ID">
    <ic:種別 xml:lang="ja">日程</ic:種別>
    <ic:開催期日 xml:lang="ja">曜日</ic:開催期日>
    <ic:開始時間 xml:lang="ja">開始時間</ic:開始時間>
    <ic:終了時間 xml:lang="ja">終了時間</ic:終了時間>
    <ic:説明 xml:lang="ja">概略</ic:説明>
  </rdf:Description>

  <rdf:Description rdf:about="http://www.city.yokohama.lg.jp/kanazawa/F7005">
    <ic:種別 xml:lang="ja">毎週</ic:種別>
    <ic:開催期日 xml:lang="ja">火</ic:開催期日>
    <ic:開始時間 xml:lang="ja">10:00</ic:開始時間>
    <ic:終了時間 xml:lang="ja">12:00</ic:終了時間>
    <ic:説明 xml:lang="ja">先輩ママである子育て支援者による、気軽な育児相談を行っています</ic:説明>
  </rdf:Description>

  <rdf:Description rdf:about="http://www.city.yokohama.lg.jp/kanazawa/F7007">
    <ic:種別 xml:lang="ja">毎週</ic:種別>
    <ic:開催期日 xml:lang="ja">水</ic:開催期日>
    <ic:開始時間 xml:lang="ja">10:00</ic:開始時間>
    <ic:終了時間 xml:lang="ja">12:00</ic:終了時間>
    <ic:説明 xml:lang="ja">先輩ママである子育て支援者による、気軽な育児相談を行っています</ic:説明>
  </rdf:Description>

</rdf:RDF>
```

## Docs
・ インストールマニュアル

## License
本ツールのソースコードは[MIT License](http://opensource.org/licenses/MIT)の下に提供されます。
