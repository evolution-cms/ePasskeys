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

  function bindLogin(config) {
    var button = document.querySelector(config.button);
    var form = document.querySelector(config.form);
    var rememberSelector = config.rememberSelector || null;

    if (!button || !form) {
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
          setValue(config.responseInput, JSON.stringify(credential));
          if (rememberSelector) {
            setValue(config.rememberInput, getValue(rememberSelector));
          }
          form.submit();
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
