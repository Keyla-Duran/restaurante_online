// Archivo JavaScript del proyecto. Se puede ampliar con validaciones del frontend.
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', () => {
      const button = form.querySelector('button[type="submit"],button:not([type])');
      if (button) button.disabled = true;
    });
  });
});
