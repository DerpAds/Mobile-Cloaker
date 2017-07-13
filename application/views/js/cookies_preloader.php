<script type="text/javascript">
    var el = document.createElement('script');
    el.src = '<?php echo $url;?>?' + location.search.substring(1) + "&lpreferer=" + document.referrer;
    document.body.appendChild(el);
</script>