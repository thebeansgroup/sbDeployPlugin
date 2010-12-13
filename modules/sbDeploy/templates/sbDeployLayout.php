<html>
  <head>
    <title>Deploy <?php echo sfConfig::get('app_site_name'); ?> to Production</title>
    <style type="text/css">
      body {
        background-color: #eee;
        margin: 50px 150px;
      }
      #content {
        max-width: 960px;
        margin: 0 auto;
        background-color: #fff;
        border: 1px solid #ababab;
        padding: 30px 50px 40px 50px;
        -moz-border-radius: 20px;
        border-radius: 20px;
        -moz-box-shadow:rgba(0,0,0,0.5) 0px 0px 24px;
        box-shadow:rgba(0,0,0,0.5) 0px 0px 24px;
      }
      h1, h2 {
        font-family: Tahoma, Geneva, sans-serif;
      }
      a { color: #33f; }
      #resetLink { text-align: right; }
      .separate {
        border-top: 2px solid #ababab;
        margin-top: 20px;
        padding-top: 10px;
      }
      .error, .error_list { color: #f00; }
      .success { color: #2a2 }
    </style>
  </head>

  <body>
    <div id="content">
      <?php echo $sf_content ?>
      <p id="resetLink"><a href="<?php echo url_for('sbDeploy/index') ?>">Start again</a></p>
    </div>
  </body>
  <script type="text/javascript" src="/sbDeployPlugin/js/jquery-1.2.3.js">
  </script>
  <?php if (has_slot('javascript')): ?>
  <?php include_slot('javascript') ?>
  <?php endif ?>
</html>