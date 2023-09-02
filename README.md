# dailyreport_system

参考資料
【開発実況シリーズ】Web日報登録システムを作る

https://blog.senseshare.jp/category/develop/


一般ユーザ用ファイルは『web』フォルダ

- login.php：一般ユーザのログイン画面
- index.php：日報登録、月別リスト画面

管理者ユーザ用ファイルは『admin』フォルダ

- login.php：管理者ユーザのログイン画面
- user_list.php：ユーザ一覧表示画面
- user_result.php：選択したユーザの日報月別リスト表示画面

その他ファイルについて

- config/config.php：データベースへの認証情報などが記載。今はテスト環境でのパスワードなどが入っている
- lib/crypt.php：任意の文字列を暗号化、復号するための関数。キーは消してある
- css/style.css：スタイルを定義したファイル
- templates/head_tag.php：htmlのheadタグを共通関数化したもの
- encrypt.php：テスト用ユーザの暗号化に使用したファイル
- error.php：エラーが起きた際に遷移する画面
- functions.php：共通関数をまとめたもの
- logout.php：認証を切るもの。login.phpへ遷移する


