<?php if (isset($relogin_script) && $relogin_script): ?>
<script>
  var userId = localStorage.getItem('user_id');
  if (userId) {
    // To prevent infinite loops, only redirect if user_id is not already in the URL
    var urlParams = new URLSearchParams(window.location.search);
    if (!urlParams.has('user_id')) {
      window.location.href = '<?php echo site_url('mobilegpt?user_id='); ?>' + userId;
    }
  }
</script>
<?php endif; ?>

<chatbot-widget></chatbot-widget>

<style type="text/css">
    header, section.footer {
        display: none !important;
    }
</style>
