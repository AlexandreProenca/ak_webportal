/**
 * AK Soluções — template behaviour.
 * Header scroll state, mobile drawer, language toggle, gallery carousel,
 * reveal-on-scroll, floating WhatsApp button and contact mailto helper.
 */
(function () {
  document.documentElement.classList.add("js");

  const header = document.getElementById("siteHeader");
  const menuButton = document.getElementById("menuButton");
  const drawer = document.getElementById("mobileDrawer");
  const gallery = document.getElementById("gallery");
  const fab = document.querySelector(".fab");
  const contactForm = document.getElementById("contactForm");
  const formStatus = document.getElementById("formStatus");
  const slides = Array.from(document.querySelectorAll(".gallery-slide"));
  const dotsWrap = document.getElementById("galleryDots");
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  let activeSlide = 0;

  function syncHeader() {
    header?.classList.toggle("scrolled", window.scrollY > 24);
    fab?.classList.toggle("is-visible", window.scrollY > window.innerHeight * 0.65);
  }

  function setDrawer(open) {
    if (!drawer || !menuButton) return;
    drawer.classList.toggle("open", open);
    drawer.setAttribute("aria-hidden", String(!open));
    menuButton.setAttribute("aria-expanded", String(open));
    document.body.classList.toggle("drawer-open", open);
  }

  function setSlide(index) {
    if (!slides.length) return;
    activeSlide = (index + slides.length) % slides.length;
    slides.forEach((slide, i) => {
      slide.classList.toggle("is-active", i === activeSlide);
      slide.setAttribute("aria-hidden", String(i !== activeSlide));
    });
    dotsWrap?.querySelectorAll("button").forEach((dot, i) => {
      dot.classList.toggle("is-active", i === activeSlide);
      dot.setAttribute("aria-selected", String(i === activeSlide));
    });
  }

  syncHeader();
  window.addEventListener("scroll", syncHeader, { passive: true });

  menuButton?.addEventListener("click", () => {
    setDrawer(!drawer?.classList.contains("open"));
  });

  drawer?.querySelectorAll("a").forEach((link) => {
    link.addEventListener("click", () => setDrawer(false));
  });

  document.querySelectorAll(".lang-toggle button").forEach((button) => {
    button.addEventListener("click", () => {
      document.querySelectorAll(".lang-toggle button").forEach((item) => item.classList.remove("active"));
      button.classList.add("active");
      document.documentElement.lang = button.dataset.lang === "pt" ? "pt-BR" : button.dataset.lang || "pt-BR";
    });
  });

  contactForm?.addEventListener("submit", (event) => {
    event.preventDefault();

    if (!contactForm.reportValidity()) return;

    const data = new FormData(contactForm);
    const name = String(data.get("name") || "").trim();
    const company = String(data.get("company") || "").trim();
    const email = String(data.get("email") || "").trim();
    const phone = String(data.get("phone") || "").trim();
    const interest = String(data.get("interest") || "").trim();
    const message = String(data.get("message") || "").trim();
    const target = contactForm.dataset.email || "contato@aksolucoes.com.br";

    const body = [
      "Olá, equipe AK Soluções.",
      "",
      "Gostaria de falar sobre um projeto.",
      "",
      `Nome: ${name}`,
      `Empresa: ${company || "Não informado"}`,
      `E-mail: ${email}`,
      `Telefone: ${phone || "Não informado"}`,
      `Interesse: ${interest}`,
      "",
      "Mensagem:",
      message
    ].join("\n");

    const subject = `Contato pelo site - ${interest}`;
    const mailto = `mailto:${target}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;

    if (formStatus) {
      formStatus.textContent = "Abrindo seu aplicativo de e-mail com a mensagem preenchida.";
    }

    window.location.href = mailto;
  });

  if (dotsWrap && slides.length) {
    dotsWrap.innerHTML = slides
      .map((_, index) => `<button class="gallery-dot" type="button" role="tab" aria-label="Ver imagem ${index + 1}" data-gallery-dot="${index}"></button>`)
      .join("");

    dotsWrap.querySelectorAll("button").forEach((dot) => {
      dot.addEventListener("click", () => setSlide(Number(dot.dataset.galleryDot)));
    });
  }

  gallery?.querySelector("[data-gallery-prev]")?.addEventListener("click", () => setSlide(activeSlide - 1));
  gallery?.querySelector("[data-gallery-next]")?.addEventListener("click", () => setSlide(activeSlide + 1));
  setSlide(0);

  if (!reduceMotion && "IntersectionObserver" in window) {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add("is-visible");
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.08, rootMargin: "0px 0px 160px 0px" }
    );

    document.querySelectorAll(".reveal").forEach((element) => observer.observe(element));
  } else {
    document.querySelectorAll(".reveal").forEach((element) => element.classList.add("is-visible"));
  }

  // Inject the contact-page map. Kept out of the article body because Joomla's
  // text filter strips <iframe>; centred on the AK Soluções office address.
  const mapFrame = document.querySelector(".map-frame");
  if (mapFrame && !mapFrame.querySelector("iframe")) {
    const q = encodeURIComponent("Rua da Glória, 72, Centro Cívico, Curitiba - PR, 80030-060");
    const frame = document.createElement("iframe");
    frame.src = "https://www.google.com/maps?q=" + q + "&z=16&output=embed";
    frame.title = "Mapa — AK Soluções em Tecnologia";
    frame.loading = "lazy";
    frame.setAttribute("referrerpolicy", "no-referrer-when-downgrade");
    frame.setAttribute("allowfullscreen", "");
    mapFrame.appendChild(frame);
  }

  if (window.lucide) {
    window.lucide.createIcons();
  }

  // HikaShop's mini-cart returns text such as "2 itens". Keep only the
  // quantity in the header badge and repeat this after its AJAX refreshes.
  document.querySelectorAll(".btn-cart__count").forEach((badge) => {
    const refreshCartBadge = () => {
      const label = badge.querySelector(".hikashop_small_cart_total_title");
      if (!label) return;

      const quantity = label.textContent.match(/\d+/);
      if (!quantity) return;

      if (label.textContent.trim() !== quantity[0]) {
        label.textContent = quantity[0];
      }
      label.setAttribute("aria-label", `${quantity[0]} itens no carrinho`);
    };

    refreshCartBadge();
    new MutationObserver(refreshCartBadge).observe(badge, { childList: true, subtree: true });
  });
})();
