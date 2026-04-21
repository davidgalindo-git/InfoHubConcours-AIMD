<script>
(function () {
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-pw-toggle]');
    if (!btn) return;
    e.preventDefault();
    var id = btn.getAttribute('data-pw-toggle');
    var input = id && document.getElementById(id);
    if (!input) return;
    input.type = input.type === 'password' ? 'text' : 'password';
    var masked = input.type === 'password';
    btn.setAttribute('aria-pressed', masked ? 'false' : 'true');
    var eyeHide = btn.querySelector('.pw-eye-on');
    var eyeShow = btn.querySelector('.pw-eye-off');
    if (eyeHide && eyeShow) {
      eyeHide.classList.toggle('hidden', masked);
      eyeShow.classList.toggle('hidden', !masked);
    }
  });
})();
</script>
