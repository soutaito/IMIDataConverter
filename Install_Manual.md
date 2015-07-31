# IMI Data Converter インストールマニュアル


## セキュリティ設定
セッションID保護のため、全ページSSL接続を必須とします。有効なセキュリティ証明書をインストールしてください。

## ライブラリのインストール
[Composer](https://getcomposer.org/)を使用して必要なライブラリをインストールします。サーバーにComposerをインストールし、ソースコードを設置したディレクトリでcomposer updateを実行して下さい。

## Rewrite設定
納品ソースの /public をドキュメントルートに設定し、ウェブサーバの設定ファイルに下記の設定をしてください。
【Apacheの場合】

```
<IfModule mod_rewrite.c>
	RewriteEngine On
	RewriteBase /
	RewriteRule ^index\.php$ - [L]
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteCond %{REQUEST_FILENAME} !-d
	RewriteRule . /index.php [L]
</IfModule>
```

【Nginxの場合】

```
location / {
        index  index.php;
        try_files $uri $uri/ /index.php?$args;
    }
```

## パーミッション設定
ドキュメントルートから参照して、uploadedフォルダ、schemaフォルダ、logsフォルダの３つに対し、ウェブサーバによる書き込みを許可して下さい。uploadedフォルダは、ユーザーがアップロードした表形式データや出力する構造化データの一時的な書き込み場所となります。schemaフォルダは、初期化処理時に各語彙がキャッシュされます。logsフォルダはアプリケーションのエラーログが格納されます。

## 設定ファイルの編集
app/config.phpの設定内容を示します。

```
$config = array(
	'product_path' => '/',
	'static_path' => '/',
	'debug' => false,
	'cookies.secure' => true,
	'cookies.httponly' => true,
	'view' => '\Slim\LayoutView',
	'templates.path' => __DIR__ . '/../views/',
	'uploadPath' => __DIR__ . '/../uploaded/',
	'schemaPath' => __DIR__ . '/../schema/',
	'mongo_username' => 'imitool',
	'mongo_password' => 'imitool',
	'mongo_db' => 'imitool',
	'log.enabled' => true,
	'log.writer' => new \Slim\Logger\DateTimeFileWriter(array(
		'path' => __DIR__ . '/../logs/',
	)),
	'log.level' => \Slim\Log::DEBUG,
	'salt' => 'e80efe9f2a5af77556bc283db1ca9eae6b158218',
	'timeout' => 60 * 30,
	'tool_name' => 'IMI Data Converter',
	'tool_version' => '1.0',
	'template_version' => '1.0',
	'prefix' => array(
		'ic' => array(
			'name' => '共通語彙基盤 コア語彙 2（バージョン2.2）',(…中略)
	),
	'show_row_num' => 5,
	'perpage' => 10,
	'tag' => array(
		'戸籍・住民票・印鑑登録', 'ごみ・資源', '税金',		),
	'license' => array(
		'CC BY 4.0' => 'https://creativecommons.org/licenses/cc-by/4.0/', (…中略)
	),
	'api_component' => array(
		'address' => array(
			'name' => '住所整形API', (…中略)
		),
	),
	'api_request_limit' => 100,
);
```

|設定キー|インストール時の編集必要性|説明|
|:----------|:----------|:----------|
|product_path|有り|サブディレクトリ形式のURLでツールを設置する場合に、そのディレクトリ名を設定下さい。末尾にスラッシュが必要です。例）’/imitool/’|
|static_path|無し|静的ファイルの配置パスを設定します。通常変更の必要はありません。|
|debug|有り|デバッグモードへ設定します。falseになっていることを確認下さい。|
|cookies.secure|有り|Cookieのsecure属性をフレームワーク側で規定します。trueになっていることを確認下さい。|
|cookies.httpOnly|有り|CookieのhttpOnly属性をフレームワーク側で規定します。確認下さい。|
|mongo_username|有り|mongoDBに接続するユーザー名です。|
|mongo_password|有り|mongoDBに接続するパスワードです。|
|mongo_db|有り|mongoDBに接続するデータベース名です。|
|log.enabled|無し|フレームワークのエラーログ出力可否です。trueになっていることを確認下さい。|
|log.writer|無し|フレームワークのエラーログクラスを指定しています。|
|log.level|無し|フレームワークのエラーログレベルを設定しています。|
|salt|有り|ログインパスワード保存時の、ハッシュ値生成時のソルトとして機能しています。**16進数40桁**の値を設定下さい。|
|timeout|有り|ログインの保持時間を秒単位で定義します。|
|tool_name|有り|Htmlのtitleタグや、データテンプレートに出力される本ツールの名称です。|
|tool_version|無し|データテンプレートに出力される本ツールのバージョンです。|
|template_version|無し|データテンプレートに出力されるデータテンプレートの書式バージョンです。|
|prefix|無し|語彙のprefixをキーに指定します。値は配列であり、nameがマッピング時に表示される語彙の名称、namespaceが構造化データの出力に使用されるURI、urlが初期化処理時にスキーマをリクエストするURL、acceptがその際に指定するHTTP HeaderのAccept値、extensionが返却されるスキーマのファイル形式となっています。通常は、namespaceとURLは等しくなりますが、schema.orgはコンテンツネゴシエーションが機能しないため、このような構成としています。|
|show_row_num|無し|表形式データのプレビュー時に表示する行数です。|
|perpage|無し|トップページのプロジェクト一覧において、ページ送りをする件数です|
|tag|有り|プロジェクトのタグ付けに使用される一覧です。ツールの初期化時、またはbin/updateTag.phpを呼び出した際に参照されます。|
|license|無し|配列のキーがライセンスの表示名です。値はライセンスのURIであり、データテンプレートに出力されます。|
|api_component|無し|内部定型化コンポーネントの一覧を定義しています。現在は住所定型化コンポーネントのみ登録されており、運用上変更は不要です。|
|api_request_limit|無し|外部定型化コンポーネントのリクエスト時に、一度に許可するリクエスト数の最大値です。これを超えると外部URLのリクエストを停止し、データの出力を途中終了します。|

## MongoDBの認証設定
上記設定ファイルの、mongo_dbで指定するデータベースに、mongo_username、mongo_passwordで設定されるMongoDBユーザーに対して、readWrite権限を設定してください。

## 初期化
管理・運用者向けの操作は、ドキュメントルートのbin/配下にあるphpファイルをコマンドラインから実行することで行っていただきます。
初期化は ドキュメントルートに移動した後 php bin/init.php を実行して下さい。プロジェクトの全削除、ユーザーの全削除、タグをconfig.phpのtagに登録された内容で書き換え、config.phpのprefixに登録された語彙のキャッシュを行います。

## ユーザー管理
ユーザーの追加、削除、更新、一覧取得が可能です。

### ユーザーの追加
ドキュメントルートに移動し、php bin/addUser.phpを実行します。引数を3つ、username, email, passwordの順に与えて下さい。usernameは、既存ユーザーと重複することができません。
>例）
>php bin/addUser.php 横浜市金沢区役所 test@example.com testpassword

### ユーザーの削除
ドキュメントルートに移動し、php bin/removeUser.phpを実行します。引数としてusernameを与えて下さい。
>例）
>php bin/removeUser.php 横浜市金沢区役所

### ユーザーの更新
ドキュメントルートに移動し、php bin/updateUser.phpを実行します。引数を3つ、username, email, passwordの順に与えて下さい。Usernameの一致するユーザーについて更新を実施します。
>例）
>php bin/updateUser.php 横浜市金沢区役所 test@example.com testpasswordmod

### ユーザーの一覧取得
ドキュメントルートに移動し、php bin/getUserList.phpを実行します。UsernameとemailがCSVフォーマットで標準出力されます。
>例）
>php bin/getUserList.php

## プロジェクト管理
### プロジェクトの削除
ドキュメントルートに移動し、php bin/removeProject.phpを実行します。引数としてプロジェクトのidを与えて下さい。プロジェクトのidは、ツールにアクセスしプロジェクトの詳細画面URLにおける、/project/***の部分です。
>例）
>php bin/getUserList.php

## タグ管理
### タグの更新
ドキュメントルートに移動し、php bin/updateTag.phpを実行します。app/config.phpに設定されたタグ情報でツール上のタグが更新されます。
>例）
>php bin/updateTag.php

