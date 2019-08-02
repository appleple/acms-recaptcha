(function () {
  var fn = function () {
    var form = document.querySelector('.js-recaptcha-form');
    var submitButton = document.querySelector('.js-submit');
    if (!form) {
      return;
    }
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (submitButton) {
        submitButton.disabled = true;
      }
      grecaptcha.ready(function () {
        try {
          grecaptcha.execute(ACMS.Config.ReCaptcha, {
            action: 'homepage'
          }).then(function (token) {
            var q = document.createElement('input');
            q.type = 'hidden';
            q.name = 'g-recaptcha-token';
            q.value = token;
            form.appendChild(q);
            var q2 = document.createElement('input');
            q2.type = 'hidden';
            q2.name = 'ACMS_POST_Form_Submit';
            q2.value = 'send';
            form.appendChild(q2);
            form.submit();
          }, function (reason) {
            alert('送信に失敗しました。お手数ですが最初からやり直してください。');
            if (submitButton) {
              submitButton.disabled = false;
            }
          });
        } catch (e) {
          alert('送信に失敗しました。お手数ですが最初からやり直してください。');
          if (submitButton) {
            submitButton.disabled = false;
          }
        }
      });
    });
  };
  if (document.readyState !== 'loading') {
    fn();
  } else {
    document.addEventListener('DOMContentLoaded', fn);
  }
})();
