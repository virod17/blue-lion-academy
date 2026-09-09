(() => {
  const form = document.querySelector("#contactForm");

  if (!form) return;

  const submitButton = form.querySelector('button[type="submit"]');
  const statusElement = document.querySelector("#contactFormStatus");
  const apiUrl = form.dataset.apiUrl;

  const showStatus = (message, type) => {
    statusElement.textContent = message;
    statusElement.style.color =
      type === "success" ? "#166534" : "#be123c";
    statusElement.setAttribute("role", type === "error" ? "alert" : "status");
  };

  form.addEventListener("submit", async (event) => {
    event.preventDefault();

    if (!form.reportValidity()) return;

    submitButton.disabled = true;
    submitButton.textContent = "Enviando...";
    statusElement.textContent = "";

    const formData = new FormData(form);
    const payload = Object.fromEntries(formData.entries());

    try {
      const response = await fetch(apiUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "omit",
        body: JSON.stringify(payload),
      });

      const result = await response.json().catch(() => null);

      if (!response.ok) {
        const validationMessages = result?.errors
          ?.map((error) => error.message)
          .filter(Boolean);

        throw new Error(
          validationMessages?.length
            ? validationMessages.join(" ")
            : result?.message ||
                "No se pudo enviar el mensaje. Inténtalo nuevamente."
        );
      }

      form.reset();
      showStatus(
        "¡Gracias! Tu mensaje fue enviado correctamente. Nos pondremos en contacto contigo pronto.",
        "success"
      );
    } catch (error) {
      showStatus(
        error.message ||
          "No se pudo conectar con el servicio. Inténtalo nuevamente.",
        "error"
      );
    } finally {
      submitButton.disabled = false;
      submitButton.textContent = "Enviar mensaje";
    }
  });
})();
