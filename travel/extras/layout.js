const menuBtn = document.getElementById('menuBtn');
const sidebar = document.getElementById('sidebar');
const profileBtn = document.getElementById('profileBtn');
const dropdownMenu = document.getElementById('dropdownMenu');

menuBtn.addEventListener('click', () => {
  sidebar.classList.toggle('expanded');
});

profileBtn.addEventListener('click', () => {
  dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
});

document.addEventListener('click', function (e) {
  if (!profileBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
    dropdownMenu.style.display = 'none';
  }
});
