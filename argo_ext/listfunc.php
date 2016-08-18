#!/usr/bin/php
  <?php
    dl("argo_ext.so");
    print_r(get_extension_funcs("argo_ext"));
  ?>
