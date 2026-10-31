"use strict";
// Único script do painel: abre e fecha a navegação lateral em telas estreitas.
// Sem dependências e sem chamadas externas.
(function () {
  var botao = document.getElementById("menu-button");
  var barra = document.getElementById("sidebar");
  var sombra = document.getElementById("mobile-shade");
  if (!botao || !barra || !sombra) return;

  function definir(aberto) {
    barra.classList.toggle("open", aberto);
    sombra.classList.toggle("visible", aberto);
    botao.setAttribute("aria-expanded", String(aberto));
  }

  botao.addEventListener("click", function () {
    definir(!barra.classList.contains("open"));
  });
  sombra.addEventListener("click", function () {
    definir(false);
  });
  barra.addEventListener("click", function (e) {
    if (e.target.closest && e.target.closest("a")) definir(false);
  });
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") definir(false);
  });
})();
