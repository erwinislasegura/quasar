document.addEventListener('DOMContentLoaded', () => {
  const button = document.getElementById('menuButton');
  const overlay = document.getElementById('overlay');
  button?.addEventListener('click', () => document.body.classList.toggle('sidebar-open'));
  overlay?.addEventListener('click', () => document.body.classList.remove('sidebar-open'));
});
