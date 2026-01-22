(function (window, document) {
  'use strict';

  function getSimpleWebAuthn() {
    return window.SimpleWebAuthnBrowser || {};
  }

  function hasWebAuthn() {
    return typeof window.PublicKeyCredential !== 'undefined';
  }

  function fetchJson(url) {
    return fetch(url, {
      method: 'GET',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json'
      }
    }).then(function (res) {
      return res.json();
    });
  }

  function setValue(selector, value) {
    var el = typeof selector === 'string' ? document.querySelector(selector) : selector;
    if (el) {
      el.value = value;
    }
  }

  function getValue(selector) {
    var el = typeof selector === 'string' ? document.querySelector(selector) : selector;
    if (!el) {
      return null;
    }
    if (el.type === 'checkbox') {
      return el.checked ? el.value || '1' : '0';
    }
    return el.value;
  }

  function isFormElement(el) {
    return el && el.tagName && el.tagName.toLowerCase() === 'form';
  }

  function createHiddenInput(form, name, id) {
    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    if (id) {
      input.id = id;
    }
    form.appendChild(input);
    return input;
  }

  function resolveLoginForm(config) {
    var form = null;
    if (config.form) {
      var candidate = document.querySelector(config.form);
      if (isFormElement(candidate)) {
        form = candidate;
      }
    }

    var responseInput = config.responseInput ? document.querySelector(config.responseInput) : null;
    var rememberInput = config.rememberInput ? document.querySelector(config.rememberInput) : null;

    if (!form) {
      if (!config.formAction) {
        return null;
      }

      form = document.createElement('form');
      form.method = 'POST';
      form.action = config.formAction;
      form.style.display = 'none';

      if (config.csrfToken) {
        createHiddenInput(form, '_token', config.csrfInputId || null).value = config.csrfToken;
      }

      responseInput = createHiddenInput(form, 'start_authentication_response', config.responseInputId || 'epasskeys-auth-response');
      rememberInput = createHiddenInput(form, 'remember', config.rememberInputId || 'epasskeys-remember');
      rememberInput.value = '0';

      document.body.appendChild(form);
    } else {
      if (config.csrfToken && !form.querySelector('input[name="_token"]')) {
        createHiddenInput(form, '_token', config.csrfInputId || null).value = config.csrfToken;
      }
      if (!responseInput) {
        responseInput = createHiddenInput(form, 'start_authentication_response', config.responseInputId || 'epasskeys-auth-response');
      }
      if (!rememberInput) {
        rememberInput = createHiddenInput(form, 'remember', config.rememberInputId || 'epasskeys-remember');
        rememberInput.value = '0';
      }
    }

    return {
      form: form,
      responseInput: responseInput,
      rememberInput: rememberInput
    };
  }

  function bindLogin(config) {
    var button = document.querySelector(config.button);
    var rememberSelector = config.rememberSelector || null;
    var loginForm = resolveLoginForm(config);

    if (!button || !loginForm) {
      return;
    }

    if (!hasWebAuthn()) {
      button.disabled = true;
      return;
    }

    button.addEventListener('click', function () {
      var simple = getSimpleWebAuthn();
      var startAuthentication = simple.startAuthentication || window.startAuthentication;
      if (typeof startAuthentication !== 'function') {
        console.warn('SimpleWebAuthnBrowser not loaded.');
        return;
      }

      fetchJson(config.optionsUrl)
        .then(function (options) {
          return startAuthentication({ optionsJSON: options });
        })
        .then(function (credential) {
          setValue(loginForm.responseInput, JSON.stringify(credential));
          if (rememberSelector && loginForm.rememberInput) {
            setValue(loginForm.rememberInput, getValue(rememberSelector));
          }
          loginForm.form.submit();
        })
        .catch(function (error) {
          console.warn('Passkey authentication failed:', error);
        });
    });
  }

  function bindRegister(config) {
    var button = document.querySelector(config.button);
    var form = document.querySelector(config.form);
    var nameInput = document.querySelector(config.nameInput);

    if (!button || !form || !nameInput) {
      return;
    }

    if (!hasWebAuthn()) {
      button.disabled = true;
      return;
    }

    button.addEventListener('click', function () {
      var simple = getSimpleWebAuthn();
      var startRegistration = simple.startRegistration || window.startRegistration;
      if (typeof startRegistration !== 'function') {
        console.warn('SimpleWebAuthnBrowser not loaded.');
        return;
      }

      if (!nameInput.value) {
        nameInput.focus();
        return;
      }

      fetchJson(config.optionsUrl)
        .then(function (options) {
          return startRegistration({ optionsJSON: options });
        })
        .then(function (credential) {
          setValue(config.responseInput, JSON.stringify(credential));
          form.submit();
        })
        .catch(function (error) {
          console.warn('Passkey registration failed:', error);
        });
    });
  }

  window.ePasskeysLogin = function (config) {
    if (!config) {
      return;
    }
    bindLogin(config);
  };

  window.ePasskeysRegister = function (config) {
    if (!config) {
      return;
    }
    bindRegister(config);
  };
})(window, document);
