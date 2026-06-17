# AK Soluções — Template Joomla 5 · Guia de Instalação

Template de **site** standalone para Joomla 5.4.6. Reproduz a identidade visual da AK Soluções
(hero em vídeo, bandas parallax, galeria, processo, parceiros, CTA, footer e botão de WhatsApp).
Fontes e ícones são **self-hosted** (sem CDN).

---

## 1. Instalar o template

Os arquivos já estão em `public_html/templates/aksolucoes/`. Escolha **uma** opção:

**Opção A — Discover (recomendada, arquivos já no servidor)**
1. Admin → **System → Install → Discover**.
2. Clique em **Discover** (ícone de busca). O item *AK Soluções* (tipo Template, cliente Site) aparece.
3. Selecione e clique em **Install**.

**Opção B — Upload do pacote**
1. Gere/baixe o `tpl_aksolucoes-1.0.0.zip`.
2. Admin → **System → Install → Extensions → Upload Package File** → envie o `.zip`.

---

## 2. Ativar como template padrão

1. Admin → **System → Site Template Styles**.
2. Abra **AK Soluções** e clique em **Save**; marque a estrela para torná-lo **Default**
   (ou em *Assignment* atribua a *All pages* / menus específicos).

---

## 3. Configurar os parâmetros do template

Em **Site Template Styles → AK Soluções**, aba **Brand / Contact / Layout**:

- **Marca:** símbolo (logo-mark), wordmark, cores principal/destaques (navy `#0e2a5c`, ciano `#1fb6ee`, verde `#8dc63f`).
- **Contato:** WhatsApp (somente dígitos, ex. `5541991387368`), telefone comercial/técnico, e-mail.
  Esses valores alimentam o botão "Falar agora", o FAB de WhatsApp e o rodapé.
- **Layout:** exibir barra superior, seletor de idioma (visual) e botão flutuante de WhatsApp.

---

## 4. Criar a página inicial (conteúdo editável)

1. Admin → **Content → Articles → New**.
2. Título: `Home` (pode ocultar o título depois nas opções do item de menu).
3. No editor, alterne para o modo **Código / `< >`** e **cole todo o conteúdo de** `docs/home-content.html`.
4. **Salve.**

> **Importante (filtro de texto):** para preservar `<video>`, `<picture>` e os ícones `data-lucide`,
> o autor precisa estar num grupo com **"Sem Filtro"** em **System → Global Configuration → Text Filters**
> (Super Users já são "No Filtering" por padrão). Caso o vídeo/ícones suma após salvar, ajuste aqui.

Depois crie o item de menu:
1. Admin → **Menus → Main Menu → New**.
2. **Menu Item Type:** *Articles → Single Article* → selecione o artigo `Home`.
3. Marque este item como **Home** (estrela / Default) no menu.
4. Em *Page Display*, defina **Show Page Heading = No** (a home não usa título de página).

A página inicial é detectada pelo template (`is-home`): o cabeçalho fica transparente sobre o hero
e some o offset superior. Páginas internas (artigos, categorias) recebem cabeçalho sólido e
espaçamento automático abaixo do header fixo.

---

## 5. Menu de navegação (cabeçalho + drawer mobile)

Como o site é uma landing de página única, os itens da navegação são **âncoras** que rolam
até cada seção da home. O logotipo no cabeçalho já leva à home, então o menu pode começar em "Sobre".

### 5.1. Criar os itens de menu

Admin → **Menus → Main Menu → Add New Menu Item**. Crie os itens abaixo, todos do tipo
**System Links → URL** (campo *Link*). Use caminhos relativos com âncora — funcionam tanto na
home quanto vindo de páginas internas (carrega a home e rola até a seção):

| # | Menu Title | Menu Item Type | Link |
|---|------------|----------------|------|
| 1 | **Home**   | Articles → Single Article (artigo da home) | — *(marque como página inicial / Default)* |
| 2 | Sobre      | System Links → URL | `/#about` |
| 3 | Soluções   | System Links → URL | `/#solutions` |
| 4 | Processo   | System Links → URL | `/#process` |
| 5 | Parceiros  | System Links → URL | `/#partners` |
| 6 | Contato    | System Links → URL | `/#contact` |

