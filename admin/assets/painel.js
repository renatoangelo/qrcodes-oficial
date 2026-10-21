// Painel de QR codes: copiar link, confirmação antes de ações destrutivas, prévia do alias e
// envio automático de selects. Arquivo próprio (sem scripts embutidos), por causa da política de segurança.
(function () {
  'use strict';

  document.addEventListener('click', function (evento) {
    var botao = evento.target.closest('[data-copiar]');
    if (!botao) return;
    var texto = botao.getAttribute('data-copiar');
    var feito = function () {
      botao.classList.add('botao--copiado');
      botao.setAttribute('title', 'Copiado!');
      setTimeout(function () { botao.classList.remove('botao--copiado'); botao.setAttribute('title', 'Copiar link'); }, 1800);
    };
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(texto).then(feito, function () { window.prompt('Copie o link:', texto); });
    } else {
      window.prompt('Copie o link:', texto);
    }
  });

  // Primeira confirmação (a segunda é digitar o alias, conferida no servidor)
  document.addEventListener('submit', function (evento) {
    var form = evento.target.closest('[data-confirmar]');
    if (form && !window.confirm(form.getAttribute('data-confirmar'))) evento.preventDefault();
  });

  document.addEventListener('change', function (evento) {
    if (evento.target.matches('[data-enviar-ao-mudar]')) evento.target.form.submit();
  });

  var alias = document.querySelector('[data-previa-alias]');
  var previa = document.querySelector('[data-previa]');
  if (alias && previa) {
    alias.addEventListener('input', function () {
      alias.value = alias.value.toLowerCase().replace(/\s+/g, '-');
      previa.textContent = alias.value || 'alias';
    });
  }
})();
