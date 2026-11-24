/**
 * GameVault - interações de front-end (JavaScript puro).
 */
(function () {
  'use strict';

  const BRL = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

  /* Botões +/- de quantidade (respeitam min/max = estoque) */
  document.querySelectorAll('[data-qty]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const input = btn.closest('.gv-qty-group').querySelector('input[type="number"]');
      if (!input || input.disabled) return;

      const min = parseInt(input.min || '1', 10);
      const max = parseInt(input.max || '9999', 10);
      let value = parseInt(input.value || '1', 10);

      value = btn.dataset.qty === 'inc' ? value + 1 : value - 1;
      value = Math.min(max, Math.max(min, value));

      input.value = value;
      input.dispatchEvent(new Event('input', { bubbles: true }));
    });
  });

  /* Carrinho: recalcula subtotal/total em tempo real enquanto o usuário edita */
  const cartTable = document.getElementById('cart-table');
  if (cartTable) {
    const recalc = function () {
      let total = 0;
      cartTable.querySelectorAll('tbody tr').forEach(function (row) {
        const price = parseFloat(row.dataset.price || '0');
        const input = row.querySelector('.cart-qty');
        const max = parseInt(input.max || '9999', 10);
        let qty = parseInt(input.value || '0', 10);

        if (qty > max) {
          qty = max;
          input.value = max;
          input.classList.add('is-invalid');
          setTimeout(function () { input.classList.remove('is-invalid'); }, 1200);
        }

        const subtotal = price * Math.max(0, qty);
        row.querySelector('.cart-subtotal').textContent = BRL.format(subtotal);
        total += subtotal;
      });
      const preview = document.getElementById('cart-total-preview');
      if (preview) preview.textContent = BRL.format(total);
    };
    cartTable.addEventListener('input', recalc);
  }

  /* Confirmação antes de ações destrutivas */
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      if (!window.confirm(form.dataset.confirm)) ev.preventDefault();
    });
  });

  /* Checkout: mostra os campos do cartão apenas quando selecionado */
  const paymentOptions = document.getElementById('payment-options');
  const cardFields = document.getElementById('card-fields');
  if (paymentOptions && cardFields) {
    paymentOptions.addEventListener('change', function (ev) {
      if (ev.target.name === 'forma_pagamento') {
        cardFields.classList.toggle('d-none', ev.target.value !== 'cartao');
      }
    });
  }

  /* Máscara simples do número do cartão (0000 0000 0000 0000) */
  const cardNumber = document.getElementById('cartao_numero');
  if (cardNumber) {
    cardNumber.addEventListener('input', function () {
      const digits = cardNumber.value.replace(/\D/g, '').slice(0, 19);
      cardNumber.value = digits.replace(/(\d{4})(?=\d)/g, '$1 ');
    });
  }

  /* Mensagens flash somem sozinhas após alguns segundos */
  document.querySelectorAll('#flash-container .alert').forEach(function (alert) {
    setTimeout(function () {
      if (window.bootstrap && bootstrap.Alert) {
        bootstrap.Alert.getOrCreateInstance(alert).close();
      }
    }, 6000);
  });
})();
