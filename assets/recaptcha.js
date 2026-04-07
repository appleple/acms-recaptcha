(function () {
  const ADMIN_POST_MODULE_NAMES = [
    'ACMS_POST_Member_Admin_Login',
    'ACMS_POST_Member_Admin_ResetPassword',
    'ACMS_POST_Member_Admin_Tfa_Recovery',
  ];

  function getSiteKey() {
    return document.querySelector('script#acms-recaptcha-js')?.dataset?.sitekey ?? window.ACMS?.Config?.ReCaptcha;
  }

  function getRecaptchaToken() {
    return new Promise(function (resolve, reject) {
      grecaptcha.ready(function () {
        grecaptcha.execute(getSiteKey(), { action: 'homepage' }).then(resolve, reject);
      });
    });
  }

  function needsRecaptcha(form, submitter) {
    if (form.classList.contains('js-recaptcha-form')) {
      return true;
    }
    if (submitter && ADMIN_POST_MODULE_NAMES.includes(submitter.name)) {
      return true;
    }
    return ADMIN_POST_MODULE_NAMES.some(function (name) {
      return form.elements.namedItem(name) !== null;
    });
  }

  function initRecaptcha() {
    document.querySelectorAll('form').forEach(function (form) {
      form.addEventListener('submit', async function (event) {
        const submitButton = event.submitter;

        if (!needsRecaptcha(form, submitButton)) {
          return;
        }

        event.preventDefault();

        if (!form.reportValidity()) return;

        if (submitButton) submitButton.disabled = true;

        try {
          const token = await getRecaptchaToken();

          const tokenInput = document.createElement('input');
          tokenInput.type = 'hidden';
          tokenInput.name = 'g-recaptcha-token';
          tokenInput.value = token;

          const submitInput = document.createElement('input');
          submitInput.type = 'hidden';
          submitInput.name = submitButton?.name || 'ACMS_POST_Form_Submit';
          submitInput.value = submitButton?.value || 'send';

          form.append(tokenInput, submitInput);
          form.submit();
        } catch {
          alert('送信に失敗しました。お手数ですが最初からやり直してください。');
          if (submitButton) submitButton.disabled = false;
        }
      });
    });
  }

  if (document.readyState !== 'loading') {
    initRecaptcha();
  } else {
    document.addEventListener('DOMContentLoaded', initRecaptcha);
  }
})();
