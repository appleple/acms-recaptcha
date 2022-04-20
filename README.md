# reCAPTCHA V3 for a-blog cms

a-blog cms の 拡張アプリ「reCAPTCHA for a-blog cms」を使うと、GoogleのreCAPTCHAを使用してボットからの
フォームアクセスを防御することが出来るようになります。この拡張アプリはVer.2.8.0より利用可能です。

## ダウンロード

[reCAPTCHA for a-blog cms](https://github.com/appleple/acms-recaptcha/raw/master/build/recaptcha.zip)

利用するためにはダウンロード後、解凍して **extension/plugins** に設置してください。

* extension/plugins/ReCaptcha

## インストール

管理ページ > 拡張アプリより「拡張アプリ管理」のページに移動します。そのページより下の図のようにreCAPTCHAをインストールします。

![アプリ一覧](./images/app-index.png "アプリ一覧")

## API登録とキーの取得

まずreCAPTCHAを導入するために、[reCAPTCHA](https://www.google.com/recaptcha/admin#list) にアクセスして、必要な情報を取得します。

**ReCAPTCHA V3** を選択し、Domainを登録します。

![Register a new site](./images/api-input.png "Register a new site")

**Site key** と **Secret key** をコピーしてメモしておきます。

![Site key と　Secret key の取得](./images/api-input2.png "Site key と　Secret key の取得")

### メモする情報

* Site key
* Secret key

## 使い方

### 設定

管理ページ > reCAPTCHA に移動し、**Site key** と **Secret key** を設定します。

![設定画面](./images/setting.png "設定画面")


### JavaScript

以下コードを フォームテンプレートのhead要素内に読み込んでください。

```
<!-- BEGIN_MODULE ReCaptcha -->
<script src="https://www.google.com/recaptcha/api.js?render={sitekey}"></script>
<script src="/extension/plugins/ReCaptcha/assets/recaptcha.js"></script>
<script>
  if (window.ACMS === undefined) {
    ACMS = {};
    ACMS.Config = {};
    ACMS.Config.ReCaptcha = '{sitekey}';
  } else {
    ACMS.Ready(function () {
      ACMS.Config.ReCaptcha = '{sitekey}';
    });
  }
</script>
<!-- END_MODULE ReCaptcha -->
```

### HTML

フォーム送信時（確認画面）の form要素を修正します。<br>
form要素のclass属性に **js-recaptcha-form** クラスを追加します。

```
<form action="thanks.html" method="post" enctype="multipart/form-data" class="js-recaptcha-form">
	...
	<input type="submit" name="ACMS_POST_Form_Submit" value="送信する"/>
</form>
```

### エラー表示

eCaptchaによってロボットだと認識された場合は、Formモジュールの **step#forbidden** ブロックが表示されます。

```
<!-- BEGIN step#forbidden -->
	<p>不正なアクセスです。</p>
<!-- END step#forbidden -->
```

### 確認

クライアントサイドは完成です。フォームの確認画面まで行くと、ReCaptchaのロゴが右下に表示されていると思います。実際にフォームを送信して、送信できるか確認しましょう。

![チェック画面](./images/result.png "チェック画面")

### 注意

config.server.phpでHOOKを有効にしておく必要があります。

```
define('HOOK_ENABLE', 1);
```