Âncoras disponíveis na home (`docs/home-content.html`): `#about`, `#solutions`, `#produtos`,
`#process`, `#partners`, `#contact` (a seção de contato é o rodapé) e também `#hero` / `#contact-center`.
Adicione um item **Produtos → URL → `/#produtos`** se quiser a vitrine no menu.
Adicione `#blog` apenas se publicar uma seção de blog.

> Dica: deixe o item **Home** com *Show in Menu = No* se não quiser exibi-lo na barra (o logo já
> cumpre esse papel). Em *Page Display* do item Home, use **Show Page Heading = No**.
> Se preferir URLs absolutas, use `https://www.aksolucoes.com.br/#solutions` no lugar de `/#solutions`.

### 5.2. Publicar o módulo de menu

1. Admin → **Content → Site Modules → New → Menu**.
2. **Title:** ex. "Navegação". **Select Menu:** *Main Menu*. **Position:** `menu`.
3. **Status:** Published. **Show Title:** Hide. **Menu Class Suffix:** deixe em branco.
4. Em *Menu Assignment*, mantenha "On all pages".

O mesmo módulo é renderizado automaticamente no cabeçalho (em linha) e no drawer mobile
(empilhado) — não precisa de um segundo módulo. O CSS do template estiliza `ul.mod-menu`
nos dois contextos.

---

## 6. Opcionais

- **Rodapé por módulos:** publique módulos na posição `footer` para substituir as colunas padrão
  (Soluções / Contato / Endereço). Sem módulos nessa posição, o template usa os dados dos parâmetros.
- **Barra superior:** ative *Exibir barra superior* e publique um módulo na posição `topbar`.
- **Sidebar/extras em páginas internas:** posições `main-top`, `main-bottom`, `sidebar-right`, `bottom-a`, `bottom-b`.
- **Multi-idioma real:** substitua o seletor visual por um módulo *Language Switcher* (posição `menu` ou `topbar`)
  e configure os idiomas do site.
- **Vitrine de produtos (`#produtos`):** a seção "Loja AK" no `home-content.html` traz 4 cards de exemplo
  (nome, categoria, descrição, preço e botão que abre o WhatsApp com mensagem pré-preenchida). Edite
  textos/preços direto no artigo. Para usar **fotos reais**, troque o conteúdo de `<div class="product-media">`
  (hoje um ícone `data-lucide`) por `<img src="/templates/aksolucoes/images/media/SEU-PRODUTO.jpg" alt="...">`
  — o CSS já recorta a imagem (`object-fit: cover`). Para uma loja completa, considere integrar um
  componente de e-commerce do Joomla (ex.: HikaShop/VirtueMart) e apontar os botões para os produtos.

---

## 7. Posições de módulo disponíveis

`topbar`, `menu`, `hero`, `main-top`, `main-bottom`, `sidebar-right`,
`bottom-a`, `bottom-b`, `footer`, `footer-bottom`, `debug`, `error-404`.

---

## 8. Verificação

- Home: vídeo do hero toca, cabeçalho fica sólido ao rolar, seções fazem *reveal*, galeria navega,
  FAB aparece, links de WhatsApp/telefone/e-mail corretos.
- Responsivo (≈1280 / 820 / 390 px): nav vira *burger* + drawer; grids empilham; parallax desativa no mobile.
- Página interna: cabeçalho sólido e conteúdo abaixo do header fixo.
- Erro 404: página branded, sem stack trace (detalhe só com *Debug* ligado).

---

## 9. Dependências / observações

- **Fontes e ícones self-hosted** em `fonts/` e `js/lucide.min.js` — sem chamadas a CDN.
  O `lucide.min.js` traz o set completo (~400 KB, carregado com `defer`); pode ser *subsetado* depois.
- **Caminhos da home** assumem o Joomla na **raiz** do domínio. Em subpasta, prefixe os caminhos
  `/templates/aksolucoes/...` com o nome da subpasta.
- **Mídia** (`images/media/`): vídeos e imagens vieram de `context/assets/media` (já otimizados, 1024px, sem áudio).
- **Thumbnail do admin** (`template_thumbnail.png` 206×128 / `template_preview.png` 900×562):
  screenshots reais da home (gerados a partir do layout renderizado). Substitua se redesenhar a página.
